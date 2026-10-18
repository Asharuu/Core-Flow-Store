<?php
$pageTitle = 'Margin Penjualan';
require_once __DIR__ . '/config/database.php';

$success = '';
$error = '';

if (isset($_GET['aktifkan'])) {
    $idm = (int)$_GET['aktifkan'];
    try {
        $pdo->beginTransaction();
        $pdo->query("UPDATE margin_penjualan SET status = 0");
        $stmt = $pdo->prepare("UPDATE margin_penjualan SET status = 1, updated_at = NOW() WHERE idmargin_penjualan = ?");
        $stmt->execute([$idm]);
        $pdo->commit();
        $success = "Margin #{$idm} berhasil diaktifkan sebagai tarif profit utama kasir!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Gagal mengaktifkan margin: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_margin') {
    $persen = (float)($_POST['persen'] ?? 0);

    if ($persen <= 0) {
        $error = 'Persentase margin harus lebih besar dari 0!';
    } else {
        try {
            $idmargin = getNextId($pdo, 'margin_penjualan', 'idmargin_penjualan');
            $iduser = $_SESSION['user']['iduser'] ?? 1;

            $stmt = $pdo->prepare("INSERT INTO margin_penjualan (idmargin_penjualan, created_at, persen, status, iduser, updated_at) VALUES (?, NOW(), ?, 0, ?, NOW())");
            $stmt->execute([$idmargin, $persen, $iduser]);
            $success = "Tarif margin baru ({$persen}%) berhasil ditambahkan ke daftar!";
        } catch (Exception $e) {
            $error = 'Gagal menambah margin: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_margin') {
    $idmargin = (int)($_POST['idmargin_penjualan'] ?? 0);
    $persen   = (float)($_POST['persen'] ?? 0);

    if ($idmargin <= 0 || $persen <= 0) {
        $error = 'Data persentase margin tidak valid!';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE margin_penjualan SET persen = ?, updated_at = NOW() WHERE idmargin_penjualan = ?");
            $stmt->execute([$persen, $idmargin]);
            $success = "Tarif margin #{$idmargin} berhasil diubah menjadi {$persen}%!";
        } catch (Exception $e) {
            $error = 'Gagal mengupdate margin: ' . $e->getMessage();
        }
    }
}

if (isset($_GET['hapus'])) {
    $idHapus = (int)$_GET['hapus'];
    if ($idHapus > 0) {
        try {
            $cekAktif = $pdo->prepare("SELECT status FROM margin_penjualan WHERE idmargin_penjualan = ?");
            $cekAktif->execute([$idHapus]);
            $isAktif = $cekAktif->fetchColumn();

            if ($isAktif == 1) {
                $error = "Margin #{$idHapus} sedang aktif digunakan oleh kasir dan tidak boleh dihapus! Aktifkan tarif lain terlebih dahulu.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM margin_penjualan WHERE idmargin_penjualan = ?");
                $stmt->execute([$idHapus]);
                $success = "Skema margin #{$idHapus} berhasil dihapus dari database!";
            }
        } catch (PDOException $e) {
            $error = "Margin #{$idHapus} sudah tercatat dalam riwayat transaksi kasir dan tidak dapat dihapus demi integritas data audit.";
        }
    }
}

$editMargin = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmtEdit = $pdo->prepare("SELECT * FROM margin_penjualan WHERE idmargin_penjualan = ?");
    $stmtEdit->execute([$editId]);
    $editMargin = $stmtEdit->fetch();
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$margins = $pdo->query("
    SELECT m.*, u.username
    FROM margin_penjualan m
    LEFT JOIN user u ON m.iduser = u.iduser
    ORDER BY m.idmargin_penjualan DESC
")->fetchAll();

$currentActive = getActiveMargin($pdo);
?>

<div class="c-card-header" style="margin-bottom: var(--space-md);">
  <div>
    <div class="mono" style="color: var(--color-accent-2); font-size: 0.75rem;">PENGATURAN HARGA &amp; KEBIJAKAN LABA</div>
    <h1 class="dsp" style="font-size: 2.2rem; margin: 0;">Margin Penjualan Toko</h1>
  </div>
  <span class="mono badge badge-inflow">Margin Aktif Kasir: <?= $currentActive ?>%</span>
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

<?php if ($editMargin): ?>
  <div class="c-card c-card--mustard" style="margin-bottom: var(--space-xl); border: 2px solid var(--color-ink);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--space-sm);">
      <h2 class="c-card-title">Edit Tarif Margin #<?= $editMargin['idmargin_penjualan'] ?></h2>
      <a href="master-margin.php" class="btn btn-sm btn-secondary">Batal</a>
    </div>

    <form method="POST" action="master-margin.php" style="display:flex; flex-wrap:wrap; gap:var(--space-md); align-items:flex-end;">
      <input type="hidden" name="action" value="update_margin">
      <input type="hidden" name="idmargin_penjualan" value="<?= $editMargin['idmargin_penjualan'] ?>">

      <div class="form-group" style="margin:0; width:260px;">
        <label class="form-label">Persentase Laba / Margin (%)</label>
        <input type="number" name="persen" step="0.1" class="form-control" value="<?= $editMargin['persen'] ?>" required>
      </div>
      <button type="submit" class="btn btn-secondary">Simpan Perubahan &rarr;</button>
    </form>
  </div>
<?php else: ?>
  <div class="c-card" style="margin-bottom: var(--space-xl); background: var(--color-paper-2);">
    <h2 class="c-card-title" style="margin-bottom: var(--space-md);">Tambah Skema Tarif Baru</h2>
    <form method="POST" action="master-margin.php" style="display:flex; flex-wrap:wrap; gap:var(--space-md); align-items:flex-end;">
      <input type="hidden" name="action" value="tambah_margin">
      <div class="form-group" style="margin:0; width:260px;">
        <label class="form-label">Persentase Laba / Margin (%)</label>
        <input type="number" name="persen" step="0.1" class="form-control" placeholder="Contoh: 18.5" required>
      </div>
      <button type="submit" class="btn btn-primary">Simpan Skema Margin &rarr;</button>
    </form>
  </div>
<?php endif; ?>

<div class="c-card">
  <div class="c-card-header">
    <h2 class="c-card-title">Daftar Skema Margin Toko</h2>
    <span class="mono badge badge-active"><?= count($margins) ?> Skema Terdaftar</span>
  </div>

  <div class="table-responsive">
    <table class="c-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Tarif Persen</th>
          <th>Status Penerapan</th>
          <th>Ditetapkan Oleh</th>
          <th>Terakhir Diperbarui</th>
          <th style="text-align:center;">Tindakan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($margins as $m): ?>
          <tr style="<?= $m['status'] == 1 ? 'background:var(--color-paper-2); font-weight:bold;' : '' ?>">
            <td class="mono">#<?= $m['idmargin_penjualan'] ?></td>
            <td><strong style="font-size:1.25rem;"><?= $m['persen'] ?>%</strong></td>
            <td>
              <span class="mono badge <?= $m['status'] == 1 ? 'badge-inflow' : 'badge' ?>">
                <?= $m['status'] == 1 ? '✓ Digunakan di Kasir' : 'Tidak Aktif' ?>
              </span>
            </td>
            <td class="mono"><?= htmlspecialchars($m['username'] ?? 'admin') ?></td>
            <td class="mono"><?= $m['updated_at'] ?></td>
            <td style="text-align:center; white-space:nowrap;">
              <?php if ($m['status'] != 1): ?>
                <a href="master-margin.php?aktifkan=<?= $m['idmargin_penjualan'] ?>" class="btn btn-sm btn-primary" title="Aktifkan Tarif">
                  Aktifkan
                </a>
              <?php else: ?>
                <span class="mono" style="color:var(--color-accent-2); font-weight:800; padding:0.2rem 0.5rem;">[Aktif]</span>
              <?php endif; ?>

              <a href="master-margin.php?edit=<?= $m['idmargin_penjualan'] ?>" class="btn btn-sm btn-secondary" style="margin-left:0.25rem;" title="Edit Nilai">
                Edit
              </a>

              <?php if ($m['status'] != 1): ?>
                <a href="master-margin.php?hapus=<?= $m['idmargin_penjualan'] ?>" class="btn btn-sm btn-danger" style="margin-left:0.25rem;" onclick="return confirm('Hapus skema margin <?= $m['persen'] ?>%?')" title="Hapus">
                  Hapus
                </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
