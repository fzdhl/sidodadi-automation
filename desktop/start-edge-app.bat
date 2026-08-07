@echo off
title Sidodadi Document Generator
setlocal enabledelayedexpansion

REM Set working directory to repo root or release root
cd /d "%~dp0"
if exist "www\public" (
    set WWW_PATH=%~dp0www
) else (
    cd /d "%~dp0\.."
    set WWW_PATH=%CD%
)

set PORT=8000
set HOST=127.0.0.1
set APP_URL=http://%HOST%:%PORT%

REM Detect PHP executable and ini configuration
if exist "%~dp0php\php.exe" (
    set PHP_BIN="%~dp0php\php.exe"
    if exist "%~dp0php\php.ini" (
        set PHP_ARGS=-c "%~dp0php\php.ini"
    ) else (
        set PHP_ARGS=
    )
) else (
    set PHP_BIN=php
    set PHP_ARGS=
)

REM Start PHP Built-in Web Server silently
start "SidodadiPHPServer" /B %PHP_BIN% %PHP_ARGS% -S %HOST%:%PORT% -t "%WWW_PATH%\public" >nul 2>&1

REM Wait 1 second for PHP server to initialize
timeout /t 1 /nobreak >nul

REM Detect Microsoft Edge or Chrome browser path for App Mode
set BROWSER=
if exist "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" (
    set BROWSER="C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
) else if exist "C:\Program Files\Microsoft\Edge\Application\msedge.exe" (
    set BROWSER="C:\Program Files\Microsoft\Edge\Application\msedge.exe"
) else if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" (
    set BROWSER="C:\Program Files\Google\Chrome\Application\chrome.exe"
) else if exist "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" (
    set BROWSER="C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
)

if defined BROWSER (
    %BROWSER% --app=%APP_URL% --user-data-dir="%TEMP%\SidodadiEdgeProfile" --window-size=1280,800
) else (
    start %APP_URL%
)

REM Cleanup: Stop PHP server when window is closed
powershell -Command "Stop-Process -Name php -ErrorAction SilentlyContinue" >nul 2>&1
