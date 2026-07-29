<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$resident = App\Models\Resident::first();
if (! $resident) {
    echo "no resident found\n";
    return;
}
var_export($resident->toArray());
echo "\n";
echo 'tanggal_lahir raw: ' . var_export($resident->tanggal_lahir, true) . "\n";
