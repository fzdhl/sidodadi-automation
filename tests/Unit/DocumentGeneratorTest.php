<?php

namespace Tests\Unit;

use App\Documents\DocumentGenerator;
use App\Documents\DocumentTemplateRegistry;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class DocumentGeneratorTest extends TestCase
{
    public function test_it_generates_a_docx_from_a_registered_template(): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sidodadi-document-generator-'.uniqid();
        mkdir($directory, 0775, true);

        $templatePath = $directory.DIRECTORY_SEPARATOR.'template.docx';
        $outputPath = $directory.DIRECTORY_SEPARATOR.'generated.docx';

        $word = new PhpWord();
        $word->addSection()->addText('{{nama}}');
        $word->save($templatePath);

        $registry = new DocumentTemplateRegistry($directory, [
            'test' => ['name' => 'Test', 'template' => 'template.docx'],
        ]);

        app()->make(DocumentGenerator::class, ['templates' => $registry])
            ->generate('test', ['nama' => 'Siti'], $outputPath);

        $this->assertFileExists($outputPath);
        $this->assertGreaterThan(0, filesize($outputPath));

        $zip = new \ZipArchive();
        $this->assertSame(true, $zip->open($outputPath));
        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('SITI', $documentXml);
        $this->assertStringNotContainsString('{{nama}}', $documentXml);

        unlink($templatePath);
        unlink($outputPath);
        rmdir($directory);
    }
}
