@echo off
setlocal enabledelayedexpansion

REM Build a simple ZIP package for distribution.
cd /d "%~dp0\.."

set RELEASE_ROOT=%~dp0release
set ZIP_FILE=%~dp0Sidodadi-Desktop-App.zip

if not exist "%RELEASE_ROOT%" (
    echo Release folder not found. Run desktop\package.bat first.
    pause
    exit /b 1
)

if exist "%ZIP_FILE%" del /q "%ZIP_FILE%"

echo Creating ZIP package...
powershell -NoProfile -Command "Add-Type -AssemblyName System.IO.Compression.FileSystem; [System.IO.Compression.ZipFile]::CreateFromDirectory('%RELEASE_ROOT%', '%ZIP_FILE%')"

echo ZIP package created: %ZIP_FILE%
pause
