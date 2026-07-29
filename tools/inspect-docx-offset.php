<?php
require __DIR__ . '/../vendor/autoload.php';

if ($argc < 2) {
    echo "Usage: php inspect-docx-offset.php path/to/file.docx [offset]\n";
    exit(1);
}

$path = $argv[1];
$offset = isset($argv[2]) ? (int) $argv[2] : 4627;
$range = 200;

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

$start = max(0, $offset - $range);
$end = min(strlen($xml), $offset + $range);
$snippet = substr($xml, $start, $end - $start);

echo "Path: {$path}\n";
echo "Offset: {$offset}\n";
echo "Start: {$start}\n";
echo "End: {$end}\n";
echo "--- SNIPPET ---\n";
echo $snippet . "\n";

$tagPattern = '/<\/?[a-zA-Z0-9:_]+[^>]*>/';
preg_match_all($tagPattern, $snippet, $tags, PREG_OFFSET_CAPTURE);
echo "--- TAGS IN SNIPPET ---\n";
foreach ($tags[0] as $tag) {
    echo $tag[1] . ': ' . $tag[0] . "\n";
}
