<?php

declare(strict_types=1);

use App\Documents\DocumentGenerator;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$templates = [
    'KETERANGAN BIASA.docx' => ['alamat', 'keperluan'],
    'KETERANGAN DOMISILI.docx' => ['alamat', 'keperluan'],
    'KETERANGAN TIDAK MAMPU.docx' => ['alamat', 'keperluan'],
    'KETERANGAN USAHA.docx' => ['alamat', 'alamat_usaha', 'keperluan'],
];

$namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
$templatesPath = resource_path('templates');

function w(DOMDocument $document, string $name): DOMElement
{
    return $document->createElementNS(
        'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
        'w:'.$name,
    );
}

function textNode(DOMDocument $document, string $text): DOMElement
{
    $textElement = w($document, 't');
    if ($text !== ltrim($text) || $text !== rtrim($text)) {
        $textElement->setAttribute('xml:space', 'preserve');
    }
    $textElement->appendChild($document->createTextNode($text));
    return $textElement;
}

function makeCell(DOMDocument $document, string $text, ?DOMNode $rPr, int $width, ?DOMNode $pPr): DOMElement
{
    $tc = w($document, 'tc');
    $tcPr = w($document, 'tcPr');
    $tcW = w($document, 'tcW');
    $tcW->setAttribute('w:w', (string) $width);
    $tcW->setAttribute('w:type', 'dxa');
    $tcPr->appendChild($tcW);
    $tc->appendChild($tcPr);

    $p = w($document, 'p');
    if ($pPr !== null) {
        $p->appendChild($pPr->cloneNode(true));
    }
    $r = w($document, 'r');
    if ($rPr !== null) {
        $r->appendChild($rPr->cloneNode(true));
    }
    $r->appendChild(textNode($document, $text));
    $p->appendChild($r);
    $tc->appendChild($p);
    return $tc;
}

function paragraphText(DOMElement $paragraph, DOMXPath $xpath): string
{
    return implode('', array_map(
        static fn (DOMElement $text): string => $text->textContent,
        iterator_to_array($xpath->query('.//w:t', $paragraph)),
    ));
}

function convertTemplate(string $path, array $fields, string $namespace): void
{
    $source = new ZipArchive();
    if ($source->open($path) !== true) {
        throw new RuntimeException("Unable to open {$path}");
    }
    $xml = $source->getFromName('word/document.xml');
    if ($xml === false) {
        throw new RuntimeException("Missing document.xml in {$path}");
    }
    $source->close();

    $macroNames = [
        'nomor_surat', 'tanggal_surat', 'nama', 'nik', 'tempat_lahir',
        'tanggal_lahir', 'ttl', 'jenis_kelamin', 'agama', 'status_perkawinan',
        'kewarganegaraan', 'pekerjaan', 'alamat', 'keperluan', 'jenis_usaha',
        'nama_usaha', 'lama_usaha', 'alamat_usaha', 'nama_anak', 'nik_anak',
        'nama_sekolah', 'anak_nama', 'anak_nik',
    ];
    foreach ($macroNames as $macroName) {
        $pattern = '~<w:t[^>]*>\{\{</w:t>.*?<w:t[^>]*>'.preg_quote($macroName, '~').'</w:t>.*?<w:t[^>]*>\}\}</w:t>~s';
        $xml = preg_replace($pattern, '<w:t>{{'.$macroName.'}}</w:t>', $xml) ?? $xml;
    }

    $document = new DOMDocument();
    $document->preserveWhiteSpace = false;
    $document->loadXML($xml);
    $xpath = new DOMXPath($document);
    $xpath->registerNamespace('w', $namespace);

    foreach ($xpath->query('//w:p[.//w:t]') as $paragraph) {
        $paragraphText = paragraphText($paragraph, $xpath);
        $field = null;
        foreach ($fields as $candidate) {
            if (str_contains($paragraphText, '{{'.$candidate.'}}')) {
                $field = $candidate;
                break;
            }
        }
        if ($field === null) {
            continue;
        }

        $marker = '{{'.$field.'}}';
        $before = strstr($paragraphText, $marker, true);
        if ($before === false || !preg_match('/(^|:)\s*$/', $before)) {
            continue;
        }

        $label = rtrim($before);
        $value = $marker;
        $firstRun = $xpath->query('./w:r', $paragraph)->item(0);
        $rPr = $firstRun instanceof DOMElement ? $xpath->query('./w:rPr', $firstRun)->item(0) : null;
        $pPr = $xpath->query('./w:pPr', $paragraph)->item(0);

        $table = w($document, 'tbl');
        $tblPr = w($document, 'tblPr');
        $tblW = w($document, 'tblW');
        $tblW->setAttribute('w:w', '9360');
        $tblW->setAttribute('w:type', 'dxa');
        $tblPr->appendChild($tblW);
        $borders = w($document, 'tblBorders');
        foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $border) {
            $element = w($document, $border);
            $element->setAttribute('w:val', 'nil');
            $borders->appendChild($element);
        }
        $tblPr->appendChild($borders);
        $table->appendChild($tblPr);

        $grid = w($document, 'tblGrid');
        foreach ([1800, 7560] as $width) {
            $gridColumn = w($document, 'gridCol');
            $gridColumn->setAttribute('w:w', (string) $width);
            $grid->appendChild($gridColumn);
        }
        $table->appendChild($grid);

        $row = w($document, 'tr');
        $row->appendChild(makeCell($document, $label, $rPr, 1800, $pPr));
        $row->appendChild(makeCell($document, $value, $rPr, 7560, $pPr));
        $table->appendChild($row);

        $paragraph->parentNode->replaceChild($table, $paragraph);
    }

    $temporary = $path.'.tmp';
    $target = new ZipArchive();
    if ($target->open($temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException("Unable to create {$temporary}");
    }
    $source = new ZipArchive();
    $source->open($path);
    for ($index = 0; $index < $source->numFiles; $index++) {
        $name = $source->getNameIndex($index);
        if ($name === 'word/document.xml') {
            $target->addFromString($name, $document->saveXML());
        } else {
            $target->addFromString($name, $source->getFromIndex($index));
        }
    }
    $source->close();
    $target->close();
    rename($temporary, $path);
}

foreach ($templates as $filename => $fields) {
    convertTemplate($templatesPath.DIRECTORY_SEPARATOR.$filename, $fields, $namespace);
}

$values = [
    'nomor_surat' => '470/001/SIDODADI/2026',
    'tanggal_surat' => '27 Juli 2026',
    'nama' => 'Siti Aminah',
    'nik' => '3507010101010001',
    'tempat_lahir' => 'Malang',
    'tanggal_lahir' => '1 Januari 1990',
    'ttl' => 'Malang, 1 Januari 1990',
    'jenis_kelamin' => 'Perempuan',
    'agama' => 'Islam',
    'status_perkawinan' => 'Belum Kawin',
    'kewarganegaraan' => 'Indonesia',
    'pekerjaan' => 'Wiraswasta',
    'alamat' => 'Dusun Sidodadi RT 004 RW 002, Desa Sidodadi, Kecamatan Ngajum, Kabupaten Malang, Provinsi Jawa Timur',
    'keperluan' => 'Digunakan untuk keperluan administrasi dan pengajuan dokumen resmi kepada instansi terkait.',
    'jenis_usaha' => 'Perdagangan kebutuhan sehari-hari',
    'nama_usaha' => 'Toko Siti Aminah',
    'lama_usaha' => 'Lima tahun',
    'alamat_usaha' => 'Dusun Sidodadi RT 004 RW 002, Desa Sidodadi, Kecamatan Ngajum, Kabupaten Malang, Provinsi Jawa Timur',
    'nama_anak' => 'Ahmad Fajar',
    'nik_anak' => '3507010101010002',
    'nama_sekolah' => 'SMA Negeri 1 Ngajum',
    'anak_nama' => 'Ahmad Fajar',
    'anak_nik' => '3507010101010002',
];

$generator = $app->make(DocumentGenerator::class);
$types = [
    'domisili' => 'KETERANGAN DOMISILI.docx',
    'usaha' => 'KETERANGAN USAHA.docx',
    'tidak-mampu' => 'KETERANGAN TIDAK MAMPU.docx',
    'keterangan' => 'KETERANGAN BIASA.docx',
];

foreach ($types as $type => $template) {
    $output = $templatesPath.DIRECTORY_SEPARATOR.'HASIL-GENERATE-'.$type.'.docx';
    $generator->generate($type, $values, $output);
    echo "Generated {$output}".PHP_EOL;
}
