<?php
require __DIR__ . '/../vendor/autoload.php';

if ($argc !== 2) {
    echo "Usage: php inspect-docx.php path/to/file.docx\n";
    exit(1);
}

$path = $argv[1];
if (! file_exists($path)) {
    echo "File not found: {$path}\n";
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($path) !== true) {
    echo "Unable to open docx: {$path}\n";
    exit(1);
}

$xml = $zip->getFromName('word/document.xml');
$zip->close();
if ($xml === false) {
    echo "Missing word/document.xml\n";
    exit(1);
}

echo "File: {$path}\n";
echo "Size: " . filesize($path) . "\n";

echo "--- first 1200 chars ---\n";
echo substr($xml, 0, 1200) . "\n";

echo "--- last 1200 chars ---\n";
echo substr($xml, max(0, strlen($xml) - 1200), 1200) . "\n";

echo "--- xml validity ---\n";
libxml_use_internal_errors(true);
$doc = new DOMDocument();
$ok = $doc->loadXML($xml);
foreach (libxml_get_errors() as $error) {
    echo trim($error->message) . " at line {$error->line} col {$error->column}\n";
}
var_dump($ok);
