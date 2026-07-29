<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$registry = $app->make(App\Documents\DocumentTemplateRegistry::class);
$generator = $app->make(App\Documents\DocumentGenerator::class);

$data = [
    'nomor_surat' => '470/123/2026',
    'tanggal_surat' => '2026-07-29',
    'nama' => 'Fadhil Alauddin',
    'nik' => '3573041603060004',
    'tempat_lahir' => 'Kuwait',
    'tanggal_lahir' => '2006-03-16',
    'jenis_kelamin' => 'Laki-laki',
    'agama' => 'Islam',
    'status_perkawinan' => 'Belum Kawin',
    'kewarganegaraan' => 'Indonesia',
    'pekerjaan' => 'Mahasiswa',
    'alamat' => 'Perum Puri Kartika Asri Blok FF-3, RT 01, RW 09, Kel. Arjowinangun, Kec. Kedungkandang, Kota Malang',
    'keperluan' => 'Pengajuan surat keterangan usaha',
    'jenis_usaha' => 'Perdagangan',
    'nama_usaha' => 'Toko Fadhil',
    'lama_usaha' => '1 tahun',
    'alamat_usaha' => 'Perum Puri Kartika Asri',
];

$output = __DIR__ . '/debug-output.docx';

try {
    $path = $generator->generate('usaha', $data, $output);
    echo "generated: $path\n";
} catch (Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
