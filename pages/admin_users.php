<?php
/**
 * Kullanıcı Yönetimi — Admin & Normal Kullanıcı
 */
requireRole(ROLE_ADMIN);
?>

<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline card-outline-tabs">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" id="userTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-admins" role="tab">
                            <i class="fas fa-user-shield me-1"></i>Adminler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-users" role="tab">
                            <i class="fas fa-user-cog me-1"></i>Program Yöneticileri
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content">
                    <!-- ══ ADMINLER ══ -->
                    <div class="tab-pane fade show active" id="tab-admins" role="tabpanel">
                        <div class="p-3 d-flex justify-content-between align-items-center border-bottom bg-light">
                            <h3 class="card-title text-sm text-bold">Admin Kullanıcıları</h3>
                            <div class="card-tools d-flex gap-2">
                                <select id="perPageAdmins" class="form-select form-select-sm" style="width:auto">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <div class="input-group input-group-sm" style="width: 200px;">
                                    <input type="text" id="searchAdmins" class="form-control" placeholder="Ara...">
                                    <div class="input-group-append">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                </div>
                                <button class="btn btn-primary btn-sm px-3 shadow-sm" onclick="openAdminModal()"
                                    title="Yeni Admin Ekle">
                                    <i class="fas fa-plus me-1"></i> Yeni Admin Ekle
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped m-0 table-valign-middle">
                                <thead class="bg-light text-muted small text-uppercase">
                                    <tr>
                                        <th style="width:60px" class="ps-3">#</th>
                                        <th>Ad Soyad</th>
                                        <th>E-posta</th>
                                        <th>Son Giriş</th>
                                        <th style="width:100px">Durum</th>
                                        <th style="width:120px" class="text-center pe-3">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyAdmins"></tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top clearfix bg-light">
                            <div id="paginationAdmins" class="float-end m-0"></div>
                        </div>
                    </div>

                    <!-- ══ PROGRAM YÖNETİCİLERİ ══ -->
                    <div class="tab-pane fade" id="tab-users" role="tabpanel">
                        <div class="p-3 d-flex justify-content-between align-items-center border-bottom bg-light">
                            <h3 class="card-title text-sm text-bold">Program Yöneticileri</h3>
                            <div class="card-tools d-flex gap-2">
                                <select id="perPageUsers" class="form-select form-select-sm" style="width:auto">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <div class="input-group input-group-sm" style="width: 200px;">
                                    <input type="text" id="searchUsers" class="form-control" placeholder="Ara...">
                                    <div class="input-group-append">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                </div>
                                <button class="btn btn-primary btn-sm px-3 shadow-sm" onclick="openUserModal('user')"
                                    title="Yeni Yönetici Ekle">
                                    <i class="fas fa-plus me-1"></i> Yeni Yönetici Ekle
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped m-0 table-valign-middle">
                                <thead class="bg-light text-muted small text-uppercase">
                                    <tr>
                                        <th style="width:60px" class="ps-3">#</th>
                                        <th>Ad Soyad</th>
                                        <th>E-posta</th>
                                        <th>Son Giriş</th>
                                        <th style="width:100px">Durum</th>
                                        <th style="width:120px" class="text-center pe-3">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyUsers"></tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top clearfix bg-light">
                            <div id="paginationUsers" class="float-end m-0"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Kullanıcı Ekle / Düzenle -->
<div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalUserTitle">Kullanıcı Ekle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formUser">
                    <input type="hidden" name="action" id="userAction" value="add_user">
                    <input type="hidden" name="type" id="userType">
                    <input type="hidden" name="id" id="userId" value="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">E-posta <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Şifre <span class="text-danger"
                                        id="passRequired">*</span></label>
                                <input type="password" name="password" class="form-control" id="userPassword"
                                    autocomplete="new-password">
                                <small class="text-muted" id="passHint" style="display:none">Değiştirmek için
                                    doldurun.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="d-block mb-2">Durum</label>
                                <input type="hidden" name="is_active" id="is_active_input" value="1">
                                <div class="status-btn-group">
                                    <button type="button" class="status-btn-item" id="set_active"
                                        onclick="setStatus(1)">
                                        <i class="fas fa-check-circle me-1"></i> AKTİF
                                    </button>
                                    <button type="button" class="status-btn-item" id="set_inactive"
                                        onclick="setStatus(0)">
                                        <i class="fas fa-times-circle me-1"></i> PASİF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary" id="btnSaveUser">
                    <i class="fas fa-save me-1"></i>Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    var adminPage = 1, adminPerPage = 10, adminSearch = '';
    var userPage = 1, userPerPage = 10, userSearch = '';

    function loadAdmins() {
        $.get('<?= BASE_URL ?>/api/users.php', { action: 'list', type: 'admin', page: adminPage, per_page: adminPerPage, search: adminSearch }, function (r) {
            if (!r.success || !r.data.data) return;
            var html = '';
            $.each(r.data.data, function (i, u) {
                html += '<tr>';
                html += '<td>' + u.id + '</td>';
                html += '<td>' + $('<span>').text(u.name).html() + '</td>';
                html += '<td>' + $('<span>').text(u.email).html() + '</td>';
                html += '<td>' + (u.last_login ? u.last_login : '<span class="text-muted">—</span>') + '</td>';
                html += '<td>' + (u.is_active == 1 ? '<span class="status-badge active" onclick="toggleUser(\'admin\',' + u.id + ',1)"><i class="fas fa-check"></i> AKTİF</span>' : '<span class="status-badge inactive" onclick="toggleUser(\'admin\',' + u.id + ',0)"><i class="fas fa-times"></i> PASİF</span>') + '</td>';
                html += '<td>';
                html += '<button class="btn btn-xs btn-info me-1" onclick="editUser(\'admin\',' + u.id + ')"><i class="fas fa-edit"></i></button>';
                html += '<button class="btn btn-xs btn-danger" onclick="deleteUser(\'admin\',' + u.id + ')"><i class="fas fa-trash"></i></button>';
                html += '</td></tr>';
            });
            $('#bodyAdmins').html(html || '<tr><td colspan="6" class="text-center text-muted">Kayıt yok</td></tr>');
            renderPagination('#paginationAdmins', r.data.total, adminPerPage, adminPage, function (p) { adminPage = p; loadAdmins(); });
        }, 'json');
    }

    function loadUsers() {
        $.get('<?= BASE_URL ?>/api/users.php', { action: 'list', type: 'user', page: userPage, per_page: userPerPage, search: userSearch }, function (r) {
            if (!r.success || !r.data.data) return;
            var html = '';
            $.each(r.data.data, function (i, u) {
                html += '<tr>';
                html += '<td>' + u.id + '</td>';
                html += '<td>' + $('<span>').text(u.name).html() + '</td>';
                html += '<td>' + $('<span>').text(u.email).html() + '</td>';
                html += '<td>' + (u.last_login ? u.last_login : '<span class="text-muted">—</span>') + '</td>';
                html += '<td>' + (u.is_active == 1 ? '<span class="status-badge active" onclick="toggleUser(\'user\',' + u.id + ',1)"><i class="fas fa-check"></i> AKTİF</span>' : '<span class="status-badge inactive" onclick="toggleUser(\'user\',' + u.id + ',0)"><i class="fas fa-times"></i> PASİF</span>') + '</td>';
                html += '<td>';
                html += '<button class="btn btn-xs btn-info me-1" onclick="editUser(\'user\',' + u.id + ')"><i class="fas fa-edit"></i></button>';
                html += '<button class="btn btn-xs btn-danger" onclick="deleteUser(\'user\',' + u.id + ')"><i class="fas fa-trash"></i></button>';
                html += '</td></tr>';
            });
            $('#bodyUsers').html(html || '<tr><td colspan="6" class="text-center text-muted">Kayıt yok</td></tr>');
            renderPagination('#paginationUsers', r.data.total, userPerPage, userPage, function (p) { userPage = p; loadUsers(); });
        }, 'json');
    }

    function renderPagination(container, total, perPage, current, callback) {
        var pages = Math.ceil(total / perPage);
        if (pages <= 1) { $(container).html(''); return; }
        var html = '<ul class="pagination pagination-sm">';
        for (var p = 1; p <= pages; p++) {
            html += '<li class="page-item' + (p === current ? ' active' : '') + '"><a class="page-link" href="#" data-p="' + p + '">' + p + '</a></li>';
        }
        html += '</ul>';
        $(container).html(html).find('a').on('click', function (e) {
            e.preventDefault(); callback(parseInt($(this).data('p')));
        });
    }

    function openUserModal(type) {
        $('#userAction').val('add_user');
        $('#userType').val(type);
        $('#userId').val('');
        $('#formUser')[0].reset();
        setStatus(1);
        $('#passRequired').show();
        $('#passHint').hide();
        $('#userPassword').prop('required', true);
        $('#modalUserTitle').text(type === 'admin' ? 'Admin Ekle' : 'Yönetici Ekle');
        $('#modalUser').modal('show');
    }

    function editUser(type, id) {
        $.get('<?= BASE_URL ?>/api/users.php', { action: 'get', type: type, id: id }, function (r) {
            if (!r.success) return showError(r.message);
            var u = r.data;
            $('#userAction').val('edit_user');
            $('#userType').val(type);
            $('#userId').val(u.id);
            $('[name="name"]').val(u.name);
            $('[name="email"]').val(u.email);
            setStatus(u.is_active);
            $('[name="password"]').val('');
            $('#passRequired').hide();
            $('#passHint').show();
            $('#userPassword').prop('required', false);
            $('#modalUserTitle').text(type === 'admin' ? 'Admin Düzenle' : 'Yönetici Düzenle');
            $('#modalUser').modal('show');
        }, 'json');
    }

    function setStatus(val) {
        $('#is_active_input').val(val);
        $('.status-btn-item').removeClass('active-state inactive-state');
        if (val == 1) {
            $('#set_active').addClass('active-state');
        } else {
            $('#set_inactive').addClass('inactive-state');
        }
    }

    function toggleUser(type, id, current) {
        var newStatus = current == 1 ? 0 : 1;
        $.post('<?= BASE_URL ?>/api/users.php', { action: 'toggle_user', type: type, id: id, status: newStatus }, function (r) {
            if (r.success) { showSuccess(r.message); type === 'admin' ? loadAdmins() : loadUsers(); }
            else showError(r.message);
        }, 'json');
    }

    function deleteUser(type, id) {
        confirmAction('Bu kullanıcıyı silmek istediğinize emin misiniz? Bu işlem geri alınamaz.', function () {
            $.post('<?= BASE_URL ?>/api/users.php', { action: 'delete_user', type: type, id: id }, function (r) {
                if (r.success) { showSuccess(r.message); type === 'admin' ? loadAdmins() : loadUsers(); }
                else showError(r.message);
            }, 'json');
        });
    }

    $('#btnSaveUser').on('click', function () {
        var data = $('#formUser').serialize();
        $.post('<?= BASE_URL ?>/api/users.php', data, function (r) {
            if (r.success) {
                showSuccess(r.message);
                $('#modalUser').modal('hide');
                var t = $('#userType').val();
                t === 'admin' ? loadAdmins() : loadUsers();
            } else showError(r.message);
        }, 'json');
    });

    // Arama debounce
    var adminTimer, userTimer;
    $('#searchAdmins').on('input', function () { clearTimeout(adminTimer); adminSearch = $(this).val(); adminTimer = setTimeout(function () { adminPage = 1; loadAdmins(); }, 400); });
    $('#searchUsers').on('input', function () { clearTimeout(userTimer); userSearch = $(this).val(); userTimer = setTimeout(function () { userPage = 1; loadUsers(); }, 400); });
    $('#perPageAdmins').on('change', function () { adminPerPage = parseInt($(this).val()); adminPage = 1; loadAdmins(); });
    $('#perPageUsers').on('change', function () { userPerPage = parseInt($(this).val()); userPage = 1; loadUsers(); });

    // İlk yükleme
    loadAdmins();
    loadUsers();
</script>