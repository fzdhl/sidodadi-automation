<?php
if ($argc !== 3) {
    echo "Usage: php compare-docx-xml.php path1.docx path2.docx\n";
    exit(1);
}

$paths = [$argv[1], $argv[2]];
$docs = [];
foreach ($paths as $path) {
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        echo "Unable to open {$path}\n";
        exit(1);
    }
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    if ($xml === false) {
        echo "Missing word/document.xml in {$path}\n";
        exit(1);
    }
    $docs[] = $xml;
    echo "{$path}: size=".filesize($path)." xml=".strlen($xml)." md5xml=".md5($xml)."\n";
}

$xml1 = $docs[0];
$xml2 = $docs[1];
$diffAt = null;
$len = min(strlen($xml1), strlen($xml2));
for ($i = 0; $i < $len; $i++) {
    if ($xml1[$i] !== $xml2[$i]) {
        $diffAt = $i;
        break;
    }
}
if ($diffAt === null) {
    echo "XML contents are identical up to length {$len}\n";
    if (strlen($xml1) !== strlen($xml2)) {
        echo "Differences due to length only\n";
    }
    exit(0);
}

echo "First diff at offset {$diffAt}\n";
echo "xml1 char='".htmlspecialchars($xml1[$diffAt], ENT_QUOTES)."'\n";
echo "xml2 char='".htmlspecialchars($xml2[$diffAt], ENT_QUOTES)."'\n";
$start = max(0, $diffAt - 200);
$end = min(max(strlen($xml1), strlen($xml2)), $diffAt + 200);

echo "--- xml1 snippet ---\n";
echo substr($xml1, $start, min(strlen($xml1), $end) - $start) . "\n";

echo "--- xml2 snippet ---\n";
echo substr($xml2, $start, min(strlen($xml2), $end) - $start) . "\n";
