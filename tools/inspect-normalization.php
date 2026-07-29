<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$generator = $app->make(App\Documents\DocumentGenerator::class);
$reflection = new ReflectionClass($generator);
$method = $reflection->getMethod('normalizeTemplateMacros');
$method->setAccessible(true);
$template = resource_path('templates/TEMPLATE KETERANGAN USAHA.docx');
$normalized = $method->invoke($generator, $template);
$xml = file_get_contents($normalized);
file_put_contents(__DIR__ . '/normalized-usaha.xml', $xml);
echo "Normalized XML written to tools/normalized-usaha.xml (size=" . strlen($xml) . ")\n";
$zip = new ZipArchive();
if ($zip->open($normalized) !== true) { echo "Failed open normalized\n"; exit(1);} $inner = $zip->getFromName('word/document.xml'); $zip->close(); echo "Word/document.xml length: " . strlen($inner) . "\n";
$keywords = ['{{', '}}', 'nik', 'ttl', 'jenis_kelamin'];
foreach ($keywords as $kw) {
    $pos = strpos($inner, $kw);
    echo $kw . ': ' . ($pos === false ? 'not found' : $pos) . "\n";
}

preg_match_all('/\{\{\s*([A-Za-z0-9_-]+)\s*\}\}/', $inner, $m);
echo "Placeholders: " . count($m[1]) . "\n";
print_r(array_unique($m[1]));
