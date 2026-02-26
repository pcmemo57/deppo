<?php
// PHPMailer/mail.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/src/Exception.php';
require __DIR__ . '/src/PHPMailer.php';
require __DIR__ . '/src/SMTP.php';

function sendEmail($to, $subject, $message, $firma_id = null, $replyTo = null, $replyToName = null)
{
    global $db;

    if ($firma_id === null) {
        $firma_id = $_SESSION['admin_firma_id'] ?? $_SESSION['user_firma_id'] ?? 0;
    }

    // Fetch Settings
    try {
        $stmt = $db->prepare("SELECT * FROM tbl_settings WHERE firma_id = ? LIMIT 1");
        $stmt->execute([$firma_id]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    catch (PDOException $e) {
        return ['status' => false, 'message' => 'DB Error: ' . $e->getMessage()];
    }

    if (empty($settings['smtp_host']) || empty($settings['smtp_user'])) {
        return ['status' => false, 'message' => 'Sistem e-posta ayarları (SMTP) henüz yapılandırılmamış. Lütfen Ayarlar sayfasından SMTP bilgilerini giriniz.'];
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $settings['smtp_host'] ?? '';
        $mail->SMTPAuth = true;
        $mail->Username = $settings['smtp_user'] ?? '';
        $mail->Password = $settings['smtp_password'] ?? '';
        $mail->SMTPSecure = $settings['smtp_encryption'] ?? 'tls'; // 'ssl' or 'tls'
        $mail->Port = $settings['smtp_port'] ?? 587;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 20;

        // SSL verification bypass
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Recipients
        $fromEmail = !empty($settings['smtp_user']) ? $settings['smtp_user'] : ('noreply@' . ($_SERVER['HTTP_HOST'] ?? 'gsm-servis.com'));
        $fromName = $settings['smtp_from_name'] ?? 'GSM Teknik Servis';
        $mail->setFrom($fromEmail, $fromName);

        if ($replyTo) {
            // Log for debugging
            file_put_contents(__DIR__ . '/mail_debug.log', "[" . date('Y-m-d H:i:s') . "] Setting Reply-To: $replyTo ($replyToName)" . PHP_EOL, FILE_APPEND);

            try {
                if (PHPMailer::validateAddress($replyTo)) {
                    $mail->addReplyTo($replyTo, $replyToName ?: '');
                }
                else {
                    file_put_contents(__DIR__ . '/mail_debug.log', "[" . date('Y-m-d H:i:s') . "] Invalid Reply-To address: $replyTo" . PHP_EOL, FILE_APPEND);
                }
            }
            catch (Exception $e) {
                file_put_contents(__DIR__ . '/mail_debug.log', "[" . date('Y-m-d H:i:s') . "] Error adding Reply-To: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
            }
        }

        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);

        $mail->send();
        return ['status' => true, 'message' => 'Email başarıyla gönderildi'];
    }
    catch (Exception $e) {
        file_put_contents(__DIR__ . '/mail_errors.log', "[" . date('Y-m-d H:i:s') . "] Mail Error: " . $mail->ErrorInfo . " | To: $to | Subject: $subject" . PHP_EOL, FILE_APPEND);
        return ['status' => false, 'message' => "Mail gönderilemedi. Hata: {$mail->ErrorInfo}"];
    }
}