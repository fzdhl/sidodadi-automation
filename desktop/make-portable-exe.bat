@echo off
title Build Portable Single-File EXE - Sidodadi Generator
cd /d "%~dp0"

echo [1/2] Updating release bundle with latest website code...
call "%~dp0package-edge-app.bat"

echo [2/2] Compiling into single-file executable...
powershell -ExecutionPolicy Bypass -File "%~dp0build-single-exe.ps1"

pause
