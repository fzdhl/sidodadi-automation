@echo off
title Build Portable Single-File EXE - Sidodadi Generator
cd /d "%~dp0"

powershell -ExecutionPolicy Bypass -File "%~dp0build-single-exe.ps1"

pause
