<?php
$pageTitle = 'Retur Barang ke Vendor';
require_once __DIR__ . '/config/database.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buat_retur') {
    $iddetail_penerimaan = (int)($_POST['iddetail_penerimaan'] ?? 0);
    $jumlah              = (int)($_POST['jumlah'] ?? 0);
    $alasan              = trim($_POST['alasan'] ?? '');

    if ($iddetail_penerimaan <= 0 || $jumlah <= 0 || $alasan === '') {
        $error = 'Pilih barang penerimaan, isi jumlah retur, dan cantumkan alasan retur dengan lengkap!';
    } else {
        try {
            $pdo->beginTransaction();

            $cekStmt = $pdo->prepare("
                SELECT dp.iddetail_penerimaan, dp.idpenerimaan, dp.barang_idbarang, dp.jumlah_terima,
                       r.idpengadaan, b.nama AS nama_barang,
                       fn_cek_stok(dp.barang_idbarang) AS cur_stock,
                       COALESCE((SELECT SUM(dr.jumlah) FROM detail_retur dr WHERE dr.iddetail_penerimaan = dp.iddetail_penerimaan), 0) AS sudah_diretur
                FROM detail_penerimaan dp
                JOIN penerimaan r ON dp.idpenerimaan = r.idpenerimaan
                JOIN barang b ON dp.barang_idbarang = b.idbarang
                WHERE dp.iddetail_penerimaan = ?
            ");
            $cekStmt->execute([$iddetail_penerimaan]);
            $item = $cekStmt->fetch();

            if (!$item) {
                throw new Exception('Data detail penerimaan tidak ditemukan.');
            }

            $sisaBisaRetur = (int)$item['jumlah_terima'] - (int)$item['sudah_diretur'];
            if ($jumlah > $sisaBisaRetur) {
                throw new Exception("Jumlah retur ({$jumlah}) melebihi sisa jumlah penerimaan yang dapat diretur ({$sisaBisaRetur}).");
            }

            if ($jumlah > (int)$item['cur_stock']) {
                throw new Exception("Stok gudang saat ini ({$item['cur_stock']}) tidak mencukupi untuk diretur sebanyak {$jumlah} unit.");
            }

            $idretur = getNextId($pdo, 'retur', 'idretur');
            $iddetail_retur = getNextId($pdo, 'detail_retur', 'iddetail_retur');
            $iduser = $_SESSION['user']['iduser'] ?? 3;

            $stmtRetur = $pdo->prepare("INSERT INTO retur (idretur, created_at, idpenerimaan, idpengadaan, iduser) VALUES (?, NOW(), ?, ?, ?)");
            $stmtRetur->execute([$idretur, $item['idpenerimaan'], $item['idpengadaan'], $iduser]);

            $stmtDetRetur = $pdo->prepare("INSERT INTO detail_retur (iddetail_retur, jumlah, alasan, idretur, iddetail_penerimaan) VALUES (?, ?, ?, ?, ?)");
            $stmtDetRetur->execute([$iddetail_retur, $jumlah, $alasan, $idretur, $iddetail_penerimaan]);

            $pdo->commit();
            $success = "Retur #{$idretur} untuk \"{$item['nama_barang']}\" ({$jumlah} unit) berhasil dicatat dan stok gudang telah diperbarui.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal memproses retur: ' . $e->getMessage();
        }
    }
}

$preselectedPenerimaan = (int)($_GET['idpenerimaan'] ?? 0);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$penerimaanItems = $pdo->query("
    SELECT dp.iddetail_penerimaan, dp.idpenerimaan, dp.barang_idbarang, dp.jumlah_terima, dp.harga_satuan_terima,
           r.created_at AS tgl_terima, r.idpengadaan,
           v.nama_vendor, b.nama AS nama_barang, s.nama_satuan,
           fn_cek_stok(dp.barang_idbarang) AS cur_stock,
           COALESCE((SELECT SUM(dr.jumlah) FROM detail_retur dr WHERE dr.iddetail_penerimaan = dp.iddetail_penerimaan), 0) AS sudah_diretur
    FROM detail_penerimaan dp
    JOIN penerimaan r ON dp.idpenerimaan = r.idpenerimaan
    LEFT JOIN pengadaan p ON r.idpengadaan = p.idpengadaan
    LEFT JOIN vendor v ON p.vendor_idvendor = v.idvendor
    LEFT JOIN barang b ON dp.barang_idbarang = b.idbarang
    LEFT JOIN satuan s ON b.idsatuan = s.idsatuan
    ORDER BY dp.iddetail_penerimaan DESC
")->fetchAll();

$returHistory = $pdo->query("
    SELECT rt.idretur, rt.created_at, rt.idpenerimaan, rt.idpengadaan,
           dr.iddetail_retur, dr.jumlah, dr.alasan,
           b.nama AS nama_barang, s.nama_satuan,
           v.nama_vendor, u.username
    FROM detail_retur dr
    JOIN retur rt ON dr.idretur = rt.idretur
    LEFT JOIN detail_penerimaan dp ON dr.iddetail_penerimaan = dp.iddetail_penerimaan
    LEFT JOIN barang b ON dp.barang_idbarang = b.idbarang
    LEFT JOIN satuan s ON b.idsatuan = s.idsatuan
    LEFT JOIN pengadaan p ON rt.idpengadaan = p.idpengadaan
    LEFT JOIN vendor v ON p.vendor_idvendor = v.idvendor
    LEFT JOIN user u ON rt.iduser = u.iduser
    ORDER BY rt.idretur DESC
")->fetchAll();
?>

<div class="c-card-header" style="margin-bottom: var(--space-md);">
  <div>
    <div class="mono" style="color: var(--color-accent-2); font-size: 0.75rem;">LOGISTIK &amp; PENGEMBALIAN BARANG</div>
    <h1 class="dsp" style="font-size: 2.2rem; margin: 0;">Retur Barang ke Vendor</h1>
  </div>
  <span class="mono badge badge-outflow">Petugas: <?= htmlspecialchars($currentUser['username']) ?></span>
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
  <h2 class="c-card-title" style="margin-bottom: var(--space-md);">Catat Pengembalian (Retur) Barang</h2>

  <?php if (empty($penerimaanItems)): ?>
    <p style="margin: 0; color: var(--color-muted); font-size: var(--text-sm);">
      Belum ada penerimaan barang di gudang yang dapat diajukan retur. Silakan terima barang dari pengadaan terlebih dahulu.
    </p>
  <?php else: ?>
    <form method="POST" action="retur.php">
      <input type="hidden" name="action" value="buat_retur">

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: var(--space-md);">
        <div class="form-group" style="grid-column: span 2;">
          <label class="form-label">Pilih Dokumen Penerimaan &amp; Barang</label>
          <select name="iddetail_penerimaan" class="form-control" required>
            <?php foreach ($penerimaanItems as $pi):
              $sisaRetur = max(0, (int)$pi['jumlah_terima'] - (int)$pi['sudah_diretur']);
            ?>
              <option value="<?= $pi['iddetail_penerimaan'] ?>" <?= $pi['idpenerimaan'] == $preselectedPenerimaan ? 'selected' : '' ?> <?= $sisaRetur <= 0 ? 'disabled' : '' ?>>
                Terima #<?= $pi['idpenerimaan'] ?> (Pengadaan #<?= $pi['idpengadaan'] ?>) — <?= htmlspecialchars($pi['nama_barang'] ?? '-') ?> · <?= htmlspecialchars($pi['nama_vendor'] ?? '-') ?> (Diterima: <?= $pi['jumlah_terima'] ?>, Bisa Diretur: <?= $sisaRetur ?> <?= htmlspecialchars($pi['nama_satuan'] ?? 'Unit') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Jumlah Diretur (Qty)</label>
          <input type="number" name="jumlah" class="form-control" value="1" min="1" required>
        </div>

        <div class="form-group">
          <label class="form-label">Alasan Pengembalian</label>
          <input type="text" name="alasan" class="form-control" placeholder="Contoh: Kemasan rusak saat pengiriman" maxlength="200" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="margin-top: var(--space-sm);">
        Simpan Retur Barang &rarr;
      </button>
    </form>
  <?php endif; ?>
</div>

<div class="c-card">
  <div class="c-card-header">
    <h2 class="c-card-title">Riwayat Retur Barang ke Vendor</h2>
    <span class="mono badge badge-active"><?= count($returHistory) ?> Catatan</span>
  </div>
  <div class="table-responsive">
    <table class="c-table">
      <thead>
        <tr>
          <th>ID Retur</th>
          <th>Waktu Retur</th>
          <th>Ref. Terima</th>
          <th>Vendor</th>
          <th>Barang</th>
          <th>Jumlah Retur</th>
          <th>Alasan</th>
          <th>Petugas</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($returHistory)): ?>
          <tr><td colspan="8" style="text-align:center; padding:1.5rem;">Belum ada riwayat retur barang ke vendor.</td></tr>
        <?php else: ?>
          <?php foreach ($returHistory as $rt): ?>
            <tr>
              <td class="mono">#<?= $rt['idretur'] ?></td>
              <td class="mono"><?= $rt['created_at'] ?></td>
              <td class="mono">#<?= $rt['idpenerimaan'] ?></td>
              <td><strong><?= htmlspecialchars($rt['nama_vendor'] ?? '-') ?></strong></td>
              <td><?= htmlspecialchars($rt['nama_barang'] ?? '-') ?></td>
              <td>
                <span class="mono badge badge-outflow">-<?= $rt['jumlah'] ?> <?= htmlspecialchars($rt['nama_satuan'] ?? 'Unit') ?></span>
              </td>
              <td><?= htmlspecialchars($rt['alasan']) ?></td>
              <td class="mono"><?= htmlspecialchars($rt['username'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
