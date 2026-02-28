<?php
/**
 * API — Email Rol Kontrolü
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json; charset=utf-8');

$email = sanitize($_GET['email'] ?? '');

if (!$email) {
    echo json_encode(['success' => false, 'role' => null]);
    exit;
}

// Rolleri sırayla kontrol et
// Önce Admin
$admin = Database::fetchOne("SELECT id FROM tbl_dp_admins WHERE email = ? AND is_active = 1 AND hidden = 0", [$email]);
if ($admin) {
    echo json_encode(['success' => true, 'role' => ROLE_ADMIN]);
    exit;
}

// Sonra Yönetici (User)
$user = Database::fetchOne("SELECT id FROM tbl_dp_users WHERE email = ? AND is_active = 1 AND hidden = 0", [$email]);
if ($user) {
    echo json_encode(['success' => true, 'role' => ROLE_USER]);
    exit;
}

// Sonra Talep Eden
$requester = Database::fetchOne("SELECT id FROM tbl_dp_requesters WHERE email = ? AND is_active = 1 AND hidden = 0", [$email]);
if ($requester) {
    echo json_encode(['success' => true, 'role' => ROLE_REQUESTER]);
    exit;
}

echo json_encode(['success' => false, 'role' => null]);
