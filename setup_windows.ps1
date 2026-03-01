# DEPPO - PowerShell Kurulum Yardımcısı
$ErrorActionPreference = "Stop"

function Write-Header($text) {
    Write-Host "`n==========================================" -ForegroundColor Cyan
    Write-Host "      $text" -ForegroundColor Cyan
    Write-Host "==========================================`n" -ForegroundColor Cyan
}

Write-Header "DEPPO SISTEM HAZIRLIK ARACI (PowerShell)"

# 1. Yönetici Kontrolü
$currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Host "[!] Lütfen bu scripti YÖNETİCİ olarak çalıştırın." -ForegroundColor Red
    Write-Host "Sağ tıklayıp 'PowerShell ile çalıştır' diyebilirsiniz."
    Read-Host "`nDevam etmek için Enter tuşuna basın..."
    exit
}

# 2. Git Kontrolü ve Kurulumu
try {
    git --version | Out-Null
    Write-Host "[+] Git zaten yüklü." -ForegroundColor Green
} catch {
    Write-Host "[!] Git bulunamadı. Kurulum başlatılıyor..." -ForegroundColor Yellow
    
    # Winget denemesi
    if (Get-Command winget -ErrorAction SilentlyContinue) {
        Write-Host "[+] Winget bulundu. Git kuruluyor (lütfen bekleyin)..." -ForegroundColor Cyan
        winget install --id Git.Git -e --source winget --accept-package-agreements --accept-source-agreements
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "[+] Git başarıyla kuruldu!" -ForegroundColor Green
            # Path'i güncelle (mevcut oturum için)
            $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
        }
    } else {
        # Web'den indirip kurma (Fallback)
        Write-Host "[!] Winget bulunamadı. Manuel indirme başlatılıyor..." -ForegroundColor Yellow
        $url = "https://github.com/git-for-windows/git/releases/download/v2.44.0.windows.1/Git-2.44.0-64-bit.exe"
        $outPath = "$env:TEMP\git-setup.exe"
        Write-Host "[+] Git indiriliyor..."
        Invoke-WebRequest -Uri $url -OutFile $outPath
        Write-Host "[+] Kurulum başlatılıyor... Lütfen pencereleri takip edin."
        Start-Process -FilePath $outPath -ArgumentList "/SILENT" -Wait
        Write-Host "[+] Kurulum tamamlandı." -ForegroundColor Green
    }
}
# 2.5 Git Senkronizasyon ve Çakışma Giderici
Write-Host "`n[+] Git Senkronizasyonu kontrol ediliyor..." -ForegroundColor Cyan
try {
    # Önce fetch yapmaya çalış
    git fetch --all
    
    # Durumu kontrol et
    $status = git status --porcelain
    if ($status) {
        Write-Host "[!] Yerel dizinde degisiklikler veya cakismalar algilandi." -ForegroundColor Yellow
        Write-Host "Bu durum genellikle dosyaların manuel kopyalanmasından kaynaklanır."
        $choice = Read-Host "Yerel dosyalari SİLİP GitHub ile tam esitlensin mi? (E/H)"
        if ($choice -eq "E" -or $choice -eq "e") {
            Write-Host "[+] Yerel dosyalar temizleniyor ve GitHub ile esitleniyor..." -ForegroundColor Cyan
            git reset --hard origin/main
            git clean -fd
            Write-Host "[+] Senkronizasyon basarili!" -ForegroundColor Green
        } else {
            Write-Host "[!] Senkronizasyon atlandi. Guncelleme sirasinda hata alabilirsiniz." -ForegroundColor Yellow
        }
    } else {
        Write-Host "[+] Git dizini temiz ve güncel." -ForegroundColor Green
    }
} catch {
    Write-Host "[!] Git senkronizasyonu sirasinda bir hata olustu. (Git deposu henüz kurulmamis olabilir)" -ForegroundColor Gray
}

# 3. Klasör İzinleri (XAMPP htdocs için güvenli yaklaşım)
Write-Host "`n[+] Klasör yapısı kontrol ediliyor..." -ForegroundColor Cyan
$configPath = Join-Path $PSScriptRoot "config"
if (Test-Path $configPath) {
    # Gerekiyorsa izin işlemleri burada yapılabilir (Windows'ta genelde gerekmez)
    Write-Host "[+] Klasörler hazır." -ForegroundColor Green
}

Write-Header "HAZIRLIK TAMAMLANDI!"
Write-Host "1. Path değişikliklerinin etkili olması için bu pencereyi KAPATIN." -ForegroundColor Yellow
Write-Host "2. XAMPP Control Panel'i kapatıp tekrar açın." -ForegroundColor Yellow
Write-Host "3. Tarayıcıdan http://localhost/deppo/setup/install.php adresine gidin." -ForegroundColor Green

Read-Host "`nÇıkmak için Enter tuşuna basın..."
