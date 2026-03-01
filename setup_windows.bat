@echo off
setlocal
title DEPPO - Sistem Hazirlik Araci

echo ==========================================
echo       DEPPO SISTEM HAZIRLIK ARACI
echo ==========================================
echo.

:: Git Kontrolu
git --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [!] Git bulunamadi. Otomatik kurulum denetlenecek...
    
    :: Winget Kontrolu (Windows 10/11)
    winget --version >nul 2>&1
    if %errorlevel% equ 0 (
        echo [+] Winget bulundu. Git kuruluyor...
        echo Lutfen bekleyin, bu islem biraz zaman alabilir...
        winget install --id Git.Git -e --source winget
        
        if %errorlevel% equ 0 (
            echo.
            echo [SUCCESS] Git basariyla kuruldu!
            echo [IMPORTANT] Path ayarlarinin gecerli olmasi icin BU PENCEREYI KAPATIN.
            echo Ardindan XAMPP'i yeniden baslatin ve kuruluma devam edin.
            pause
            exit
        ) else (
            echo [HATA] Git kurulumu basarisiz oldu. Lutfen manuel kurun: https://git-scm.com/
        )
    ) else (
        echo [!] Winget bulunamadi. Lutfen Git'i manuel kurun: https://git-scm.com/
    )
) else (
    echo [+] Git zaten yuklu.
    git --version
)

echo.
echo [+] Klasör izinleri kontrol ediliyor...
echo [NOT] Windows'ta XAMPP genelde yazma izniyle calisir.

echo.
echo ==========================================
echo HAZIRLIK TAMAMLANDI!
echo Tarayicidan http://localhost/deppo/setup/install.php adresine gidin.
echo ==========================================
pause
