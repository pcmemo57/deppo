<?php
/**
 * Sistem Güncelleme Sayfası
 */
requireRole(ROLE_ADMIN);
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card card-primary card-outline shadow">
            <div class="card-header">
                <h3 class="card-title text-bold">
                    <i class="fas fa-sync-alt me-1"></i> Sistem Güncelleme
                </h3>
            </div>
            <div class="card-body">
                <div class="text-center py-4">
                    <div class="mb-4">
                        <i class="fas fa-cloud-download-alt text-primary" style="font-size: 4rem;"></i>
                    </div>
                    <h4>Mevcut Sürüm: <span class="badge bg-secondary">v
                            <?= APP_VERSION ?>
                        </span></h4>
                    <p class="text-muted mt-2">Sisteminizin güncel olduğundan emin olun. Yeni özellikleri ve güvenlik
                        düzeltmelerini almak için düzenli olarak güncellemeleri kontrol edin.</p>

                    <hr class="my-4">

                    <div id="update-status" class="alert alert-info d-none">
                        <i class="fas fa-spinner fa-spin me-2"></i> Güncellemeler kontrol ediliyor...
                    </div>

                    <div id="update-info" class="d-none">
                        <div class="alert alert-success border-left-success">
                            <h5 class="mb-1">Yeni Bir Sürüm Mevcut!</h5>
                            <p class="mb-0">Bulunan Sürüm: <strong id="remote-version"></strong></p>
                        </div>
                        <button id="btnPerformUpdate" class="btn btn-success btn-lg px-5 shadow-sm mt-3">
                            <i class="fas fa-download me-1"></i> Şimdi Güncelle
                        </button>
                        <button id="btnForceUpdate" class="btn btn-danger btn-lg px-5 shadow-sm mt-3 d-none">
                            <i class="fas fa-exclamation-triangle me-1"></i> Çakışmaları Gider ve Zorla Güncelle
                        </button>
                    </div>

                    <div id="no-update-info" class="d-none">
                        <div class="alert alert-light border shadow-sm">
                            <i class="fas fa-check-circle text-success me-2"></i> Sisteminiz şu anda en güncel sürümde.
                        </div>
                    </div>

                    <button id="btnCheckUpdate" class="btn btn-primary btn-lg px-5 shadow-sm mt-3">
                        <i class="fas fa-search me-1"></i> Güncellemeleri Kontrol Et
                    </button>
                </div>
            </div>
            <div class="card-footer bg-light small text-muted">
                <i class="fas fa-info-circle me-1"></i> Güncelleme işlemi projenin ana şubesi (main) üzerinden
                <code>git pull</code> komutu ile gerçekleştirilir.
            </div>
        </div>

        <div id="update-log-container" class="card card-dark mt-4 d-none">
            <div class="card-header">
                <h3 class="card-title text-sm">Güncelleme Günlüğü</h3>
            </div>
            <div class="card-body p-0">
                <pre id="update-log" class="m-0 p-3 bg-dark text-light small"
                    style="max-height: 200px; overflow-y: auto;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        $('#btnCheckUpdate').on('click', function () {
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Kontrol Ediliyor...');
            $('#update-status').removeClass('d-none').html('<i class="fas fa-spinner fa-spin me-2"></i> Güncellemeler kontrol ediliyor...');
            $('#update-info, #no-update-info, #update-log-container').addClass('d-none');

            $.get('<?= BASE_URL ?>/api/check_update.php', function (r) {
                btn.prop('disabled', false).html('<i class="fas fa-search me-1"></i> Güncellemeleri Kontrol Et');
                $('#update-status').addClass('d-none');

                if (r.success) {
                    if (r.data.update_available) {
                        $('#remote-version').text('v' + r.data.remote_version);
                        $('#update-info').removeClass('d-none');
                    } else {
                        $('#no-update-info').removeClass('d-none');
                    }
                } else {
                    showError(r.message);
                }
            }, 'json').fail(function () {
                btn.prop('disabled', false).html('<i class="fas fa-search me-1"></i> Güncellemeleri Kontrol Et');
                $('#update-status').addClass('d-none');
                showError('Bağlantı hatası oluştu.');
            });
        });

        $('#btnPerformUpdate').on('click', function () {
            confirmAction('Sistemi şimdi güncellemek istediğinize emin misiniz? Bu işlem sırasında sisteminiz kısa süreliğine erişilemez olabilir.', function () {
                const btn = $('#btnPerformUpdate');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Güncelleniyor...');
                $('#btnCheckUpdate').prop('disabled', true);

                $.get('<?= BASE_URL ?>/api/perform_update.php', function (r) {
                    if (r.success) {
                        $('#update-log').text(r.data.output);
                        $('#update-log-container').removeClass('d-none');
                        showSuccess(r.message);

                        // 3 saniye sonra sayfayı yenile
                        setTimeout(function () {
                            location.reload();
                        }, 3000);
                    } else {
                        btn.prop('disabled', false).html('<i class="fas fa-download me-1"></i> Şimdi Güncelle');
                        $('#btnCheckUpdate').prop('disabled', false);
                        $('#update-log').text(r.data.output);
                        $('#update-log-container').removeClass('d-none');

                        // Çakışma hatası varsa Zorla Güncelle butonu göster
                        if (r.message.includes('değişiklikler güncellemeye engel oluyor')) {
                            $('#btnPerformUpdate').addClass('d-none');
                            $('#btnForceUpdate').removeClass('d-none');
                        }

                        showError(r.message);
                    }
                }, 'json').fail(function () {
                    btn.prop('disabled', false).html('<i class="fas fa-download me-1"></i> Şimdi Güncelle');
                    $('#btnCheckUpdate').prop('disabled', false);
                    showError('Güncelleme sırasında bir ağ hatası oluştu.');
                });
            });
        });

        $('#btnForceUpdate').on('click', function () {
            confirmAction('DİKKAT: Bu işlem yerel bilgisayardaki tüm dosya değişikliklerini SİLECEK ve buluttaki (GitHub) haliyle birebir eşitleyecektir. Emin misiniz?', function () {
                const btn = $('#btnForceUpdate');
                btn.prop('disabled', true).html('<i class="fas fa-exclamation-triangle fa-spin me-1"></i> Zorla Güncelleniyor...');

                $.get('<?= BASE_URL ?>/api/perform_update.php?force=1', function (r) {
                    if (r.success) {
                        $('#update-log').text(r.data.output);
                        $('#update-log-container').removeClass('d-none');
                        showSuccess('Sistem başarıyla sıfırlandı ve güncellendi!');
                        setTimeout(function () {
                            location.reload();
                        }, 3000);
                    } else {
                        btn.prop('disabled', false).html('<i class="fas fa-exclamation-triangle me-1"></i> Çakışmaları Gider ve Zorla Güncelle');
                        $('#update-log').text(r.data.output);
                        showError(r.message);
                    }
                }, 'json');
            });
        });
    });
</script>