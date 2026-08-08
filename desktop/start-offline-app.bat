@echo off
REM Run the Sidodadi Document Generator locally in portable/offline mode.

cd /d "%~dp0\.."

if not exist vendor\autoload.php (
    echo Composer dependencies not found. Run "composer install --no-dev" first.
    pause
    exit /b 1
)

if not exist database\database.sqlite (
    echo SQLite database not found at database\database.sqlite.
    echo If this is a fresh install, run "php artisan migrate --force" or copy the database file.
    pause
    exit /b 1
)

if not exist public\build\manifest.json (
    echo Frontend assets not found. Run "npm run build" first.
    pause
    exit /b 1
)

start "Sidodadi Document Generator" "http://127.0.0.1:8000"
php artisan serve --host=127.0.0.1 --port=8000
