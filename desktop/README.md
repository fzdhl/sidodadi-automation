# Desktop Packaging for Sidodadi Document Generator

This directory contains guidance and helper scripts for converting the Laravel web app into an offline Windows desktop application.

## What this repo already supports

- Laravel app with `sqlite` support
- Local assets built by Vite
- No online-only dependencies in the application core

## Recommended desktop approach

### Option 1: PHP Desktop (recommended)

1. Download a Windows PHP Desktop runtime bundle such as `phpdesktop-chrome`.
2. Place the runtime files next to this repository contents or copy this repository into the runtime `www` folder.
3. Build the web assets:
   - `npm install`
   - `npm run build`
4. Install PHP dependencies:
   - `composer install --no-dev`
5. Ensure the SQLite database exists at `database/database.sqlite`.
6. Configure the runtime to start the app at `public/index.php`.
7. Package the runtime folder into an installer using Inno Setup, NSIS, or a zip archive.

### Option 2: Portable local server (proof of concept)

This repository can also run offline using the built-in PHP server and SQLite if you ship a portable PHP binary.

## Important notes for offline usage

- Set `DB_CONNECTION=sqlite` in `.env`.
- Ensure `APP_URL` is a local address such as `http://127.0.0.1:8000`.
- Keep `database/database.sqlite` bundled or create it during installer setup.
- Use `npm run build` before packaging so the app does not require `npm` or Vite at runtime.

## Packaging checklist

- `vendor/` installed (production dependencies only)
- `public/build/` assets generated
- `database/database.sqlite` present and migrated
- `.env` configured for local environment
- Runtime launcher or installer created

## Next step

If you want, I can continue by adding a desktop packaging scaffold for PHP Desktop or by creating an installer script and a portable Windows launcher.
