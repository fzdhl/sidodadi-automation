<?php

declare(strict_types=1);

use App\Documents\DocumentGenerator;
use App\Documents\DocumentTemplateRegistry;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sourcePath = dirname(__DIR__).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'KETERANGAN USAHA.docx';
$diagnosticDir = dirname(__DIR__).DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'usaha-xml-diagnostics';
if (! is_dir($diagnosticDir)) {
    mkdir($diagnosticDir, 0775, true);
}

$values = [
    'nomor_surat' => '470/998/SIDODADI/2026',
    'tanggal_surat' => '27 Juli 2026',
    'nama' => 'Siti Aminah Maharani Putri',
    'nik' => '3507010101010001',
    'tempat_lahir' => 'Malang',
    'tanggal_lahir' => '1 Januari 1990',
    'ttl' => 'Malang, 1 Januari 1990',
    'jenis_kelamin' => 'Perempuan',
    'agama' => 'Islam',
    'status_perkawinan' => 'Belum Kawin',
    'kewarganegaraan' => 'Indonesia',
    'pekerjaan' => 'Wiraswasta',
    'alamat' => 'Dusun Sumber Makmur RT 014 RW 008, Desa Sidodadi, Kecamatan Lawang, Kabupaten Malang, Provinsi Jawa Timur',
    'keperluan' => 'Keperluan administrasi resmi pada instansi terkait',
    'jenis_usaha' => 'Perdagangan makanan siap saji',
    'nama_usaha' => 'Warung Sembako Siti Aminah Sejahtera',
    'lama_usaha' => 'Delapan tahun',
    'alamat_usaha' => 'Dusun Sumber Makmur RT 014 RW 008, Desa Sidodadi, Kecamatan Lawang, Kabupaten Malang, Provinsi Jawa Timur',
];

function copyVariant(string $sourcePath, string $targetPath, callable $transform): void
{
    $source = new ZipArchive();
    $source->open($sourcePath);
    $target = new ZipArchive();
    $target->open($targetPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    for ($index = 0; $index < $source->numFiles; $index++) {
        $name = $source->getNameIndex($index);
        $content = $source->getFromIndex($index);
        if ($content !== false && preg_match('#^word/(document|header\d+|footer\d+)\.xml$#', $name)) {
            $content = $transform($name, $content);
        }
        $target->addFromString($name, $content === false ? '' : $content);
    }

    $source->close();
    $target->close();
}

function removeXmlNodes(string $xml, string $xpathExpression): string
{
    $doc = new DOMDocument();
    $doc->preserveWhiteSpace = false;
    $doc->loadXML($xml);
    $xpath = new DOMXPath($doc);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    foreach ($xpath->query($xpathExpression) as $node) {
        $node->parentNode->removeChild($node);
    }
    return $doc->saveXML();
}

$variants = [
    'baseline' => static fn (string $name, string $xml): string => $xml,
    'without-document-drawings' => static fn (string $name, string $xml): string => str_ends_with($name, 'document.xml')
        ? removeXmlNodes($xml, '//w:drawing')
        : $xml,
    'without-header-footer-drawings' => static fn (string $name, string $xml): string => ! str_ends_with($name, 'document.xml')
        ? removeXmlNodes($xml, '//w:drawing')
        : $xml,
    'without-all-drawings' => static fn (string $name, string $xml): string => removeXmlNodes($xml, '//w:drawing'),
    'without-tables' => static fn (string $name, string $xml): string => str_ends_with($name, 'document.xml')
        ? removeXmlNodes($xml, '//w:tbl')
        : $xml,
];

foreach ($variants as $name => $transform) {
    $variantPath = $diagnosticDir.DIRECTORY_SEPARATOR.$name.'.docx';
    copyVariant($sourcePath, $variantPath, $transform);
    try {
        $outputPath = $diagnosticDir.DIRECTORY_SEPARATOR.$name.'-generated.docx';
        $registry = new DocumentTemplateRegistry($diagnosticDir, [
            'usaha' => ['name' => 'Surat Keterangan Usaha', 'template' => basename($variantPath)],
        ]);
        (new DocumentGenerator($registry))->generate('usaha', $values, $outputPath);
        echo $name.": PASS".PHP_EOL;
    } catch (Throwable $exception) {
        echo $name.": FAIL - ".$exception->getMessage().PHP_EOL;
    }
}
