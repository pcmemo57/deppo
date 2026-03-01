<?php
/**
 * Sistem Görevleri Tetikleme API'si
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';

// Sadece yönetici erişebilir
if (currentUser()['role'] !== ROLE_ADMIN) {
    jsonResponse(false, 'Bu işlem için yetkiniz yok.');
}

$task = sanitize($_POST['task'] ?? $_GET['task'] ?? '');

if (!$task) {
    jsonResponse(false, 'Görev belirtilmedi.');
}

$allowedTasks = ['update_currency', 'entrusted_reminder', 'clear_data', 'clear_selective_data'];

if (!in_array($task, $allowedTasks)) {
    jsonResponse(false, 'Geçersiz görev.');
}

// Log admin action for critical tasks
if ($task === 'clear_data' || $task === 'clear_selective_data') {
    $adminId = currentUser()['id'] ?? 'UNKNOWN';
    error_log("CRITICAL: Admin ID " . $adminId . " triggered $task task at " . date('Y-m-d H:i:s'));
}

$scriptPath = __DIR__ . "/../cron/$task.php";
if (!file_exists($scriptPath)) {
    jsonResponse(false, 'Görev dosyası bulunamadı.');
}

// Prepare arguments if any
$args = '';
if ($task === 'clear_selective_data') {
    $categories = $_POST['categories'] ?? '[]';
    $args = escapeshellarg($categories);
}

// PHP komutunu çalıştır
$command = "php " . escapeshellarg($scriptPath) . " $args 2>&1";
$output = [];
$return_var = 0;

exec($command, $output, $return_var);

$output_str = implode("\n", $output);

if ($return_var === 0) {
    jsonResponse(true, 'Görev başarıyla tamamlandı.', [
        'output' => $output_str
    ]);
} else {
    jsonResponse(false, 'Görev çalıştırılırken bir hata oluştu.', [
        'output' => $output_str,
        'return_code' => $return_var
    ]);
}
