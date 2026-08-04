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

    public function test_resident_search_returns_json_suggestions(): void
    {
        Resident::create([
            'nik' => '3507010101010008',
            'nama' => 'Budi Pencarian',
        ]);

        $this->getJson(route('residents.search', ['q' => 'Budi']))
            ->assertOk()
            ->assertJsonPath('data.0.nik', '3507010101010008')
            ->assertJsonPath('data.0.nama', 'Budi Pencarian');
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

    public function test_resident_export_xlsx_returns_spreadsheet_download(): void
    {
        Resident::create([
            'nik' => '3507010101010007',
            'nama' => 'Export Test',
        ]);

        $response = $this->get(route('residents.export.xlsx'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('Content-Disposition', 'attachment; filename=residents.xlsx');
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
            ->assertSessionHas('success', 'Berhasil mengimpor 1 data penduduk. 1 baris dilewati karena NIK kosong atau tidak valid.');

        $this->assertDatabaseHas('residents', ['nik' => '3507010101010004', 'nama' => 'Budi Santoso']);
        $this->assertDatabaseMissing('residents', ['nama' => 'No NIK']);

        fclose($file);
    }

    public function test_import_xlsx_maps_dpt_columns_and_supports_lookup(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'sidodadi-dpt-').'.xlsx';
        $workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $workbook->getActiveSheet();
        $sheet->setTitle('URUT NAMA');
        $sheet->fromArray([
            ['DPID', 'NO_KK', 'NIK', 'NAMA_LGKP', 'TMPT_LHR', 'TGL_LAHIR', 'STATUS', 'JENIS_KELAMIN', 'ALAMAT', 'NO_RT', 'NO_RW', 'DISABILITAS', 'EKTP', 'KET', 'SUMBER', 'TPS', 'DESA/KELURAHAN'],
            ['1', '3507010000000001', '3507010101010001', 'BUDI SANTOSO', 'MALANG', '01|01|1965', 'B', 'L', 'GEDANGAN', '1', '11', '0', 's', '0', 'daftar pemilih', '1', 'SIDODADI'],
            ['2', '3507010000000002', '', 'TANPA NIK', 'MALANG', '02|02|1966', 'B', 'P', 'GEDANGAN', '2', '11', '0', 's', '0', 'daftar pemilih', '1', 'SIDODADI'],
        ], null, 'A1');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($workbook))->save($path);

        $uploaded = new \Illuminate\Http\UploadedFile($path, 'dpt.xlsx', null, null, true);
        $response = $this->post(route('residents.import'), ['resident_csv' => $uploaded]);

        $response->assertRedirect(route('residents.index'))
            ->assertSessionHas('success', 'Berhasil mengimpor 1 data penduduk. 1 baris dilewati karena NIK kosong atau tidak valid.');

        $this->assertDatabaseHas('residents', [
            'nik' => '3507010101010001',
            'nama' => 'BUDI SANTOSO',
            'tempat_lahir' => 'MALANG',
            'tanggal_lahir' => '1965-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'GEDANGAN, RT 1, RW 11, SIDODADI',
        ]);

        $lookup = $this->postJson(route('residents.lookup'), ['nik' => '3507010101010001']);
        $lookup->assertOk()
            ->assertJsonPath('data.nama', 'BUDI SANTOSO')
            ->assertJsonPath('data.jenis_kelamin', 'Laki-laki');

        @unlink($path);
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

    public function test_resident_index_paginates_results_and_preserves_search_query(): void
    {
        for ($index = 1; $index <= 26; $index++) {
            Resident::create([
                'nik' => str_pad((string) (3507010101010000 + $index), 16, '0', STR_PAD_LEFT),
                'nama' => "Penduduk {$index}",
            ]);
        }

        $response = $this->get(route('residents.index', ['search' => 'Penduduk', 'page' => 2]));

        $response->assertOk()
            ->assertSee('Penduduk 9')
            ->assertDontSee('Penduduk 1')
            ->assertSee('search=Penduduk');
    }
}
