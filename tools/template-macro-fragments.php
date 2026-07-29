<?php
require __DIR__ . '/../vendor/autoload.php';

$template = __DIR__ . '/../resources/templates/TEMPLATE KETERANGAN USAHA.docx';
$zip = new ZipArchive();
if ($zip->open($template) !== true) {
    echo "Unable to open template\n";
    exit(1);
}
$xml = $zip->getFromName('word/document.xml');
$zip->close();
if ($xml === false) {
    echo "word/document.xml missing\n";
    exit(1);
}

$strings = ['{{', '}}'];
foreach ($strings as $needle) {
    $pos = 0;
    while (($pos = strpos($xml, $needle, $pos)) !== false) {
        $start = max(0, $pos - 80);
        $end = min(strlen($xml), $pos + 80);
        echo "--- {$needle} at offset {$pos} ---\n";
        echo substr($xml, $start, $end - $start) . "\n\n";
        $pos += strlen($needle);
    }
}
