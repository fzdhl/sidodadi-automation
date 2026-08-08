# PowerShell Script to compile desktop/release into a native Single-File EXE (Sidodadi-Generator.exe)
# using built-in Windows C# compiler (csc.exe).

$ErrorActionPreference = "Stop"

$repoRoot = Resolve-Path "$PSScriptRoot\.."
$releaseDir = Join-Path $repoRoot "desktop\release"
$outputExe = Join-Path $repoRoot "desktop\Sidodadi-Generator.exe"
$payloadZip = Join-Path $repoRoot "desktop\payload.zip"
$csFile = Join-Path $repoRoot "desktop\Launcher.cs"

Write-Host "========================================================" -ForegroundColor Cyan
Write-Host "  Building Single-File Portable EXE: Sidodadi-Generator.exe" -ForegroundColor Cyan
Write-Host "========================================================" -ForegroundColor Cyan

# Step 1: Ensure desktop/release exists
if (-not (Test-Path $releaseDir)) {
    Write-Host "[1/3] desktop\release directory not found. Packaging release first..." -ForegroundColor Yellow
    cmd /c "$repoRoot\desktop\package-edge-app.bat < NUL"
}

if (-not (Test-Path "$releaseDir\php\php.exe")) {
    Write-Host "[ERROR] Portable PHP runtime not found in desktop\release\php!" -ForegroundColor Red
    Write-Host "Please extract Portable PHP into desktop\release\php first." -ForegroundColor Red
    exit 1
}

# Step 2: Zip desktop/release folder into payload.zip using tar.exe
Write-Host "[2/3] Compressing application payload into payload.zip with tar.exe..." -ForegroundColor Green
if (Test-Path $payloadZip) { Remove-Item $payloadZip -Force }

tar -caf "$payloadZip" -C "$releaseDir" .

# Verify zip integrity before compiling
Add-Type -AssemblyName System.IO.Compression.FileSystem
$verifier = [System.IO.Compression.ZipFile]::OpenRead($payloadZip)
$entryCount = $verifier.Entries.Count
$verifier.Dispose()

$zipSize = (Get-Item $payloadZip).Length / 1MB
Write-Host "      Payload compressed size: $([math]::Round($zipSize, 2)) MB ($entryCount entries verified)" -ForegroundColor Gray

# Step 3: Compile C# launcher into single-file EXE using csc.exe
Write-Host "[3/3] Compiling native executable with C# Compiler (csc.exe)..." -ForegroundColor Green

$cscPath = "C:\Windows\Microsoft.NET\Framework64\v4.0.30319\csc.exe"
if (-not (Test-Path $cscPath)) {
    $cscPath = "C:\Windows\Microsoft.NET\Framework\v4.0.30319\csc.exe"
}

$icoFile = Join-Path $repoRoot "desktop\app.ico"
$iconFlag = if (Test-Path $icoFile) { "/win32icon:`"$icoFile`"" } else { "" }
$compileCmd = "& `"$cscPath`" /target:winexe /out:`"$outputExe`" $iconFlag /resource:`"$payloadZip`",payload.zip /r:System.dll /r:System.Drawing.dll /r:System.Windows.Forms.dll /r:System.IO.Compression.dll /r:System.IO.Compression.FileSystem.dll /r:System.Management.dll /optimize+ `"$csFile`""
Invoke-Expression $compileCmd

# Cleanup temp payload zip
Remove-Item $payloadZip -Force -ErrorAction SilentlyContinue

if (Test-Path $outputExe) {
    $finalSize = (Get-Item $outputExe).Length / 1MB
    Write-Host "========================================================" -ForegroundColor Green
    Write-Host " SUCCESS! Native Single-File EXE Created!" -ForegroundColor Green
    Write-Host " Location : $outputExe" -ForegroundColor Green
    Write-Host " File Size: $([math]::Round($finalSize, 2)) MB" -ForegroundColor Green
    Write-Host "========================================================" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Compilation failed to produce output executable." -ForegroundColor Red
}
