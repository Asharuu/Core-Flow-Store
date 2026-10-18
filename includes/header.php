<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'iduser'    => 1,
        'username'  => 'admin_utama',
        'idrole'    => 1,
        'role_name' => 'Admin'
    ];
    $_SESSION['role'] = 'Admin';
}

$currentUser = $_SESSION['user'];
$currentRole = $_SESSION['role'] ?? 'Admin';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <title><?= $pageTitle ?? 'Core-Flow Store' ?> · Sistem Manajemen Ritel</title>
  <link rel="stylesheet" href="tokens.css" />
  <link rel="stylesheet" href="styles.css" />
</head>
<body>

  <header class="topbar">
    <div class="topbar__inner">
      <div class="brand-wrap">
        <a href="index.php" class="brand-logo" aria-label="Core-Flow Store">
          CORE-FLOW <span class="highlight">STORE</span>
        </a>
        <div class="brand-tagline">
          SISTEM OPERASIONAL TOKO &amp; GUDANG
        </div>
      </div>

      <div class="topbar__meta">
        <div class="role-badge" title="Peran pengguna yang sedang aktif">
          <div class="role-badge__dot"></div>
          <div>
            <span style="font-weight: 800;"><?= strtoupper(htmlspecialchars($currentRole)) ?></span>
            <span style="opacity: 0.8; font-size: 0.75em; margin-left: 0.2rem;">(<?= htmlspecialchars($currentUser['username']) ?>)</span>
          </div>
        </div>

        <select class="role-switcher-select" onchange="window.location.href='switch-role.php?role=' + encodeURIComponent(this.value) + '&redirect=' + encodeURIComponent(window.location.pathname)" title="Beralih peran pengujian">
          <option value="Admin" <?= $currentRole === 'Admin' ? 'selected' : '' ?>>Peran: Admin</option>
          <option value="Staff Pengadaan" <?= $currentRole === 'Staff Pengadaan' ? 'selected' : '' ?>>Peran: Pengadaan</option>
          <option value="Staff Gudang" <?= $currentRole === 'Staff Gudang' ? 'selected' : '' ?>>Peran: Gudang</option>
          <option value="Kasir" <?= $currentRole === 'Kasir' ? 'selected' : '' ?>>Peran: Kasir</option>
          <option value="Supervisor" <?= $currentRole === 'Supervisor' ? 'selected' : '' ?>>Peran: Supervisor</option>
        </select>

        <a href="logout.php" class="btn btn-sm btn-secondary" style="font-size: 0.85rem;" title="Keluar dari akun">
          Keluar
        </a>
      </div>
    </div>
  </header>

  <div class="dash-shell">
