<?php
$pageTitle = 'Buku Mutasi Kartu Stok';
require_once __DIR__ . '/config/database.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$filterBarang = (int)($_GET['idbarang'] ?? 0);
$filterJenis  = trim($_GET['jenis'] ?? '');

$sql = "
    SELECT k.*, b.nama AS nama_barang, s.nama_satuan
    FROM kartu_stok k
    LEFT JOIN barang b ON k.idbarang = b.idbarang
    LEFT JOIN satuan s ON b.idsatuan = s.idsatuan
    WHERE 1=1
";
$params = [];

if ($filterBarang > 0) {
    $sql .= " AND k.idbarang = ?";
    $params[] = $filterBarang;
}
if ($filterJenis !== '') {
    $sql .= " AND k.jenis_transaksi = ?";
    $params[] = $filterJenis;
}

$sql .= " ORDER BY k.idkartu_stok DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$barangOptions = $pdo->query("SELECT idbarang, nama FROM barang ORDER BY nama ASC")->fetchAll();
?>

<div class="c-card-header" style="margin-bottom: var(--space-md);">
  <div>
    <div class="mono" style="color: var(--color-accent-2); font-size: 0.75rem;">AUDIT &amp; MUTASI INVENTORI</div>
    <h1 class="dsp" style="font-size: 2.2rem; margin: 0;">Buku Mutasi Kartu Stok</h1>
  </div>
  <span class="mono badge badge-active"><?= count($logs) ?> Catatan Terakhir</span>
</div>

<div class="c-card" style="margin-bottom: var(--space-lg); background: var(--color-paper-2);">
  <form method="GET" action="kartu-stok.php" style="display:flex; flex-wrap:wrap; gap:var(--space-md); align-items:flex-end;">
    <div class="form-group" style="margin:0; flex:1; min-width:200px;">
      <label class="form-label">Pilih Produk</label>
      <select name="idbarang" class="form-control">
        <option value="0">-- Semua Barang Dagangan --</option>
        <?php foreach ($barangOptions as $bo): ?>
          <option value="<?= $bo['idbarang'] ?>" <?= $bo['idbarang'] == $filterBarang ? 'selected' : '' ?>>
            <?= htmlspecialchars($bo['nama']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group" style="margin:0; width:220px;">
      <label class="form-label">Jenis Mutasi</label>
      <select name="jenis" class="form-control">
        <option value="">Semua (Masuk &amp; Keluar)</option>
        <option value="M" <?= $filterJenis === 'M' ? 'selected' : '' ?>>Barang Masuk (Inflow)</option>
        <option value="K" <?= $filterJenis === 'K' ? 'selected' : '' ?>>Barang Keluar (Outflow)</option>
      </select>
    </div>

    <div>
      <button type="submit" class="btn btn-secondary">Filter Data</button>
      <a href="kartu-stok.php" class="btn btn-primary" style="margin-left:0.3rem;">Reset</a>
    </div>
  </form>
</div>

<div class="c-card">
  <div class="table-responsive">
    <table class="c-table">
      <thead>
        <tr>
          <th>ID Mutasi</th>
          <th>Waktu Transaksi</th>
          <th>Nama Barang</th>
          <th>Jenis Aliran</th>
          <th>Masuk (+)</th>
          <th>Keluar (-)</th>
          <th>Sisa Stok</th>
          <th>Ref. Transaksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr><td colspan="8" style="text-align:center; padding:2rem;">Belum ada catatan mutasi stok yang sesuai kriteria.</td></tr>
        <?php else: ?>
          <?php foreach ($logs as $k): ?>
            <tr>
              <td class="mono">#<?= $k['idkartu_stok'] ?></td>
              <td class="mono"><?= $k['created_at'] ?></td>
              <td><strong><?= htmlspecialchars($k['nama_barang'] ?? '-') ?></strong></td>
              <td>
                <span class="mono badge <?= $k['jenis_transaksi'] === 'M' ? 'badge-inflow' : 'badge-outflow' ?>">
                  <?= $k['jenis_transaksi'] === 'M' ? 'Masuk' : 'Keluar' ?>
                </span>
              </td>
              <td class="mono" style="color:#15803d; font-weight:800;">
                <?= $k['masuk'] > 0 ? "+{$k['masuk']}" : '-' ?>
              </td>
              <td class="mono" style="color:var(--color-accent-2); font-weight:800;">
                <?= $k['keluar'] > 0 ? "-{$k['keluar']}" : '-' ?>
              </td>
              <td class="mono" style="font-size:1.15rem; font-weight:900;">
                <?= $k['stock'] ?>
              </td>
              <td class="mono">
                #<?= $k['idtransaksi'] ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
