<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class DocumentFormTest extends TestCase
{
    public function test_document_form_lists_the_twenty_document_types(): void
    {
        $response = $this->get(route('documents.create'));

        $response->assertOk()
            ->assertSee('Keterangan Domisili')
            ->assertSee('Keterangan Usaha')
            ->assertSee('Keterangan Tidak Mampu')
            ->assertSee('Keterangan Biasa');
    }

    public function test_business_letter_form_accepts_valid_applicant_data(): void
    {
        $templatePath = resource_path('templates/surat-keterangan-usaha.docx');
        config(['documents.types.usaha.template' => basename($templatePath)]);
        $word = new PhpWord();
        $word->addSection()->addText('{{nama}} / {{ttl}}');
        $word->save($templatePath);

        try {
            $response = $this->post(route('documents.store'), [
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

            $response->assertOk()
                ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
                ->assertHeader('Content-Disposition');
        } finally {
            unlink($templatePath);
            Storage::disk('local')->delete(Storage::disk('local')->files('generated-documents'));
        }
    }

    public function test_nik_is_required_to_have_sixteen_digits(): void
    {
        $response = $this->from(route('documents.create'))
            ->post(route('documents.store'), [
                'document_type' => 'domisili',
                'nik' => '123',
            ]);

        $response->assertRedirect(route('documents.create'))
            ->assertSessionHasErrors(['nik', 'nama', 'nomor_surat']);
    }

    public function test_validation_errors_are_displayed_in_indonesian(): void
    {
        $response = $this->from(route('documents.create'))
            ->post(route('documents.store'), []);

        $response->assertRedirect(route('documents.create'));
        $this->assertStringContainsString('Kolom nomor surat wajib diisi.', session('errors')->first('nomor_surat'));
    }

    public function test_runtime_exceptions_are_shown_as_user_friendly_error(): void
    {
        config(['documents.types.domisili.template' => 'not-found-template.docx']);

        $response = $this->from(route('documents.create'))
            ->post(route('documents.store'), [
                'document_type' => 'domisili',
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
                'keperluan' => 'Pengajuan surat keterangan domisili',
            ]);

        $response->assertRedirect(route('documents.create'));
        $this->assertMatchesRegularExpression(
            '/^Terjadi kesalahan saat membuat dokumen\. Silakan coba lagi\. Kode: DOCGEN-[A-Z0-9]{8}$/',
            session('error'),
        );
    }
}
