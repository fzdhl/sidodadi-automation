<?php

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

$files = glob(__DIR__.'/../resources/templates/*.docx');
foreach ($files as $file) {
    if (str_contains(basename($file), 'HASIL-')) {
        continue;
    }

    $processor = new TemplateProcessor($file);
    $processor->setMacroChars('{{', '}}');
    echo '--- '.basename($file).' ---'.PHP_EOL;
    echo 'variables: '.implode(', ', $processor->getVariables()).PHP_EOL;
}
