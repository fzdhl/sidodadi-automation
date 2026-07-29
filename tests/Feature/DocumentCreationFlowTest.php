<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Documents\DocumentGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class DocumentCreationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_creation_form_can_lookup_and_save_resident_then_generate_document(): void
    {
        Storage::fake('local');

        $templatePath = resource_path('templates/surat-keterangan-usaha.docx');
        $word = new PhpWord();
        $word->addSection()->addText('{{nama}} / {{ttl}}');
        $word->save($templatePath);

        config(['documents.types.usaha.template' => basename($templatePath)]);

        Resident::create([
            'nik' => '3507010101010004',
            'nama' => 'Budi Santoso',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '1985-05-12',
            'jenis_kelamin' => 'Laki-laki',
            'agama' => 'Islam',
            'status_perkawinan' => 'Kawin',
            'kewarganegaraan' => 'Indonesia',
            'pekerjaan' => 'Wiraswasta',
            'alamat' => 'Jl. Merdeka No.1',
            'keperluan' => 'Pengajuan surat keterangan usaha',
            'jenis_usaha' => 'Perdagangan',
            'nama_usaha' => 'Toko Budi',
            'lama_usaha' => '10 tahun',
            'alamat_usaha' => 'Jl. Merdeka No.1',
        ]);

        $response = $this->postJson(route('residents.lookup'), ['nik' => '3507010101010004']);
        $response->assertOk()
            ->assertJsonPath('data.nama', 'Budi Santoso')
            ->assertJsonPath('data.pekerjaan', 'Wiraswasta');

        $storeResponse = $this->postJson(route('documents.store'), [
            'document_type' => 'usaha',
            'nomor_surat' => '470/002/2026',
            'tanggal_surat' => '2026-07-29',
            'nama' => 'Budi Santoso',
            'nik' => '3507010101010004',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '1985-05-12',
            'jenis_kelamin' => 'Laki-laki',
            'agama' => 'Islam',
            'status_perkawinan' => 'Kawin',
            'kewarganegaraan' => 'Indonesia',
            'pekerjaan' => 'Wiraswasta',
            'alamat' => 'Jl. Merdeka No.1',
            'keperluan' => 'Pengajuan surat keterangan usaha',
            'jenis_usaha' => 'Perdagangan',
            'nama_usaha' => 'Toko Budi',
            'lama_usaha' => '10 tahun',
            'alamat_usaha' => 'Jl. Merdeka No.1',
            'save_as_resident' => 1,
        ]);

        $storeResponse->assertOk()
            ->assertJsonStructure(['message', 'download_url']);

        $this->assertDatabaseHas('residents', ['nik' => '3507010101010004', 'nama' => 'Budi Santoso']);

        $downloadUrl = $storeResponse->json('download_url');
        $this->assertStringContainsString('documents/download', $downloadUrl);

        @unlink($templatePath);
    }

    public function test_document_generation_escapes_ampersands_for_xml(): void
    {
        Storage::fake('local');

        $templatePath = resource_path('templates/surat-keterangan-usaha.docx');
        $word = new PhpWord();
        $word->addSection()->addText('{{nama_usaha}}');
        $word->save($templatePath);

        config(['documents.types.usaha.template' => basename($templatePath)]);

        $response = $this->postJson(route('documents.store'), [
            'document_type' => 'usaha',
            'nomor_surat' => '470/003/2026',
            'tanggal_surat' => '2026-07-29',
            'nama' => 'Budi Santoso',
            'nik' => '3507010101010004',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '1985-05-12',
            'jenis_kelamin' => 'Laki-laki',
            'agama' => 'Islam',
            'status_perkawinan' => 'Kawin',
            'kewarganegaraan' => 'Indonesia',
            'pekerjaan' => 'Wiraswasta',
            'alamat' => 'Jl. Merdeka No.1',
            'keperluan' => 'Pengajuan surat keterangan usaha',
            'jenis_usaha' => 'Perdagangan',
            'nama_usaha' => 'Toko A & B',
            'lama_usaha' => '10 tahun',
            'alamat_usaha' => 'Jl. Merdeka No.1',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'download_url']);

        @unlink($templatePath);
    }

    public function test_document_generation_errors_are_exposed_in_json_response(): void
    {
        Storage::fake('local');

        $invalidTemplate = resource_path('templates/INVALID-TEMPLATE.docx');
        file_put_contents($invalidTemplate, 'not-a-docx');

        config(['documents.types.usaha.template' => basename($invalidTemplate)]);

        $response = $this->postJson(route('documents.store'), [
            'document_type' => 'usaha',
            'nomor_surat' => '470/002/2026',
            'tanggal_surat' => '2026-07-29',
            'nama' => 'Budi Santoso',
            'nik' => '3507010101010004',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '1985-05-12',
            'jenis_kelamin' => 'Laki-laki',
            'agama' => 'Islam',
            'status_perkawinan' => 'Kawin',
            'kewarganegaraan' => 'Indonesia',
            'pekerjaan' => 'Wiraswasta',
            'alamat' => 'Jl. Merdeka No.1',
            'keperluan' => 'Pengajuan surat keterangan usaha',
            'jenis_usaha' => 'Perdagangan',
            'nama_usaha' => 'Toko Budi',
            'lama_usaha' => '10 tahun',
            'alamat_usaha' => 'Jl. Merdeka No.1',
        ]);

        @unlink($invalidTemplate);

        $response->assertStatus(500)
            ->assertJsonStructure(['message', 'reference_id'])
            ->assertJsonPath('message', 'Terjadi kesalahan saat membuat dokumen. Silakan coba lagi.');

        $this->assertMatchesRegularExpression('/^DOCGEN-[A-Z0-9]{8}$/', $response->json('reference_id'));
    }

    public function test_document_generation_failure_logs_reference_id_for_troubleshooting(): void
    {
        Storage::fake('local');
        Log::spy();

        $invalidTemplate = resource_path('templates/INVALID-TEMPLATE.docx');
        file_put_contents($invalidTemplate, 'not-a-docx');
        config(['documents.types.usaha.template' => basename($invalidTemplate)]);

        $response = $this->postJson(route('documents.store'), [
            'document_type' => 'usaha',
            'nomor_surat' => '470/004/2026',
            'tanggal_surat' => '2026-07-29',
            'nama' => 'Budi Santoso',
            'nik' => '3507010101010004',
            'tempat_lahir' => 'Surabaya',
            'tanggal_lahir' => '1985-05-12',
            'jenis_kelamin' => 'Laki-laki',
            'agama' => 'Islam',
            'status_perkawinan' => 'Kawin',
            'kewarganegaraan' => 'Indonesia',
            'pekerjaan' => 'Wiraswasta',
            'alamat' => 'Jl. Merdeka No.1',
            'keperluan' => 'Pengajuan surat keterangan usaha',
            'jenis_usaha' => 'Perdagangan',
            'nama_usaha' => 'Toko Budi',
            'lama_usaha' => '10 tahun',
            'alamat_usaha' => 'Jl. Merdeka No.1',
        ]);

        @unlink($invalidTemplate);

        $response->assertStatus(500)
            ->assertJsonStructure(['message', 'reference_id']);

        $referenceId = $response->json('reference_id');
        Log::shouldHaveReceived('error')
            ->with(
                'Document generation failed',
                Mockery::on(fn ($context) => isset($context['reference_id']) && $context['reference_id'] === $referenceId),
            );
    }
}
