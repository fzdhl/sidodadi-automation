<?php

namespace App\Documents;

final class DocumentTemplateAnalyzer
{
    public function __construct(
        private readonly DocumentTemplateRegistry $templates,
    ) {
    }

    public function detectBoldPlaceholders(string $type): array
    {
        $template = $this->templates->get($type);

        if (! is_file($template->path)) {
            throw new \RuntimeException("Document template does not exist: {$template->path}");
        }

        $zip = new \ZipArchive();
        if ($zip->open($template->path) !== true) {
            throw new \RuntimeException("Unable to open document template: {$template->path}");
        }

        $placeholders = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (! preg_match('#^word/(document|header\d+|footer\d+)\.xml$#', $name)) {
                continue;
            }

            $xml = $zip->getFromIndex($index);
            if ($xml === false) {
                continue;
            }

            $document = new \DOMDocument();
            $document->preserveWhiteSpace = false;
            if (! $document->loadXML($xml)) {
                continue;
            }

            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

            foreach ($xpath->query('//w:p[.//w:t]') as $paragraph) {
                $textNodes = iterator_to_array($xpath->query('.//w:t', $paragraph));
                if ($textNodes === []) {
                    continue;
                }

                $joined = implode('', array_map(
                    static fn (\DOMElement $node): string => $node->textContent,
                    $textNodes,
                ));

                preg_match_all('/\{\{\s*([A-Za-z0-9_-]+)\s*\}\}/', $joined, $matches, PREG_OFFSET_CAPTURE);
                if (empty($matches[0])) {
                    continue;
                }

                $fullMatches = $matches[0];
                $nameMatches = $matches[1];

                foreach ($fullMatches as $indexMatch => $match) {
                    $rawMacro = $match[0];
                    $start = $match[1];
                    $macroName = $nameMatches[$indexMatch][0] ?? trim(substr($rawMacro, 2, -2));
                    $end = $start + strlen($rawMacro);

                    $offset = 0;
                    $startIndex = null;
                    $endIndex = null;
                    $startOffset = 0;
                    $endOffset = 0;

                    foreach ($textNodes as $textIndex => $node) {
                        $length = strlen($node->textContent);
                        if ($startIndex === null && $start >= $offset && $start < $offset + $length) {
                            $startIndex = $textIndex;
                            $startOffset = $start - $offset;
                        }
                        if ($end > $offset && $end <= $offset + $length) {
                            $endIndex = $textIndex;
                            $endOffset = $end - $offset;
                            break;
                        }
                        $offset += $length;
                    }

                    if ($startIndex === null || $endIndex === null) {
                        continue;
                    }

                    $hasBold = false;
                    for ($i = $startIndex; $i <= $endIndex; $i++) {
                        $node = $textNodes[$i];
                        $run = $node->parentNode;
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

        return array_values(array_unique($placeholders));
    }
}
