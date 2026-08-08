@echo off
setlocal enabledelayedexpansion

REM Build a portable release package for the Sidodadi desktop app.
cd /d "%~dp0\.."

if not exist vendor\autoload.php (
    echo Composer dependencies not found. Run "composer install --no-dev" first.
    pause
    exit /b 1
)

if not exist public\build\manifest.json (
    echo Frontend assets not found. Running build...
    npm install
    npm run build
    if not exist public\build\manifest.json (
        echo Failed to build frontend assets.
        pause
        exit /b 1
    )
)

set RELEASE_ROOT=%~dp0release
set WWW_ROOT=%RELEASE_ROOT%\www

if exist "%RELEASE_ROOT%" rd /s /q "%RELEASE_ROOT%"
mkdir "%WWW_ROOT%"

echo Copying application files...
robocopy "%CD%\app" "%WWW_ROOT%\app" /e >nul
robocopy "%CD%\bootstrap" "%WWW_ROOT%\bootstrap" /e >nul
robocopy "%CD%\config" "%WWW_ROOT%\config" /e >nul
robocopy "%CD%\database" "%WWW_ROOT%\database" /e >nul
robocopy "%CD%\public" "%WWW_ROOT%\public" /e >nul
robocopy "%CD%\resources" "%WWW_ROOT%\resources" /e >nul
robocopy "%CD%\routes" "%WWW_ROOT%\routes" /e >nul
robocopy "%CD%\storage" "%WWW_ROOT%\storage" /e >nul
robocopy "%CD%\vendor" "%WWW_ROOT%\vendor" /e >nul

copy /y artisan "%WWW_ROOT%\" >nul
copy /y composer.json "%WWW_ROOT%\" >nul
copy /y composer.lock "%WWW_ROOT%\" >nul
if exist .env copy /y .env "%WWW_ROOT%\" >nul
if exist .env.example copy /y .env.example "%WWW_ROOT%\" >nul
copy /y desktop\phpdesktop.settings.json "%RELEASE_ROOT%\" >nul

echo Release directory created at "%RELEASE_ROOT%".
echo To complete packaging, download a PHP Desktop runtime and place its executable files into "%RELEASE_ROOT%".
echo The application will then run from "%RELEASE_ROOT%\www".
echo
if exist "%RELEASE_ROOT%\phpdesktop.exe" (
    echo If you have phpdesktop.exe present, you can launch it now.
) else (
    echo phpdesktop.exe not found in the release folder.
    echo Download PHP Desktop runtime from https://github.com/cztomczak/phpdesktop/releases and extract it here.
)

pause
