<?php
/**
 * Deppo - Gelişmiş Kurulum Scripti
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$configFile = __DIR__ . '/../config/database.php';
$sqlFile = __DIR__ . '/database.sql';

// Zaten kurulu mu kontrol et
if (file_exists($configFile) && !isset($_GET['retry'])) {
    // Session veya benzeri bir kontrol eklenebilir ama basitlik adına dosya varlığına bakıyoruz
}

$step = $_POST['step'] ?? 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? 'deppo';

    try {
        // 1. Bağlantı Testi ve DB Oluşturma
        $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");

        // 2. SQL Dosyasını İçe Aktar
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL dosyası bulunamadı: setup/database.sql");
        }

        $sql = file_get_contents($sqlFile);

        // PDO exec birden fazla sorguyu her zaman desteklemeyebilir, 
        // ancak mysqldump çıktısı genellikle tek seferde çalıştırılabilir.
        // Daha güvenli olması için query splitting yapılabilir ama mysqldump için exec yeterlidir.
        $pdo->exec($sql);

        // 3. Config Dosyasını Oluştur
        $configContent = "<?php
/**
 * Veritabanı Bağlantısı — Otomatik Oluşturuldu
 */

define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_CHARSET', 'utf8mb4');

class Database
{
    private static ?PDO \$instance = null;

    public static function getInstance(): PDO
    {
        if (self::\$instance === null) {
            \$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            \$options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            try {
                self::\$instance = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
            }
            catch (PDOException \$e) {
                http_response_code(500);
                die(json_encode(['success' => false, 'message' => 'Veritabanı bağlantı hatası.']));
            }
        }
        return self::\$instance;
    }

    public static function query(string \$sql, array \$params = []): \PDOStatement
    {
        \$stmt = self::getInstance()->prepare(\$sql);
        \$stmt->execute(\$params);
        return \$stmt;
    }

    public static function fetchAll(string \$sql, array \$params = []): array
    {
        return self::query(\$sql, \$params)->fetchAll();
    }

    public static function fetchOne(string \$sql, array \$params = []): array |false
    {
        return self::query(\$sql, \$params)->fetch();
    }

    public static function insert(string \$sql, array \$params = []): int
    {
        self::query(\$sql, \$params);
        return (int)self::getInstance()->lastInsertId();
    }

    public static function execute(string \$sql, array \$params = []): int
    {
        return self::query(\$sql, \$params)->rowCount();
    }
}";

        if (file_put_contents($configFile, $configContent) === false) {
            throw new Exception("config/database.php dosyası yazılamadı. Lütfen klasör izinlerini kontrol edin.");
        }

        $success = "Kurulum başarıyla tamamlandı!";
        $step = 3;

    } catch (Exception $e) {
        $error = "Hata: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deppo - Kurulum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .setup-container {
            max-width: 600px;
            margin: 100px auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .btn-primary {
            background: #4e73df;
            border: none;
            padding: 10px 25px;
        }

        .btn-primary:hover {
            background: #2e59d9;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="setup-container">
            <div class="logo">
                <h2 class="text-primary fw-bold">DEPPO</h2>
                <p class="text-muted">Sistem Kurulumu</p>
            </div>

            <?php
            // Git Kontrolü
            exec('git --version', $git_test, $git_return);
            if ($git_return !== 0): ?>
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i> Git Eksik!</h5>
                    <p class="small mb-0">Bu bilgisayarda <b>Git</b> kurulu görünmüyor. Sistem kurulabilir ancak "Otomatik
                        Güncelleme" özelliği çalışmayacaktır. Sorunsuz bir deneyim için Git kurmanızı öneririz.</p>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <div class="welcome-step">
                    <p>Kuruluma başlamak için lütfen veritabanı bilgilerinizi hazırlayın.</p>
                    <form method="post">
                        <input type="hidden" name="step" value="2">
                        <div class="mb-3">
                            <label class="form-label">Veritabanı Host</label>
                            <input type="text" name="db_host" class="form-control" value="localhost" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Veritabanı Adı</label>
                            <input type="text" name="db_name" class="form-control" value="deppo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" name="db_user" class="form-control" value="root" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre</label>
                            <input type="password" name="db_pass" class="form-control">
                            <div class="form-text">XAMPP kullanıyorsanız genelde boştur.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Kurulumu Başlat</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($step == 3): ?>
                <div class="success-step text-center">
                    <div class="mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="green"
                            class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                            <path
                                d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                        </svg>
                    </div>
                    <h4 class="text-success mb-3"><?php echo $success; ?></h4>
                    <p>Sistem kullanıma hazır. Güvenliğiniz için <b>setup/</b> klasörünü silmeyi unutmayın.</p>
                    <div class="mt-4">
                        <a href="../login.php" class="btn btn-primary">Giriş Yap</a>
                    </div>
                    <p class="mt-3 text-muted small">Varsayılan Admin: admin@deppo.local / Admin123!</p>
                </div>
            <?php endif; ?>

        </div>
    </div>

</body>

</html>