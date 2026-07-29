<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$invalidTemplate = resource_path('templates/INVALID-TEMPLATE.docx');
file_put_contents($invalidTemplate, 'not-a-docx');

config(['documents.types.usaha.template' => basename($invalidTemplate)]);

$request = Illuminate\Http\Request::create(
    '/documents',
    'POST',
    [
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
    ],
    [],
    [],
    ['HTTP_ACCEPT' => 'application/json']
);

$response = $app->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";

unlink($invalidTemplate);
