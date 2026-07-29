<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$path = __DIR__ . '/debug-output.docx';
if (! file_exists($path)) {
    echo "Output file not found: {$path}\n";
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($path) !== true) {
    echo "Unable to open generated docx\n";
    exit(1);
}

$xml = $zip->getFromName('word/document.xml');
$zip->close();
if ($xml === false) {
    echo "word/document.xml missing\n";
    exit(1);
}

echo substr($xml, 0, 1200);
