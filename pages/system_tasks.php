<?php
/**
 * Sistem Görevleri Sayfası
 */
requireRole(ROLE_ADMIN);
?>

<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card card-primary card-outline shadow">
            <div class="card-header">
                <h3 class="card-title text-bold">
                    <i class="fas fa-tasks me-1"></i> Sistem Görevleri
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Buradan sistemdeki otomatik görevleri (cron) manuel olarak tetikleyebilirsiniz.
                </p>

                <div class="row g-4 mt-2">
                    <!-- Döviz Kuru Güncelleme -->
                    <div class="col-md-6">
                        <div class="card h-100 border shadow-sm">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="fas fa-money-bill-wave text-success" style="font-size: 2.5rem;"></i>
                                </div>
                                <h5 class="fw-bold">Döviz Kurlarını Güncelle</h5>
                                <p class="small text-muted">TCMB üzerinden güncel USD ve EUR kurlarını çeker ve sisteme
                                    kaydeder.</p>
                                <button class="btn btn-outline-success btn-sm px-4 run-task"
                                    data-task="update_currency">
                                    <i class="fas fa-play me-1"></i> Şimdi Çalıştır
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Emanet Hatırlatıcı -->
                    <div class="col-md-6">
                        <div class="card h-100 border shadow-sm">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <i class="fas fa-bell text-warning" style="font-size: 2.5rem;"></i>
                                </div>
                                <h5 class="fw-bold">Emanet Hatırlatıcı</h5>
                                <p class="small text-muted">İade süresi geçen emanetler için ilgili kişilere otomatik
                                    e-posta gönderir.</p>
                                <button class="btn btn-outline-warning btn-sm px-4 run-task"
                                    data-task="entrusted_reminder">
                                    <i class="fas fa-play me-1"></i> Şimdi Çalıştır
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Log Çıktısı -->
        <div id="log-container" class="card card-dark mt-4 d-none">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title text-sm">İşlem Günlüğü (Log)</h3>
                <button class="btn btn-xs btn-outline-light"
                    onclick="$('#log-container').addClass('d-none')">Kapat</button>
            </div>
            <div class="card-body p-0">
                <pre id="log-output" class="m-0 p-3 bg-dark text-light small"
                    style="max-height: 300px; overflow-y: auto; font-family: 'Courier New', Courier, monospace;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        $('.run-task').on('click', function () {
            const btn = $(this);
            const task = btn.data('task');
            const originalHtml = btn.html();

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Çalışıyor...');
            $('#log-container').addClass('d-none');
            $('#log-output').text('');

            $.get('<?= BASE_URL ?>/api/system_tasks.php', { task: task }, function (r) {
                btn.prop('disabled', false).html(originalHtml);

                $('#log-output').text(r.data.output || 'Çıktı yok.');
                $('#log-container').removeClass('d-none');

                if (r.success) {
                    showSuccess(r.message);
                } else {
                    showError(r.message);
                }

                // Log alanına odaklan
                $('html, body').animate({
                    scrollTop: $("#log-container").offset().top - 20
                }, 500);

            }, 'json').fail(function () {
                btn.prop('disabled', false).html(originalHtml);
                showError('Bağlantı hatası oluştu.');
            });
        });
    });
</script>