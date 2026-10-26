<?php
require_once __DIR__ . '/../config/database.php';

try {
    $allRoles = $pdo->query("SELECT id, nama FROM v_role ORDER BY id ASC")->fetchAll();
} catch (Exception $e) {
    $allRoles = [
        ['id' => 1, 'nama' => 'Admin'],
        ['id' => 2, 'nama' => 'Staff Pengadaan'],
        ['id' => 3, 'nama' => 'Staff Gudang'],
        ['id' => 4, 'nama' => 'Kasir'],
        ['id' => 5, 'nama' => 'Supervisor'],
    ];
}

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
        'file'  => 'produk.php',
        'label' => 'Produk',
        'icon'  => '🏷️',
        'roles' => ['Admin', 'Staff Pengadaan', 'Staff Gudang']
    ],
    [
        'file'  => 'vendor.php',
        'label' => 'Vendor',
        'icon'  => '🏢',
        'roles' => ['Admin', 'Staff Pengadaan']
    ],
    [
        'file'  => 'margin-penjualan.php',
        'label' => 'Margin Penjualan',
        'icon'  => '📈',
        'roles' => ['Admin', 'Supervisor']
    ],
];
?>
<aside class="dash-sidebar" aria-label="Menu Utama">
  <div class="sidebar-title">Menu Navigasi · <?= htmlspecialchars($currentRole) ?></div>
  
  <?php foreach ($menus as $m): ?>
    <?php 
    $isAllowed = in_array($currentRole, $m['roles']) || !in_array($currentRole, ['Staff Pengadaan', 'Staff Gudang', 'Kasir', 'Supervisor']);
    ?>
    <?php if ($isAllowed): ?>
      <?php $isActive = ($currentPage === $m['file']) ? 'active' : ''; ?>
      <a href="<?= $m['file'] ?>" class="nav-item <?= $isActive ?>">
        <span><?= $m['icon'] ?></span>
        <span><?= $m['label'] ?></span>
      </a>
    <?php endif; ?>
  <?php endforeach; ?>

  <!-- Menu Ganti Role & Dropdown Tambah Role (Kiri Bawah) -->
  <div class="sidebar-role-panel">
    <div class="sidebar-title" style="margin-top: 0; padding-left: 0; border-bottom: none;">
      Ganti Peran (Role)
    </div>

    <div>
      <select class="sidebar-role-select" onchange="handleRoleSwitch(this.value)" title="Pilih peran aktif">
        <optgroup label="Pilih Peran:">
          <?php foreach ($allRoles as $r): ?>
            <option value="<?= htmlspecialchars($r['nama']) ?>" <?= $currentRole === $r['nama'] ? 'selected' : '' ?>>
              <?= $currentRole === $r['nama'] ? '✓ ' : '  ' ?><?= htmlspecialchars($r['nama']) ?>
            </option>
          <?php endforeach; ?>
        </optgroup>
        <optgroup label="Aksi:">
          <option value="__tambah__">➕ Tambah Role Baru...</option>
        </optgroup>
      </select>
    </div>

    <details id="dropdown-tambah-role" class="dropdown-tambah-role" <?= (isset($_GET['role_status']) && $_GET['role_status'] !== 'success') ? 'open' : '' ?>>
      <summary>
        <span>➕ Tambah Role</span>
        <span class="arrow-indicator" style="font-size: 0.7rem;">▾</span>
      </summary>
      <div class="dropdown-tambah-role-body">
        <form method="POST" action="tambah-role.php">
          <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'index.php') ?>">
          <label style="font-family: var(--font-label); font-size: 0.7rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.3rem; display: block; color: var(--color-ink);">
            Nama Role Baru:
          </label>
          <input type="text" name="nama_role" placeholder="Contoh: Manager" class="form-control" style="width: 100%; font-size: 0.8rem; padding: 0.4rem; margin-bottom: 0.4rem; background: #fff;" required>
          <button type="submit" class="btn btn-sm btn-primary" style="width: 100%; font-size: 0.75rem; padding: 0.4rem; justify-content: center;">
            Simpan Role &rarr;
          </button>
        </form>
      </div>
    </details>

    <?php if (isset($_GET['role_status'])): ?>
      <?php if ($_GET['role_status'] === 'success'): ?>
        <div style="background: #bbf7d0; color: #14532d; border: 1.5px solid #14532d; padding: 0.35rem 0.5rem; font-family: var(--font-label); font-size: 0.7rem; margin-top: 0.2rem;">
          ✓ Role <strong><?= htmlspecialchars($_GET['role_name'] ?? '') ?></strong> aktif!
        </div>
      <?php elseif ($_GET['role_status'] === 'exists'): ?>
        <div style="background: #fed7aa; color: #7c2d12; border: 1.5px solid #7c2d12; padding: 0.35rem 0.5rem; font-family: var(--font-label); font-size: 0.7rem; margin-top: 0.2rem;">
          ⚠️ Role <strong><?= htmlspecialchars($_GET['role_name'] ?? '') ?></strong> sudah ada.
        </div>
      <?php elseif ($_GET['role_status'] === 'error'): ?>
        <div style="background: #fecdd3; color: #881337; border: 1.5px solid #881337; padding: 0.35rem 0.5rem; font-family: var(--font-label); font-size: 0.7rem; margin-top: 0.2rem;">
          ⚠️ Gagal menyimpan role baru.
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <script>
  function handleRoleSwitch(val) {
    if (val === '__tambah__') {
      const el = document.getElementById('dropdown-tambah-role');
      if (el) {
        el.open = true;
        const input = el.querySelector('input[name="nama_role"]');
        if (input) input.focus();
      }
    } else {
      window.location.href = 'switch-role.php?role=' + encodeURIComponent(val) + '&redirect=' + encodeURIComponent(window.location.pathname);
    }
  }
  </script>
</aside>

<main class="main-content" style="min-width: 0;">
