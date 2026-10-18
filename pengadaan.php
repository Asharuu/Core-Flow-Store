<?php
$pageTitle = 'Pengadaan Barang (PO)';
require_once __DIR__ . '/config/database.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_po') {
    $vendor_id = (int)($_POST['vendor_id'] ?? 0);
    $barang_id = (int)($_POST['barang_id'] ?? 0);
    $jumlah    = (int)($_POST['jumlah'] ?? 0);
    $harga     = (int)($_POST['harga_satuan'] ?? 0);

    if ($vendor_id <= 0 || $barang_id <= 0 || $jumlah <= 0 || $harga <= 0) {
        $error = 'Semua field order pengadaan wajib diisi dengan benar!';
    } else {
        try {
            $pdo->beginTransaction();

            $subtotal = $jumlah * $harga;
            $ppn = (int) round($subtotal * 0.11);
            $total = $subtotal + $ppn;

            $idpengadaan = getNextId($pdo, 'pengadaan', 'idpengadaan');
            $user_id = $_SESSION['user']['iduser'] ?? 2;

            $stmtPo = $pdo->prepare("INSERT INTO pengadaan (idpengadaan, timestamp, user_iduser, status, vendor_idvendor, subtotal_nilai, ppn, total_nilai) VALUES (?, NOW(), ?, 'P', ?, ?, ?, ?)");
            $stmtPo->execute([$idpengadaan, $user_id, $vendor_id, $subtotal, $ppn, $total]);

            $iddetail = getNextId($pdo, 'detail_pengadaan', 'iddetail_pengadaan');
            $stmtDet = $pdo->prepare("INSERT INTO detail_pengadaan (iddetail_pengadaan, harga_satuan, jumlah, sub_total, idbarang, idpengadaan) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtDet->execute([$iddetail, $harga, $jumlah, $subtotal, $barang_id, $idpengadaan]);

            $pdo->commit();
            $success = "Purchase Order #{$idpengadaan} berhasil diterbitkan dan siap dikirim ke vendor!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal membuat PO pengadaan: ' . $e->getMessage();
        }
    }
}

if (isset($_GET['batalkan'])) {
    $idPoBatal = (int)$_GET['batalkan'];
    if ($idPoBatal > 0) {
        try {
            $pdo->beginTransaction();
            $cek = $pdo->prepare("SELECT status FROM pengadaan WHERE idpengadaan = ?");
            $cek->execute([$idPoBatal]);
            $statusPo = $cek->fetchColumn();

            if ($statusPo === 'P') {
                $pdo->prepare("DELETE FROM detail_pengadaan WHERE idpengadaan = ?")->execute([$idPoBatal]);
                $pdo->prepare("DELETE FROM pengadaan WHERE idpengadaan = ?")->execute([$idPoBatal]);
                $pdo->commit();
                $success = "PO #{$idPoBatal} yang masih pending berhasil dibatalkan dan dihapus!";
            } else {
                $pdo->rollBack();
                $error = "PO #{$idPoBatal} sudah diterima di gudang (status S) dan tidak boleh dibatalkan!";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal membatalkan PO: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$vendors = $pdo->query("SELECT * FROM vendor WHERE status = 'A' ORDER BY nama_vendor ASC")->fetchAll();
$barangs = $pdo->query("SELECT * FROM barang ORDER BY nama ASC")->fetchAll();

$poList = $pdo->query("
    SELECT p.*, v.nama_vendor, u.username,
           d.jumlah, d.harga_satuan, b.nama AS nama_barang, s.nama_satuan
    FROM pengadaan p
    LEFT JOIN vendor v ON p.vendor_idvendor = v.idvendor
    LEFT JOIN user u ON p.user_iduser = u.iduser
    LEFT JOIN detail_pengadaan d ON p.idpengadaan = d.idpengadaan
    LEFT JOIN barang b ON d.idbarang = b.idbarang
    LEFT JOIN satuan s ON b.idsatuan = s.idsatuan
    ORDER BY p.idpengadaan DESC
")->fetchAll();

$preselectedBarang = (int)($_GET['idbarang'] ?? ($barangs[0]['idbarang'] ?? 0));
?>

<div class="c-card-header" style="margin-bottom: var(--space-md);">
  <div>
    <div class="mono" style="color: var(--color-accent-2); font-size: 0.75rem;">PENGADAAN &amp; PASOKAN BARANG</div>
    <h1 class="dsp" style="font-size: 2.2rem; margin: 0;">Purchase Order ke Vendor</h1>
  </div>
  <span class="mono badge badge-inflow">Petugas: <?= htmlspecialchars($currentUser['username']) ?></span>
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

<div class="c-card" style="margin-bottom: var(--space-xl); background: var(--color-paper-2);">
  <h2 class="c-card-title" style="margin-bottom: var(--space-md);">Terbitkan Purchase Order (PO) Baru</h2>

  <form method="POST" action="pengadaan.php">
    <input type="hidden" name="action" value="create_po">

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: var(--space-md);">
      <div class="form-group">
        <label class="form-label">Pilih Vendor / Rekanan</label>
        <select name="vendor_id" class="form-control" required>
          <?php foreach ($vendors as $v): ?>
            <option value="<?= $v['idvendor'] ?>">
              <?= htmlspecialchars($v['nama_vendor']) ?> (<?= $v['badan_hukum'] === 'P' ? 'PT' : 'CV' ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Pilih Produk Dipesan</label>
        <select name="barang_id" id="barang-select" class="form-control" required>
          <?php foreach ($barangs as $b): ?>
            <option value="<?= $b['idbarang'] ?>" data-harga="<?= round($b['harga'] * 0.85) ?>" <?= $b['idbarang'] == $preselectedBarang ? 'selected' : '' ?>>
              <?= htmlspecialchars($b['nama']) ?> (Harga Jual: <?= formatRupiah($b['harga']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Jumlah Pemesanan (Qty)</label>
        <input type="number" name="jumlah" class="form-control" value="25" min="1" required>
      </div>

      <div class="form-group">
        <label class="form-label">Harga Satuan Beli (Rp)</label>
        <input type="number" name="harga_satuan" id="harga-beli" class="form-control" value="3000" min="100" required>
      </div>
    </div>

    <button type="submit" class="btn btn-primary" style="margin-top: var(--space-sm);">
      Terbitkan Purchase Order &rarr;
    </button>
  </form>
</div>

<div class="c-card">
  <div class="c-card-header">
    <h2 class="c-card-title">Riwayat Transaksi Purchase Order</h2>
    <span class="mono badge badge-active"><?= count($poList) ?> Transaksi</span>
  </div>
  <div class="table-responsive">
    <table class="c-table">
      <thead>
        <tr>
          <th>ID PO</th>
          <th>Tanggal</th>
          <th>Vendor</th>
          <th>Rincian Barang</th>
          <th>Status Pengiriman</th>
          <th>Total Biaya (+PPN)</th>
          <th style="text-align:center;">Tindakan</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($poList)): ?>
          <tr><td colspan="7" style="text-align:center; padding:1.5rem;">Belum ada riwayat PO pengadaan.</td></tr>
        <?php else: ?>
          <?php foreach ($poList as $po): ?>
            <tr>
              <td class="mono">#<?= $po['idpengadaan'] ?></td>
              <td class="mono"><?= $po['timestamp'] ?></td>
              <td><strong><?= htmlspecialchars($po['nama_vendor']) ?></strong></td>
              <td><?= htmlspecialchars($po['nama_barang'] ?? '-') ?> (<?= $po['jumlah'] ?? 0 ?> <?= $po['nama_satuan'] ?? 'Unit' ?>)</td>
              <td>
                <span class="mono badge <?= $po['status'] === 'S' ? 'badge-inflow' : 'badge-pending' ?>">
                  <?= $po['status'] === 'S' ? '✓ Diterima Gudang' : 'Menunggu Kedatangan' ?>
                </span>
              </td>
              <td class="mono"><strong><?= formatRupiah($po['total_nilai']) ?></strong></td>
              <td style="text-align:center; white-space:nowrap;">
                <?php if ($po['status'] === 'P'): ?>
                  <a href="penerimaan.php?terima=<?= $po['idpengadaan'] ?>" class="btn btn-sm btn-secondary" title="Terima Barang di Gudang">
                    Terima Barang &rarr;
                  </a>
                  <a href="pengadaan.php?batalkan=<?= $po['idpengadaan'] ?>" class="btn btn-sm btn-danger" style="margin-left:0.25rem;" onclick="return confirm('Batalkan dan hapus PO #<?= $po['idpengadaan'] ?>?')" title="Batalkan PO">
                    Batal
                  </a>
                <?php else: ?>
                  <span class="mono" style="color:var(--color-muted); font-size: 0.85rem;">Selesai</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  const select = document.getElementById('barang-select');
  const hargaInput = document.getElementById('harga-beli');
  if (select && hargaInput) {
    const updateHarga = () => {
      const opt = select.options[select.selectedIndex];
      if (opt && opt.dataset.harga) {
        hargaInput.value = opt.dataset.harga;
      }
    };
    select.addEventListener('change', updateHarga);
    updateHarga();
  }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
