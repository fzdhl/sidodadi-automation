@echo off
setlocal enabledelayedexpansion

REM Build an ultra-lightweight release package using MS Edge App Mode (~25-30MB).
cd /d "%~dp0\.."

echo ========================================================
echo   Packaging Sidodadi Document Generator (Ultra-Light)   
echo ========================================================

if not exist vendor\autoload.php (
    echo [ERROR] Composer dependencies not found. Run "composer install --no-dev" first.
    pause
    exit /b 1
)

if not exist public\build\manifest.json (
    echo [INFO] Frontend assets not found. Building assets...
    call npm install
    call npm run build
    if not exist public\build\manifest.json (
        echo [ERROR] Failed to build frontend assets.
        pause
        exit /b 1
    )
)

set RELEASE_ROOT=%~dp0release
set WWW_ROOT=%RELEASE_ROOT%\www

if exist "%RELEASE_ROOT%" rd /s /q "%RELEASE_ROOT%"
mkdir "%WWW_ROOT%"

echo [1/4] Optimizing PHP dependencies (removing dev packages)...
call composer install --no-dev --optimize-autoloader >nul 2>&1

echo [2/4] Copying application code and assets...
robocopy "%CD%\app" "%WWW_ROOT%\app" /e /njh /njs /ndl /nc /ns >nul
robocopy "%CD%\bootstrap" "%WWW_ROOT%\bootstrap" /e /njh /njs /ndl /nc /ns >nul
robocopy "%CD%\config" "%WWW_ROOT%\config" /e /njh /njs /ndl /nc /ns >nul
robocopy "%CD%\database" "%WWW_ROOT%\database" /e /njh /njs /ndl /nc /ns >nul
robocopy "%CD%\public" "%WWW_ROOT%\public" /e /njh /njs /ndl /nc /ns >nul
robocopy "%CD%\resources" "%WWW_ROOT%\resources" /e /njh /njs /ndl /nc /ns >nul
robocopy "%CD%\routes" "%WWW_ROOT%\routes" /e /njh /njs /ndl /nc /ns >nul
robocopy "%CD%\storage" "%WWW_ROOT%\storage" /e /njh /njs /ndl /nc /ns >nul
robocopy "%CD%\vendor" "%WWW_ROOT%\vendor" /e /njh /njs /ndl /nc /ns >nul

copy /y artisan "%WWW_ROOT%\" >nul
copy /y composer.json "%WWW_ROOT%\" >nul
copy /y composer.lock "%WWW_ROOT%\" >nul
if exist .env copy /y .env "%WWW_ROOT%\" >nul
if exist .env.example copy /y .env.example "%WWW_ROOT%\" >nul

echo [3/4] Trimming unnecessary test, doc, hidden .git, and sample files from vendor...
powershell -Command "Get-ChildItem '%WWW_ROOT%\vendor' -Recurse -Hidden -Directory -Filter '.git' -ErrorAction SilentlyContinue | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue" >nul 2>&1
powershell -Command "Get-ChildItem '%WWW_ROOT%\vendor' -Recurse -Directory -ErrorAction SilentlyContinue | Where-Object { $_.Name -match '^(samples|docs|tests|Test|Tests|documentation|\.github)$' } | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue" >nul 2>&1
powershell -Command "Get-ChildItem '%WWW_ROOT%\vendor' -Recurse -File -ErrorAction SilentlyContinue | Where-Object { $_.Name -match '\.(md|markdown|txt|yml|yaml|gitignore|gitattributes)$|^CHANGELOG|^LICENSE|^phpunit' -and $_.Name -ne 'autoload.php' } | Remove-Item -Force -ErrorAction SilentlyContinue" >nul 2>&1
powershell -Command "Remove-Item -Path '%WWW_ROOT%\storage\logs\*.log' -Force -ErrorAction SilentlyContinue" >nul 2>&1

echo [4/4] Copying Edge App Mode Launchers and optimizing Portable PHP runtime...
copy /y "%~dp0start-edge-app.bat" "%RELEASE_ROOT%\start-edge-app.bat" >nul
copy /y "%~dp0Start-App.vbs" "%RELEASE_ROOT%\Start-App.vbs" >nul

if exist "%RELEASE_ROOT%\php" (
    copy /y "%~dp0php.ini" "%RELEASE_ROOT%\php\php.ini" >nul
    powershell -Command "Remove-Item -Path '%RELEASE_ROOT%\php\icu*.dll' -Force -ErrorAction SilentlyContinue" >nul 2>&1
    powershell -Command "Remove-Item -Path '%RELEASE_ROOT%\php\dev', '%RELEASE_ROOT%\php\extras' -Recurse -Force -ErrorAction SilentlyContinue" >nul 2>&1
    powershell -Command "Remove-Item -Path '%RELEASE_ROOT%\php\phpdbg.exe', '%RELEASE_ROOT%\php\deplister.exe', '%RELEASE_ROOT%\php\news.txt', '%RELEASE_ROOT%\php\readme-redist-bins.txt' -Force -ErrorAction SilentlyContinue" >nul 2>&1
)

echo Package preparation complete!
echo ========================================================
echo  Location: %RELEASE_ROOT%
echo ========================================================
echo.
echo NEXT STEPS FOR PORTABLE RUNTIME:
echo 1. Download minimal Portable PHP 8.2 or 8.3 NTS for Windows (~20MB zip)
echo    from https://windows.php.net/download/
echo 2. Extract PHP into "%RELEASE_ROOT%\php"
echo 3. Users can launch the app by double-clicking "Start-App.vbs"
echo.
pause
