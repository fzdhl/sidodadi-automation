<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$template = resource_path('templates/TEMPLATE KETERANGAN USAHA.docx');

$zip = new ZipArchive();
if ($zip->open($template) !== true) {
    echo "Unable to open template\n";
    exit(1);
}

$xml = $zip->getFromName('word/document.xml');
$zip->close();

if ($xml === false) {
    echo "Missing word/document.xml\n";
    exit(1);
}

preg_match_all('/\{\{\s*([A-Za-z0-9_-]+)\s*\}\}/', $xml, $matches);
$unique = array_unique($matches[1]);
echo "Found placeholders: \n";
foreach ($unique as $placeholder) {
    echo "- $placeholder\n";
}

echo "\nAttempting normalizeXmlMacros on document.xml...\n";

$generator = $app->make(App\Documents\DocumentGenerator::class);
try {
    $reflection = new ReflectionClass($generator);
    $method = $reflection->getMethod('normalizeXmlMacros');
    $method->setAccessible(true);
    $normalized = $method->invoke($generator, $xml);
    echo "Normalized XML length: " . strlen($normalized) . "\n";
} catch (Throwable $ex) {
    echo get_class($ex) . ": " . $ex->getMessage() . "\n";
    echo $ex->getTraceAsString() . "\n";
}
