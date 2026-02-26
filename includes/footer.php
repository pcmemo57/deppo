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
<footer class="main-footer py-2" style="background:<?= e($footerBg)?>; color:<?= e($footerColor)?>; border:none;">
    <div class="float-right d-none d-sm-inline">
        <small>v
            <?= APP_VERSION?>
        </small>
    </div>
    <strong>
        <?= e($footerText)?>
    </strong>
</footer>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE 3 -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<!-- XLSX (Excel export) -->
<script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>

<script>
    // Global SweetAlert tema
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
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

    // Türkçe binler ayıracı formatı
    function formatTurkish(val) {
        val = String(val).replace(/[^0-9,]/g, '');
        var parts = val.split(',');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return parts.join(',');
    }

    // Price input'ları gerçek zamanlı formatla
    $(document).on('input', '.price-format', function () {
        var raw = $(this).val().replace(/\./g, '');
        $(this).val(formatTurkish(raw));
    });

    // Price input'tan temiz sayısal değer al
    function getPriceValue(selector) {
        var val = $(selector).val().replace(/\./g, '').replace(',', '.');
        return parseFloat(val) || 0;
    }
</script>

<?php if (isset($extraScripts))
    echo $extraScripts; ?>
</div><!-- /.wrapper -->
</body>

</html>