<?php

namespace Tests\Unit;

use App\Documents\DocumentTemplateRegistry;
use InvalidArgumentException;
use Tests\TestCase;

class DocumentTemplateRegistryTest extends TestCase
{
    public function test_it_exposes_the_four_poc_templates(): void
    {
        $templates = app(DocumentTemplateRegistry::class)->all();

        $this->assertCount(4, $templates);
        $this->assertSame('Surat Keterangan Usaha', $templates[1]->name);
        $this->assertSame(
            resource_path('templates'.DIRECTORY_SEPARATOR.'surat-keterangan-usaha.docx'),
            $templates[1]->path,
        );
    }

    public function test_unknown_document_types_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(DocumentTemplateRegistry::class)->get('unknown');
    }
}
