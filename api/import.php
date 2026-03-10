<?php
/**
 * API - Excel İçe Aktarma (Import)
 * Kullanıcının tarayıcıdan (xlsx.bundle.js) JSON'a çevirdiği
 * excel verilerini alır ve veritabanına işler.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

requireLogin();
header('Content-Type: application/json; charset=utf-8');

// CSRF check
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        jsonResponse(false, 'Güvenlik doğrulaması başarısız (CSRF).');
    }
}

// Sadece Adminler ve Program Yöneticileri toplu yükleme yapabilsin
requireRole(ROLE_ADMIN, ROLE_USER);

$type = sanitize($_POST['type'] ?? '');
$dataRaw = $_POST['data'] ?? '';

if (empty($type) || empty($dataRaw)) {
    jsonResponse(false, 'Geçersiz parametreler (Veri boş).');
}

$rows = json_decode($dataRaw, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($rows)) {
    jsonResponse(false, 'JSON veri ayrıştırma hatası.');
}

if (count($rows) === 0) {
    jsonResponse(false, 'Excel dosyasında eklenecek veri bulunamadı.');
}

$addedCount = 0;
$errorCount = 0;
$errorList = [];

// ============================================
// ÜRÜNLER İÇE AKTARMA (Products)
// ============================================
if ($type === 'products') {
    $table = 'tbl_dp_products';

    foreach ($rows as $index => $row) {
        $name = sanitize($row['Ürün Adı'] ?? '');
        $code = sanitize($row['Ürün Kodu'] ?? '');
        $unit = sanitize($row['Birim (Adet vb.)'] ?? 'Adet');
        $desc = sanitize($row['Açıklama'] ?? '');
        $alarm = (int) ($row['Alarm Seviyesi'] ?? 0);

        // Zorunlu alan kontrolü
        if (!$name) {
            $errorCount++;
            $errorList[] = "Satır " . ($index + 2) . ": Ürün adı boş olamaz.";
            continue;
        }

        // Ekle
        try {
            Database::insert(
                "INSERT INTO `$table` (name, code, unit, description, stock_alarm, is_active) VALUES (?,?,?,?,?,?)",
                [$name, $code, $unit, $desc, $alarm, 1]
            );
            $addedCount++;
        } catch (Exception $e) {
            $errorCount++;
            $errorList[] = "Satır " . ($index + 2) . ": Veritabanı hatası.";
        }
    }
}

// ============================================
// MÜŞTERİLER İÇE AKTARMA (Customers)
// ============================================
elseif ($type === 'customers') {
    $table = 'tbl_dp_customers';

    foreach ($rows as $index => $row) {
        $name = sanitize($row['Ad / Ünvan'] ?? '');
        $contact = sanitize($row['Yetkili Kişi'] ?? '');
        $email = sanitize($row['E-posta'] ?? '');
        $phone = sanitize($row['Telefon'] ?? '');
        $address = sanitize($row['Adres'] ?? '');

        if (!$name) {
            $errorCount++;
            $errorList[] = "Satır " . ($index + 2) . ": Müşteri adı boş olamaz.";
            continue;
        }

        // Email kontrolü (Benzersiz olmalı)
        if ($email) {
            $exists = Database::fetchOne("SELECT id FROM `$table` WHERE email = ? AND hidden = 0", [$email]);
            if ($exists) {
                $errorCount++;
                $errorList[] = "Satır " . ($index + 2) . ": '$email' adresi zaten kullanımda.";
                continue;
            }
        }

        try {
            Database::insert(
                "INSERT INTO `$table` (name, contact, email, phone, address, is_active) VALUES (?,?,?,?,?,?)",
                [$name, $contact, $email, $phone, $address, 1]
            );
            $addedCount++;
        } catch (Exception $e) {
            $errorCount++;
            $errorList[] = "Satır " . ($index + 2) . ": Veritabanı hatası.";
        }
    }
}

// ============================================
// TEDARİKÇİLER İÇE AKTARMA (Suppliers)
// ============================================
elseif ($type === 'suppliers') {
    $table = 'tbl_dp_suppliers';

    foreach ($rows as $index => $row) {
        $name = sanitize($row['Ad / Ünvan'] ?? '');
        $contact = sanitize($row['Yetkili Kişi'] ?? '');
        $email = sanitize($row['E-posta'] ?? '');
        $phone = sanitize($row['Telefon'] ?? '');
        $address = sanitize($row['Adres'] ?? '');

        if (!$name) {
            $errorCount++;
            $errorList[] = "Satır " . ($index + 2) . ": Tedarikçi adı boş olamaz.";
            continue;
        }

        try {
            Database::insert(
                "INSERT INTO `$table` (name, contact, email, phone, address, is_active) VALUES (?,?,?,?,?,?)",
                [$name, $contact, $email, $phone, $address, 1]
            );
            $addedCount++;
        } catch (Exception $e) {
            $errorCount++;
            $errorList[] = "Satır " . ($index + 2) . ": Veritabanı hatası.";
        }
    }
} else {
    jsonResponse(false, 'Geliştirilmeyen veya hatalı bir içe aktarım türü seçildi.');
}

// Yanıt Dönüşü
if ($addedCount > 0 && $errorCount === 0) {
    jsonResponse(true, "Mükemmel! Toplam $addedCount kayıt başarılı bir şekilde içeri aktarıldı.");
} elseif ($addedCount > 0 && $errorCount > 0) {
    jsonResponse(true, "$addedCount kayıt eklendi. Ancak $errorCount satırda hata çıktı.", ['errors' => $errorList]);
} else {
    jsonResponse(false, "Hiçbir kayıt eklenemedi. Bütün satırlarda hata var ($errorCount hata).", ['errors' => $errorList]);
}
