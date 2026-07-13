<!-- Sidebar Menu -->
<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

        <?php if (!auth()->user()->inGroup('rw')): ?>
        <li class="nav-item">
            <a href="<?= base_url('admin/dashboard') ?>" class="nav-link">
                <i class="nav-icon fas fa-th"></i>
                <p>
                    Dashboard
                </p>
            </a>
        </li>

        <!-- <li class="nav-item treeview-menu">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-money-check-alt"></i>
                <p>
                    Layanan
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="<?= base_url('admin/surat') ?>" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Surat Keterangan</p>
                    </a>
                </li>
            </ul>
        </li> -->

        <li class="nav-header">INFORMASI</li>

        <li class="nav-item">
            <a href="<?= base_url('admin/warga') ?>" class="nav-link">
                <i class="nav-icon fas fa-book-reader"></i>
                <p>
                    Warga
                </p>
            </a>
        </li>

        <!-- <li class="nav-item">
            <a href="<?= base_url('admin/inventaris') ?>" class="nav-link">
                <i class="nav-icon fas fa-boxes"></i>
                <p>
                    Inventaris RT
                </p>
            </a>
        </li> -->

        <li class="nav-header">MASTER DATA</li>
        <li class="nav-item">
            <a href="<?= base_url('admin/berita') ?>" class="nav-link">
                <i class="nav-icon fas fa-book"></i>
                <p>
                    Berita
                </p>
            </a>
        </li>

        <li class="nav-item">
            <a href="<?= base_url('admin/alamat') ?>" class="nav-link">
                <i class="nav-icon fas fa-address-book"></i>
                <p>
                    Alamat
                </p>
            </a>
        </li>
        <?php endif ?>

        <li class="nav-header">DATA RT/RW</li>
        <?php if (auth()->user() && auth()->user()->inGroup('superadmin')): ?>
        <li class="nav-item">
            <a href="<?= base_url('admin/pekerjaan') ?>" class="nav-link">
                <i class="nav-icon fas fa-users"></i>
                <p>
                    Pekerjaan
                </p>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= base_url('admin/tenants') ?>" class="nav-link">
                <i class="nav-icon fas fa-sitemap"></i>
                <p>
                    Kelola RT/RW
                </p>
            </a>
        </li>
        <?php endif ?>

        <?php if (auth()->user() && (auth()->user()->inGroup('rw') || auth()->user()->inGroup('superadmin'))): ?>
        <li class="nav-item">
            <a href="<?= base_url('admin/rekap') ?>" class="nav-link">
                <i class="nav-icon fas fa-chart-bar"></i>
                <p>
                    Rekap RW
                </p>
            </a>
        </li>
        <?php endif ?>

        <?php if (auth()->user() && (auth()->user()->inGroup('admin') || auth()->user()->inGroup('rw') || auth()->user()->inGroup('superadmin'))): ?>
        <li class="nav-item">
            <a href="<?= base_url('admin/kesehatan') ?>" class="nav-link">
                <i class="nav-icon fas fa-heartbeat"></i>
                <p>
                    Kesehatan Lansia
                </p>
            </a>
        </li>
        <?php endif ?>

        <?php if (auth()->user() && auth()->user()->inGroup('superadmin')): ?>
        <li class="nav-item">
            <a href="<?= base_url('admin/users') ?>" class="nav-link">
                <i class="nav-icon fas fa-user"></i>
                <p>
                    User
                </p>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= base_url('admin/dbsync') ?>" class="nav-link">
                <i class="nav-icon fas fa-sync-alt"></i>
                <p>
                    Sinkronisasi DB
                </p>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= base_url('admin/logs') ?>" class="nav-link">
                <i class="nav-icon fas fa-bug"></i>
                <p>
                    Log Error
                </p>
            </a>
        </li>
        <?php endif ?>

    </ul>
</nav>
<!-- /.sidebar-menu -->