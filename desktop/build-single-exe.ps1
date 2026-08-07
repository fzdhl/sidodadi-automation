# PowerShell Script to build a Single-File Portable Executable (Sidodadi-Generator.exe)
# using Windows Built-in IExpress tool.

$ErrorActionPreference = "Stop"

$repoRoot = Resolve-Path "$PSScriptRoot\.."
$releaseDir = Join-Path $repoRoot "desktop\release"
$outputExe = Join-Path $repoRoot "desktop\Sidodadi-Generator.exe"
$sedFile = Join-Path $repoRoot "desktop\iexpress.sed"

Write-Host "========================================================" -ForegroundColor Cyan
Write-Host "  Building Single-File Executable: Sidodadi-Generator.exe" -ForegroundColor Cyan
Write-Host "========================================================" -ForegroundColor Cyan

if (-not (Test-Path $releaseDir)) {
    Write-Host "[INFO] desktop\release directory not found. Running package-edge-app.bat first..." -ForegroundColor Yellow
    cmd /c "$repoRoot\desktop\package-edge-app.bat < NUL"
}

if (-not (Test-Path "$releaseDir\php\php.exe")) {
    Write-Host "[ERROR] Portable PHP runtime not found in desktop\release\php!" -ForegroundColor Red
    Write-Host "Please extract Portable PHP into desktop\release\php first." -ForegroundColor Red
    exit 1
}

Write-Host "[1/2] Scanning release files..." -ForegroundColor Green

# Collect all files recursively in releaseDir
$files = Get-ChildItem -Path $releaseDir -Recurse -File

# Group files by their parent directory
$groups = $files | Group-Object DirectoryName

# Generate SED configuration
$sedContent = @()
$sedContent += "[Version]"
$sedContent += "Class=IExpress"
$sedContent += "SEDVersion=3.0"
$sedContent += "[Options]"
$sedContent += "PackagePurpose=InstallApp"
$sedContent += "ShowInstallProgramWindow=0"
$sedContent += "HideExtractAnimation=1"
$sedContent += "UseLongFileName=1"
$sedContent += "InsideCompressed=1"
$sedContent += "CAB_FixedSize=0"
$sedContent += "CAB_ResvCodeSigning=0"
$sedContent += "RebootMode=N"
$sedContent += "InstallPrompt=%InstallPrompt%"
$sedContent += "DisplayLicense=%DisplayLicense%"
$sedContent += "FinishMessage=%FinishMessage%"
$sedContent += "TargetName=%TargetName%"
$sedContent += "FriendlyName=%FriendlyName%"
$sedContent += "AppLaunched=%AppLaunched%"
$sedContent += "PostInstallCmd=%PostInstallCmd%"
$sedContent += "AdminQuietInstCmd=%AdminQuietInstCmd%"
$sedContent += "UserQuietInstCmd=%UserQuietInstCmd%"
$sedContent += "SourceFiles=SourceFiles"
$sedContent += "[Strings]"
$sedContent += "InstallPrompt="
$sedContent += "DisplayLicense="
$sedContent += "FinishMessage="
$sedContent += "TargetName=$outputExe"
$sedContent += "FriendlyName=Sidodadi Document Generator"
$sedContent += "AppLaunched=wscript.exe //nologo Start-App.vbs"
$sedContent += "PostInstallCmd=<None>"
$sedContent += "AdminQuietInstCmd="
$sedContent += "UserQuietInstCmd="
$sedContent += "[SourceFiles]"

for ($i = 0; $i -lt $groups.Count; $i++) {
    $dirPath = $groups[$i].Name
    if (-not $dirPath.EndsWith("\")) { $dirPath += "\" }
    $sedContent += "SourceFiles$i=$dirPath"
}

for ($i = 0; $i -lt $groups.Count; $i++) {
    $sedContent += "[SourceFiles$i]"
    foreach ($file in $groups[$i].Group) {
        $sedContent += "$($file.Name)="
    }
}

Set-Content -Path $sedFile -Value ($sedContent -join "`r`n") -Encoding ASCII

Write-Host "[2/2] Compiling single-file EXE using IExpress..." -ForegroundColor Green
$iexpressPath = "$env:SystemRoot\System32\iexpress.exe"
$process = Start-Process -FilePath $iexpressPath -ArgumentList "/N `"$sedFile`"" -Wait -NoNewWindow -PassThru

if ($process.ExitCode -eq 0 -and (Test-Path $outputExe)) {
    $exeSize = (Get-Item $outputExe).Length / 1MB
    Write-Host "========================================================" -ForegroundColor Green
    Write-Host " SUCCESS! Executable created successfully!" -ForegroundColor Green
    Write-Host " File Location: $outputExe" -ForegroundColor Green
    Write-Host " File Size    : $([math]::Round($exeSize, 2)) MB" -ForegroundColor Green
    Write-Host "========================================================" -ForegroundColor Green
} else {
    Write-Host "[ERROR] IExpress failed to build executable." -ForegroundColor Red
}

# Clean up temp SED file
Remove-Item $sedFile -Force -ErrorAction SilentlyContinue
