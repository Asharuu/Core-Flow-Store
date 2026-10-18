<?php
$menus = [
    [
        'file'  => 'index.php',
        'label' => 'Ringkasan Toko',
        'icon'  => '📊',
        'roles' => ['Admin', 'Staff Pengadaan', 'Staff Gudang', 'Kasir', 'Supervisor']
    ],
    [
        'file'  => 'pos.php',
        'label' => 'Kasir Penjualan',
        'icon'  => '🛒',
        'roles' => ['Admin', 'Kasir']
    ],
    [
        'file'  => 'pengadaan.php',
        'label' => 'Pengadaan (PO)',
        'icon'  => '🚚',
        'roles' => ['Admin', 'Staff Pengadaan', 'Supervisor']
    ],
    [
        'file'  => 'penerimaan.php',
        'label' => 'Penerimaan Gudang',
        'icon'  => '📦',
        'roles' => ['Admin', 'Staff Gudang']
    ],
    [
        'file'  => 'kartu-stok.php',
        'label' => 'Buku Kartu Stok',
        'icon'  => '📋',
        'roles' => ['Admin', 'Staff Gudang', 'Staff Pengadaan', 'Supervisor', 'Kasir']
    ],
    [
        'file'  => 'master-barang.php',
        'label' => 'Master Produk',
        'icon'  => '🏷️',
        'roles' => ['Admin', 'Staff Pengadaan', 'Staff Gudang']
    ],
    [
        'file'  => 'master-vendor.php',
        'label' => 'Master Vendor',
        'icon'  => '🏢',
        'roles' => ['Admin', 'Staff Pengadaan']
    ],
    [
        'file'  => 'master-margin.php',
        'label' => 'Margin Penjualan',
        'icon'  => '📈',
        'roles' => ['Admin', 'Supervisor']
    ],
];
?>
<aside class="dash-sidebar" aria-label="Menu Utama">
  <div class="sidebar-title">Menu Navigasi · <?= htmlspecialchars($currentRole) ?></div>
  
  <?php foreach ($menus as $m): ?>
    <?php if (in_array($currentRole, $m['roles'])): ?>
      <?php $isActive = ($currentPage === $m['file']) ? 'active' : ''; ?>
      <a href="<?= $m['file'] ?>" class="nav-item <?= $isActive ?>">
        <span><?= $m['icon'] ?></span>
        <span><?= $m['label'] ?></span>
      </a>
    <?php endif; ?>
  <?php endforeach; ?>
</aside>

<main class="main-content" style="min-width: 0;">
