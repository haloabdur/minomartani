<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="RT 29 Minomartani Admin Panel">
    <meta name="theme-color" content="#15C269" />
    <link rel="icon" href="<?= base_url('public/img/logo.png') ?>" type="image/x-icon">

    <title>RT 29 Minomartani | Admin</title>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="<?= base_url('public') ?>/plugins/fontawesome-free/css/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= base_url('public') ?>/dist/css/adminlte.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="<?= base_url('public') ?>/plugins/datatables-bs4/css/dataTables.bootstrap4.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="<?= base_url('public') ?>/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="<?= base_url('public') ?>/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <!-- include summernote css -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">

    <style type="text/css">
        .nav-link.active {
            background-color: #007bff !important;
            color: #fff;
        }

        .select2-container .select2-selection--single {
            height: calc(2.25rem + 2px);
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 31px;
            right: 6px;
        }

        label:not(.form-check-label):not(.custom-file-label) {
            font-weight: 300;
        }

        .nav-link.active {
            color: white !important;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-dark navbar-primary">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <!-- Notifications Dropdown Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <i class="far fa-user"></i>
                        <?= auth()->user() ? auth()->user()->username : '' ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <a href="<?= base_url('admin/ganti-password') ?>" class="dropdown-item">
                            <i class="fas fa-lock mr-2"></i> Ganti Password
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?= url_to('logout') ?>" class="dropdown-item">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="<?= base_url('admin/dashboard') ?>" class="brand-link">
                <img src="<?= base_url('public') ?>/home/assets/img/logo-white.png" alt="Logo RT 29 Minomartani" class="brand-image">
                <span class="brand-text font-weight-light">&nbsp;</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">

                <?= view('layouts/sidebar_menu') ?>
            </div>
            <!-- /.sidebar -->
        </aside>


        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col">
                            <h1 class="m-0 text-dark">
                                <?php
                                $segments = service('uri')->getSegments();
                                if (count($segments) > 2): ?>
                                    <a href="<?= !empty($back) ? base_url($back) : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '#') ?>" class="pr-2 text-dark"><small><i class="fa fa-arrow-left"></i></small></a>
                                <?php endif ?>
                                <strong><?= $pageTitle ?? 'Admin' ?></strong>
                            </h1>
                        </div><!-- /.col -->
                    </div><!-- /.row -->
                </div><!-- /.container-fluid -->
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <div class="content">
                <?= loadFlashData() ?>