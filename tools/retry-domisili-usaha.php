<?php

declare(strict_types=1);

use App\Documents\DocumentGenerator;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$values = [
    'nomor_surat' => '470/998/SIDODADI/2026',
    'tanggal_surat' => '27 Juli 2026',
    'nama' => 'Siti Aminah Maharani Putri',
    'nik' => '3507010101010001',
    'tempat_lahir' => 'Malang',
    'tanggal_lahir' => '1 Januari 1990',
    'ttl' => 'Malang, 1 Januari 1990',
    'jenis_kelamin' => 'Perempuan',
    'agama' => 'Islam',
    'status_perkawinan' => 'Belum Kawin',
    'kewarganegaraan' => 'Indonesia',
    'pekerjaan' => 'Wiraswasta dan pengelola usaha keluarga',
    'alamat' => 'Dusun Sumber Makmur RT 014 RW 008, Desa Sidodadi, Kecamatan Lawang, Kabupaten Malang, Provinsi Jawa Timur, tepatnya di sebelah balai desa dan dekat jalan utama menuju pasar kecamatan',
    'keperluan' => 'Digunakan untuk melengkapi persyaratan administrasi pengajuan bantuan usaha dan keperluan resmi lainnya pada instansi terkait',
    'jenis_usaha' => 'Perdagangan makanan siap saji dan kebutuhan rumah tangga',
    'nama_usaha' => 'Warung Sembako dan Makanan Siti Aminah Sejahtera',
    'lama_usaha' => 'Delapan tahun tiga bulan',
    'alamat_usaha' => 'Dusun Sumber Makmur RT 014 RW 008, Desa Sidodadi, Kecamatan Lawang, Kabupaten Malang, Provinsi Jawa Timur, di ruko nomor 12 dekat pasar desa',
    'nama_anak' => 'Ahmad Fajar Ramadhan Pratama',
    'nik_anak' => '3507010101010002',
    'nama_sekolah' => 'SMA Negeri 1 Lawang Kabupaten Malang',
    'anak_nama' => 'Ahmad Fajar Ramadhan Pratama',
    'anak_nik' => '3507010101010002',
];

$generator = $app->make(DocumentGenerator::class);
$directory = dirname(__DIR__).DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'templates';

foreach (['domisili', 'usaha'] as $type) {
    $output = $directory.DIRECTORY_SEPARATOR.'HASIL-RETRY-'.$type.'.docx';
    $generator->generate($type, $values, $output);
    echo "Generated {$output}".PHP_EOL;
}
