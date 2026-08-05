<?php

namespace App\Documents;

use Carbon\Carbon;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;

final class DocumentGenerator
{
    public function __construct(
        private readonly DocumentTemplateRegistry $templates,
    ) {
    }

    public function generate(string $type, array $values, string $outputPath): string
    {
        $template = $this->templates->get($type);

        if (! is_file($template->path)) {
            throw new RuntimeException("Document template does not exist: {$template->path}");
        }

        if (strtolower(pathinfo($template->path, PATHINFO_EXTENSION)) !== 'docx') {
            throw new RuntimeException("Document template must be a .docx file: {$template->path}");
        }

        $directory = dirname($outputPath);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create output directory: {$directory}");
        }

        $normalizedTemplate = $this->normalizeTemplateMacros($template->path);

        try {
            $processor = new TemplateProcessor($normalizedTemplate);
            $processor->setMacroChars('{{', '}}');

            if ($processor->getVariables() === []) {
                throw new RuntimeException("Document template has no {{placeholder}} variables: {$template->path}");
            }

            $values = $this->formatDateFields($values, $type);
            if (! isset($values['ttl']) && isset($values['tempat_lahir'], $values['tanggal_lahir'])) {
                $values['ttl'] = $values['tempat_lahir'].', '.$values['tanggal_lahir'];
            }

            $uppercase = (array) config('documents.uppercase_placeholders', []);
            if ($uppercase !== []) {
                foreach ($values as $key => $value) {
                    if (is_string($value) && in_array($key, $uppercase, true)) {
                        $values[$key] = mb_strtoupper($value, 'UTF-8');
                    }
                }
            }

            $processor->setValues($this->sanitizeValuesForXml($values));
            $processor->saveAs($outputPath);
        } finally {
            if (is_file($normalizedTemplate)) {
                unlink($normalizedTemplate);
            }
        }

        $this->assertValidDocx($outputPath);

        return $outputPath;
    }

    public function renderPreviewHtml(string $type, array $values): string
    {
        $temporaryPath = $this->buildTemporaryDocument($type, $values);

        try {
            $phpWord = IOFactory::load($temporaryPath);
            $writer = IOFactory::createWriter($phpWord, 'HTML');

            ob_start();
            $writer->save('php://output');
            $html = ob_get_clean();

            if ($html === false) {
                throw new RuntimeException('Unable to render preview HTML.');
            }

            if (str_starts_with($html, "\xFF\xFEL") || str_starts_with($html, "\xFE\xFF")) {
                $html = mb_convert_encoding($html, 'UTF-8', 'UTF-16');
            }

            if (str_starts_with($html, "\xEF\xBB\xBF")) {
                $html = substr($html, 3);
            }

            return $html;
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    private function buildTemporaryDocument(string $type, array $values): string
    {
        $template = $this->templates->get($type);

        if (! is_file($template->path)) {
            throw new RuntimeException("Document template does not exist: {$template->path}");
        }

        if (strtolower(pathinfo($template->path, PATHINFO_EXTENSION)) !== 'docx') {
            throw new RuntimeException("Document template must be a .docx file: {$template->path}");
        }

        $normalizedTemplate = $this->normalizeTemplateMacros($template->path);
        $temporaryPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('sidodadi-preview-', true).'.docx';

        try {
            $processor = new TemplateProcessor($normalizedTemplate);
            $processor->setMacroChars('{{', '}}');
            $values = $this->formatDateFields($values, $type);
            if (! isset($values['ttl']) && isset($values['tempat_lahir'], $values['tanggal_lahir'])) {
                $values['ttl'] = $values['tempat_lahir'].', '.$values['tanggal_lahir'];
            }

            $uppercase = (array) config('documents.uppercase_placeholders', []);
            if ($uppercase !== []) {
                foreach ($values as $key => $value) {
                    if (is_string($value) && in_array($key, $uppercase, true)) {
                        $values[$key] = mb_strtoupper($value, 'UTF-8');
                    }
                }
            }

            $processor->setValues($this->sanitizeValuesForXml($values));
            $processor->saveAs($temporaryPath);
        } finally {
            if (is_file($normalizedTemplate)) {
                unlink($normalizedTemplate);
            }
        }

        $this->assertValidDocx($temporaryPath);

        return $temporaryPath;
    }

    private function normalizeTemplateMacros(string $templatePath): string
    {
        $source = new \ZipArchive();
        if ($source->open($templatePath) !== true) {
            throw new RuntimeException("Unable to open document template: {$templatePath}");
        }

        $temporary = tempnam(sys_get_temp_dir(), 'sidodadi-template-');
        if ($temporary === false) {
            $source->close();
            throw new RuntimeException('Unable to create a temporary document template.');
        }

        $target = new \ZipArchive();
        $target->open($temporary, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        for ($index = 0; $index < $source->numFiles; $index++) {
            $name = $source->getNameIndex($index);
            $content = $source->getFromIndex($index);

            if ($content !== false && preg_match('#^word/(document|header\d+|footer\d+)\.xml$#', $name)) {
                $content = $this->normalizeXmlMacros($content);
            }

            $target->addFromString($name, $content === false ? '' : $content);
        }

        $source->close();
        $target->close();

        return $temporary;
    }

    private function normalizeXmlMacros(string $xml): string
    {
        $document = new \DOMDocument();
        $document->preserveWhiteSpace = false;
        if (! $document->loadXML($xml)) {
            throw new RuntimeException('Document template contains invalid XML.');
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

            $fullMatches = $matches[0];
            $nameMatches = $matches[1];

            for ($m = count($fullMatches) - 1; $m >= 0; $m--) {
                $match = $fullMatches[$m];
                $rawMacro = $match[0];
                $start = $match[1];
                $macroName = $nameMatches[$m][0] ?? trim(substr($rawMacro, 2, -2));
                $canonical = '{{'.$macroName.'}}';
                $end = $start + strlen($rawMacro);
                $offset = 0;
                $startIndex = null;
                $endIndex = null;
                $startOffset = 0;
                $endOffset = 0;

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

                $startText = $textNodes[$startIndex]->textContent;
                $endText = $textNodes[$endIndex]->textContent;
                $prefix = substr($startText, 0, $startOffset);
                $suffix = substr($endText, $endOffset);
                $textNodes[$startIndex]->nodeValue = $prefix.$canonical.($startIndex === $endIndex ? $suffix : '');

                if ($startIndex !== $endIndex) {
                    for ($index = $startIndex + 1; $index < $endIndex; $index++) {
                        $textNodes[$index]->nodeValue = '';
                    }
                    $textNodes[$endIndex]->nodeValue = $suffix;
                }
            }
        }

        return $document->saveXML();
    }

    private function formatDateFields(array $values, string $type): array
    {
        $fields = array_merge(
            config('documents.fields.common', []),
            config("documents.fields.{$type}", []),
        );

        foreach ($fields as $field) {
            if (($field['type'] ?? null) !== 'date') {
                continue;
            }

            $name = $field['name'];
            if (! isset($values[$name]) || ! is_string($values[$name])) {
                continue;
            }

            try {
                $values[$name] = Carbon::parse($values[$name])->translatedFormat('d F Y');
            } catch (\Throwable) {
                // Leave invalid or unexpected date formats as-is.
            }
        }

        return $values;
    }

    private function sanitizeValuesForXml(array $values): array
    {
        foreach ($values as $key => $value) {
            if (! is_string($value)) {
                continue;
            }

            $values[$key] = htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        }

        return $values;
    }

    private function assertValidDocx(string $path): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException("Generated document is not a valid DOCX archive: {$path}");
        }

        foreach (['word/document.xml'] as $part) {
            $xml = $zip->getFromName($part);
            $document = new \DOMDocument();
            if ($xml === false || ! $document->loadXML($xml)) {
                $zip->close();
                throw new RuntimeException("Generated document contains invalid XML in {$part}: {$path}");
            }
        }

        $zip->close();
    }
}
