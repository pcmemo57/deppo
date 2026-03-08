<?php
/**
 * Otomatik Güncelleme Modalı
 */
if (currentUser()['role'] !== ROLE_ADMIN)
    return;
?>
<div class="modal fade" id="modalAutoUpdate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-sync-alt me-2"></i> Yeni Güncelleme Mevcut!
                </h5>
            </div>
            <div class="modal-body text-center p-4">
                <div class="mb-4">
                    <i class="fas fa-cloud-download-alt text-primary" style="font-size: 4rem;"></i>
                </div>

                <div class="alert alert-success border-0 shadow-sm" style="background: #eef2ff;">
                    <h5 class="mb-1 text-primary">Yeni Bir Sürüm Mevcut!</h5>
                    <p class="mb-0">Bulunan Sürüm: <strong id="auto-remote-version"></strong></p>
                </div>

                <div class="mb-3 small text-muted">
                    <span>Mevcut Sürüm: <span class="badge bg-secondary">v<?= APP_VERSION ?></span></span>
                    <span class="ms-2">Veritabanı: <span
                            class="badge bg-info">v<?= e(get_setting('db_version', '1.0.0')) ?></span></span>
                </div>

                <div id="auto-update-status" class="alert alert-info d-none">
                    <i class="fas fa-spinner fa-spin me-2"></i> Güncelleniyor, lütfen bekleyin...
                </div>

                <div id="auto-update-log-container" class="mt-3 d-none">
                    <pre id="auto-update-log" class="p-2 bg-dark text-light small text-start"
                        style="max-height: 150px; overflow-y: auto; border-radius: 5px;"></pre>
                    <button id="btnAutoForceUpdate" class="btn btn-danger btn-sm w-100 mt-2 d-none">
                        <i class="fas fa-exclamation-triangle me-1"></i> Çakışmaları Gider ve Zorla Güncelle
                    </button>
                </div>

                <div class="form-check mt-3 text-start">
                    <input class="form-check-input" type="checkbox" id="checkIgnoreUpdate">
                    <label class="form-check-label small text-muted" for="checkIgnoreUpdate">
                        Bu sürümü bir sonraki güncellemeye kadar göz ardı et.
                    </label>
                </div>
            </div>
            <div class="modal-footer border-0 d-flex justify-content-between">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Daha Sonra</button>
                <button id="btnAutoPerformUpdate" class="btn btn-success px-4 shadow-sm">
                    <i class="fas fa-download me-1"></i> Şimdi Güncelle
                </button>
            </div>
        </div>
    </div>
</div>