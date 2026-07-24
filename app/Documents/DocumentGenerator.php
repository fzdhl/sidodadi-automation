<?php

namespace App\Documents;

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

        $directory = dirname($outputPath);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create output directory: {$directory}");
        }

        $processor = new TemplateProcessor($template->path);
        $processor->setValues($values);
        $processor->saveAs($outputPath);

        return $outputPath;
    }
}
