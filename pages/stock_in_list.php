<?php
/**
 * Stok Giriş Listesi — Tüm giriş kayıtlarını listeler
 */
requireRole(ROLE_ADMIN, ROLE_USER);
$warehouses = Database::fetchAll("
    SELECT w.id, w.name, 
    (SELECT COUNT(*) FROM inventory_sessions WHERE warehouse_id = w.id AND status = 'open') > 0 as is_inventory_open 
    FROM tbl_dp_warehouses w 
    WHERE w.hidden=0 AND w.is_active=1 
    ORDER BY w.name
");
?>

<style>
  /* Select2 z-index fix for overlapping modals */
  .select2-container--open {
    z-index: 1080 !important;
  }

  /* Modal z-index fixes for nested modals */
  #quickProductModal,
  #quickSupplierModal {
    z-index: 2000 !important;
  }


  #q_cropModal {
    z-index: 2100 !important;
  }

  /* Ürün Durum Butonları */
  .status-btn-group {
    display: flex;
    gap: 5px;
  }

  .status-btn-item {
    flex: 1;
    padding: 0.375rem 0.625rem;
    border: 1px solid #dee2e6;
    background: #fff;
    font-size: 0.75rem;
    font-weight: bold;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.2s;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .status-btn-item.active-state {
    background: #dcfce7;
    color: #166534;
    border-color: #86efac;
  }

  .status-btn-item.inactive-state {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fca5a5;
  }

  /* ───────────────────────────────────────────
     KART HEADER — araç çubuğu hizalama
  ─────────────────────────────────────────── */
  /* ─── HEADER ARAÇ ÇUBUĞU ─── */
  .card-header .card-tools {
    align-items: center;
    gap: 10px;
  }

  /* Tüm header araçlarını aynı yüksekliğe sabitle - ARTIK GLOBALDEN GELİYOR ANCAK ÖZEL DURUM VARSA BURADA KALABİLİR */
  .card-header .card-tools .form-select-sm,
  .card-header .card-tools .input-group-sm .form-control,
  .card-header .card-tools .input-group-sm .input-group-text,
  .card-header .card-tools .btn-sm {
    height: 2rem;
    line-height: 1;
    font-size: 0.8125rem;
    padding-top: 0;
    padding-bottom: 0;
    box-sizing: border-box;
  }

  /* input-group-text (büyüteç) */
  .card-header .card-tools .input-group-sm .input-group-text {
    background: #f4f6f9;
    border-color: #ced4da;
    color: #6c757d;
    padding: 0 10px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  /* Buton dikey ortalama */
  .card-header .card-tools .btn-sm {
    display: inline-flex;
    align-items: center;
  }

  /* Card footer flex düzeni */
  .card-footer.clearfix {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .card-footer .float-start,
  .card-footer .float-end {
    float: none !important;
  }

  /* ───────────────────────────────────────────
     GENEL TABLO & KART
  ─────────────────────────────────────────── */
  .select2-product-img {
    width: 1.625rem;
    height: 1.625rem;
    object-fit: cover;
    border-radius: var(--radius-sm);
    margin-right: 6px;
    vertical-align: middle;
    border: 1px solid #e2e8f0;
  }

  /* ───────────────────────────────────────────
     MODAL GENEL
  ─────────────────────────────────────────── */
  #addStockModal .modal-dialog,
  #editModal .modal-dialog {
    max-width: 860px;
  }

  #addStockModal .modal-content,
  #editModal .modal-content,
  #viewModal .modal-content {
    border: none;
    border-radius: var(--radius-xl);
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
  }

  /* Header */
  #addStockModal .modal-header {
    background: linear-gradient(135deg, #1a56db 0%, #0c3daa 100%);
    padding: 20px 28px;
    border-bottom: none;
  }

  #editModal .modal-header {
    background: linear-gradient(135deg, #0e9f6e 0%, #057a55 100%);
    padding: 20px 28px;
    border-bottom: none;
  }

  #viewModal .modal-header {
    background: linear-gradient(135deg, #6b7280 0%, #374151 100%);
    padding: 20px 28px;
    border-bottom: none;
  }

  .card-header .card-title {
    font-size: 1.5rem;
    display: flex;
    align-items: center;
  }

  .card-header .card-title i {
    font-size: 1.25rem;
    margin-right: 0.75rem;
  }

  /* Sayfa tepesindeki boşluk */
  .stock-in-row {}

  #addStockModal .modal-title,
  #editModal .modal-title,
  #viewModal .modal-title {
    font-size: 1.05rem;
    font-weight: 600;
    letter-spacing: 0.01em;
  }

  /* Body */
  #addStockModal .modal-body,
  #editModal .modal-body {
    padding: 28px 32px 12px;
    background: #f8fafd;
  }

  #viewModal .modal-body {
    padding: 28px 32px;
    background: #f8fafd;
  }

  /* Footer */
  #addStockModal .modal-footer,
  #editModal .modal-footer,
  #viewModal .modal-footer {
    padding: 16px 32px 20px;
    background: #f8fafd;
    border-top: 1px solid #e4e9f0;
  }

  /* Bölüm başlıkları */
  .modal-section-label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #6b7a99;
    margin-bottom: 14px;
    padding-bottom: 6px;
    border-bottom: 2px solid #e4e9f0;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  /* Modal butonları */
  .btn-modal-cancel {
    background: transparent;
    border: 1.5px solid #c9d3e0;
    color: #4a5568;
    border-radius: var(--radius-md);
    padding: 9px 22px;
    font-size: 0.87rem;
    font-weight: 500;
    transition: all 0.2s;
  }

  .btn-modal-cancel:hover {
    background: #f0f4f9;
    border-color: #a0aec0;
    color: #1f2937;
  }

  .btn-modal-save {
    background: linear-gradient(135deg, #1a56db, #0c3daa);
    border: none;
    color: #fff;
    border-radius: var(--radius-md);
    padding: 9px 32px;
    font-size: 0.87rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    box-shadow: 0 4px 12px rgba(26, 86, 219, 0.3);
    transition: all 0.2s;
  }

  .btn-modal-save:hover {
    background: linear-gradient(135deg, #1d4ed8, #0a35a0);
    box-shadow: 0 6px 16px rgba(26, 86, 219, 0.38);
    transform: translateY(-1px);
    color: #fff;
  }

  .btn-modal-update {
    background: linear-gradient(135deg, #0e9f6e, #057a55);
    border: none;
    color: #fff;
    border-radius: var(--radius-md);
    padding: 9px 32px;
    font-size: 0.87rem;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(14, 159, 110, 0.3);
    transition: all 0.2s;
  }

  .btn-modal-update:hover {
    background: linear-gradient(135deg, #057a55, #046c4e);
    box-shadow: 0 6px 16px rgba(14, 159, 110, 0.38);
    transform: translateY(-1px);
    color: #fff;
  }

  /* View modal — edit ile aynı görünüm, sadece disabled */
  #viewModal .modal-dialog {
    max-width: 860px;
  }

  #viewModal .modal-body {
    padding: 28px 32px 12px;
    background: #f8fafd;
  }

  #viewModal .form-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
    display: block;
  }

  #viewModal .form-control:disabled,
  #viewModal .form-select:disabled {
    border: 1.5px solid #e4e9f0;
    border-radius: var(--radius-md);
    padding: 9px 13px;
    font-size: 0.88rem;
    color: #374151;
    background: #f0f4f9;
    opacity: 1;
    cursor: default;
  }

  #viewModal .input-icon-wrap .form-control:disabled,
  #viewModal .input-icon-wrap .form-select:disabled {
    padding-left: 32px;
  }

  #viewModal .input-icon-wrap .field-icon {
    color: #c2cfe0;
  }

  #viewModal .qty-group .unit-badge {
    background: #e8edf5;
    border: 1.5px solid #e4e9f0;
    border-left: none;
    border-radius: 0 var(--radius-md) var(--radius-md) 0;
    padding: 0 14px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #6b7a99;
    display: flex;
    align-items: center;
    white-space: nowrap;
    min-width: 56px;
    justify-content: center;
  }

  #viewModal .qty-group .form-control:disabled {
    border-radius: var(--radius-md) 0 0 var(--radius-md);
  }

  #viewModal textarea.form-control:disabled {
    min-height: 72px;
    resize: none;
  }

  .price-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }

  /* Responsive */
  @media (max-width: 768px) {

    #addStockModal .modal-body,
    #editModal .modal-body {
      padding: 20px 18px 8px;
    }

    .price-row {
      grid-template-columns: 1fr;
    }
  }
</style>

<!-- ═══════════════════════════════════════════════
     ANA SAYFA
═══════════════════════════════════════════════ -->
<div class="row stock-in-row">
  <div class="col-12">
    <div class="card card-primary card-outline">
      <div class="card-header">
        <h3 class="card-title text-bold"><i class="fas fa-clipboard-list me-2"></i> Ürün Giriş Listesi</h3>
        <div class="card-tools d-flex">
          <select id="perPage" class="form-select form-select-sm me-2" style="width:auto">
            <option value="10" selected>10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <select id="warehouseFilter" class="form-select form-select-sm" style="width:auto">
            <option value="">Tüm Depolar</option>
            <?php foreach ($warehouses as $w): ?>
              <option value="<?= e($w['id']) ?>" <?= count($warehouses) === 1 ? 'selected' : '' ?>>
                <?= e($w['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="input-group input-group-sm" style="width:200px">
            <input type="text" id="searchBox" class="form-control" placeholder="Ara...">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
          </div>
          <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal"
            data-bs-target="#addStockModal">
            <i class="fas fa-plus me-1"></i> Yeni Giriş
          </button>
        </div>
      </div>

      <div class="card-body p-0 table-responsive">
        <table class="table table-hover table-striped m-0 table-valign-middle">
          <thead class="bg-light">
            <tr>
              <th>Ürün</th>
              <th>Depo</th>
              <th>Tedarikçi</th>
              <th class="num-align">Adet</th>
              <th class="num-align">Birim Fiyat</th>
              <th class="num-align"><?= getCurrencySymbol() ?> Fiyat</th>
              <th style="width:100px" class="num-align">Tarih</th>
              <th style="width:120px">İşlemi Yapan</th>
              <th style="width:100px" class="text-center pe-3">İşlem</th>
            </tr>
          </thead>
          <tbody id="tableBody"></tbody>
        </table>
      </div>

      <div class="card-footer clearfix">
        <div class="float-start">
          <span id="totalCount" class="text-muted small"></span>
        </div>
        <div id="pagination" class="float-end m-0"></div>
      </div>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════════
     GÖRÜNTÜLEME MODAL
═══════════════════════════════════════════════ -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-modal="true" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title text-white">
          <i class="fas fa-eye me-2 opacity-75"></i>Giriş Kaydı Detayı
        </h5>
        <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
            class="fas fa-times"></i></button>
      </div>

      <div class="modal-body">

        <!-- Konum & Ürün -->
        <div class="modal-section-label">
          <i class="fas fa-map-marker-alt"></i> Konum &amp; Ürün
        </div>
        <div class="row g-3 mb-1">
          <div class="col-md-6">
            <label class="form-label">Depo</label>
            <div class="input-icon-wrap">
              <i class="fas fa-warehouse field-icon"></i>
              <input type="text" id="vWarehouse" class="form-control" disabled>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Ürün</label>
            <div class="input-icon-wrap">
              <i class="fas fa-box field-icon"></i>
              <input type="text" id="vProduct" class="form-control" disabled>
            </div>
          </div>
        </div>

        <!-- Miktar & Tedarikçi -->
        <div class="modal-section-label mt-3">
          <i class="fas fa-boxes"></i> Miktar &amp; Tedarikçi
        </div>
        <div class="row g-3 mb-1">
          <div class="col-md-6">
            <label class="form-label">Adet</label>
            <div class="qty-group">
              <input type="text" id="vQty" class="form-control num-align" disabled>
              <span class="unit-badge" id="vUnit">Adet</span>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Tedarikçi</label>
            <div class="input-icon-wrap">
              <i class="fas fa-truck field-icon"></i>
              <input type="text" id="vSupplier" class="form-control" disabled>
            </div>
          </div>
        </div>

        <!-- Fiyatlandırma -->
        <div class="modal-section-label mt-3">
          <i class="fas fa-tag"></i> Fiyatlandırma
        </div>
        <div class="row g-3 mb-1">
          <div class="col-12">
            <div class="price-row">
              <div>
                <label class="form-label">Birim Fiyat</label>
                <div class="input-icon-wrap">
                  <i class="fas fa-lira-sign field-icon"></i>
                  <input type="text" id="vPrice" class="form-control num-align" disabled>
                </div>
              </div>
              <div>
                <label class="form-label">Para Birimi</label>
                <div class="input-icon-wrap">
                  <i class="fas fa-coins field-icon"></i>
                  <input type="text" id="vCurrency" class="form-control" disabled>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="row g-3 mb-1 mt-1">
          <div class="col-md-6">
            <label class="form-label"><?= e(get_setting('base_currency', 'EUR')) ?> Karşılığı</label>
            <div class="input-icon-wrap">
              <i class="fas fa-coins field-icon"></i>
              <input type="text" id="vEur" class="form-control num-align" disabled>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">İşlem Tarihi</label>
            <div class="input-icon-wrap">
              <i class="fas fa-calendar-alt field-icon"></i>
              <input type="text" id="vDate" class="form-control" disabled>
            </div>
          </div>
        </div>
        <div class="row g-3 mb-1 mt-1">
          <div class="col-md-6">
            <label class="form-label">İşlemi Yapan</label>
            <div class="input-icon-wrap">
              <i class="fas fa-user field-icon"></i>
              <input type="text" id="vUser" class="form-control" disabled>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Son Güncelleyen</label>
            <div class="input-icon-wrap">
              <i class="fas fa-user-edit field-icon"></i>
              <input type="text" id="vUpdatedUser" class="form-control" disabled>
            </div>
          </div>
        </div>

        <!-- Not -->
        <div class="modal-section-label mt-3">
          <i class="fas fa-comment-dots"></i> Not
        </div>
        <div class="row g-3">
          <div class="col-12">
            <textarea id="vNote" class="form-control" rows="2" disabled></textarea>
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i> Kapat
        </button>
      </div>

    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════════
     YENİ GİRİŞ MODAL
═══════════════════════════════════════════════ -->
<div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-modal="true"
  role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title text-white" id="addStockModalLabel">
          <i class="fas fa-plus-circle me-2 opacity-75"></i>Yeni Stok Girişi
        </h5>
        <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
            class="fas fa-times"></i></button>
      </div>

      <div class="modal-body">
        <form id="formStockIn" autocomplete="off">

          <!-- Konum & Ürün -->
          <div class="modal-section-label">
            <i class="fas fa-map-marker-alt"></i> Konum &amp; Ürün
          </div>
          <div class="row g-3 mb-1">
            <div class="col-md-6">
              <label class="form-label">Depo <span class="req">*</span></label>
              <div class="input-icon-wrap">
                <i class="fas fa-warehouse field-icon"></i>
                <select name="warehouse_id" id="warehouseSelect" class="form-select" required>
                  <option value="">— Depo Seçin —</option>
                  <?php foreach ($warehouses as $w): ?>
                    <option value="<?= e($w['id']) ?>" <?= count($warehouses) === 1 && !$w['is_inventory_open'] ? 'selected' : '' ?>   <?= $w['is_inventory_open'] ? 'disabled style="color:red"' : '' ?>>
                      <?= e($w['name']) ?>   <?= $w['is_inventory_open'] ? ' (SAYIM DEVAM EDİYOR)' : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label mb-0">Ürün <span class="req">*</span></label>
                <a href="javascript:void(0)" onclick="openQuickProductModal()"
                  class="text-primary small fw-bold text-decoration-none">
                  <i class="fas fa-plus-circle me-1"></i>Yeni Ürün
                </a>
              </div>
              <div class="input-icon-wrap">
                <i class="fas fa-box field-icon"></i>
                <select name="product_id" id="productSelect" class="form-select" required disabled>
                  <option value="">— Ürün arayın... —</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Miktar & Tedarikçi -->
          <div class="modal-section-label mt-3">
            <i class="fas fa-boxes"></i> Miktar &amp; Tedarikçi
          </div>
          <div class="row g-3 mb-1">
            <div class="col-md-6">
              <label class="form-label">Adet <span class="req">*</span></label>
              <div class="qty-group">
                <input type="number" name="quantity" id="quantity" class="form-control" min="1" step="1"
                  oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                  placeholder="0" required disabled>
                <span class="unit-badge" id="unitLabel">Adet</span>
              </div>
            </div>
            <div class="col-md-6">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label mb-0">Tedarikçi</label>
                <a href="javascript:void(0)" onclick="openQuickSupplierModal()"
                  class="text-primary small fw-bold text-decoration-none">
                  <i class="fas fa-plus-circle me-1"></i>Yeni Tedarikçi
                </a>
              </div>
              <div class="input-icon-wrap">
                <i class="fas fa-truck field-icon"></i>
                <select name="supplier_id" id="supplierSelect" class="form-select" disabled>
                  <option value="">— Tedarikçi Seçin —</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Fiyatlandırma -->
          <div class="modal-section-label mt-3">
            <i class="fas fa-tag"></i> Fiyatlandırma
          </div>
          <div class="row g-3 mb-1">
            <div class="col-12">
              <div class="price-row">
                <div>
                  <label class="form-label">Birim Fiyat</label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-lira-sign field-icon"></i>
                    <input type="text" name="unit_price" id="unitPrice" class="form-control price-format"
                      placeholder="0,00" disabled>
                  </div>
                </div>
                <div>
                  <label class="form-label">Para Birimi <span class="req">*</span></label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-coins field-icon"></i>
                    <select name="currency" id="currency" class="form-select" required disabled>
                      <option value="">—</option>
                      <option value="TL">TL</option>
                      <option value="USD">USD</option>
                      <option value="EUR">EUR</option>
                    </select>
                  </div>
                </div>
              </div>
              <div id="conversionNote" class="conversion-note" style="display:none">
                <i class="fas fa-sync-alt fa-spin"></i>
                <span id="conversionNoteText"></span>
              </div>
            </div>
          </div>

          <!-- Not -->
          <div class="modal-section-label mt-3">
            <i class="fas fa-comment-dots"></i> Not
          </div>
          <div class="row g-3">
            <div class="col-12">
              <textarea name="note" id="note" class="form-control" rows="2"
                placeholder="İsteğe bağlı açıklama girebilirsiniz..." disabled></textarea>
            </div>
          </div>

        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i> Vazgeç
        </button>
        <button type="submit" form="formStockIn" class="btn btn-modal-save">
          <i class="fas fa-save me-1"></i> Girişi Kaydet
        </button>
      </div>

    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════════
     DÜZENLEME MODAL
═══════════════════════════════════════════════ -->
<div class="modal fade" id="editModal" tabindex="-1" aria-modal="true" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title text-white">
          <i class="fas fa-edit me-2 opacity-75"></i>Girişi Düzenle
        </h5>
        <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
            class="fas fa-times"></i></button>
      </div>

      <div class="modal-body">
        <form id="editForm" autocomplete="off">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" id="editId">

          <!-- Konum & Ürün -->
          <div class="modal-section-label">
            <i class="fas fa-map-marker-alt"></i> Konum &amp; Ürün
          </div>
          <div class="row g-3 mb-1">
            <div class="col-md-6">
              <label class="form-label">Depo <span class="req">*</span></label>
              <div class="input-icon-wrap">
                <i class="fas fa-warehouse field-icon"></i>
                <select name="warehouse_id" id="editWarehouse" class="form-select" required>
                  <option value="">— Depo Seçin —</option>
                  <?php foreach ($warehouses as $w): ?>
                    <option value="<?= e($w['id']) ?>" <?= $w['is_inventory_open'] ? 'disabled' : '' ?>>
                      <?= e($w['name']) ?>   <?= $w['is_inventory_open'] ? ' (SAYIM DEVAM EDİYOR)' : '' ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label mb-0">Ürün <span class="req">*</span></label>
                <a href="javascript:void(0)" onclick="openQuickProductModal()"
                  class="text-primary small fw-bold text-decoration-none">
                  <i class="fas fa-plus-circle me-1"></i>Yeni Ürün
                </a>
              </div>
              <div class="input-icon-wrap">
                <i class="fas fa-box field-icon"></i>
                <select name="product_id" id="editProduct" class="form-select" required>
                  <option value="">— Ürün —</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Miktar & Tedarikçi -->
          <div class="modal-section-label mt-3">
            <i class="fas fa-boxes"></i> Miktar &amp; Tedarikçi
          </div>
          <div class="row g-3 mb-1">
            <div class="col-md-6">
              <label class="form-label">Adet <span class="req">*</span></label>
              <div class="qty-group">
                <input type="number" name="quantity" id="editQty" class="form-control" min="1" step="1"
                  oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                  placeholder="0" required>
                <span class="unit-badge" id="editUnitLabel">Adet</span>
              </div>
            </div>
            <div class="col-md-6">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label mb-0">Tedarikçi</label>
                <a href="javascript:void(0)" onclick="openQuickSupplierModal()"
                  class="text-primary small fw-bold text-decoration-none">
                  <i class="fas fa-plus-circle me-1"></i>Yeni Tedarikçi
                </a>
              </div>
              <div class="input-icon-wrap">
                <i class="fas fa-truck field-icon"></i>
                <select name="supplier_id" id="editSupplier" class="form-select">
                  <option value="">— Tedarikçi Seçin —</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Fiyatlandırma -->
          <div class="modal-section-label mt-3">
            <i class="fas fa-tag"></i> Fiyatlandırma
          </div>
          <div class="row g-3 mb-1">
            <div class="col-12">
              <div class="price-row">
                <div>
                  <label class="form-label">Birim Fiyat</label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-lira-sign field-icon"></i>
                    <input type="text" name="unit_price" id="editPrice" class="form-control price-format"
                      placeholder="0,00">
                  </div>
                </div>
                <div>
                  <label class="form-label">Para Birimi <span class="req">*</span></label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-coins field-icon"></i>
                    <select name="currency" id="editCurrency" class="form-select" required>
                      <option value="">—</option>
                      <option value="TL">TL</option>
                      <option value="USD">USD</option>
                      <option value="EUR">EUR</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Not -->
          <div class="modal-section-label mt-3">
            <i class="fas fa-comment-dots"></i> Not
          </div>
          <div class="row g-3">
            <div class="col-12">
              <textarea name="note" id="editNote" class="form-control" rows="2"
                placeholder="İsteğe bağlı açıklama..."></textarea>
            </div>
          </div>

        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-modal-cancel" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i> Vazgeç
        </button>
        <button type="button" id="btnEditSave" class="btn btn-modal-update">
          <i class="fas fa-check me-1"></i> Güncelle
        </button>
      </div>

    </div>
  </div>
</div>


<!-- Quick Product Add Modal -->
<div class="modal fade" id="quickProductModal" tabindex="-1" style="z-index: 2000;">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header bg-success text-white py-3">
        <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i> Yeni Ürün Ekle</h5>
        <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
            class="fas fa-times"></i></button>
      </div>
      <div class="modal-body p-4 bg-light">
        <form id="quickProductForm" enctype="multipart/form-data">
          <input type="hidden" name="action" value="add">
          <div class="row">
            <div class="col-md-4 text-center">
              <div class="mb-2">
                <img id="q_previewImg" src="<?= BASE_URL ?>/assets/no-image.png" alt="Ürün Resmi"
                  style="width:150px;height:150px;object-fit:cover;border-radius:10px;border:2px solid #dee2e6;">
              </div>
              <label class="form-label fw-bold small text-uppercase">Ürün Resmi</label>
              <input type="file" name="image" id="q_imageInput" class="form-control" accept="image/*">
              <small class="text-muted">Maks. 5MB — jpg, png, webp</small>
            </div>
            <div class="col-md-8">
              <div class="row g-3">
                <div class="col-md-12">
                  <label class="form-label fw-bold small text-uppercase">Ürün Adı <span
                      class="text-danger">*</span></label>
                  <input type="text" name="name" class="form-control form-control-lg border-0 shadow-sm" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-uppercase">Birim</label>
                  <select name="unit" id="q_unit" class="form-select form-select-lg border-0 shadow-sm">
                    <option value="Adet">Adet</option>
                    <option value="Kg">Kg</option>
                    <option value="Litre">Litre</option>
                    <option value="Metre">Metre</option>
                    <option value="Kutu">Kutu</option>
                    <option value="Paket">Paket</option>
                    <option value="Ton">Ton</option>
                    <option value="Set">Set</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-uppercase">Ürün Kodu</label>
                  <input type="text" name="code" class="form-control form-control-lg border-0 shadow-sm"
                    placeholder="SKU-001">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-bold small text-uppercase">Alarm Seviyesi (Stok Az) <i
                      class="fas fa-bell text-warning ms-1"></i></label>
                  <input type="number" name="stock_alarm" class="form-control form-control-lg border-0 shadow-sm"
                    value="0" min="0">
                </div>
                <div class="col-md-6 text-start">
                  <label class="form-label fw-bold small text-uppercase d-block mb-1">Durum</label>
                  <input type="hidden" name="is_active" id="q_is_active_input" value="1">
                  <div class="status-btn-group">
                    <button type="button" class="status-btn-item" id="q_set_active" onclick="setQuickProductStatus(1)">
                      <i class="fas fa-check-circle me-1"></i> AKTİF
                    </button>
                    <button type="button" class="status-btn-item" id="q_set_inactive"
                      onclick="setQuickProductStatus(0)">
                      <i class="fas fa-times-circle me-1"></i> PASİF
                    </button>
                  </div>
                </div>
                <div class="col-md-12">
                  <label class="form-label fw-bold small text-uppercase">Açıklama</label>
                  <textarea name="description" class="form-control form-control-lg border-0 shadow-sm"
                    rows="3"></textarea>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0 p-4 bg-light">
        <button type="button" class="btn-modal-cancel bg-white border" data-bs-dismiss="modal">Vazgeç</button>
        <button type="button" class="btn btn-success btn-lg px-4 fw-bold text-white shadow-sm" id="btnSaveQuickProduct">
          <i class="fas fa-save me-1"></i> Ürünü Kaydet
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Quick Image Crop Modal -->
<div class="modal fade" id="q_cropModal" data-bs-backdrop="static" tabindex="-1" style="z-index: 2010;">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-crop-alt me-2"></i>Görseli Düzenle</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div class="img-container" style="max-height: 500px;">
          <img id="q_cropImage" src="" style="max-width: 100%;">
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnDoQuickProductCrop">
          <i class="fas fa-check me-1"></i>Kırp ve Uygula
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Quick Supplier Add Modal -->
<div class="modal fade" id="quickSupplierModal" tabindex="-1" style="z-index: 2000;">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i> Yeni Tedarikçi Ekle</h5>
        <button type="button" class="btn btn-link text-white p-0 border-0" data-bs-dismiss="modal"><i
            class="fas fa-times"></i></button>
      </div>
      <div class="modal-body p-4 bg-light">
        <form id="quickSupplierForm">
          <input type="hidden" name="action" value="add">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-bold small text-uppercase">Firma Adı <span
                  class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control form-control-lg border-0 shadow-sm" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small text-uppercase">Yetkili Kişi</label>
              <input type="text" name="contact" class="form-control form-control-lg border-0 shadow-sm">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small text-uppercase">E-posta</label>
              <input type="email" name="email" class="form-control form-control-lg border-0 shadow-sm">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold small text-uppercase">Telefon</label>
              <input type="text" name="phone" class="form-control form-control-lg border-0 shadow-sm phone-format"
                placeholder="(5xx) xxx xx xx" maxlength="15">
            </div>
            <div class="col-md-12">
              <label class="form-label fw-bold small text-uppercase">Adres</label>
              <textarea name="address" class="form-control form-control-lg border-0 shadow-sm" rows="3"></textarea>
            </div>
            <input type="hidden" name="is_active" value="1">
          </div>
        </form>
      </div>
      <div class="modal-footer border-0 p-4 bg-light">
        <button type="button" class="btn-modal-cancel bg-white border" data-bs-dismiss="modal">Vazgeç</button>
        <button type="button" class="btn btn-primary btn-lg px-4 fw-bold text-white shadow-sm"
          id="btnSaveQuickSupplier">
          <i class="fas fa-save me-1"></i> Tedarikçiyi Kaydet
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════ -->
<script>
  var curPage = 1, curPerPage = 10, curSearch = '', searchTimer, warehouseFilter = 0;
  var apiUrl = '<?= BASE_URL ?>/api/stock_in.php';
  var eurRate = <?= (float) get_setting('eur_rate', '0') ?>;
  var isSingleWarehouse = <?= count($warehouses) === 1 ? 'true' : 'false' ?>;

  function esc(v) { return $('<span>').text(v || '').html(); }

  /* ─── Select2 Başlatma ─── */
  function initSelect2() {
    $('#warehouseSelect').select2({
      theme: 'bootstrap-5', dropdownParent: $('#addStockModal'), width: '100%',
      templateResult: function (data) {
        if (!data.id) return data.text;
        if (data.text.indexOf('(SAYIM DEVAM EDİYOR)') !== -1) {
          return $('<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> ' + data.text + '</span>');
        }
        return data.text;
      }
    });
    $('#currency').select2({
      theme: 'bootstrap-5', dropdownParent: $('#addStockModal'), width: '100%'
    });
    $('#editWarehouse').select2({
      theme: 'bootstrap-5', dropdownParent: $('#editModal'), width: '100%',
      templateResult: function (data) {
        if (!data.id) return data.text;
        if (data.text.indexOf('(SAYIM DEVAM EDİYOR)') !== -1) {
          return $('<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> ' + data.text + '</span>');
        }
        return data.text;
      }
    });
    $('#editCurrency').select2({
      theme: 'bootstrap-5', dropdownParent: $('#editModal'), width: '100%'
    });

    var productAjax = {
      url: '<?= BASE_URL ?>/api/products.php',
      data: function (p) { return { action: 'search_select2', q: p.term || '' }; },
      processResults: function (d) { return { results: d.results }; },
      delay: 300
    };
    var productTemplate = function (i) {
      if (i.loading) return i.text;
      var no = '<?= BASE_URL ?>/assets/no-image.png';
      var img = i.image ? '<?= BASE_URL ?>/images/UrunResim/' + i.image : no;
      return $('<span><img src="' + img + '" class="select2-product-img" onerror="this.src=\'' + no + '\'"> '
        + $('<span>').text(i.text).html() + '</span>');
    };

    $('#productSelect').select2({
      theme: 'bootstrap-5', dropdownParent: $('#addStockModal'), width: '100%',
      ajax: productAjax, templateResult: productTemplate
    });

    $('#editProduct').select2({
      theme: 'bootstrap-5', placeholder: '— Ürün —', width: '100%', dropdownParent: $('#editModal'),
      ajax: productAjax, templateResult: productTemplate
    });

    /* Tedarikçi listesi — her iki modala yükle */
    $.get('<?= BASE_URL ?>/api/suppliers.php', { action: 'active_list' }, function (r) {
      if (!r.success) return;
      var opts = '';
      $.each(r.data, function (i, s) { opts += '<option value="' + s.id + '">' + esc(s.name) + '</option>'; });
      $('#supplierSelect, #editSupplier').append(opts);
      $('#supplierSelect').select2({
        theme: 'bootstrap-5', placeholder: '—', allowClear: true,
        dropdownParent: $('#addStockModal'), width: '100%'
      });
      $('#editSupplier').select2({
        theme: 'bootstrap-5', placeholder: '—', allowClear: true,
        dropdownParent: $('#editModal'), width: '100%'
      });
    }, 'json');

    /* Modal açıldığında depoya odaklan */
    $('#addStockModal').on('shown.bs.modal', function () {
      $('#warehouseSelect').select2('open');
      // Eğer depo otomatik seçili ise (tek depo), diğer alanları aktif etmek için tetikle
      if ($('#warehouseSelect').val()) {
        $('#warehouseSelect').trigger('change');
      }
    });
  }

  /* ─── Ürün seçilince birimi güncelle ve miktara odaklan ─── */
  $('#productSelect').on('select2:select change', function (e) {
    var unit = $(this).find(':selected').data('unit') || 'Adet';
    $('#unitLabel').text(unit);

    $('#quantity').prop('disabled', false); // Miktarı aktif et
    $(this).select2('close');
    setTimeout(() => { $('#quantity').focus().select(); }, 50);
  });

  /* ─── Depo seçilince diğer alanları aktif et ve ürünü aç ─── */
  $('#warehouseSelect').on('change', function () {
    var val = $(this).val();
    var selects = $('#productSelect, #supplierSelect, #currency');
    var others = $('#quantity, #unitPrice, #note');

    if (val) {
      selects.prop('disabled', false).trigger('change');
      others.prop('disabled', false);
      setTimeout(() => { $('#productSelect').select2('open'); }, 50);
    } else {
      selects.prop('disabled', true).trigger('change');
      others.prop('disabled', true);
    }
  });

  $('#warehouseSelect').on('select2:select', function () {
    $(this).select2('close');
  });

  /* ─── Miktar girilince tedarikçiyi aç ─── */
  $('#quantity').on('keydown', function (e) {
    if (e.which == 13) {
      e.preventDefault();
      $('#supplierSelect').select2('open');
    }
  });

  /* ─── Tedarikçi seçilince birim fiyata odaklan ─── */
  $('#supplierSelect').on('select2:select', function () {
    $(this).select2('close');
    $('#unitPrice, #currency').prop('disabled', false); // Alanları aktif et
    setTimeout(() => { $('#unitPrice').focus().select(); }, 50);
  });

  /* ─── Birim fiyat girilince para birimini aç ─── */
  $('#unitPrice').on('keydown', function (e) {
    if (e.which == 13) {
      e.preventDefault();
      $('#currency').select2('open');
    }
  });

  /* ─── Para birimi seçilince nota odaklan ─── */
  $('#currency').on('select2:select', function () {
    $(this).select2('close');
    setTimeout(() => { $('#note').focus(); }, 50);
  });

  /* Düzenleme modunda birimi güncelle */
  $('#editProduct').on('select2:select', function (e) {
    $('#editUnitLabel').text(e.params.data.unit || 'Adet');
  });

  /* ─── Para birimi değişince EUR notu ─── */
  $('#currency').on('change', function () {
    var cur = $(this).val();
    var baseCurrency = '<?= get_setting('base_currency', 'EUR') ?>';
    if ((cur === 'TL' || cur === 'USD') && eurRate > 0) {
      $('#conversionNoteText').text(cur + ' → ' + baseCurrency + ' dönüşümü otomatik yapılacak (1 ' + baseCurrency + ' = ' + formatTurkish(eurRate.toFixed(2)) + ' TL)');
      $('#conversionNote').show();
    } else {
      $('#conversionNote').hide();
    }
  });

  /* ─── Yeni Giriş Kaydet ─── */
  $('#formStockIn').on('submit', function (e) {
    e.preventDefault();
    if (!this.checkValidity()) {
      this.reportValidity();
      return;
    }
    $.post(apiUrl, $(this).serialize() + '&action=add', function (r) {
      if (r.success) {
        showSuccess('Stok girişi kaydedildi!');
        $('#addStockModal').modal('hide');
        $('#formStockIn')[0].reset();
        $('#productSelect').val(null).trigger('change');
        $('#supplierSelect').val(null).trigger('change');
        $('#conversionNote').hide();
        load();
      } else showError(r.message);
    }, 'json');
  });

  /* ─── Tablo Yükle ─── */
  function load() {
    $.get(apiUrl, {
      action: 'list', page: curPage, per_page: curPerPage,
      search: curSearch, warehouse_id: warehouseFilter
    }, function (r) {
      if (!r.success || !r.data.data) return;
      var html = '';
      $.each(r.data.data, function (i, d) {
        html += '<tr>';
        html += '<td>' + esc(d.product) + '</td>';
        html += '<td>' + esc(d.warehouse) + '</td>';
        html += '<td>' + esc(d.supplier || '—') + '</td>';
        html += '<td class="num-align">' + formatQty(d.quantity) + '</td>';
        html += '<td class="num-align">' + formatTurkish(d.unit_price, 2) + ' ' + esc(d.currency) + '</td>';
        html += '<td class="num-align">' + formatTurkish(d.price_eur, 2) + ' <small>' + '<?= get_setting('base_currency', 'EUR') ?>' + '</small></td>';
        html += '<td class="num-align">' + (d.created_at ? d.created_at.split(' ')[0] : '—') + '</td>';
        html += '<td><small class="text-muted">' + esc(d.created_by_name || '—') + '</small></td>';
        html += '<td class="text-center pe-3">';
        html += '<div class="d-flex gap-1 justify-content-center">';
        html += '<button class="btn btn-xs btn-outline-secondary shadow-sm" onclick="viewRow(' + d.id + ')" title="Görüntüle"><i class="fas fa-eye"></i></button>';
        html += '<button class="btn btn-xs btn-outline-info shadow-sm" onclick="editRow(' + d.id + ')" title="Düzenle"><i class="fas fa-edit"></i></button>';
        html += '</div></td>';
        html += '</tr>';
      });
      $('#tableBody').html(html || '<tr><td colspan="9" class="text-center text-muted p-4">Kayıt bulunamadı</td></tr>');
      $('#totalCount').text('Toplam: ' + formatQty(r.data.total) + ' kayıt');
      renderPag(r.data.total);
    }, 'json');
  }

  /* ─── Sayfalama ─── */
  function renderPag(total) {
    var pages = Math.ceil(total / curPerPage);
    if (pages <= 1) { $('#pagination').html(''); return; }
    var html = '<ul class="pagination pagination-sm">';
    var s = Math.max(1, curPage - 2), e = Math.min(pages, curPage + 2);
    if (curPage > 1) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage - 1) + '" href="#">&laquo;</a></li>';
    for (var p = s; p <= e; p++) {
      html += '<li class="page-item' + (p === curPage ? ' active' : '') + '">';
      html += '<a class="page-link" data-p="' + p + '" href="#">' + p + '</a></li>';
    }
    if (curPage < pages) html += '<li class="page-item"><a class="page-link" data-p="' + (curPage + 1) + '" href="#">&raquo;</a></li>';
    html += '</ul>';
    $('#pagination').html(html).find('a').on('click', function (ev) {
      ev.preventDefault(); curPage = parseInt($(this).data('p')); load();
    });
  }

  /* ─── Görüntüle ─── */
  function viewRow(id) {
    $.get(apiUrl, { action: 'get', id: id }, function (r) {
      if (!r.success) return showError(r.message);
      var d = r.data;
      $('#vProduct').val(d.product_name);
      $('#vWarehouse').val(d.warehouse_name);
      $('#vQty').val(formatQty(d.quantity));
      $('#vUnit').text(d.unit || 'Adet');
      $('#vSupplier').val(d.supplier_name || '—');
      $('#vPrice').val(formatTurkish(d.unit_price, 2));
      $('#vCurrency').val(d.currency || '—');
      $('#vEur').val(formatTurkish(d.price_eur, 2) + ' ' + '<?= get_setting('base_currency', 'EUR') ?>');
      $('#vDate').val(d.created_at || '—');
      $('#vUser').val(d.created_by_name || '—');
      $('#vUpdatedUser').val(d.updated_by_name || '—');
      $('#vNote').val(d.note || '');
      $('#viewModal').modal('show');
    }, 'json');
  }

  /* ─── Düzenle ─── */
  function editRow(id) {
    $.get(apiUrl, { action: 'get', id: id }, function (r) {
      if (!r.success) return showError(r.message);
      var d = r.data;
      $('#editId').val(d.id);
      $('#editWarehouse').val(d.warehouse_id).trigger('change');

      // Önceki ürün seçimini temizle, sonra yeni option ekle
      $('#editProduct').empty().append('<option value="">— Ürün —</option>');
      var opt = new Option(d.product_name, d.product_id, true, true);
      $('#editProduct').append(opt).trigger('change');

      $('#editQty').val(parseFloat(d.quantity));
      $('#editUnitLabel').text(d.unit || 'Adet'); // birimi de güncelle
      $('#editPrice').val(formatTurkish(d.unit_price, 2));
      $('#editCurrency').val(d.currency).trigger('change');
      $('#editNote').val(d.note || '');
      if (d.supplier_id) {
        $('#editSupplier').val(d.supplier_id).trigger('change');
      } else {
        $('#editSupplier').val('').trigger('change');
      }
      $('#editModal').modal('show');
    }, 'json');
  }

  /* ─── Güncelle ─── */
  $('#btnEditSave').on('click', function () {
    $.post(apiUrl, $('#editForm').serialize(), function (r) {
      if (r.success) {
        showSuccess(r.message);
        $('#editModal').modal('hide');
        load();
      } else showError(r.message);
    }, 'json');
  });

  /* ─── addStockModal kapanınca formu sıfırla ─── */
  $('#addStockModal').on('hidden.bs.modal', function () {
    $('#formStockIn')[0].reset();
    $('#productSelect').val(null).trigger('change');
    $('#supplierSelect').val(null).trigger('change');
    $('#warehouseSelect').val('').trigger('change');
    $('#currency').val('').trigger('change');
    $('#unitLabel').text('Adet');
    $('#conversionNote').hide();
  });

  /* ─── Arama & filtreler ─── */
  $('#searchBox').on('input', function () {
    clearTimeout(searchTimer);
    curSearch = $(this).val();
    searchTimer = setTimeout(function () { curPage = 1; load(); }, 400);
  });
  $('#warehouseFilter').on('change', function () { warehouseFilter = $(this).val(); curPage = 1; load(); });
  $('#perPage').on('change', function () { curPerPage = parseInt($(this).val()); curPage = 1; load(); });

  /* ─── Başlat ─── */
  $(document).ready(function () {
    initSelect2();
    load();
    setupQuickSupplierEvents();
    setupQuickProductEvents();
  });

  // --- Hızlı Tedarikçi Ekleme Fonksiyonları ---
  function openQuickSupplierModal() {
    $('#quickSupplierForm')[0].reset();
    $('#quickSupplierModal').modal('show');
  }

  function setupQuickSupplierEvents() {
    $('#btnSaveQuickSupplier').on('click', function () {
      var form = $('#quickSupplierForm');
      if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
      }

      var btn = $(this);
      var originalHtml = btn.html();
      btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Kaydediliyor...');

      $.post('<?= BASE_URL ?>/api/suppliers.php', form.serialize(), function (r) {
        btn.prop('disabled', false).html(originalHtml);
        if (r.success) {
          showSuccess(r.message);
          $('#quickSupplierModal').modal('hide');

          // Yeni tedarikçiyi her iki Select2'ye ekle ve seç
          if (r.data && r.data.id) {
            var newOption = new Option(r.data.name, r.data.id, true, true);
            $('#supplierSelect').append(newOption).trigger('change');
            $('#supplierSelect').trigger({
              type: 'select2:select',
              params: {
                data: { id: r.data.id, text: r.data.name }
              }
            });

            // Düzenleme modalındaki Select2 için de ekle
            var newOptionEdit = new Option(r.data.name, r.data.id, false, false);
            $('#editSupplier').append(newOptionEdit).trigger('change');
            $('#editSupplier').trigger({
              type: 'select2:select',
              params: {
                data: { id: r.data.id, text: r.data.name }
              }
            });

            // Fiyat alanlarını garantili aktif et ve odağı taşı
            $('#unitPrice, #currency').prop('disabled', false);
            setTimeout(() => { $('#unitPrice').focus().select(); }, 100);
          }
        } else {
          showError(r.message);
        }
      }, 'json').fail(function () {
        btn.prop('disabled', false).html(originalHtml);
        showError('Bir hata oluştu.');
      });
    });
  }

  // --- Hızlı Ürün Ekleme Fonksiyonları ---
  function openQuickProductModal() {
    $('#quickProductForm')[0].reset();
    $('#q_previewImg').attr('src', '<?= BASE_URL ?>/assets/no-image.png');
    setQuickProductStatus(1);
    q_croppedBlob = null;
    $('#quickProductModal').modal('show');
  }

  function setQuickProductStatus(val) {
    $('#q_is_active_input').val(val);
    $('.status-btn-item').removeClass('active-state inactive-state');
    if (val == 1) {
      $('#q_set_active').addClass('active-state');
    } else {
      $('#q_set_inactive').addClass('inactive-state');
    }
  }

  var q_cropper;
  var q_croppedBlob = null;

  $('#q_imageInput').on('change', function () {
    var file = this.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      $('#q_cropImage').attr('src', e.target.result);
      $('#q_cropModal').modal('show');
    };
    reader.readAsDataURL(file);
  });

  $('#q_cropModal').on('shown.bs.modal', function () {
    q_cropper = new Cropper(document.getElementById('q_cropImage'), {
      aspectRatio: 1,
      viewMode: 2,
      autoCropArea: 1,
    });
  }).on('hidden.bs.modal', function () {
    if (q_cropper) {
      q_cropper.destroy();
      q_cropper = null;
    }
    if (!q_croppedBlob) {
      $('#q_imageInput').val('');
    }
  });

  $('#btnDoQuickProductCrop').on('click', function () {
    if (!q_cropper) return;
    var canvas = q_cropper.getCroppedCanvas({ width: 800, height: 800 });
    canvas.toBlob(function (blob) {
      q_croppedBlob = blob;
      var url = URL.createObjectURL(blob);
      $('#q_previewImg').attr('src', url);
      $('#q_cropModal').modal('hide');
    }, 'image/jpeg', 0.9);
  });

  function setupQuickProductEvents() {
    $('#btnSaveQuickProduct').on('click', function () {
      var form = $('#quickProductForm');
      if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
      }

      var btn = $(this);
      var originalHtml = btn.html();
      btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Kaydediliyor...');

      var formData = new FormData(form[0]);
      if (q_croppedBlob) {
        formData.set('image', q_croppedBlob, 'product.jpg');
      }

      $.ajax({
        url: '<?= BASE_URL ?>/api/products.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (r) {
          btn.prop('disabled', false).html(originalHtml);
          if (r.success) {
            showSuccess(r.message);
            $('#quickProductModal').modal('hide');
            q_croppedBlob = null;

            // Yeni ürünü Select2'ye ekle ve seç
            if (r.data && r.data.id) {
              var text = r.data.name + (r.data.code ? ' [' + r.data.code + ']' : '');
              var newOption = new Option(text, r.data.id, true, true);
              $(newOption).data('unit', r.data.unit);
              $('#productSelect').append(newOption).trigger('change');

              $('#quantity').prop('disabled', false); // Yeni ürün eklendikten sonra miktarı garantili aktif et

              var newOptionEdit = new Option(text, r.data.id, false, false);
              $('#editProduct').append(newOptionEdit).trigger('change.select2');

              $('#unitLabel').text(r.data.unit || 'Adet');
            }
          } else {
            showError(r.message);
          }
        },
        error: function () {
          btn.prop('disabled', false).html(originalHtml);
          showError('Bir hata oluştu.');
        },
        dataType: 'json'
      });
    });
  }
</script>