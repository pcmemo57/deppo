@echo off
setlocal enabledelayedexpansion
title DEPPO Kurulum Yardimcisi

:: Check for default XAMPP htdocs path
set "HTDOCS_PATH=C:\xampp\htdocs"

if not exist "!HTDOCS_PATH!" (
    echo [HATA] Varsayilan XAMPP htdocs klasoru bulunamadi: !HTDOCS_PATH!
    set /p "USER_PATH=Lutfen XAMPP htdocs klasorunun tam yolunu girin (Ornek: D:\xampp\htdocs): "
    set "HTDOCS_PATH=!USER_PATH!"
)

:: Re-verify the path
if not exist "!HTDOCS_PATH!" (
    echo [HATA] Girilen klasor yolu bulunamadi. Kurulum iptal edildi.
    pause
    exit /b 1
)

:: Create deppo directory if not exists
set "TARGET_DIR=!HTDOCS_PATH!\deppo"
if not exist "!TARGET_DIR!" (
    echo [BILGI] Deppo klasoru olusturuluyor: !TARGET_DIR!
    mkdir "!TARGET_DIR!"
)

:: Copy setup folder and content
echo [BILGI] Kurulum dosyalari kopyalaniyor...
xcopy /E /I /Y "%~dp0setup" "!TARGET_DIR!\setup"

:: Launch installer in browser
echo [BILGI] Kurulum baslatiliyor...
start http://localhost/deppo/setup/install.php

echo.
echo [TAMAMLANDI] Dosyalar kopyalandi ve tarayici acildi.
pause

