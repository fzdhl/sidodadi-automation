<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$path = __DIR__ . '/../resources/templates/HASIL-RETRY-usaha.docx';
if (! file_exists($path)) {
    echo "Missing generated file at {$path}\n";
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($path) !== true) {
    echo "Unable to open generated file\n";
    exit(1);
}
$xml = $zip->getFromName('word/document.xml');
$zip->close();
if ($xml === false) {
    echo "Missing word/document.xml\n";
    exit(1);
}

echo "=== XML SNIPPET ===\n";
echo substr($xml, 0, 2000);

echo "\n=== ERRORS ===\n";
libxml_use_internal_errors(true);
$doc = new DOMDocument();
$loaded = $doc->loadXML($xml);
foreach (libxml_get_errors() as $error) {
    echo sprintf("%d: %s at line %d col %d\n", $error->level, trim($error->message), $error->line, $error->column);
}
var_dump($loaded);
