@echo off
:: =========================================================================
:: Depo Yonetim Sistemi - Otomatik Kur Guncelleme BATCH (.bat) Dosyasi
:: =========================================================================
::
:: AMAC: 
:: Bilgisayar acildiginda ekranda siyah cmd penceresi gostermeden (tamamen gizli)
:: arka planda PHP cron (cron_currency.php) dosyasini calistirmaktir.
:: 
:: KULLANIM (Windows):
:: 1. Bu dosyanin kisayolunu olusturun (Saga tik > Kisayol Olustur).
:: 2. Klavyeden Windows + R tuslarina basin, "shell:startup" yazip Enter'a basin.
:: 3. Acilan "Baslangic" (Startup) klasorune olusturdugunuz kisayolu yapistirin.
:: Artik bilgisayar her acildiginda kurlar otomatik ve gizli bir sekilde guncellenecektir.
::
:: NOT: Eger XAMPP'i C:\xampp yerine baska bir surucuye veya klasore kurduysaniz, 
:: asagidaki yollari kendi kurulumunuza gore duzenlemelisiniz.

set "PHP_BIN=C:\xampp\php\php.exe"
set "SCRIPT_PATH=C:\xampp\htdocs\deppo\cron_currency.php"
set "VBS_FILE=%TEMP%\run_hidden_cron.vbs"

:: Cscript kullanarak komut satirini gizlemek icin gecici birc VBScript olusturuyoruz
echo Set WshShell = CreateObject("WScript.Shell") > "%VBS_FILE%"
echo WshShell.Run """%PHP_BIN%"" -f ""%SCRIPT_PATH%""", 0, False >> "%VBS_FILE%"

:: Olusturulan betigi calistir (Siyah ekrani engeller, islem arka planda biter)
cscript.exe //nologo "%VBS_FILE%"

:: Gecici dosyayi sil
del "%VBS_FILE%"
