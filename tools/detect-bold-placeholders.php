<?php
// Script: detect-bold-placeholders.php
// Usage: php tools/detect-bold-placeholders.php

$templatesDir = __DIR__ . '/../resources/templates';
if (! is_dir($templatesDir)) {
    fwrite(STDERR, "Templates directory not found: {$templatesDir}\n");
    exit(2);
}

$files = glob($templatesDir . '/*.docx');
if ($files === false || count($files) === 0) {
    echo "No .docx templates found in {$templatesDir}\n";
    exit(0);
}

foreach ($files as $file) {
    $zip = new ZipArchive();
    $res = $zip->open($file);
    if ($res !== true) {
        echo basename($file) . ": unable to open template\n";
        continue;
    }

    $placeholders = [];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (! preg_match('#^word/(document|header\d+|footer\d+)\.xml$#', $name)) {
            continue;
        }
        $xml = $zip->getFromIndex($i);
        if ($xml === false) {
            continue;
        }

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        if (! $doc->loadXML($xml)) {
            libxml_clear_errors();
            continue;
        }
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        foreach ($xpath->query('//w:p[.//w:t]') as $paragraph) {
            $textNodes = iterator_to_array($xpath->query('.//w:t', $paragraph));
            if ($textNodes === []) {
                continue;
            }

            $joined = implode('', array_map(function ($n) { return $n->textContent; }, $textNodes));
            preg_match_all('/\{\{\s*([A-Za-z0-9_-]+)\s*\}\}/', $joined, $matches, PREG_OFFSET_CAPTURE);
            if (empty($matches[0])) {
                continue;
            }

            foreach ($matches[0] as $idx => $m) {
                $raw = $m[0];
                $start = $m[1];
                $macroName = $matches[1][$idx][0] ?? trim(substr($raw, 2, -2));
                $end = $start + strlen($raw);

                $offset = 0;
                $startIndex = null; $endIndex = null; $startOffset = 0; $endOffset = 0;

                foreach ($textNodes as $index => $node) {
                    $length = strlen($node->textContent);
                    if ($startIndex === null && $start >= $offset && $start < $offset + $length) {
                        $startIndex = $index;
                        $startOffset = $start - $offset;
                    }
                    if ($end > $offset && $end <= $offset + $length) {
                        $endIndex = $index;
                        $endOffset = $end - $offset;
                        break;
                    }
                    $offset += $length;
                }

                if ($startIndex === null || $endIndex === null) {
                    continue;
                }

                // Check runs for bold within the span
                $hasBold = false;
                for ($j = $startIndex; $j <= $endIndex; $j++) {
                    $tNode = $textNodes[$j];
                    $run = $tNode->parentNode;
                    while ($run !== null && $run->localName !== 'r') {
                        $run = $run->parentNode;
                    }
                    if ($run === null) {
                        continue;
                    }
                    $bNodes = $xpath->query('.//w:rPr/w:b', $run);
                    if ($bNodes !== false && $bNodes->length > 0) {
                        $hasBold = true;
                        break;
                    }
                }

                if ($hasBold) {
                    $placeholders[] = $macroName;
                }
            }
        }
    }

    $zip->close();

    $placeholders = array_values(array_unique($placeholders));
    echo basename($file) . ": ";
    if ($placeholders === []) {
        echo "(none)\n";
    } else {
        echo implode(', ', $placeholders) . "\n";
    }
}

exit(0);
