<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$resident = App\Models\Resident::first();
if (! $resident) {
    echo "no resident\n";
    return;
}
$response = response()->json(['data' => $resident]);
echo $response->getContent();
