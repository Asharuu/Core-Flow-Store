<?php
$pageTitle = 'Master Vendor';
require_once __DIR__ . '/config/database.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_vendor') {
    $nama  = trim($_POST['nama_vendor'] ?? '');
    $badan = trim($_POST['badan_hukum'] ?? 'P');

    if ($nama === '') {
        $error = 'Nama vendor wajib diisi!';
    } else {
        try {
            $idvendor = getNextId($pdo, 'vendor', 'idvendor');
            $stmt = $pdo->prepare("INSERT INTO vendor (idvendor, nama_vendor, badan_hukum, status) VALUES (?, ?, ?, 'A')");
            $stmt->execute([$idvendor, $nama, $badan]);
            $success = "Vendor \"{$nama}\" berhasil ditambahkan!";
        } catch (Exception $e) {
            $error = 'Gagal menambah vendor: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_vendor') {
    $idvendor = (int)($_POST['idvendor'] ?? 0);
    $nama     = trim($_POST['nama_vendor'] ?? '');
    $badan    = trim($_POST['badan_hukum'] ?? 'P');
    $status   = trim($_POST['status'] ?? 'A');

    if ($idvendor <= 0 || $nama === '') {
        $error = 'Data update vendor tidak valid!';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE vendor SET nama_vendor = ?, badan_hukum = ?, status = ? WHERE idvendor = ?");
            $stmt->execute([$nama, $badan, $status, $idvendor]);
            $success = "Data vendor #{$idvendor} (\"{$nama}\") berhasil diperbarui!";
        } catch (Exception $e) {
            $error = 'Gagal mengupdate vendor: ' . $e->getMessage();
        }
    }
}

if (isset($_GET['hapus'])) {
    $idHapus = (int)$_GET['hapus'];
    if ($idHapus > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM vendor WHERE idvendor = ?");
            $stmt->execute([$idHapus]);
            $success = "Vendor #{$idHapus} berhasil dihapus permanen dari database!";
        } catch (PDOException $e) {
            $stmtSoft = $pdo->prepare("UPDATE vendor SET status = 'N' WHERE idvendor = ?");
            $stmtSoft->execute([$idHapus]);
            $success = "Vendor #{$idHapus} memiliki riwayat transaksi PO, status otomatis diubah menjadi NON-AKTIF (Soft Delete).";
        }
    }
}

$editVendor = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmtEdit = $pdo->prepare("SELECT * FROM vendor WHERE idvendor = ?");
    $stmtEdit->execute([$editId]);
    $editVendor = $stmtEdit->fetch();
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$vendors = $pdo->query("SELECT * FROM vendor ORDER BY idvendor ASC")->fetchAll();
?>

<div class="c-card-header" style="margin-bottom: var(--space-md);">
  <div>
    <div class="mono" style="color: var(--color-accent-2); font-size: 0.75rem;">MITRA &amp; PEMASOK</div>
    <h1 class="dsp" style="font-size: 2.2rem; margin: 0;">Daftar Vendor Rekanan</h1>
  </div>
</div>

<?php if ($success): ?>
  <div class="c-card c-card--mustard" style="margin-bottom:var(--space-md);">
    <strong>✓ Sukses:</strong> <?= htmlspecialchars($success) ?>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="c-card" style="background:#fecdd3; border-color:#881337; color:#881337; margin-bottom:var(--space-md);">
    <strong>Perhatian:</strong> <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<?php if ($editVendor): ?>
  <div class="c-card c-card--mustard" style="margin-bottom: var(--space-xl); border: 2px solid var(--color-ink);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--space-sm);">
      <h2 class="c-card-title">Edit Data Vendor #<?= $editVendor['idvendor'] ?></h2>
      <a href="master-vendor.php" class="btn btn-sm btn-secondary">Batal</a>
    </div>

    <form method="POST" action="master-vendor.php">
      <input type="hidden" name="action" value="update_vendor">
      <input type="hidden" name="idvendor" value="<?= $editVendor['idvendor'] ?>">

      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:var(--space-md);">
        <div class="form-group">
          <label class="form-label">Nama Vendor / Perusahaan</label>
          <input type="text" name="nama_vendor" class="form-control" value="<?= htmlspecialchars($editVendor['nama_vendor']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Badan Hukum</label>
          <select name="badan_hukum" class="form-control" required>
            <option value="P" <?= $editVendor['badan_hukum'] === 'P' ? 'selected' : '' ?>>Perseroan Terbatas (PT)</option>
            <option value="C" <?= $editVendor['badan_hukum'] === 'C' ? 'selected' : '' ?>>Persekutuan Komanditer (CV)</option>
            <option value="U" <?= $editVendor['badan_hukum'] === 'U' ? 'selected' : '' ?>>Usaha Dagang (UD) / Perorangan</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Status Kemitraan</label>
          <select name="status" class="form-control" required>
            <option value="A" <?= $editVendor['status'] === 'A' ? 'selected' : '' ?>>Aktif Beroperasi</option>
            <option value="N" <?= $editVendor['status'] === 'N' ? 'selected' : '' ?>>Nonaktif</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-secondary" style="margin-top:var(--space-sm);">Simpan Perubahan &rarr;</button>
    </form>
  </div>
<?php else: ?>
  <div class="c-card" style="margin-bottom: var(--space-xl); background: var(--color-paper-2);">
    <h2 class="c-card-title" style="margin-bottom: var(--space-md);">Tambah Vendor / Rekanan Baru</h2>
    <form method="POST" action="master-vendor.php">
      <input type="hidden" name="action" value="tambah_vendor">
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:var(--space-md);">
        <div class="form-group">
          <label class="form-label">Nama Vendor / Perusahaan</label>
          <input type="text" name="nama_vendor" class="form-control" placeholder="Contoh: PT Sumber Pangan Makmur" required>
        </div>
        <div class="form-group">
          <label class="form-label">Badan Hukum</label>
          <select name="badan_hukum" class="form-control" required>
            <option value="P">Perseroan Terbatas (PT)</option>
            <option value="C">Persekutuan Komanditer (CV)</option>
            <option value="U">Usaha Dagang (UD) / Perorangan</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:var(--space-sm);">Simpan Vendor &rarr;</button>
    </form>
  </div>
<?php endif; ?>

<div class="c-card">
  <div class="c-card-header">
    <h2 class="c-card-title">Daftar Vendor Rekanan</h2>
    <span class="mono badge badge-active"><?= count($vendors) ?> Mitra Terdaftar</span>
  </div>

  <div class="table-responsive">
    <table class="c-table">
      <thead>
        <tr>
          <th>ID Vendor</th>
          <th>Nama Vendor / Perusahaan</th>
          <th>Badan Hukum</th>
          <th>Status Kemitraan</th>
          <th style="text-align:center;">Tindakan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($vendors as $v): ?>
          <tr style="<?= $v['status'] === 'N' ? 'opacity:0.6; background:#f5f5f5;' : '' ?>">
            <td class="mono">#<?= $v['idvendor'] ?></td>
            <td><strong><?= htmlspecialchars($v['nama_vendor']) ?></strong></td>
            <td>
              <span class="mono badge">
                <?= $v['badan_hukum'] === 'P' ? 'PT' : ($v['badan_hukum'] === 'C' ? 'CV' : 'UD / Perorangan') ?>
              </span>
            </td>
            <td>
              <span class="mono badge <?= $v['status'] === 'A' ? 'badge-inflow' : 'badge-outflow' ?>">
                <?= $v['status'] === 'A' ? '✓ Aktif' : 'Nonaktif' ?>
              </span>
            </td>
            <td style="text-align:center; white-space:nowrap;">
              <a href="master-vendor.php?edit=<?= $v['idvendor'] ?>" class="btn btn-sm btn-secondary" title="Edit Data Vendor">
                Edit
              </a>
              <a href="master-vendor.php?hapus=<?= $v['idvendor'] ?>" class="btn btn-sm btn-danger" style="margin-left:0.25rem;" onclick="return confirm('Apakah Anda yakin ingin menghapus vendor \'<?= htmlspecialchars(addslashes($v['nama_vendor'])) ?>\'?')" title="Hapus Vendor">
                Hapus
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
