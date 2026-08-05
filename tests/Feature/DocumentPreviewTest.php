<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class DocumentPreviewTest extends TestCase
{
    public function test_document_preview_returns_html_for_registered_template(): void
    {
        $templatePath = resource_path('templates/surat-keterangan-usaha-preview.docx');
        $word = new PhpWord();
        $word->addSection()->addText('{{nama}} / {{ttl}} / {{keperluan}}');
        $word->save($templatePath);

        config(['documents.types.usaha.template' => basename($templatePath)]);

        try {
            $response = $this->postJson(route('documents.preview'), [
                'document_type' => 'usaha',
                'nomor_surat' => '470/001/2026',
                'tanggal_surat' => '2026-07-24',
                'nama' => 'Siti Aminah',
                'nik' => '3507010101010001',
                'tempat_lahir' => 'Malang',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'Perempuan',
                'agama' => 'Islam',
                'status_perkawinan' => 'Belum Kawin',
                'kewarganegaraan' => 'Indonesia',
                'pekerjaan' => 'Wiraswasta',
                'alamat' => 'Dusun Sidodadi',
                'keperluan' => 'Pengajuan surat keterangan usaha',
                'jenis_usaha' => 'Perdagangan',
                'nama_usaha' => 'Toko Siti',
                'lama_usaha' => '5 tahun',
                'alamat_usaha' => 'Dusun Sidodadi',
            ]);

            $response->assertOk();
            $html = $response->json('html');
            $this->assertStringContainsString('SITI AMINAH', $html);
            $this->assertStringContainsString('Malang', $html);
            $this->assertStringContainsString('PENGAJUAN SURAT KETERANGAN USAHA', $html);
            $this->assertStringNotContainsString('{{nama}}', $html);
            $this->assertStringNotContainsString('{{ttl}}', $html);
        } finally {
            if (is_file($templatePath)) {
                unlink($templatePath);
            }
        }
    }

    public function test_document_preview_returns_validation_error_for_unknown_type(): void
    {
        $response = $this->postJson(route('documents.preview'), [
            'document_type' => 'unknown-type',
        ]);

        $response->assertStatus(422);
    }
}
