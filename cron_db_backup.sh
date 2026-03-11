#!/bin/bash

# Deppo Veritabanı Yedekleme Cron (macOS)
# Bu dosya, XAMPP ortamındaki PHP'yi kullanarak yedekleme scriptini çalıştırır.

# Yapılandırma
PROJECT_DIR="/Applications/XAMPP/xamppfiles/htdocs/deppo"
PHP_BIN="/Applications/XAMPP/xamppfiles/bin/php"

# Proje dizinine git
cd "$PROJECT_DIR" || { echo "HATA: Proje dizinine ulaşılamadı."; exit 1; }

# PHP scriptini çalıştır
if [ -f "$PHP_BIN" ]; then
    "$PHP_BIN" cron_db_backup.php
else
    # Eğer XAMPP yolu farklıysa sistem php'sini dene
    /usr/bin/php cron_db_backup.php
fi
