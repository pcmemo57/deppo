<?php
/**
 * AdminLTE Sidebar — Rol Bazlı Menü
 */
$currentPage = $_GET['page'] ?? 'dashboard';
$role = currentUser()['role'];
$warehouseCount = Database::fetchOne("SELECT COUNT(*) as c FROM tbl_dp_warehouses WHERE hidden=0 AND is_active=1")['c'];
?>
<!-- Sidebar -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="<?= BASE_URL ?>/index.php" class="brand-link">
        <i class="fas fa-warehouse brand-image ml-3" style="font-size:1.5rem; opacity:.8"></i>
        <span class="brand-text font-weight-light ml-2">DEPPO</span>
    </a>
    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
            <div class="image">
                <div
                    style="width:35px;height:35px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-user text-white" style="font-size:0.9rem"></i>
                </div>
            </div>
            <div class="info">
                <a href="#" class="d-block text-truncate" style="max-width:130px">
                    <?= e(currentUser()['name']) ?>
                </a>
                <small class="text-muted">
                    <?= $role === ROLE_ADMIN ? 'Admin' : ($role === ROLE_USER ? 'Yönetici' : 'Talep Eden') ?>
                </small>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <!-- Dashboard — herkese görünür -->
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/index.php?page=dashboard"
                        class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Kontrol Paneli</p>
                    </a>
                </li>

                <?php if ($role === ROLE_REQUESTER): ?>
                    <!-- Talep eden sadece çıkış yapabilir -->
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/index.php?page=stock_out"
                            class="nav-link <?= $currentPage === 'stock_out' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-truck-loading"></i>
                            <p>Ürün Talep Et</p>
                        </a>
                    </li>

                    <?php
                else: ?>

                    <!-- STOK DURUMU -->
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/index.php?page=stock_status"
                            class="nav-link <?= $currentPage === 'stock_status' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>Stok Durumu</p>
                        </a>
                    </li>
                    <!-- STOK HAREKETLERİ -->


                    <!-- STOK HAREKETLERİ -->
                    <li
                        class="nav-item has-treeview <?= in_array($currentPage, ['stock_in', 'stock_in_list', 'stock_out', 'stock_out_orders', 'transfer', 'transfer_history', 'entrusted']) ? 'menu-open' : '' ?>">
                        <a href="#"
                            class="nav-link <?= in_array($currentPage, ['stock_in', 'stock_in_list', 'stock_out', 'stock_out_orders', 'transfer', 'transfer_history', 'entrusted']) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-exchange-alt"></i>
                            <p>Stok Hareketleri <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/index.php?page=stock_out_orders"
                                    class="nav-link <?= $currentPage === 'stock_out_orders' ? 'active' : '' ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Depodan Çıkış</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/index.php?page=stock_in_list"
                                    class="nav-link <?= $currentPage === 'stock_in_list' ? 'active' : '' ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Giriş Listesi</p>
                                </a>
                            </li>
                            <?php if ($warehouseCount > 1): ?>
                                <li class="nav-item">
                                    <a href="<?= BASE_URL ?>/index.php?page=transfer"
                                        class="nav-link <?= $currentPage === 'transfer' ? 'active' : '' ?>">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Depolar Arası Transfer</p>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/index.php?page=entrusted"
                                    class="nav-link <?= $currentPage === 'entrusted' ? 'active' : '' ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Emanet Yönetimi</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- TANIMLAMALAR -->
                    <li
                        class="nav-item has-treeview <?= in_array($currentPage, ['products', 'warehouses', 'customers', 'suppliers']) ? 'menu-open' : '' ?>">
                        <a href="#"
                            class="nav-link <?= in_array($currentPage, ['products', 'warehouses', 'customers', 'suppliers']) ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-database"></i>
                            <p>Tanımlamalar <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/index.php?page=warehouses"
                                    class="nav-link <?= $currentPage === 'warehouses' ? 'active' : '' ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Depo Yönetimi</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/index.php?page=products"
                                    class="nav-link <?= $currentPage === 'products' ? 'active' : '' ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Ürün Yönetimi</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/index.php?page=customers"
                                    class="nav-link <?= $currentPage === 'customers' ? 'active' : '' ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Müşteriler</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/index.php?page=suppliers"
                                    class="nav-link <?= $currentPage === 'suppliers' ? 'active' : '' ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Tedarikçiler</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/index.php?page=requesters"
                                    class="nav-link <?= $currentPage === 'requesters' ? 'active' : '' ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Talep Eden Yönetimi</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <?php if ($role === ROLE_ADMIN): ?>
                        <!-- KULLANICI YÖNETİMİ — sadece admin -->
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/index.php?page=admin_users"
                                class="nav-link <?= $currentPage === 'admin_users' ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Kullanıcı Yönetimi</p>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($role === ROLE_ADMIN): ?>
                        <!-- AYARLAR — sadece admin -->
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/index.php?page=settings"
                                class="nav-link <?= $currentPage === 'settings' ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>Ayarlar</p>
                            </a>
                        </li>

                        <!-- SİSTEM GÜNCELLEME — sadece admin -->
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/index.php?page=updates"
                                class="nav-link <?= $currentPage === 'updates' ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-sync-alt"></i>
                                <p>Sistem Güncelleme</p>
                            </a>
                        </li>

                        <!-- SİSTEM GÖREVLERİ — sadece admin -->
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/index.php?page=system_tasks"
                                class="nav-link <?= $currentPage === 'system_tasks' ? 'active' : '' ?>">
                                <i class="nav-icon fas fa-tasks"></i>
                                <p>Sistem Görevleri</p>
                            </a>
                        </li>
                        <?php
                    endif; ?>

                    <?php
                endif; ?>

            </ul>
        </nav>
    </div>
</aside>