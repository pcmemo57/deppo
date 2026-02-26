<?php
/**
 * Oturum Yönetimi
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

// Güvenli oturum ayarları
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Giriş yapılmış mı kontrol et
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['dp_user_id']) && isset($_SESSION['dp_role']);
}

/**
 * Rol kontrolü
 */
function hasRole(string...$roles): bool
{
    return in_array($_SESSION['dp_role'] ?? '', $roles, true);
}

/**
 * Oturum gerektiren sayfa — otomatik yönlendir
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Belirli rol gerektiren sayfa
 */
function requireRole(string...$roles): void
{
    requireLogin();
    if (!hasRole(...$roles)) {
        http_response_code(403);
        die('<h3>Bu sayfaya erişim yetkiniz yok.</h3>');
    }
}

/**
 * CSRF token oluştur
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF token doğrula
 */
function validateCsrfToken(string $token): bool
{
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Mevcut kullanıcı bilgileri
 */
function currentUser(): array
{
    return [
        'id' => $_SESSION['dp_user_id'] ?? 0,
        'name' => $_SESSION['dp_user_name'] ?? '',
        'email' => $_SESSION['dp_user_email'] ?? '',
        'role' => $_SESSION['dp_role'] ?? '',
    ];
}