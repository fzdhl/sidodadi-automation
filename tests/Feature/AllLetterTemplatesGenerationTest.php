<?php

namespace Tests\Feature;

use App\Documents\DocumentGenerator;
use App\Documents\DocumentTemplateRegistry;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class AllLetterTemplatesGenerationTest extends TestCase
{
    public function test_all_active_letter_templates_generate_without_unresolved_placeholders(): void
    {
        $templates = app(DocumentTemplateRegistry::class)->all();
        $baseValues = [
            'nomor_surat' => '470/TEST/2026',
            'tanggal_surat' => '2026-08-04',
            'nama' => 'Siti Aminah',
            'nik' => '3507010101010001',
            'tempat_lahir' => 'Malang',
            'tanggal_lahir' => '1990-01-01',
            'ttl' => 'Malang, 01 Januari 1990',
            'jenis_kelamin' => 'Perempuan',
            'agama' => 'Islam',
            'status_perkawinan' => 'Belum Kawin',
            'kewarganegaraan' => 'Indonesia',
            'pekerjaan' => 'Wiraswasta',
            'alamat' => 'Dusun Sidodadi RT 001 RW 001 Desa Sidodadi',
            'keperluan' => 'Keperluan administrasi',
            'isi_keterangan' => 'Keterangan untuk keperluan administrasi.',
            'pihak_1_nama' => 'Budi Santoso',
            'pihak_1_nik' => '3507010101010002',
            'pihak_1_ttl' => 'Malang, 02 Februari 1992',
            'pihak_1_jenis_kelamin' => 'Laki-laki',
            'pihak_1_agama' => 'Islam',
            'pihak_1_status_perkawinan' => 'Kawin',
            'pihak_1_kewarganegaraan' => 'Indonesia',
            'pihak_1_pekerjaan' => 'Karyawan',
            'pihak_1_alamat' => 'Dusun Sidodadi RT 002 RW 002 Desa Sidodadi',
            'pihak_1_umur' => '32 Tahun',
            'pihak_1_sekolah_universitas' => 'SMK Sidodadi',
            'jenis_usaha' => 'Perdagangan',
            'nama_usaha' => 'Toko Uji',
            'lama_usaha' => '5 Tahun',
            'alamat_usaha' => 'Dusun Sidodadi',
            'merek_model' => 'Honda',
            'nomor_polisi' => 'N 1234 AB',
            'nomor_rangka' => 'RANGKA-TEST',
            'nomor_mesin' => 'MESIN-TEST',
            'nomor_bpkb' => 'BPKB-TEST',
            'warna_kendaraan' => 'Hitam',
            'alamat_objek' => 'Dusun Sidodadi',
            'nomor_pelanggan' => 'PDAM-TEST',
            'nama_pelanggan_lama' => 'Pelanggan Lama',
            'nama_pelanggan_baru' => 'Pelanggan Baru',
            'kelompok_nama' => 'Kelompok Seni Uji',
            'kelompok_ketua' => 'Ketua Uji',
            'kelompok_alamat' => 'Dusun Sidodadi',
            'nama_instansi' => 'Pemerintah Desa Sidodadi',
            'jabatan' => 'Staf',
            'periode_mulai' => 'Januari 2020',
            'periode_selesai' => 'Desember 2024',
            'yang_diampu_nama' => 'Anak Uji',
            'yang_diampu_nik' => '3507010101010003',
            'pengampu_nama' => 'Wali Uji',
            'pengampu_nik' => '3507010101010004',
            'yang_diampu_ttl' => 'Malang, 03 Maret 2015',
            'yang_diampu_jenis_kelamin' => 'Laki-laki',
            'yang_diampu_umur' => '10 Tahun',
            'yang_diampu_kewarganegaraan' => 'Indonesia',
            'yang_diampu_agama' => 'Islam',
            'yang_diampu_pekerjaan' => 'Pelajar',
            'yang_diampu_alamat' => 'Dusun Sidodadi',
            'pengampu_ttl' => 'Malang, 04 April 1985',
            'pengampu_jenis_kelamin' => 'Perempuan',
            'pengampu_status_perkawinan' => 'Kawin',
            'pengampu_agama' => 'Islam',
            'pengampu_kewarganegaraan' => 'Indonesia',
            'pengampu_pekerjaan' => 'Wiraswasta',
            'pengampu_alamat' => 'Dusun Sidodadi',
            'tanggal_mulai_pengampuan' => '2023-01-01',
            'alasan_pengampuan' => 'Kebutuhan pengasuhan keluarga.',
            'nama_anak' => 'Anak Uji',
            'nik_anak' => '3507010101010003',
            'jenjang_pendidikan' => 'SMA',
            'alasan' => 'Keterangan administratif.',
        ];

        $unresolved = [];
        $generationErrors = [];

        foreach ($templates as $template) {
            if ($template->type === 'usaha') {
                continue;
            }
            $output = tempnam(sys_get_temp_dir(), 'sidodadi-letter-').'.docx';
            $templateValues = array_intersect_key(
                $baseValues,
                array_flip($this->templateVariables($template->path)),
            );

            try {
                $generator = app(DocumentGenerator::class);
                try {
                    $generator->generate($template->type, $templateValues, $output);
                } catch (\Throwable $exception) {
                    $generationErrors[$template->type] = $exception->getMessage();
                    continue;
                }
                $zip = new \ZipArchive();
                $this->assertSame(true, $zip->open($output), $template->type);

                $xml = '';
                for ($index = 0; $index < $zip->numFiles; $index++) {
                    $name = $zip->getNameIndex($index);
                    if (preg_match('#^word/(document|header\d+|footer\d+)\.xml$#', $name)) {
                        $xml .= (string) $zip->getFromIndex($index);
                    }
                }
                $zip->close();

                if (str_contains($xml, '{{') || str_contains($xml, '}}')) {
                    preg_match_all('/\{\{.*?\}\}/s', $xml, $matches);
                    $unresolved[$template->type] = array_values(array_unique($matches[0] ?? []));
                }
            } finally {
                @unlink($output);
            }
        }

        $this->assertSame([], $generationErrors, json_encode($generationErrors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->assertSame([], $unresolved, json_encode($unresolved, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    #[RunInSeparateProcess]
    public function test_usaha_template_generates_in_an_isolated_process(): void
    {
        $template = app(DocumentTemplateRegistry::class)->get('usaha');
        $output = tempnam(sys_get_temp_dir(), 'sidodadi-usaha-').'.docx';

        try {
            app(DocumentGenerator::class)->generate('usaha', [
                'nomor_surat' => '470/TEST/2026',
                'tanggal_surat' => '2026-08-05',
                'nama' => 'Siti Aminah',
                'nik' => '3507010101010001',
                'tempat_lahir' => 'Malang',
                'tanggal_lahir' => '1990-01-01',
                'ttl' => 'Malang, 01 Januari 1990',
                'jenis_kelamin' => 'Perempuan',
                'agama' => 'Islam',
                'status_perkawinan' => 'Belum Kawin',
                'kewarganegaraan' => 'Indonesia',
                'pekerjaan' => 'Wiraswasta',
                'alamat' => 'Dusun Sidodadi',
                'keperluan' => 'Uji',
                'jenis_usaha' => 'Perdagangan',
                'nama_usaha' => 'Toko Uji',
                'lama_usaha' => '5 Tahun',
                'alamat_usaha' => 'Dusun Sidodadi',
            ], $output);

            $this->assertFileExists($output);
        } finally {
            @unlink($output);
        }
    }

    private function templateVariables(string $path): array
    {
        $zip = new \ZipArchive();
        $this->assertSame(true, $zip->open($path));
        $variables = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (! preg_match('#^word/(document|header\d+|footer\d+)\.xml$#', $name)) {
                continue;
            }

            $xml = $zip->getFromIndex($index);
            if ($xml === false) {
                continue;
            }

            $document = new \DOMDocument();
            $document->loadXML($xml);
            $xpath = new \DOMXPath($document);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

            foreach ($xpath->query('//w:p[.//w:t]') as $paragraph) {
                $text = implode('', array_map(
                    static fn (\DOMElement $node): string => $node->textContent,
                    iterator_to_array($xpath->query('.//w:t', $paragraph)),
                ));
                preg_match_all('/\{\{\s*([A-Za-z0-9_-]+)\s*\}\}/', $text, $matches);
                $variables = array_merge($variables, $matches[1] ?? []);
            }
        }

        $zip->close();

        return array_values(array_unique($variables));
    }
}
