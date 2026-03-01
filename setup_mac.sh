#!/bin/bash

echo "=========================================="
echo "      DEPPO SISTEM HAZIRLIK ARACI (MAC)"
echo "=========================================="
echo

# Git Kontrolu
if ! command -v git &> /dev/null
then
    echo "[!] Git bulunamadi."
    echo "Xcode Command Line Tools kuruluyor..."
    xcode-select --install
    echo "Kurulum tamamlandiktan sonra bu scripti tekrar calistirin."
    exit
else
    echo "[+] Git zaten yuklu."
    git --version
fi

echo
echo "[+] Klasör izinleri düzenleniyor (sudo gerekebilir)..."
sudo chmod -R 777 config/ setup/
echo "[+] Izinler güncellendi."

echo
echo "=========================================="
echo "HAZIRLIK TAMAMLANDI!"
echo "Tarayicidan http://localhost/deppo/setup/install.php adresine gidin."
echo "=========================================="
