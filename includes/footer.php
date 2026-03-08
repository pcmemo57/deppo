<?php
/**
 * Footer
 */
$footerBg = get_setting('footer_bg', '#343a40');
$footerColor = get_setting('footer_color', '#ffffff');
$footerText = get_setting('footer_text', '© 2026 Depo Yönetim Sistemi');
?>
</div><!-- /.content-wrapper -->

<!-- Footer -->
<footer class="main-footer py-2" style="background:<?= e($footerBg) ?>; color:<?= e($footerColor) ?>; border:none;">
    <div class="float-right d-none d-sm-inline">
        <small>v
            <?= APP_VERSION ?>
        </small>
    </div>
    <strong>
        <?= e($footerText) ?>
    </strong>
</footer>

<!-- jQuery -->
<script src="<?= BASE_URL ?>/assets/vendor/js/jquery.min.js"></script>
<!-- Bootstrap 5 -->
<script src="<?= BASE_URL ?>/assets/vendor/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE 3 -->
<script src="<?= BASE_URL ?>/assets/vendor/js/adminlte.min.js"></script>
<!-- Select2 -->
<script src="<?= BASE_URL ?>/assets/vendor/js/select2.min.js"></script>
<!-- SweetAlert2 -->
<script src="<?= BASE_URL ?>/assets/vendor/js/sweetalert2.all.min.js"></script>
<!-- XLSX-JS-STYLE (Excel export with styles) -->
<script src="<?= BASE_URL ?>/assets/vendor/js/xlsx.bundle.js"></script>
<!-- Cropper.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<script>
    // Global SweetAlert tema
    const Toast = Swal.mixin({
        toast: false,
        position: 'center',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true,
    });

    function showSuccess(msg) {
        Toast.fire({ icon: 'success', title: msg });
    }
    function showError(msg) {
        Toast.fire({ icon: 'error', title: msg, timer: 4000 });
    }
    function showInfo(msg) {
        Toast.fire({ icon: 'info', title: msg });
    }
    function confirmAction(msg, callback) {
        Swal.fire({
            title: 'Emin misiniz?',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Evet, devam et',
            cancelButtonText: 'İptal'
        }).then(function (result) {
            if (result.isConfirmed) callback();
        });
    }

    function formatTurkish(val, decimals = 2) {
        if (val === null || val === undefined || val === '') return '';

        var str = String(val).trim();
        var num;

        if (typeof val === 'number') {
            num = val;
        } else {
            if (str.indexOf(',') !== -1) {
                // Kesin Türk formatı: 1.234,56
                num = parseFloat(str.replace(/\./g, '').replace(',', '.'));
            } else {
                // Standart float (virgül yok): "1.000" veya "1000" -> 1.0 veya 1000.0
                num = parseFloat(str);
            }
        }

        if (isNaN(num)) return '0,00';

        var fixed = num.toFixed(decimals);
        var p = fixed.split('.');
        // Binler ayıracı ekle
        p[0] = p[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return p.join(',');
    }

    /**
     * Adet Formatlama (Ondalık yok, binler nokta)
     */
    function formatQty(val) {
        if (val === null || val === undefined || val === '') return '';
        var num;
        if (typeof val === 'number') {
            num = val;
        } else {
            var str = String(val).trim();
            if (str.indexOf(',') !== -1) {
                // Türkçe format (virgül var): 1.000,00 -> 1000.00
                num = parseFloat(str.replace(/\./g, '').replace(',', '.'));
            } else {
                // Standart float (virgül yok): "1.000" veya "1000" -> 1.0 veya 1000.0
                num = parseFloat(str);
            }
        }
        if (isNaN(num)) return val;
        return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num);
    }

    /**
     * Fiyat ve Adet inputları için gerçek zamanlı maskeleme
     */
    $(document).on('input', '.price-format', function (e) {
        var cursor = this.selectionStart;
        var oldLen = $(this).val().length;
        var val = $(this).val().replace(/[^0-9,]/g, '');

        var parts = val.split(',');
        // Binler ayıracı ekle
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        var formatted = parts[0] + (val.indexOf(',') !== -1 ? ',' + (parts[1] || '').substring(0, 4) : '');
        $(this).val(formatted);

        var newLen = formatted.length;
        this.setSelectionRange(cursor + (newLen - oldLen), cursor + (newLen - oldLen));
    });

    /**
     * Telefon Maskeleme (999) 999 99 99
     * İlk karakter 0 olamaz.
     */
    $(document).on('input', '.phone-format', function (e) {
        var val = $(this).val().replace(/\D/g, '');
        if (val.startsWith('0')) val = val.substring(1);
        val = val.substring(0, 10); // Max 10 hane

        var formatted = '';
        if (val.length > 0) {
            formatted = '(' + val.substring(0, 3);
            if (val.length > 3) {
                formatted += ') ' + val.substring(3, 6);
                if (val.length > 6) {
                    formatted += ' ' + val.substring(6, 8);
                    if (val.length > 8) {
                        formatted += ' ' + val.substring(8, 10);
                    }
                }
            }
        }
        $(this).val(formatted);
    });

    $(document).on('blur', '.price-format', function () {
        var val = $(this).val();
        if (val) $(this).val(formatTurkish(val, 2));
    });

    /**
     * Inputtan temiz float değer al
     */
    function getPriceValue(selector) {
        var val = $(selector).val() || '';
        if (!val) return 0;
        return parseFloat(val.replace(/\./g, '').replace(',', '.')) || 0;
    }

    // Navbar Döviz Güncelleme
    $(document).on('click', '#btnNavbarUpdateCurrency', function (e) {
        e.preventDefault();
        var btn = $(this);
        var originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>...');

        $.post('<?= BASE_URL ?>/api/settings.php', { action: 'update_currency' }, function (r) {
            if (r.success) {
                showSuccess('Kurlar güncellendi!');
                setTimeout(function () {
                    location.reload();
                }, 1000);
            } else {
                showError(r.message || 'Güncelleme başarısız.');
                btn.html(originalHtml);
            }
        }, 'json').fail(function () {
            showError('Bağlantı hatası.');
            btn.html(originalHtml);
        }).always(function () {
            btn.prop('disabled', false);
        });
    });

    // Global AJAX CSRF Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Sidebar rozetlerini güncelle
    function updatePendingBadges() {
        $.get('<?= BASE_URL ?>/api/stock_out.php', { action: 'pending_count' }, function (r) {
            if (r.success && r.data.count > 0) {
                $('#sidebar-pending-total').text(r.data.count).show();
                $('#sidebar-pending-out').text(r.data.count).show();
                $('#sidebar-requester-pending').text(r.data.count).show();
            } else {
                $('#sidebar-pending-total').hide();
                $('#sidebar-pending-out').hide();
                $('#sidebar-requester-pending').hide();
            }
        }, 'json');
    }
    updatePendingBadges();
    setInterval(updatePendingBadges, 60000); // 1 dakikada bir güncelle

    // --- Günlük Otomatik Güncelleme Kontrolü ---
    <?php 
    if (currentUser()['role'] === ROLE_ADMIN): 
        $lastCheck = get_setting('last_update_check');
        $today = date('Y-m-d');
        if ($lastCheck !== $today):
    ?>
        $(function () {
            const ignoredVersion = localStorage.getItem('ignored_version');

            $.get('<?= BASE_URL ?>/api/check_update.php', function (r) {
                if (r.success && r.data.update_available) {
                    // Eğer bu sürüm daha önce göz ardı edilmediyse göster
                    if (ignoredVersion !== r.data.remote_version) {
                        $('#auto-remote-version').text(r.data.remote_version);
                        $('#modalAutoUpdate').modal('show');

                        // "Daha Sonra" butonuna basıldığında veya modal kapandığında
                        $('#modalAutoUpdate').on('hidden.bs.modal', function () {
                            if ($('#checkIgnoreUpdate').is(':checked')) {
                                localStorage.setItem('ignored_version', r.data.remote_version);
                            }
                        });
                    }
                }
            }, 'json');

            $('#btnAutoPerformUpdate').on('click', function () {
                const btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>...');
                $('#auto-update-status').removeClass('d-none');

                $.get('<?= BASE_URL ?>/api/perform_update.php', function (r) {
                    $('#auto-update-status').addClass('d-none');
                    $('#auto-update-log').text(r.data.output);
                    $('#auto-update-log-container').removeClass('d-none');

                    if (r.success) {
                        showSuccess('Güncelleme başarıyla tamamlandı!');
                        setTimeout(() => location.reload(), 3000);
                    } else {
                        btn.prop('disabled', false).html('<i class="fas fa-download me-1"></i> Tekrar Dene');

                        // Çakışma hatası varsa Zorla Güncelle butonu göster
                        if (r.message.includes('değişiklikler güncellemeye engel oluyor')) {
                            $('#btnAutoForceUpdate').removeClass('d-none');
                        }
                        showError(r.message);
                    }
                }, 'json').fail(function () {
                    $('#auto-update-status').addClass('d-none');
                    btn.prop('disabled', false).html('<i class="fas fa-download me-1"></i> Tekrar Dene');
                    showError('Güncelleme sırasında hata oluştu.');
                });
            });

            $('#btnAutoForceUpdate').on('click', function () {
                confirmAction('Yerel bilgisayardaki tüm dosya değişikliklerini SİLECEK ve buluttaki haliyle eşitleyecektir. Emin misiniz?', function () {
                    const btn = $('#btnAutoForceUpdate');
                    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>...');
                    $('#auto-update-status').removeClass('d-none');

                    $.get('<?= BASE_URL ?>/api/perform_update.php?force=1', function (r) {
                        $('#auto-update-status').addClass('d-none');
                        $('#auto-update-log').text(r.data.output);

                        if (r.success) {
                            showSuccess('Sistem başarıyla sıfırlandı ve güncellendi!');
                            setTimeout(() => location.reload(), 3000);
                        } else {
                            btn.prop('disabled', false).html('<i class="fas fa-exclamation-triangle me-1"></i> Tekrar Dene');
                            showError(r.message);
                        }
                    }, 'json');
                });
            });
        });
    <?php endif; endif; ?>
</script>

<?php
if (currentUser()['role'] === ROLE_ADMIN) {
    include __DIR__ . '/update_modal.php';
}
?>

<?php if (isset($extraScripts))
    echo $extraScripts; ?>
</div><!-- /.wrapper -->
</body>

</html>