<?php
echo "<h3>Git PATH Kontrolü</h3>";
echo "<b>PHP Kullanıcısı:</b> " . get_current_user() . "<br>";
echo "<b>Sistem PATH:</b> <pre>" . getenv('PATH') . "</pre><hr>";

$output = [];
$return_var = 0;
exec('git --version 2>&1', $output, $return_var);

if ($return_var === 0) {
    echo "<h4 style='color:green'>✓ Git Bulundu!</h4>";
    echo "Sürüm: " . implode("\n", $output);
} else {
    echo "<h4 style='color:red'>✗ Git Bulunamadı!</h4>";
    echo "Hata Mesajı: " . implode("\n", $output);
    echo "<p><b>Çözüm:</b> XAMPP Kontrol Paneli'nden Apache'yi DURDURUN ve tekrar BAŞLATIN. Eğer hala düzelmezse bilgisayarı yeniden başlatın.</p>";
}
