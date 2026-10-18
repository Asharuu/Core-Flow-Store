<?php
$pageTitle = 'Penerimaan Barang Gudang';
require_once __DIR__ . '/config/database.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'terima_barang') {
    $idpengadaan = (int)($_POST['idpengadaan'] ?? 0);

    if ($idpengadaan <= 0) {
        $error = 'ID Pengadaan tidak valid.';
    } else {
        try {
            $pdo->beginTransaction();

            $check = $pdo->prepare("SELECT * FROM pengadaan WHERE idpengadaan = ? AND status = 'P'");
            $check->execute([$idpengadaan]);
            $po = $check->fetch();

            if (!$po) {
                throw new Exception("PO #{$idpengadaan} sudah selesai diproses atau tidak ditemukan.");
            }

            $detPoStmt = $pdo->prepare("SELECT * FROM detail_pengadaan WHERE idpengadaan = ?");
            $detPoStmt->execute([$idpengadaan]);
            $details = $detPoStmt->fetchAll();

            $idpenerimaan = getNextId($pdo, 'penerimaan', 'idpenerimaan');
            $iduser = $_SESSION['user']['iduser'] ?? 3;

            $stmtTerima = $pdo->prepare("INSERT INTO penerimaan (idpenerimaan, created_at, status, idpengadaan, iduser) VALUES (?, NOW(), 'A', ?, ?)");
            $stmtTerima->execute([$idpenerimaan, $idpengadaan, $iduser]);

            $pdo->prepare("UPDATE pengadaan SET status = 'S' WHERE idpengadaan = ?")->execute([$idpengadaan]);

            $stmtDetTerima = $pdo->prepare("INSERT INTO detail_penerimaan (iddetail_penerimaan, idpenerimaan, barang_idbarang, jumlah_terima, harga_satuan_terima, sub_total_terima) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtKartu = $pdo->prepare("INSERT INTO kartu_stok (idkartu_stok, jenis_transaksi, masuk, keluar, stock, created_at, idtransaksi, idbarang) VALUES (?, 'M', ?, 0, ?, NOW(), ?, ?)");

            $detTerimaId = getNextId($pdo, 'detail_penerimaan', 'iddetail_penerimaan');
            $kartuId = getNextId($pdo, 'kartu_stok', 'idkartu_stok');

            foreach ($details as $it) {
                $stmtDetTerima->execute([$detTerimaId++, $idpenerimaan, $it['idbarang'], $it['jumlah'], $it['harga_satuan'], $it['sub_total']]);

                $currentStock = getItemCurrentStock($pdo, $it['idbarang']);
                $newStock = $currentStock + (int)$it['jumlah'];

                $stmtKartu->execute([$kartuId++, $it['jumlah'], $newStock, $idpenerimaan, $it['idbarang']]);
            }

            $pdo->commit();
            $success = "Penerimaan PO #{$idpengadaan} berhasil diverifikasi! Stok fisik gudang telah bertambah dan dicatat di Kartu Stok.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal memproses penerimaan: ' . $e->getMessage();
        }
    }
}

$autoSelectId = (int)($_GET['terima'] ?? 0);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$pendingPOs = $pdo->query("
    SELECT p.*, v.nama_vendor, u.username,
           d.jumlah, d.harga_satuan, b.nama AS nama_barang, s.nama_satuan
    FROM pengadaan p
    LEFT JOIN vendor v ON p.vendor_idvendor = v.idvendor
    LEFT JOIN user u ON p.user_iduser = u.iduser
    LEFT JOIN detail_pengadaan d ON p.idpengadaan = d.idpengadaan
    LEFT JOIN barang b ON d.idbarang = b.idbarang
    LEFT JOIN satuan s ON b.idsatuan = s.idsatuan
    WHERE p.status = 'P'
    ORDER BY p.idpengadaan ASC
")->fetchAll();

$receivedHistory = $pdo->query("
    SELECT r.*, p.idpengadaan, v.nama_vendor, u.username
    FROM penerimaan r
    LEFT JOIN pengadaan p ON r.idpengadaan = p.idpengadaan
    LEFT JOIN vendor v ON p.vendor_idvendor = v.idvendor
    LEFT JOIN user u ON r.iduser = u.iduser
    ORDER BY r.idpenerimaan DESC LIMIT 10
")->fetchAll();
?>

<div class="c-card-header" style="margin-bottom: var(--space-md);">
  <div>
    <div class="mono" style="color: var(--color-accent-2); font-size: 0.75rem;">LOGISTIK &amp; GUDANG</div>
    <h1 class="dsp" style="font-size: 2.2rem; margin: 0;">Penerimaan Barang Masuk</h1>
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

<div class="c-card" style="margin-bottom: var(--space-xl);">
  <div class="c-card-header">
    <h2 class="c-card-title">Antrean Barang Masuk dari Vendor (<?= count($pendingPOs) ?>)</h2>
  </div>

  <?php if (empty($pendingPOs)): ?>
    <div style="text-align: center; padding: 2.5rem; color: var(--color-muted);">
      <div class="dsp" style="font-size: 1.5rem; margin-bottom: 0.4rem;">Semua Pengiriman Sudah Diterima</div>
      <p style="margin: 0; font-size: var(--text-sm);">Tidak ada antrean pesanan pembelian yang menunggu verifikasi di gudang.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="c-table">
        <thead>
          <tr>
            <th>ID PO</th>
            <th>Tanggal Pesan</th>
            <th>Vendor Rekanan</th>
            <th>Rincian Barang</th>
            <th>Jumlah Masuk</th>
            <th>Total Biaya</th>
            <th>Konfirmasi Gudang</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pendingPOs as $po): ?>
            <tr style="<?= $po['idpengadaan'] == $autoSelectId ? 'background:var(--color-paper-2);' : '' ?>">
              <td class="mono">#<?= $po['idpengadaan'] ?></td>
              <td class="mono"><?= $po['timestamp'] ?></td>
              <td><strong><?= htmlspecialchars($po['nama_vendor']) ?></strong></td>
              <td><?= htmlspecialchars($po['nama_barang'] ?? '-') ?></td>
              <td>
                <span class="mono badge badge-inflow">+<?= $po['jumlah'] ?? 0 ?> <?= htmlspecialchars($po['nama_satuan'] ?? 'Unit') ?></span>
              </td>
              <td class="mono"><?= formatRupiah($po['total_nilai']) ?></td>
              <td>
                <form method="POST" action="penerimaan.php" style="margin:0;">
                  <input type="hidden" name="action" value="terima_barang">
                  <input type="hidden" name="idpengadaan" value="<?= $po['idpengadaan'] ?>">
                  <button type="submit" class="btn btn-sm btn-primary">
                    Terima Barang &rarr;
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="c-card">
  <div class="c-card-header">
    <h2 class="c-card-title">Riwayat Penerimaan Gudang</h2>
    <span class="mono badge badge-active"><?= count($receivedHistory) ?> Catatan</span>
  </div>
  <div class="table-responsive">
    <table class="c-table">
      <thead>
        <tr>
          <th>ID Terima</th>
          <th>Waktu Penerimaan</th>
          <th>ID PO</th>
          <th>Vendor</th>
          <th>Petugas Penerima</th>
          <th>Status Dokumen</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($receivedHistory)): ?>
          <tr><td colspan="6" style="text-align:center; padding:1.2rem;">Belum ada riwayat penerimaan barang.</td></tr>
        <?php else: ?>
          <?php foreach ($receivedHistory as $rh): ?>
            <tr>
              <td class="mono">#<?= $rh['idpenerimaan'] ?></td>
              <td class="mono"><?= $rh['created_at'] ?></td>
              <td class="mono">#<?= $rh['idpengadaan'] ?></td>
              <td><strong><?= htmlspecialchars($rh['nama_vendor'] ?? '-') ?></strong></td>
              <td class="mono"><?= htmlspecialchars($rh['username'] ?? '-') ?></td>
              <td><span class="mono badge badge-inflow">✓ Diterima</span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
