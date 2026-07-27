<?php

declare(strict_types=1);

$namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
$templatePath = dirname(__DIR__).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'templates';
$files = [
    'KETERANGAN BIASA.docx',
    'KETERANGAN DOMISILI.docx',
    'KETERANGAN TIDAK MAMPU.docx',
    'KETERANGAN USAHA.docx',
];
$fields = [
    'nama', 'nik', 'ttl', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
    'status_perkawinan', 'agama', 'kewarganegaraan', 'pekerjaan', 'alamat',
    'keperluan', 'jenis_usaha', 'nama_usaha', 'lama_usaha', 'alamat_usaha',
    'nama_anak', 'nik_anak', 'nama_sekolah', 'anak_nama', 'anak_nik',
];

function w(DOMDocument $doc, string $name): DOMElement
{
    return $doc->createElementNS(
        'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
        'w:'.$name,
    );
}

function paragraphText(DOMXPath $xpath, DOMElement $paragraph): string
{
    return implode('', array_map(
        static fn (DOMElement $node): string => $node->textContent,
        iterator_to_array($xpath->query('.//w:t', $paragraph)),
    ));
}

function addTextRun(DOMDocument $doc, DOMElement $paragraph, string $text, ?DOMNode $rPr): void
{
    $run = w($doc, 'r');
    if ($rPr !== null) {
        $run->appendChild($rPr->cloneNode(true));
    }
    $textNode = w($doc, 't');
    $textNode->setAttribute('xml:space', 'preserve');
    $textNode->appendChild($doc->createTextNode($text));
    $run->appendChild($textNode);
    $paragraph->appendChild($run);
}

function addCell(DOMDocument $doc, string $text, int $width, ?DOMNode $rPr, ?DOMNode $pPr): DOMElement
{
    $cell = w($doc, 'tc');
    $cellProperties = w($doc, 'tcPr');
    $cellWidth = w($doc, 'tcW');
    $cellWidth->setAttribute('w:w', (string) $width);
    $cellWidth->setAttribute('w:type', 'dxa');
    $cellProperties->appendChild($cellWidth);
    $cell->appendChild($cellProperties);

    $paragraph = w($doc, 'p');
    if ($pPr !== null) {
        $paragraph->appendChild($pPr->cloneNode(true));
    }
    addTextRun($doc, $paragraph, $text, $rPr);
    $cell->appendChild($paragraph);
    return $cell;
}

function makeTable(DOMDocument $doc, string $label, string $value, ?DOMNode $rPr, ?DOMNode $pPr): DOMElement
{
    $table = w($doc, 'tbl');
    $properties = w($doc, 'tblPr');
    $width = w($doc, 'tblW');
    $width->setAttribute('w:w', '9360');
    $width->setAttribute('w:type', 'dxa');
    $properties->appendChild($width);

    $borders = w($doc, 'tblBorders');
    foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $side) {
        $border = w($doc, $side);
        $border->setAttribute('w:val', 'nil');
        $borders->appendChild($border);
    }
    $properties->appendChild($borders);
    $table->appendChild($properties);

    $grid = w($doc, 'tblGrid');
    foreach ([2300, 7060] as $columnWidth) {
        $column = w($doc, 'gridCol');
        $column->setAttribute('w:w', (string) $columnWidth);
        $grid->appendChild($column);
    }
    $table->appendChild($grid);

    $row = w($doc, 'tr');
    $row->appendChild(addCell($doc, $label, 2300, $rPr, $pPr));
    $row->appendChild(addCell($doc, $value, 7060, $rPr, $pPr));
    $table->appendChild($row);
    return $table;
}

function convertFile(string $path, array $fields, string $namespace): void
{
    $source = new ZipArchive();
    if ($source->open($path) !== true) {
        throw new RuntimeException("Unable to open {$path}");
    }
    $xml = $source->getFromName('word/document.xml');
    $source->close();
    if ($xml === false) {
        throw new RuntimeException("Missing word/document.xml in {$path}");
    }

    $doc = new DOMDocument();
    $doc->preserveWhiteSpace = false;
    if (! $doc->loadXML($xml)) {
        throw new RuntimeException("Invalid document.xml in {$path}");
    }
    $xpath = new DOMXPath($doc);
    $xpath->registerNamespace('w', $namespace);

    // Only direct body paragraphs are converted; paragraphs inside the signature table stay intact.
    $paragraphs = iterator_to_array($xpath->query('/w:document/w:body/w:p'));
    foreach ($paragraphs as $paragraph) {
        $text = paragraphText($xpath, $paragraph);
        $field = null;
        $marker = null;
        foreach ($fields as $candidate) {
            if (preg_match('/\{\{\s*'.preg_quote($candidate, '/').'\s*\}\}/', $text, $match)) {
                $field = $candidate;
                $marker = $match[0];
                break;
            }
        }
        if ($field === null || $marker === null) {
            continue;
        }

        $before = strstr($text, $marker, true);
        if ($before === false || ! preg_match('/:\s*$/', $before)) {
            continue;
        }

        $after = substr($text, strlen($before) + strlen($marker));
        $label = rtrim($before);
        $firstRun = $xpath->query('./w:r', $paragraph)->item(0);
        $rPr = $firstRun instanceof DOMElement ? $xpath->query('./w:rPr', $firstRun)->item(0) : null;
        $pPr = $xpath->query('./w:pPr', $paragraph)->item(0);
        $table = makeTable($doc, $label, $marker, $rPr, $pPr);

        $parent = $paragraph->parentNode;
        $parent->replaceChild($table, $paragraph);

        if (trim($after) !== '') {
            $continuation = w($doc, 'p');
            if ($pPr !== null) {
                $continuation->appendChild($pPr->cloneNode(true));
            }
            addTextRun($doc, $continuation, $after, $rPr);
            if ($table->nextSibling !== null) {
                $parent->insertBefore($continuation, $table->nextSibling);
            } else {
                $parent->appendChild($continuation);
            }
        }
    }

    $temporary = $path.'.tmp';
    $target = new ZipArchive();
    $target->open($temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $source->open($path);
    for ($index = 0; $index < $source->numFiles; $index++) {
        $name = $source->getNameIndex($index);
        $target->addFromString($name, $name === 'word/document.xml' ? $doc->saveXML() : $source->getFromIndex($index));
    }
    $source->close();
    $target->close();
    rename($temporary, $path);
}

foreach ($files as $file) {
    convertFile($templatePath.DIRECTORY_SEPARATOR.$file, $fields, $namespace);
    echo "Converted {$file}".PHP_EOL;
}
