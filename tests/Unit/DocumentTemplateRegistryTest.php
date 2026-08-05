<?php

namespace Tests\Unit;

use App\Documents\DocumentTemplateRegistry;
use InvalidArgumentException;
use Tests\TestCase;

class DocumentTemplateRegistryTest extends TestCase
{
    public function test_it_exposes_the_twenty_active_templates(): void
    {
        $templates = app(DocumentTemplateRegistry::class)->all();

        $this->assertCount(20, $templates);

        $usaha = collect($templates)->firstWhere('type', 'usaha');

        $this->assertNotNull($usaha);
        $this->assertSame('Keterangan Usaha', $usaha->name);
        $this->assertSame(
            resource_path('templates').DIRECTORY_SEPARATOR.'letters'.DIRECTORY_SEPARATOR.'TEMPLATE KETERANGAN USAHA.docx',
            str_replace('/', DIRECTORY_SEPARATOR, $usaha->path),
        );
    }

    public function test_unknown_document_types_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(DocumentTemplateRegistry::class)->get('unknown');
    }
}
