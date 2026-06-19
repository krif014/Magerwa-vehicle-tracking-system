<?php
$admin = $admin ?? current_admin();
$active = $active ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Magerwa Vehicle Tracking') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css?v=20260620-ui2" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="sidebar-brand" href="index.php">
            <span class="brand-mark"><i class="bi bi-house-lock"></i></span>
            <span>
                <span class="brand-name">MAGERWA</span>
                <span class="brand-subtitle">Rwanda's Public<br>Bonded Warehouse</span>
            </span>
        </a>
        <nav class="nav flex-column sidebar-nav">
            <a class="nav-link <?= $active === 'dashboard' ? 'active' : '' ?>" href="index.php"><i class="bi bi-house-door"></i>Dashboard</a>
            <a class="nav-link <?= $active === 'clients' ? 'active' : '' ?>" href="clients.php"><i class="bi bi-people"></i>Clients</a>
            <a class="nav-link <?= $active === 'vehicles' ? 'active' : '' ?>" href="vehicles.php"><i class="bi bi-truck"></i>Vehicles</a>
            <a class="nav-link <?= $active === 'links' ? 'active' : '' ?>" href="link_vehicle.php"><i class="bi bi-clipboard-check"></i>Assignments</a>
        </nav>
        <div class="sidebar-divider"></div>
        <a class="nav-link logout-link" href="logout.php"><i class="bi bi-box-arrow-left"></i>Logout</a>
    </aside>
    <div class="main-area">
        <nav class="topbar">
            <div class="topbar-search">
                <i class="bi bi-search"></i>
                <input type="search" placeholder="Search anything..." aria-label="Search">
            </div>
            <div class="topbar-actions">
                <span class="notification-icon"><i class="bi bi-bell"></i><span>3</span></span>
                <span class="admin-avatar"><i class="bi bi-person-fill"></i></span>
                <span class="admin-meta d-none d-sm-block">
                    <strong><?= e($admin['names'] ?? 'Admin User') ?></strong>
                    <small>Administrator</small>
                </span>
                <i class="bi bi-chevron-down admin-chevron d-none d-md-inline"></i>
            </div>
        </nav>
        <main class="main-content">
                <?php foreach (consume_flash() as $message): ?>
                    <div class="alert alert-<?= e($message['type']) ?> alert-dismissible fade show" role="alert">
                        <?= e($message['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
