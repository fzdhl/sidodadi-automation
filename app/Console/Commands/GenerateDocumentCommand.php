<?php

namespace App\Console\Commands;

use App\Documents\DocumentGenerator;
use App\Documents\DocumentTemplateRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use RuntimeException;

class GenerateDocumentCommand extends Command
{
    protected $signature = 'app:generate-document {--type= : Document type} {--output= : Output path} {--data= : JSON data payload}';

    protected $description = 'Generate a DOCX document from a registered template';

    public function handle(DocumentTemplateRegistry $templates, DocumentGenerator $generator): int
    {
        $type = $this->option('type');
        $output = $this->option('output');
        $data = $this->option('data');

        if (empty($type) || empty($output)) {
            $this->error('Both --type and --output are required.');
            return self::FAILURE;
        }

        $decoded = [];
        if (! empty($data)) {
            $payload = trim((string) $data);
            if ((str_starts_with($payload, '"') && str_ends_with($payload, '"')) || (str_starts_with($payload, "'") && str_ends_with($payload, "'"))) {
                $payload = substr($payload, 1, -1);
            }

            try {
                $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $decoded = [];
                foreach (preg_split('/\s*,\s*/', $payload) as $segment) {
                    if ($segment === '') {
                        continue;
                    }

                    $parts = explode('=', $segment, 2);
                    if (count($parts) !== 2) {
                        continue;
                    }

                    $key = trim($parts[0]);
                    $value = trim($parts[1], "\"' ");

                    if ($key !== '') {
                        $decoded[$key] = $value;
                    }
                }
            }
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('Data payload must be a valid JSON object or key=value list.');
        }

        $generator->generate($type, $decoded, $output);

        $this->info("Generated document: {$output}");

        return self::SUCCESS;
    }
}
