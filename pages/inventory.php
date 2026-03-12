<?php
/**
 * Depo Sayımı Modülü
 */
requireRole(ROLE_ADMIN, ROLE_USER);
?>

<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline shadow-sm">
            <div class="card-header border-0 py-3">
                <h3 class="card-title text-bold"><i class="fas fa-clipboard-check me-2 text-primary"></i>Depo Sayım
                    Yönetimi</h3>
                <div class="card-tools">
                    <button id="btn-new-session" class="btn btn-primary btn-sm px-3 shadow-sm"
                        onclick="startNewSession()">
                        <i class="fas fa-plus me-1"></i> Yeni Sayım Başlat
                    </button>
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <div id="sessionListCards" class="row g-3 p-3 d-md-none">
                    <div class="col-12 text-center p-4 text-muted"><i
                            class="fas fa-spinner fa-spin me-2"></i>Yükleniyor...</div>
                </div>
                <table class="table table-hover table-striped m-0 d-none d-md-table">
                    <thead class="bg-light">
                        <tr>
                            <th>Bilgi</th>
                            <th>Sayılan</th>
                            <th>Oluşturan</th>
                            <th>Durum</th>
                            <th class="text-end pe-3">İşlem</th>
                        </tr>
                    </thead>
                    <tbody id="sessionListBody">
                        <tr>
                            <td colspan="5" class="text-center p-4"><i
                                    class="fas fa-spinner fa-spin me-2"></i>Yükleniyor...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Yeni Sayım Başlat -->
<div class="modal fade" id="newSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Yeni Sayım Oturumu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="sessionForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Sayılacak Depo</label>
                        <select name="warehouse_id" id="warehouseSelect" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notlar (Opsiyonel)</label>
                        <textarea name="notes" class="form-control" rows="2"
                            placeholder="Örn: Yıl sonu sayımı..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" onclick="submitNewSession()">Sayımı Başlat</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Sayım Ekranı (Barkod Okutma) -->
<div class="modal fade" id="countModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-fullscreen-md-down modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-dark text-white shadow-sm">
                <h5 class="modal-title"><i class="fas fa-barcode me-2"></i>Ürün Sayımı</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light p-0">
                <div class="container-fluid p-3">
                    <!-- Scanner Area -->
                    <div id="scanner-container" class="mb-3 rounded overflow-hidden shadow-sm"
                        style="display:none; position: relative; background: #000; min-height: 250px;">
                        <div id="interactive" class="viewport"></div>
                        <button class="btn btn-danger btn-sm" onclick="stopScanner()"
                            style="position:absolute; top:10px; right:10px; z-index:100;">
                            <i class="fas fa-times me-1"></i> Kamerayı Kapat
                        </button>
                    </div>

                    <!-- Input Area -->
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="input-group input-group-lg shadow-sm">
                                <span class="input-group-text bg-white border-end-0"><i
                                        class="fas fa-barcode text-muted"></i></span>
                                <input type="text" id="barcodeInput" class="form-control border-start-0 ps-0"
                                    placeholder="Barkod okutun veya yazın...">
                                <button class="btn btn-primary" onclick="startScanner()" id="btnStartScan">
                                    <i class="fas fa-camera me-1"></i> Tara
                                </button>
                            </div>
                        </div>

                        <!-- Product Detail (Dynamic) -->
                        <div id="productDetail" class="col-12 mt-3" style="display:none;">
                            <div class="card border-0 shadow-sm overflow-hidden">
                                <div class="row g-0">
                                    <div class="col-4 col-md-3">
                                        <img src="" id="prodImage" class="img-fluid h-100 object-fit-cover"
                                            onerror="this.src='<?= BASE_URL ?>/assets/no-image.png'">
                                    </div>
                                    <div class="col-8 col-md-9 p-3">
                                        <h5 class="mb-1 text-bold" id="prodName"></h5>
                                        <p class="text-muted small mb-3">Kod: <code id="prodCode"></code> | Birim: <span
                                                id="prodUnit"></span></p>

                                        <div class="row g-2 align-items-center">
                                            <div class="col-auto">
                                                <label class="form-label mb-0 fw-bold">Miktar:</label>
                                            </div>
                                            <div class="col">
                                                <input type="number" id="countQty"
                                                    class="form-control form-control-lg text-center fw-bold text-primary"
                                                    value="1" min="1" step="1" inputmode="numeric"
                                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                            </div>
                                            <div class="col-auto">
                                                <button class="btn btn-success btn-lg px-4 fw-bold"
                                                    onclick="saveCount()">
                                                    <i class="fas fa-check me-2"></i>KAYDET
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Counts -->
                        <div class="col-12">
                            <h6 class="text-muted text-uppercase small ls-wide fw-bold mb-3 mt-2">Son Sayılanlar</h6>
                            <div class="list-group list-group-flush shadow-sm rounded border" id="recentCounts"
                                style="max-height: 450px; overflow-y: auto;">
                                <div class="list-group-item text-center p-4 text-muted small">Henüz veri yok.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between bg-white border-top-0 p-3">
                <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">
                    <i class="fas fa-pause me-2"></i>Sayıma Ara Ver
                </button>
                <button type="button" class="btn btn-danger px-3 shadow-sm" onclick="confirmCloseSession()">
                    <i class="fas fa-flag-checkered me-2"></i>Sayımı Tamamen Bitir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- HTML5-QRCode Script -->
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
    let currentSessionId = null;
    let html5QrCode = null;

    $(function () {
        loadSessions();
    });

    function getCsrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function loadSessions() {
        $.get('<?= BASE_URL ?>/api/inventory.php', { action: 'list_sessions' }, function (r) {
            if (!r.success) {
                $('#sessionListBody').html('<tr><td colspan="5" class="text-center text-danger p-4"><i class="fas fa-exclamation-circle me-1"></i>Hata: ' + r.message + '</td></tr>');
                $('#sessionListCards').html('<div class="col-12 text-center text-danger p-4"><i class="fas fa-exclamation-circle me-1"></i>Hata: ' + r.message + '</div>');
                return;
            }
            let listHtml = '';
            let cardHtml = '';
            if (r.data.length === 0) {
                listHtml = '<tr><td colspan="5" class="text-center text-muted p-4">Henüz kayıtlı sayım yok.</td></tr>';
                cardHtml = '<div class="col-12 text-center text-muted p-4">Henüz kayıtlı sayım yok.</div>';
            } else {
                $.each(r.data, function (i, s) {
                    let statusBadge = s.status === 'open'
                        ? '<span class="badge bg-success shadow-none">AÇIK</span>'
                        : '<span class="badge bg-secondary shadow-none">KAPANDI</span>';

                    // Table Row (Desktop)
                    listHtml += `<tr>
                    <td>
                        <div class="fw-bold text-primary">${s.warehouse_name}</div>
                        <div class="text-muted small"><i class="far fa-calendar-alt me-1"></i>${s.created_at}</div>
                    </td>
                    <td><span class="badge bg-info text-dark">${s.item_count} Kalem</span></td>
                    <td><small>${s.creator_name || '—'}</small></td>
                    <td>${statusBadge}</td>
                    <td class="text-end pe-3">
                        <div class="d-flex justify-content-end align-items-center">
                        ${s.status === 'open'
                            ? `<button class="btn btn-primary btn-sm px-3 me-2" onclick="continueSession(${s.id})"><i class="fas fa-play me-1"></i> Sayıma Devam</button>
                               <button class="btn btn-outline-danger btn-sm px-2" onclick="deleteSession(${s.id})" title="Oturumu İptal Et"><i class="fas fa-trash-alt"></i></button>`
                            : `<button class="btn btn-outline-info btn-sm px-3" onclick="viewDetails(${s.id})"><i class="fas fa-eye me-1"></i> Detaylar</button>
                               <button class="btn btn-link text-danger btn-sm p-0 ms-3" onclick="deleteSession(${s.id})" title="Sil"><i class="fas fa-trash-alt"></i></button>`
                        }
                        </div>
                    </td>
                </tr>`;

                    // Card Item (Mobile)
                    cardHtml += `
                    <div class="col-12">
                        <div class="card border shadow-none mb-0">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <div class="fw-bold fs-5 text-primary">${s.warehouse_name}</div>
                                        <div class="text-muted small">${s.created_at}</div>
                                    </div>
                                    <div>${statusBadge}</div>
                                </div>
                                <div class="row g-0 align-items-center bg-light rounded p-2 mb-3">
                                    <div class="col-6 border-end text-center">
                                        <div class="text-muted small">Sayılan Ürün</div>
                                        <div class="fw-bold text-info">${s.item_count} Kalem</div>
                                    </div>
                                    <div class="col-6 text-center">
                                        <div class="text-muted small">Personel</div>
                                        <div class="fw-bold">${s.creator_name || '—'}</div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    ${s.status === 'open'
                            ? `<button class="btn btn-primary w-100 py-2 fw-bold" onclick="continueSession(${s.id})"><i class="fas fa-play me-2"></i>Sayıma Devam</button>
                                           <button class="btn btn-outline-danger px-3" onclick="deleteSession(${s.id})"><i class="fas fa-trash-alt"></i></button>`
                            : `<button class="btn btn-info w-100 py-2 fw-bold" onclick="viewDetails(${s.id})"><i class="fas fa-eye me-2"></i>Sonuçları Gör</button>
                                           <button class="btn btn-outline-danger px-3" onclick="deleteSession(${s.id})"><i class="fas fa-trash-alt"></i></button>`
                        }
                                </div>
                            </div>
                        </div>
                    </div>`;
                });
            }
            $('#sessionListBody').html(listHtml);
            $('#sessionListCards').html(cardHtml);
        }).fail(function () {
            $('#sessionListBody').html('<tr><td colspan="5" class="text-center text-danger p-4"><i class="fas fa-exclamation-circle me-1"></i>Sunucu hatası oluştu.</td></tr>');
            $('#sessionListCards').html('<div class="col-12 text-center text-danger p-4"><i class="fas fa-exclamation-circle me-1"></i>Sunucu hatası oluştu.</div>');
        });
    }

    function startNewSession() {
        $.get('<?= BASE_URL ?>/api/warehouses.php', { action: 'active_list' }, function (r) {
            if (!r.success) return;
            let html = '<option value="">Seçiniz...</option>';
            $.each(r.data, function (i, w) {
                html += `<option value="${w.id}">${w.name}</option>`;
            });
            $('#warehouseSelect').html(html);
            $('#newSessionModal').modal('show');
        });
    }

    function submitNewSession() {
        let data = $('#sessionForm').serialize() + '&action=start_session' + '&csrf_token=' + getCsrfToken();
        $.post('<?= BASE_URL ?>/api/inventory.php', data, function (r) {
            if (r.success) {
                $('#newSessionModal').modal('hide');
                continueSession(r.data.session_id);
                loadSessions();
            } else showError(r.message);
        });
    }

    function continueSession(id) {
        currentSessionId = id;
        $('#barcodeInput').val('');
        $('#productDetail').hide();
        $('#recentCounts').html('<div class="list-group-item text-center p-4 text-muted small"><i class="fas fa-spinner fa-spin me-2"></i>Veriler alınıyor...</div>');
        loadRecentCounts();
        $('#countModal').modal('show');

        // Mobilde otomatik kamerayı başlat (Kullanıcı etkileşimi zinciri içinde)
        if (window.innerWidth < 768) {
            setTimeout(() => {
                startScanner();
            }, 300);
        } else {
            setTimeout(() => $('#barcodeInput').focus(), 500);
        }
    }

    function loadRecentCounts() {
        $.get('<?= BASE_URL ?>/api/inventory.php', { action: 'get_session_details', id: currentSessionId }, function (r) {
            if (!r.success) return;
            let html = '';
            if (r.data.length === 0) {
                html = '<div class="list-group-item text-center p-4 text-muted small">Henüz sayım yapılmadı.</div>';
            } else {
                $.each(r.data, function (i, item) {
                    html += `
                <div class="list-group-item d-flex justify-content-between align-items-center animate__animated animate__fadeIn">
                    <div style="flex: 1;">
                        <div class="fw-bold">${item.product_name}</div>
                        <div class="text-muted small">${item.product_code || 'Kodsuz'} | ${item.counted_at}</div>
                    </div>
                    <div class="text-end d-flex align-items-center">
                        <div class="me-3">
                            <span class="fs-5 fw-bold text-primary">${formatQty(item.counted_qty)}</span>
                            <div class="text-muted small">${item.unit}</div>
                        </div>
                        <button class="btn btn-link text-danger p-0" onclick="deleteInventoryItem(${item.id})" title="Sil" style="margin-left: 10px !important;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>`;
                });
            }
            $('#recentCounts').html(html);
        });
    }

    function deleteInventoryItem(id) {
        if (!confirm('Bu sayım satırını silmek istediğinize emin misiniz?')) return;
        $.post('<?= BASE_URL ?>/api/inventory.php', {
            action: 'delete_inventory_item',
            id: id,
            csrf_token: getCsrfToken()
        }, function (r) {
            if (r.success) {
                loadRecentCounts();
                loadSessions();
            } else showError(r.message);
        });
    }

    // Barkod Giriş Takibi
    $('#barcodeInput').on('keypress', function (e) {
        if (e.which === 13) {
            searchProduct($(this).val());
        }
    });

    // Miktar Giriş Takibi (Enter ile kaydetme)
    $('#countQty').on('keypress', function (e) {
        if (e.which === 13) {
            saveCount();
        }
    });

    function searchProduct(barcode) {
        if (!barcode) return;

        // Mobil klavyeyi zorlamak için AJAX öncesi ön-fokus
        $('#prodName').text('Aranıyor...');
        $('#productDetail').show();
        $('#countQty').focus();

        $.get('<?= BASE_URL ?>/api/inventory.php', { action: 'get_product_by_barcode', barcode: barcode }, function (r) {
            if (r.success) {
                $('#prodName').text(r.data.name);
                $('#prodCode').text(r.data.code || '—');
                $('#prodUnit').text(r.data.unit);
                $('#prodImage').attr('src', r.data.image ? '<?= BASE_URL ?>/images/UrunResim/' + r.data.image : '<?= BASE_URL ?>/assets/no-image.png');
                $('#productDetail').data('id', r.data.id);

                // Veri gelince tekrar fokuslan ve seç (Mobil klavye açık kalır)
                setTimeout(() => {
                    $('#countQty').val(1).focus().select();
                }, 50);
            } else {
                $('#productDetail').hide();
                showError('Ürün bulunamadı.');
                $('#barcodeInput').val('').focus();
            }
        });
    }

    function saveCount() {
        let prodId = $('#productDetail').data('id');
        let qty = $('#countQty').val();

        $.post('<?= BASE_URL ?>/api/inventory.php', {
            action: 'save_count',
            session_id: currentSessionId,
            product_id: prodId,
            qty: qty,
            csrf_token: getCsrfToken()
        }, function (r) {
            if (r.success) {
                $('#productDetail').hide();
                $('#barcodeInput').val('').focus();
                loadRecentCounts();

                // Kayıt sonrası mobilde otomatik olarak kamerayı tekrar aç
                if (window.innerWidth < 768) {
                    setTimeout(() => {
                        startScanner();
                    }, 50);
                }
            } else showError(r.message);
        });
    }

    // Scanner Functions
    function startScanner() {
        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            console.warn("Kamera erişimi güvenli olmayan bağlantılarda engellenebilir.");
        }

        $('#scanner-container').show();
        $('#btnStartScan').prop('disabled', true);

        if (html5QrCode) {
            html5QrCode.clear();
        }

        html5QrCode = new Html5Qrcode("interactive");

        const config = {
            fps: 24, // Hız için tekrar 24'e çıkarıldı
            qrbox: function (viewfinderWidth, viewfinderHeight) {
                let minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                let edgeSize = Math.floor(minEdge * 0.8);
                return { width: edgeSize, height: edgeSize };
            },
            aspectRatio: 1.0, // Kare oran hızlı okuma için kritiktir
            rememberLastUsedCamera: true
        };

        html5QrCode.start(
            { facingMode: "environment" },
            config,
            (decodedText) => {
                if (navigator.vibrate) navigator.vibrate(100);

                // KRİTİK: iOS'ta klavyenin açılması için fokus en başta, 
                // hiçbir asenkron işlem (stop/ajax) beklenmeden yapılmalı.
                $('#productDetail').show();
                $('#countQty').val(1).focus().select();

                // Arka planda kamerayı durdur ve veriyi ara
                stopScanner();
                $('#barcodeInput').val(decodedText);
                searchProduct(decodedText);
            }
        ).catch(err => {
            console.error(err);
            showError("Kamera başlatılamadı. Lütfen izinleri kontrol edin.");
            $('#scanner-container').hide();
            $('#btnStartScan').prop('disabled', false);
        });
    }

    function stopScanner() {
        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                $('#scanner-container').hide();
                $('#btnStartScan').prop('disabled', false);
            });
        } else {
            $('#scanner-container').hide();
            $('#btnStartScan').prop('disabled', false);
        }
    }

    function confirmCloseSession() {
        confirmAction('Bu sayım oturumunu kapatmak istediğinize emin misiniz? Kapattıktan sonra ekleme yapılamaz.', function () {
            $.post('<?= BASE_URL ?>/api/inventory.php', {
                action: 'close_session',
                id: currentSessionId,
                csrf_token: getCsrfToken()
            }, function (r) {
                if (r.success) {
                    $('#countModal').modal('hide');
                    loadSessions();
                    showSuccess('Sayım başarıyla tamamlandı.');
                }
            });
        });
    }

    function viewDetails(id) {
        // Gelecek adımda eklenebilir: Karşılaştırma raporu sayfası
        window.location.href = '<?= BASE_URL ?>/index.php?page=inventory_report&id=' + id;
    }

    function deleteSession(id) {
        confirmAction('Bu sayım oturumunu ve içindeki tüm verileri silmek istediğinize emin misiniz?', function () {
            $.post('<?= BASE_URL ?>/api/inventory.php', {
                action: 'delete_session',
                id: id,
                csrf_token: getCsrfToken()
            }, function (r) {
                if (r.success) {
                    showSuccess(r.message);
                    loadSessions();
                } else showError(r.message);
            });
        });
    }

    // Modal kapandığında scanner'ı durdur
    $('#countModal').on('hidden.bs.modal', function () {
        stopScanner();
    });
</script>

<style>
    .card-header {
        display: block !important;
    }

    .card-header .card-title {
        font-size: 1.5rem !important;
        font-weight: 700;
        margin: 0;
        float: left;
    }

    .card-header .card-tools {
        float: right;
    }

    /* Mobil Görünüm (768px altı) */
    @media (max-width: 767.98px) {
        .card-header .card-title {
            float: none;
            text-align: center;
            margin-bottom: 10px;
        }

        .card-header .card-tools {
            float: none;
            text-align: center;
        }

        #btn-new-session {
            width: 100% !important;
            padding: 12px !important;
            font-size: 1rem !important;
        }
    }

    /* Masaüstü Görünüm (768px ve üstü) */
    @media (min-width: 768px) {
        #btn-new-session {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            display: inline-block;
        }
    }

    @media (max-width: 768px) {

        .table td,
        .table th {
            padding: 0.5rem !important;
            font-size: 0.85rem;
        }

        .card-title {
            font-size: 1.1rem !important;
        }
    }

    #interactive video {
        width: 100% !important;
        height: auto !important;
        object-fit: cover !important;
        border-radius: var(--radius-md);
    }

    #interactive canvas {
        display: none !important;
    }

    /* html5-qrcode shading layers fix */
    #interactive>div {
        background: rgba(0, 0, 0, 0.5) !important;
    }

    #interactive>div>div {
        border-color: rgba(255, 255, 255, 0.3) !important;
    }

    .object-fit-cover {
        object-fit: cover;
    }

    .animate__animated {
        animation-duration: 0.4s;
    }
</style>