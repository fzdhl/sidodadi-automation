<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

$templatePath = __DIR__ . '/../resources/templates/TEMPLATE KETERANGAN USAHA.docx';
$outputPath = __DIR__ . '/check-template-processor.docx';

if (! is_file($templatePath)) {
    echo "Template not found: {$templatePath}\n";
    exit(1);
}

try {
    $processor = new TemplateProcessor($templatePath);
    $processor->setMacroChars('{{', '}}');
    $variables = $processor->getVariables();
    echo "TemplateProcessor variables: ";
    var_export($variables);
    echo "\n";

    foreach ($variables as $variable) {
        $processor->setValue($variable, 'TEST');
    }

    $processor->saveAs($outputPath);
    echo "Saved to {$outputPath}\n";

    $zip = new ZipArchive();
    if ($zip->open($outputPath) !== true) {
        echo "Failed to open generated output\n";
        exit(1);
    }
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    if ($xml === false) {
        echo "Missing word/document.xml in output\n";
        exit(1);
    }
    echo "Generated document.xml length: " . strlen($xml) . "\n";
    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $ok = $doc->loadXML($xml);
    if (! $ok) {
        echo "Generated document.xml invalid\n";
        foreach (libxml_get_errors() as $error) {
            echo trim($error->message) . " at line {$error->line} col {$error->column}\n";
        }
    } else {
        echo "Generated document.xml is valid XML\n";
    }
} catch (Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
