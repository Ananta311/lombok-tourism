<?php
// includes/header.php
require_once __DIR__ . '/../includes/auth.php';
$siteName = getSetting('site_name') ?: 'Lombok Tourism';
$currentUser = getCurrentUser();
$heroBg = getSetting('hero_bg');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $siteName) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<nav class="navbar" id="navbar">
    <div class="nav-container">
        <a href="<?= BASE_URL ?>" class="nav-logo">
            <span class="logo-icon"><i class="fas fa-compass"></i></span>
            <span class="logo-text"><?= htmlspecialchars($siteName) ?></span>
        </a>

        <button class="nav-toggle" id="navToggle">
            <span></span><span></span><span></span>
        </button>

        <ul class="nav-menu" id="navMenu">
            <li><a href="<?= BASE_URL ?>" class="nav-link">Beranda</a></li>
            <li><a href="<?= BASE_URL ?>wisata.php" class="nav-link">Wisata</a></li>
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <li><a href="<?= BASE_URL ?>admin/dashboard.php" class="nav-link nav-admin">
                        <i class="fas fa-tachometer-alt"></i> Admin
                    </a></li>
                <?php endif; ?>
                <li class="nav-dropdown">
                    <a href="#" class="nav-link nav-user-btn">
                        <?php if (!empty($currentUser['foto_profile'])): ?>
                            <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($currentUser['foto_profile']) ?>" class="nav-avatar" alt="Avatar">
                        <?php else: ?>
                            <div class="nav-avatar-placeholder"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                        <?= htmlspecialchars($currentUser['username']) ?>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="<?= BASE_URL ?>user/profile.php"><i class="fas fa-user-circle"></i> Profil Saya</a>
                        <a href="<?= BASE_URL ?>user/logout.php"><i class="fas fa-sign-out-alt"></i> Keluar</a>
                    </div>
                </li>
            <?php else: ?>
                <li><a href="<?= BASE_URL ?>user/login.php" class="nav-link">Masuk</a></li>
                <li><a href="<?= BASE_URL ?>user/register.php" class="nav-btn">Daftar</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>