<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$generator = app()->make(App\Documents\DocumentGenerator::class);
$html = $generator->renderPreviewHtml('keterangan-biasa', [
    'nomor_surat' => '123',
    'tanggal_surat' => '2026-08-05',
    'nama' => 'AGUS RUDI ISWANTO',
    'nik' => '1234567890123456',
    'tempat_lahir' => 'Malang',
    'tanggal_lahir' => '1990-01-01',
    'jenis_kelamin' => 'Laki-laki',
    'agama' => 'Islam',
    'status_perkawinan' => 'Kawin',
    'kewarganegaraan' => 'Indonesia',
    'pekerjaan' => 'Kepala Desa',
    'alamat' => 'Jalan Mawar',
    'keperluan' => 'Membuat surat keterangan.',
]);
echo $html;
