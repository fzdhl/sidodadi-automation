<?php

namespace Tests\Feature;

use Tests\TestCase;

class GenerateDocumentCommandTest extends TestCase
{
    public function test_command_generates_a_docx_from_a_template(): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sidodadi-command-test-'.uniqid('', true);
        mkdir($directory, 0775, true);

        $outputPath = $directory.DIRECTORY_SEPARATOR.'generated.docx';

        $this->artisan('app:generate-document', [
            '--type' => 'domisili',
            '--output' => $outputPath,
            '--data' => 'nama=Siti Aminah,agama=Islam',
        ])->assertSuccessful();

        $this->assertFileExists($outputPath);
        $this->assertGreaterThan(0, filesize($outputPath));

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($outputPath));
        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('SITI AMINAH', $documentXml);
        $this->assertStringNotContainsString('{{nama}}', $documentXml);
    }
}
