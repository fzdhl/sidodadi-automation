<?php

namespace Tests\Feature;

use App\Models\Resident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_lookup_returns_json_data_when_nik_exists(): void
    {
        Resident::create([
            'nik' => '3507010101010001',
            'nama' => 'Siti Aminah',
            'tempat_lahir' => 'Malang',
            'tanggal_lahir' => '1990-01-01',
        ]);

        $response = $this->postJson(route('residents.lookup'), ['nik' => '3507010101010001']);

        $response->assertOk()
            ->assertJsonPath('data.nik', '3507010101010001')
            ->assertJsonPath('data.nama', 'Siti Aminah')
            ->assertJsonPath('data.tanggal_lahir', '1990-01-01');
    }

    public function test_resident_lookup_returns_404_when_not_found(): void
    {
        $response = $this->postJson(route('residents.lookup'), ['nik' => '3507010101010002']);

        $response->assertNotFound()
            ->assertJson(['message' => 'Data tidak ditemukan. Silakan isi data secara manual dan simpan jika perlu.']);
    }

    public function test_import_csv_creates_or_updates_resident_records(): void
    {
        $csv = "nik,nama,tempat_lahir,tanggal_lahir,jenis_kelamin,agama,status_perkawinan,kewarganegaraan,pekerjaan,alamat,jenis_usaha,nama_usaha,lama_usaha,alamat_usaha,nama_anak,nik_anak,nama_sekolah\n" .
            "3507010101010003,Siti Aminah,Malang,1990-01-01,Perempuan,Islam,Belum Kawin,Indonesia,Wiraswasta,Dusun Sidodadi,Perdagangan,Toko Siti,5 tahun,Dusun Sidodadi,,,\n";

        $file = tmpfile();
        fwrite($file, $csv);
        fseek($file, 0);

        $uploaded = new \Illuminate\Http\UploadedFile(stream_get_meta_data($file)['uri'], 'residents.csv', null, null, true);

        $response = $this->post(route('residents.import'), ['resident_csv' => $uploaded]);

        $response->assertRedirect(route('residents.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('residents', ['nik' => '3507010101010003', 'nama' => 'Siti Aminah']);

        fclose($file);
    }
}
