DEPPO - YENİ BİLGİSAYAR KURULUM TALİMATLARI
==========================================

Bu projeyi başka bir bilgisayara kurmak için aşağıdaki adımları takip edin:

1. HAZIRLIK (Hedef Bilgisayar):
   - Hedef bilgisayarda XAMPP kurulu olduğundan emin olun.
   - XAMPP kontrol panelinden Apache ve MySQL servislerini başlatın.

2. DOSYALARIN KOPYALANMASI:
   - Bu klasörün tamamını (deppo klasörünü), hedef bilgisayardaki 
     C:\xampp\htdocs\ (veya Mac'te /Applications/XAMPP/htdocs/) klasörüne kopyalayın.

3. KURULUM EKRANINI ÇALIŞTIRMA:
   - Tarayıcınızı açın ve şu adrese gidin: 
     http://localhost/deppo/setup/install.php

4. VERİTABANI AYARLARI:
   - Açılan ekranda veritabanı bilgilerinizi girin (Varsayılan: localhost, root, şifre boş).
   - "Kurulumu Başlat" butonuna tıklayın.
   - Sistem otomatik olarak veritabanını oluşturacak, tabloları ve verileri içe aktaracaktır.

5. GÜVENLİK:
   - Kurulum tamamlandıktan sonra, güvenlik için htdocs/deppo/setup/ klasörünü silin.

6. GİRİŞ BİLGİLERİ:
   - Kurulum sonrası giriş yapabilirsiniz:
     E-posta: admin@deppo.local
     Şifre: Admin123!

------------------------------------------
Herhangi bir sorun yaşarsanız, config/database.php dosyasının yazılabilir olduğundan emin olun.
