<?php

namespace App\Console\Commands;

use App\Documents\DocumentTemplateAnalyzer;
use App\Documents\DocumentTemplateRegistry;
use Illuminate\Console\Command;

class AnalyzeDocumentTemplateCommand extends Command
{
    protected $signature = 'app:analyze-document-template {type : Document type}';

    protected $description = 'Analyze a document template and report placeholders styled as bold';

    public function handle(DocumentTemplateRegistry $templates, DocumentTemplateAnalyzer $analyzer): int
    {
        $type = $this->argument('type');

        try {
            $template = $templates->get($type);
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info("Analyzing template: {$template->name}");

        try {
            $placeholders = $analyzer->detectBoldPlaceholders($type);
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        if ($placeholders === []) {
            $this->info('No bold placeholders detected.');
            return self::SUCCESS;
        }

        $this->info('Detected bold placeholders:');
        foreach ($placeholders as $placeholder) {
            $this->line("- {$placeholder}");
        }

        $this->newLine();
        $this->info('Copy these values into config/documents.php under uppercase_placeholders if you want them uppercased.');

        return self::SUCCESS;
    }
}
