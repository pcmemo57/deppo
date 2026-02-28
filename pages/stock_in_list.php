<?php
/**
 * Stok Giriş Listesi — Tüm giriş kayıtlarını listeler
 */
requireRole(ROLE_ADMIN, ROLE_USER);
$warehouses = Database::fetchAll("SELECT id,name FROM tbl_dp_warehouses WHERE hidden=0 AND is_active=1 ORDER BY name");
?>

<style>
  /* ───────────────────────────────────────────
     KART HEADER — araç çubuğu hizalama
  ─────────────────────────────────────────── */
  /* ─── HEADER ARAÇ ÇUBUĞU ─── */
  .card-header .card-tools {
    align-items: center;
    gap: 10px;
  }

  /* Tüm header araçlarını aynı yüksekliğe sabitle */
  .card-header .card-tools .form-select-sm,
  .card-header .card-tools .input-group-sm .form-control,
  .card-header .card-tools .input-group-sm .input-group-text,
  .card-header .card-tools .btn-sm {
    height: 32px;
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
    width: 26px;
    height: 26px;
    object-fit: cover;
    border-radius: 5px;
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
    border-radius: 16px;
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
    font-size: 1.75rem !important;
    display: flex;
    align-items: center;
  }

  .card-header .card-title i {
    font-size: 1.5rem;
    margin-right: 12px;
  }

  /* Sayfa tepesindeki boşluk */
  .stock-in-row {
    margin-top: 1.25rem;
  }

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

  /* Form label */
  #addStockModal .form-label,
  #editModal .form-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
    display: block;
  }

  .form-label .req {
    color: #e53e3e;
    margin-left: 2px;
  }

  /* Input & Select */
  #addStockModal .form-control,
  #addStockModal .form-select,
  #editModal .form-control,
  #editModal .form-select {
    border: 1.5px solid #d1d9e6;
    border-radius: 8px;
    padding: 9px 13px;
    font-size: 0.88rem;
    color: #1f2937;
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s;
  }

  #addStockModal .form-control:focus,
  #addStockModal .form-select:focus,
  #editModal .form-control:focus,
  #editModal .form-select:focus {
    border-color: #1a56db;
    box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.12);
    outline: none;
  }

  /* İkonlu input wrapper */
  .input-icon-wrap {
    position: relative;
  }

  .input-icon-wrap .field-icon {
    position: absolute;
    left: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: #9aa5be;
    font-size: 0.82rem;
    z-index: 5;
    pointer-events: none;
  }

  .input-icon-wrap .form-control,
  .input-icon-wrap .form-select {
    padding-left: 32px;
  }

  .input-icon-wrap textarea.form-control {
    padding-left: 13px;
    /* textarea'da ikon olmayacak */
  }

  /* Adet + birim */
  .qty-group {
    display: flex;
  }

  .qty-group .form-control {
    border-radius: 8px 0 0 8px;
    flex: 1;
  }

  .qty-group .unit-badge {
    background: #e8edf5;
    border: 1.5px solid #d1d9e6;
    border-left: none;
    border-radius: 0 8px 8px 0;
    padding: 0 14px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #4a5568;
    display: flex;
    align-items: center;
    white-space: nowrap;
    min-width: 56px;
    justify-content: center;
  }

  /* Fiyat satırı */
  .price-row {
    display: grid;
    grid-template-columns: 1fr 150px;
    gap: 12px;
    align-items: end;
  }

  /* EUR notu */
  .conversion-note {
    font-size: 0.78rem;
    color: #2b6cb0;
    background: #ebf4ff;
    border: 1px solid #bee3f8;
    border-radius: 6px;
    padding: 6px 10px;
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  /* Textarea */
  #addStockModal textarea.form-control,
  #editModal textarea.form-control {
    resize: none;
    border-radius: 8px;
    min-height: 72px;
  }

  /* Select2 uyumu — ikonlu wrapper içinde */
  #addStockModal .input-icon-wrap .select2-container,
  #editModal .input-icon-wrap .select2-container {
    width: 100% !important;
  }

  #addStockModal .select2-container--bootstrap-5 .select2-selection,
  #editModal .select2-container--bootstrap-5 .select2-selection {
    border: 1.5px solid #d1d9e6 !important;
    border-radius: 8px !important;
    min-height: 40px !important;
    padding: 6px 10px 6px 32px !important;
    font-size: 0.88rem !important;
    background: #fff !important;
  }

  #addStockModal .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered,
  #editModal .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    padding-left: 0 !important;
    line-height: 1.6 !important;
    color: #1f2937 !important;
  }

  #addStockModal .select2-container--bootstrap-5.select2-container--focus .select2-selection,
  #addStockModal .select2-container--bootstrap-5.select2-container--open .select2-selection,
  #editModal .select2-container--bootstrap-5.select2-container--focus .select2-selection,
  #editModal .select2-container--bootstrap-5.select2-container--open .select2-selection {
    border-color: #1a56db !important;
    box-shadow: 0 0 0 3px rgba(26, 86, 219, 0.12) !important;
  }

  /* Modal butonları */
  .btn-modal-cancel {
    background: transparent;
    border: 1.5px solid #c9d3e0;
    color: #4a5568;
    border-radius: 8px;
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
    border-radius: 8px;
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
    border-radius: 8px;
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
    border-radius: 8px;
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
    border-radius: 0 8px 8px 0;
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
    border-radius: 8px 0 0 8px;
  }

  #viewModal textarea.form-control:disabled {
    min-height: 72px;
    resize: none;
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
              <option value="<?= e($w['id']) ?>">
                <?= e($w['name']) ?>
              </option>
              <?php
            endforeach; ?>
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
              <th style="width:60px" class="ps-3">#</th>
              <th>Ürün</th>
              <th>Depo</th>
              <th>Tedarikçi</th>
              <th class="num-align">Adet</th>
              <th class="num-align">Birim Fiyat</th>
              <th class="num-align">EUR Fiyat</th>
              <th style="width:100px">Tarih</th>
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
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
            <label class="form-label">EUR Karşılığı</label>
            <div class="input-icon-wrap">
              <i class="fas fa-euro-sign field-icon"></i>
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
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                    <option value="<?= e($w['id']) ?>">
                      <?= e($w['name']) ?>
                    </option>
                    <?php
                  endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Ürün <span class="req">*</span></label>
              <div class="input-icon-wrap">
                <i class="fas fa-box field-icon"></i>
                <select name="product_id" id="productSelect" class="form-select" required>
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
                <input type="number" name="quantity" id="quantity" class="form-control" min="0.001" step="any"
                  placeholder="0.00" required>
                <span class="unit-badge" id="unitLabel">Adet</span>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tedarikçi</label>
              <div class="input-icon-wrap">
                <i class="fas fa-truck field-icon"></i>
                <select name="supplier_id" id="supplierSelect" class="form-select">
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
                      placeholder="0,00">
                  </div>
                </div>
                <div>
                  <label class="form-label">Para Birimi <span class="req">*</span></label>
                  <div class="input-icon-wrap">
                    <i class="fas fa-coins field-icon"></i>
                    <select name="currency" id="currency" class="form-select" required>
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
                placeholder="İsteğe bağlı açıklama girebilirsiniz..."></textarea>
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
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                    <option value="<?= e($w['id']) ?>">
                      <?= e($w['name']) ?>
                    </option>
                    <?php
                  endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Ürün <span class="req">*</span></label>
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
                <input type="number" name="quantity" id="editQty" class="form-control" min="0.001" step="any"
                  placeholder="0.00" required>
                <span class="unit-badge" id="editUnitLabel">Adet</span>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tedarikçi</label>
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


<!-- ═══════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════ -->
<script>
  var curPage = 1, curPerPage = 10, curSearch = '', searchTimer, warehouseFilter = 0;
  var apiUrl = '<?= BASE_URL ?>/api/stock_in.php';
  var eurRate = <?= (float) get_setting('eur_rate', '0') ?>;

  function esc(v) { return $('<span>').text(v || '').html(); }

  /* ─── Select2 Başlatma ─── */
  function initSelect2() {
    $('#warehouseSelect').select2({
      theme: 'bootstrap-5', dropdownParent: $('#addStockModal'), width: '100%'
    });
    $('#currency').select2({
      theme: 'bootstrap-5', dropdownParent: $('#addStockModal'), width: '100%'
    });
    $('#editWarehouse').select2({
      theme: 'bootstrap-5', dropdownParent: $('#editModal'), width: '100%'
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
  }

  /* ─── Ürün seçilince birimi güncelle ve miktara odaklan ─── */
  $('#productSelect').on('select2:select', function (e) {
    $('#unitLabel').text(e.params.data.unit || 'Adet');
    $(this).select2('close');
    setTimeout(() => { $('#quantity').focus().select(); }, 50);
  });

  /* ─── Depo seçilince ürünü aç ─── */
  $('#warehouseSelect').on('select2:select', function () {
    $(this).select2('close');
    setTimeout(() => { $('#productSelect').select2('open'); }, 50);
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
    if ((cur === 'TL' || cur === 'USD') && eurRate > 0) {
      $('#conversionNoteText').text(cur + ' → EUR dönüşümü otomatik yapılacak (1 EUR = ' + formatTurkish(eurRate.toFixed(2)) + ' TL)');
      $('#conversionNote').show();
    } else {
      $('#conversionNote').hide();
    }
  });

  /* ─── Yeni Giriş Kaydet ─── */
  $('#formStockIn').on('submit', function (e) {
    e.preventDefault();
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
        html += '<td class="ps-3">' + d.id + '</td>';
        html += '<td>' + esc(d.product) + '</td>';
        html += '<td>' + esc(d.warehouse) + '</td>';
        html += '<td>' + esc(d.supplier || '—') + '</td>';
        html += '<td class="num-align">' + formatQty(d.quantity) + '</td>';
        html += '<td class="num-align">' + formatTurkish(d.unit_price, 2) + ' ' + esc(d.currency) + '</td>';
        html += '<td class="num-align">' + formatTurkish(d.price_eur, 2) + '</td>';
        html += '<td>' + (d.created_at ? d.created_at.split(' ')[0] : '—') + '</td>';
        html += '<td><small class="text-muted">' + esc(d.created_by_name || '—') + '</small></td>';
        html += '<td class="text-center pe-3">';
        html += '<div class="d-flex gap-1 justify-content-center">';
        html += '<button class="btn btn-xs btn-outline-secondary shadow-sm" onclick="viewRow(' + d.id + ')" title="Görüntüle"><i class="fas fa-eye"></i></button>';
        html += '<button class="btn btn-xs btn-outline-info shadow-sm" onclick="editRow(' + d.id + ')" title="Düzenle"><i class="fas fa-edit"></i></button>';
        html += '</div></td>';
        html += '</tr>';
      });
      $('#tableBody').html(html || '<tr><td colspan="9" class="text-center text-muted p-4">Kayıt bulunamadı</td></tr>');
      $('#totalCount').text('Toplam: ' + r.data.total + ' kayıt');
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
      $('#vEur').val(formatTurkish(d.price_eur, 2) + ' EUR');
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
  });
</script>