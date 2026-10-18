<?php
$pageTitle = 'Ringkasan Operasional';
require_once __DIR__ . '/config/database.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$totalBarang = $pdo->query("SELECT COUNT(*) FROM barang")->fetchColumn();
$totalVendor = $pdo->query("SELECT COUNT(*) FROM vendor")->fetchColumn();
$pendingPO   = $pdo->query("SELECT COUNT(*) FROM pengadaan WHERE status = 'P'")->fetchColumn();

$omzetStmt = $pdo->query("SELECT COALESCE(SUM(total_nilai), 0) AS total_omzet, COUNT(*) AS count_pj FROM penjualan");
$omzetRow = $omzetStmt->fetch();
$totalOmzet = $omzetRow['total_omzet'] ?? 0;
$countPj = $omzetRow['count_pj'] ?? 0;

$activeMargin = getActiveMargin($pdo);

$barangStmt = $pdo->query("
    SELECT b.*, s.nama_satuan,
           COALESCE((SELECT stock FROM kartu_stok WHERE idbarang = b.idbarang ORDER BY idkartu_stok DESC LIMIT 1), 0) AS cur_stock
    FROM barang b
    LEFT JOIN satuan s ON b.idsatuan = s.idsatuan
    ORDER BY b.idbarang ASC
");
$barangList = $barangStmt->fetchAll();
$totalStock = array_sum(array_column($barangList, 'cur_stock'));
?>

<div class="c-card c-card--mustard" style="margin-bottom: var(--space-lg); padding: var(--space-lg);">
  <div class="mono" style="font-weight: 800; font-size: 0.75rem; letter-spacing: 0.1em; margin-bottom: 0.2rem;">
    STATUS TOKO · OPERASIONAL AKTIF
  </div>
  <h1 class="dsp" style="font-size: 2.2rem; margin: 0; line-height: 1;">
    RINGKASAN OPERASIONAL TOKO
  </h1>
  <p style="margin: 0.5rem 0 0; max-width: 65ch; font-size: var(--text-sm);">
    Pantau pergerakan katalog inventori barang, status pemesanan ke vendor suplier, dan rekapitulasi penjualan harian.
  </p>
</div>

<div class="kpi-grid">
  <div class="kpi-card">
    <div class="mono" style="color: var(--color-muted); font-size: 0.75rem;">TOTAL KATALOG BARANG</div>
    <div class="kpi-val"><?= $totalBarang ?></div>
    <div class="mono" style="color: var(--color-accent-2); font-size: 0.75rem;"><?= $totalVendor ?> Vendor Rekanan</div>
  </div>
  <div class="kpi-card">
    <div class="mono" style="color: var(--color-muted); font-size: 0.75rem;">TOTAL UNIT STOK FISIK</div>
    <div class="kpi-val"><?= number_format($totalStock) ?></div>
    <div class="mono" style="color: var(--color-ink-2); font-size: 0.75rem;">Unit di Gudang</div>
  </div>
  <div class="kpi-card">
    <div class="mono" style="color: var(--color-muted); font-size: 0.75rem;">PO MENUNGGU PENERIMAAN</div>
    <div class="kpi-val" style="color: var(--color-accent-2);"><?= $pendingPO ?></div>
    <div class="mono" style="font-size: 0.75rem;">Pesanan dalam Pengiriman</div>
  </div>
  <div class="kpi-card">
    <div class="mono" style="color: var(--color-muted); font-size: 0.75rem;">MARGIN PENJUALAN AKTIF</div>
    <div class="kpi-val" style="color: var(--color-ink);"><?= $activeMargin ?>%</div>
    <div class="mono" style="color: var(--color-accent-2); font-size: 0.75rem;"><?= $countPj ?> Transaksi Kasir</div>
  </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-md); margin-bottom: var(--space-lg);">
  <div class="c-card" style="border-left: 6px solid var(--color-accent-2); padding: var(--space-md);">
    <div class="mono" style="color: var(--color-accent-2); font-weight: 800; font-size: 0.75rem;">LOGISTIK &amp; PASOKAN</div>
    <h3 class="dsp" style="font-size: 1.5rem; margin: 0.3rem 0;">PENGADAAN DARI VENDOR</h3>
    <p style="font-size: var(--text-sm); margin-bottom: var(--space-md); color: var(--color-ink-2);">
      Terbitkan surat pesanan (PO) ke vendor pemasok atau verifikasi kedatangan barang untuk menambah stok fisik toko.
    </p>
    <a href="pengadaan.php" class="btn btn-secondary btn-sm">Buat Pesanan PO &rarr;</a>
  </div>

  <div class="c-card" style="border-left: 6px solid var(--color-accent); padding: var(--space-md);">
    <div class="mono" style="color: var(--color-ink); font-weight: 800; font-size: 0.75rem;">TRANSAKSI KONSUMEN</div>
    <h3 class="dsp" style="font-size: 1.5rem; margin: 0.3rem 0;">LAYAR KASIR (POS)</h3>
    <p style="font-size: var(--text-sm); margin-bottom: var(--space-md); color: var(--color-ink-2);">
      Buka terminal kasir penjualan untuk melayani pembelian pelanggan dengan kalkulasi otomatis PPN dan margin laba.
    </p>
    <a href="pos.php" class="btn btn-primary btn-sm">Buka Kasir POS &rarr;</a>
  </div>
</div>

<div class="c-card">
  <div class="c-card-header">
    <h2 class="c-card-title">STATUS STOK BARANG GUDANG</h2>
    <span class="mono badge badge-active"><?= count($barangList) ?> Barang Terdaftar</span>
  </div>
  <div class="table-responsive">
    <table class="c-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nama Barang</th>
          <th>Satuan</th>
          <th>Harga Jual</th>
          <th>Sisa Stok</th>
          <th>Status Stok</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($barangList as $b): ?>
          <tr>
            <td class="mono">#<?= $b['idbarang'] ?></td>
            <td><strong><?= htmlspecialchars($b['nama']) ?></strong></td>
            <td><span class="mono"><?= htmlspecialchars($b['nama_satuan'] ?? '-') ?></span></td>
            <td class="mono"><?= formatRupiah($b['harga']) ?></td>
            <td><strong style="font-size: 1.1rem; color: <?= $b['cur_stock'] <= 20 ? 'var(--color-accent-2)' : 'var(--color-ink)' ?>;"><?= $b['cur_stock'] ?></strong></td>
            <td>
              <span class="mono badge <?= $b['cur_stock'] > 20 ? 'badge-inflow' : 'badge-outflow' ?>">
                <?= $b['cur_stock'] > 20 ? 'Tersedia' : 'Menipis' ?>
              </span>
            </td>
            <td>
              <a href="pengadaan.php?idbarang=<?= $b['idbarang'] ?>" class="btn btn-sm btn-secondary">+ Pesan PO</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
