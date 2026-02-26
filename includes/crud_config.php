<?php
/**
 * Şablon: Standart CRUD Sayfası (Requesters, Warehouses, Customers, Suppliers)
 * Her sayfa için ortak liste + modal yapısı
 */

// ─── SAYFA KONFİGÜRASYONLARI ──────────────────────────────────────────────
$pages_config = [
    'requesters' => [
        'title' => 'Talep Eden Yönetimi',
        'api' => 'requesters',
        'add_label' => 'Talep Eden Ekle',
        'cols' => ['#', 'Ad', 'Soyad', 'E-posta', 'Görev', 'Durum', 'İşlem'],
        'role' => [ROLE_ADMIN, ROLE_USER],
    ],
    'warehouses' => [
        'title' => 'Depo Yönetimi (Lokasyonlar)',
        'api' => 'warehouses',
        'add_label' => 'Depo Ekle',
        'cols' => ['#', 'Depo Adı', 'Adres', 'Açıklama', 'Durum', 'İşlem'],
        'role' => [ROLE_ADMIN, ROLE_USER],
    ],
    'customers' => [
        'title' => 'Müşteri Yönetimi',
        'api' => 'customers',
        'add_label' => 'Müşteri Ekle',
        'cols' => ['#', 'Müşteri Adı', 'Yetkili', 'E-posta', 'Telefon', 'Durum', 'İşlem'],
        'role' => [ROLE_ADMIN, ROLE_USER],
    ],
    'suppliers' => [
        'title' => 'Tedarikçi Yönetimi',
        'api' => 'suppliers',
        'add_label' => 'Tedarikçi Ekle',
        'cols' => ['#', 'Tedarikçi Adı', 'Yetkili', 'E-posta', 'Telefon', 'Durum', 'İşlem'],
        'role' => [ROLE_ADMIN, ROLE_USER],
    ],
];