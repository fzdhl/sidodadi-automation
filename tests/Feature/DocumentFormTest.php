<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class DocumentFormTest extends TestCase
{
    public function test_document_form_lists_the_four_document_types(): void
    {
        $response = $this->get(route('documents.create'));

        $response->assertOk()
            ->assertSee('Surat Keterangan Domisili')
            ->assertSee('Surat Keterangan Usaha')
            ->assertSee('Surat Keterangan Tidak Mampu')
            ->assertSee('Surat Keterangan');
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
}
