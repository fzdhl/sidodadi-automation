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

    public function test_resident_template_download_returns_csv_header(): void
    {
        $response = $this->get(route('residents.template'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="resident-template.csv"');
        $response->assertSee('nik,nama,tempat_lahir,tanggal_lahir,jenis_kelamin,agama,status_perkawinan,kewarganegaraan,pekerjaan,alamat,jenis_usaha,nama_usaha,lama_usaha,alamat_usaha,nama_anak,nik_anak,nama_sekolah');
    }

    public function test_import_csv_skips_rows_without_nik_and_reports_summary(): void
    {
        $csv = "nik,nama,tempat_lahir,tanggal_lahir\n" .
            ",No NIK,Unknown,1990-01-01\n" .
            "3507010101010004,Budi Santoso,Surabaya,1985-05-12\n";

        $file = tmpfile();
        fwrite($file, $csv);
        fseek($file, 0);

        $uploaded = new \Illuminate\Http\UploadedFile(stream_get_meta_data($file)['uri'], 'residents.csv', null, null, true);

        $response = $this->post(route('residents.import'), ['resident_csv' => $uploaded]);

        $response->assertRedirect(route('residents.index'))
            ->assertSessionHas('success', 'Berhasil mengimpor 1 data penduduk. 1 baris dilewati karena data tidak lengkap atau NIK kosong.');

        $this->assertDatabaseHas('residents', ['nik' => '3507010101010004', 'nama' => 'Budi Santoso']);
        $this->assertDatabaseMissing('residents', ['nama' => 'No NIK']);

        fclose($file);
    }

    public function test_resident_index_shows_internal_storage_table_and_count(): void
    {
        Resident::create([
            'nik' => '3507010101010005',
            'nama' => 'Lina Wulandari',
            'tempat_lahir' => 'Banyuwangi',
            'tanggal_lahir' => '1992-08-10',
        ]);

        Resident::create([
            'nik' => '3507010101010006',
            'nama' => 'Rudi Hartono',
            'tempat_lahir' => 'Malang',
            'tanggal_lahir' => '1988-05-20',
        ]);

        $response = $this->get(route('residents.index'));

        $response->assertOk();
        $response->assertSee('Internal resident storage:');
        $response->assertSee('2 data tersimpan');
        $response->assertSee('Lina Wulandari');
        $response->assertSee('Rudi Hartono');
    }
}
