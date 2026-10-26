<?php
$pageTitle = 'Produk';
require_once __DIR__ . '/config/database.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_barang') {
    $nama   = trim($_POST['nama'] ?? '');
    $jenis  = trim($_POST['jenis'] ?? 'M');
    $satuan = (int)($_POST['idsatuan'] ?? 1);
    $harga  = (int)($_POST['harga'] ?? 0);

    if ($nama === '' || $harga <= 0) {
        $error = 'Nama barang dan harga wajib diisi!';
    } else {
        try {
            $idbarang = getNextId($pdo, 'barang', 'idbarang');
            $stmt = $pdo->prepare("INSERT INTO barang (idbarang, jenis, nama, idsatuan, status, harga) VALUES (?, ?, ?, ?, 1, ?)");
            $stmt->execute([$idbarang, $jenis, $nama, $satuan, $harga]);
            $success = "Barang \"{$nama}\" berhasil ditambahkan ke database!";
        } catch (Exception $e) {
            $error = 'Gagal menambah barang: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_barang') {
    $idbarang = (int)($_POST['idbarang'] ?? 0);
    $nama     = trim($_POST['nama'] ?? '');
    $jenis    = trim($_POST['jenis'] ?? 'M');
    $satuan   = (int)($_POST['idsatuan'] ?? 1);
    $harga    = (int)($_POST['harga'] ?? 0);
    $status   = (int)($_POST['status'] ?? 1);

    if ($idbarang <= 0 || $nama === '' || $harga <= 0) {
        $error = 'Data update barang tidak valid!';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE barang SET nama = ?, jenis = ?, idsatuan = ?, harga = ?, status = ? WHERE idbarang = ?");
            $stmt->execute([$nama, $jenis, $satuan, $harga, $status, $idbarang]);
            $success = "Data barang #{$idbarang} (\"{$nama}\") berhasil diperbarui!";
        } catch (Exception $e) {
            $error = 'Gagal mengupdate barang: ' . $e->getMessage();
        }
    }
}

if (isset($_GET['hapus'])) {
    $idHapus = (int)$_GET['hapus'];
    if ($idHapus > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM barang WHERE idbarang = ?");
            $stmt->execute([$idHapus]);
            $success = "Barang #{$idHapus} berhasil dihapus permanen dari database!";
        } catch (PDOException $e) {
            $stmtSoft = $pdo->prepare("UPDATE barang SET status = 0 WHERE idbarang = ?");
            $stmtSoft->execute([$idHapus]);
            $success = "Barang #{$idHapus} memiliki riwayat transaksi/stok, status otomatis diubah menjadi NON-AKTIF (Soft Delete).";
        }
    }
}

$editItem = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmtEdit = $pdo->prepare("SELECT * FROM barang WHERE idbarang = ?");
    $stmtEdit->execute([$editId]);
    $editItem = $stmtEdit->fetch();
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$satuanList = $pdo->query("SELECT id, nama, status
                          FROM v_satuan
                          ORDER BY id ASC")->fetchAll();

$barangList = $pdo->query("
    SELECT vb.id AS idbarang, vb.jenis, vb.nama, vb.`id satuan` AS idsatuan, vb.status, vb.harga,
           vs.nama AS nama_satuan,
           COALESCE((SELECT stock FROM kartu_stok WHERE idbarang = vb.id ORDER BY idkartu_stok DESC LIMIT 1), 0) AS cur_stock
    FROM v_barang vb
    LEFT JOIN v_satuan vs ON vb.`id satuan` = vs.id
    ORDER BY vb.id ASC
")->fetchAll();
?>

<div class="c-card-header" style="margin-bottom: var(--space-md);">
  <div>
    <div class="mono" style="color: var(--color-accent-2); font-size: 0.75rem;">KATALOG &amp; INVENTORI</div>
    <h1 class="dsp" style="font-size: 2.2rem; margin: 0;">Katalog Produk Toko</h1>
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

<?php if ($editItem): ?>
  <div class="c-card c-card--mustard" style="margin-bottom: var(--space-xl); border: 2px solid var(--color-ink);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--space-sm);">
      <h2 class="c-card-title">Edit Data Produk #<?= $editItem['idbarang'] ?></h2>
      <a href="produk.php" class="btn btn-sm btn-secondary">Batal</a>
    </div>

    <form method="POST" action="produk.php">
      <input type="hidden" name="action" value="update_barang">
      <input type="hidden" name="idbarang" value="<?= $editItem['idbarang'] ?>">

      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:var(--space-md);">
        <div class="form-group">
          <label class="form-label">Nama Barang</label>
          <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($editItem['nama']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Kategori / Jenis</label>
          <select name="jenis" class="form-control" required>
            <option value="M" <?= $editItem['jenis'] === 'M' ? 'selected' : '' ?>>Makanan / Minuman / Konsumsi</option>
            <option value="N" <?= $editItem['jenis'] === 'N' ? 'selected' : '' ?>>Non-Food / Barang Lain</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Satuan</label>
          <select name="idsatuan" class="form-control" required>
            <?php foreach ($satuanList as $s): ?>
              <?php $sId = $s['id'] ?? $s['idsatuan']; ?>
              <?php $sNama = $s['nama'] ?? $s['nama_satuan']; ?>
              <option value="<?= $sId ?>" <?= $sId == $editItem['idsatuan'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($sNama) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Harga Jual (Rp)</label>
          <input type="number" name="harga" class="form-control" value="<?= $editItem['harga'] ?>" min="100" required>
        </div>
        <div class="form-group">
          <label class="form-label">Status Produk</label>
          <select name="status" class="form-control" required>
            <option value="1" <?= $editItem['status'] == 1 ? 'selected' : '' ?>>Aktif Dijual</option>
            <option value="0" <?= $editItem['status'] == 0 ? 'selected' : '' ?>>Nonaktif (Disembunyikan)</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-secondary" style="margin-top:var(--space-sm);">Simpan Perubahan &rarr;</button>
    </form>
  </div>
<?php else: ?>
  <div class="c-card" style="margin-bottom: var(--space-xl); background: var(--color-paper-2);">
    <h2 class="c-card-title" style="margin-bottom: var(--space-md);">Tambah Produk Baru</h2>
    <form method="POST" action="produk.php">
      <input type="hidden" name="action" value="tambah_barang">
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:var(--space-md);">
        <div class="form-group">
          <label class="form-label">Nama Barang</label>
          <input type="text" name="nama" class="form-control" placeholder="Contoh: Kopi Tubruk 100g" required>
        </div>
        <div class="form-group">
          <label class="form-label">Kategori / Jenis</label>
          <select name="jenis" class="form-control" required>
            <option value="M">Makanan / Minuman / Konsumsi</option>
            <option value="N">Non-Food / Barang Lainnya</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Satuan</label>
          <select name="idsatuan" class="form-control" required>
            <?php foreach ($satuanList as $s): ?>
              <?php $sId = $s['id'] ?? $s['idsatuan']; ?>
              <?php $sNama = $s['nama'] ?? $s['nama_satuan']; ?>
              <option value="<?= $sId ?>"><?= htmlspecialchars($sNama) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Harga Jual (Rp)</label>
          <input type="number" name="harga" class="form-control" placeholder="Contoh: 15000" min="500" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:var(--space-sm);">Simpan Produk &rarr;</button>
    </form>
  </div>
<?php endif; ?>

<div class="c-card">
  <div class="c-card-header">
    <h2 class="c-card-title">Daftar Produk Terdaftar</h2>
    <span class="mono badge badge-active"><?= count($barangList) ?> Produk</span>
  </div>

  <div class="table-responsive">
    <table class="c-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nama Barang</th>
          <th>Jenis</th>
          <th>Satuan</th>
          <th>Harga Jual</th>
          <th>Stok Fisik</th>
          <th>Status</th>
          <th style="text-align:center;">Tindakan</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($barangList as $b): ?>
          <tr style="<?= $b['status'] == 0 ? 'opacity:0.6; background:#f5f5f5;' : '' ?>">
            <td class="mono">#<?= $b['idbarang'] ?></td>
            <td><strong><?= htmlspecialchars($b['nama']) ?></strong></td>
            <td><span class="mono badge"><?= $b['jenis'] === 'M' ? 'Konsumsi' : 'Non-Food' ?></span></td>
            <td><span class="mono"><?= htmlspecialchars($b['nama_satuan'] ?? '-') ?></span></td>
            <td class="mono"><?= formatRupiah($b['harga']) ?></td>
            <td>
              <span class="mono badge <?= $b['cur_stock'] > 20 ? 'badge-inflow' : 'badge-outflow' ?>">
                <?= $b['cur_stock'] ?> <?= htmlspecialchars($b['nama_satuan'] ?? 'Unit') ?>
              </span>
            </td>
            <td>
              <span class="mono badge <?= $b['status'] == 1 ? 'badge-inflow' : 'badge-outflow' ?>">
                <?= $b['status'] == 1 ? 'Aktif' : 'Nonaktif' ?>
              </span>
            </td>
            <td style="text-align:center; white-space:nowrap;">
              <a href="produk.php?edit=<?= $b['idbarang'] ?>" class="btn btn-sm btn-secondary" title="Edit Data">
                Edit
              </a>
              <a href="produk.php?hapus=<?= $b['idbarang'] ?>" class="btn btn-sm btn-danger" style="margin-left:0.25rem;" onclick="return confirm('Apakah Anda yakin ingin menghapus barang \'<?= htmlspecialchars(addslashes($b['nama'])) ?>\'?')" title="Hapus Barang">
                Hapus
              </a>
              <a href="pengadaan.php?idbarang=<?= $b['idbarang'] ?>" class="btn btn-sm btn-primary" style="margin-left:0.25rem;" title="Order Pengadaan">
                + PO
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
