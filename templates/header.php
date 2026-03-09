<?php $config = app_config(); ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? $config['app_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-dark navbar-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="<?= base_url('index.php?page=dashboard') ?>">POS Flex</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div id="mainNav" class="collapse navbar-collapse">
            <?php if (Auth::check()): ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php?page=dashboard') ?>">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php?page=products') ?>">Produk</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php?page=inventory') ?>">Inventory</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php?page=sales') ?>">Penjualan</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php?page=settings') ?>">Pengaturan</a></li>
                </ul>
                <div class="text-light small">Halo, <?= htmlspecialchars(Auth::user()['name']) ?></div>
                <a class="btn btn-outline-light btn-sm ms-3" href="<?= base_url('index.php?page=logout') ?>">Logout</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="container py-4">
