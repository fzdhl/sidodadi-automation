$src = "C:\Users\fakhr\OneDrive\Documents\sidodadi-automation"
$dst = Join-Path $src "desktop\release\phpdesktop-chrome-130.1-php-8.3\www"

if (Test-Path $dst) {
    Remove-Item -LiteralPath $dst -Recurse -Force -ErrorAction Stop
}

New-Item -ItemType Directory -Path $dst | Out-Null

$items = @(
    "app",
    "bootstrap",
    "config",
    "database",
    "public",
    "resources",
    "routes",
    "storage",
    "vendor"
)

foreach ($item in $items) {
    $sourcePath = Join-Path $src $item
    $destPath = Join-Path $dst $item
    Copy-Item -Path $sourcePath -Destination $destPath -Recurse -Force -ErrorAction Stop
}

$files = @("artisan", "composer.json", "composer.lock", ".env", ".env.example")
foreach ($file in $files) {
    $sourcePath = Join-Path $src $file
    if (Test-Path $sourcePath) {
        Copy-Item -Path $sourcePath -Destination $dst -Force -ErrorAction Stop
    }
}

Write-Output "Copy complete."