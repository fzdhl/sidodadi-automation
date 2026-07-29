<?php

namespace Tests\Feature;

use App\Console\Commands\AnalyzeDocumentTemplateCommand;
use Illuminate\Support\Facades\Artisan;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class AnalyzeDocumentTemplateCommandTest extends TestCase
{
    public function test_command_reports_bold_placeholders(): void
    {
        $templatePath = resource_path('templates/test-bold-template.docx');
        $word = new PhpWord();
        $section = $word->addSection();
        $section->addText('Nomor: ');
        $section->addTextRun()->addText('{{$', ['bold' => true]);
        $section->addText('{{nama}}', ['bold' => true]);
        $word->save($templatePath);

        try {
            config(['documents.types.test' => [
                'name' => 'Test Bold Template',
                'template' => basename($templatePath),
            ]]);

            $this->artisan('app:analyze-document-template', ['type' => 'test'])
                ->assertSuccessful()
                ->expectsOutput('Detected bold placeholders:')
                ->expectsOutput('- nama');
        } finally {
            if (file_exists($templatePath)) {
                unlink($templatePath);
            }
        }
    }
}
