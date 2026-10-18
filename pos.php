<?php
$pageTitle = 'Kasir Penjualan';
require_once __DIR__ . '/config/database.php';

$successReceipt = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $itemsPost = $_POST['items'] ?? [];
    $selectedItems = [];

    foreach ($itemsPost as $idb => $qty) {
        $qty = (int)$qty;
        if ($qty > 0) {
            $selectedItems[(int)$idb] = $qty;
        }
    }

    if (empty($selectedItems)) {
        $error = 'Keranjang belanja masih kosong. Silakan tentukan jumlah barang yang ingin dibeli.';
    } else {
        try {
            $pdo->beginTransaction();

            $marginStmt = $pdo->query("SELECT idmargin_penjualan, persen FROM margin_penjualan WHERE status = 1 ORDER BY updated_at DESC LIMIT 1");
            $marginRow = $marginStmt->fetch();
            $idmargin = $marginRow ? (int)$marginRow['idmargin_penjualan'] : 1;
            $persenMargin = $marginRow ? (float)$marginRow['persen'] : 12.5;

            $subtotalBase = 0;
            $receiptItems = [];

            foreach ($selectedItems as $idb => $qty) {
                $bStmt = $pdo->prepare("SELECT idbarang, nama, harga FROM barang WHERE idbarang = ?");
                $bStmt->execute([$idb]);
                $b = $bStmt->fetch();

                if (!$b) continue;

                $harga = (int)$b['harga'];
                $sub = $harga * $qty;
                $subtotalBase += $sub;

                $receiptItems[] = [
                    'idbarang' => $b['idbarang'],
                    'nama'     => $b['nama'],
                    'harga'    => $harga,
                    'qty'      => $qty,
                    'subtotal' => $sub
                ];
            }

            $marginNominal = (int) round($subtotalBase * ($persenMargin / 100));
            $subtotalWithMargin = $subtotalBase + $marginNominal;
            $ppn = (int) round($subtotalWithMargin * 0.11);
            $grandTotal = $subtotalWithMargin + $ppn;

            $idpenjualan = getNextId($pdo, 'penjualan', 'idpenjualan');
            $iduser = $_SESSION['user']['iduser'] ?? 4;

            $stmtPj = $pdo->prepare("INSERT INTO penjualan (idpenjualan, created_at, subtotal_nilai, ppn, total_nilai, iduser, idmargin_penjualan) VALUES (?, NOW(), ?, ?, ?, ?, ?)");
            $stmtPj->execute([$idpenjualan, $subtotalBase, $ppn, $grandTotal, $iduser, $idmargin]);

            $detStmt = $pdo->prepare("INSERT INTO detail_penjualan (iddetail_penjualan, harga_satuan, jumlah, subtotal, penjualan_idpenjualan, idbarang) VALUES (?, ?, ?, ?, ?, ?)");
            $kartuStmt = $pdo->prepare("INSERT INTO kartu_stok (idkartu_stok, jenis_transaksi, masuk, keluar, stock, created_at, idtransaksi, idbarang) VALUES (?, 'K', 0, ?, ?, NOW(), ?, ?)");

            $detId = getNextId($pdo, 'detail_penjualan', 'iddetail_penjualan');
            $kartuId = getNextId($pdo, 'kartu_stok', 'idkartu_stok');

            foreach ($receiptItems as $it) {
                $detStmt->execute([$detId++, $it['harga'], $it['qty'], $it['subtotal'], $idpenjualan, $it['idbarang']]);

                $lastStock = getItemCurrentStock($pdo, $it['idbarang']);
                $newStock = max(0, $lastStock - $it['qty']);

                $kartuStmt->execute([$kartuId++, $it['qty'], $newStock, $idpenjualan, $it['idbarang']]);
            }

            $pdo->commit();

            $successReceipt = [
                'orderId'    => $idpenjualan,
                'kasir'      => $_SESSION['user']['username'] ?? 'rahel_kasir',
                'timestamp'  => date('Y-m-d H:i:s'),
                'items'      => $receiptItems,
                'subtotal'   => $subtotalBase,
                'margin'     => $marginNominal,
                'persenMargin' => $persenMargin,
                'ppn'        => $ppn,
                'grandTotal' => $grandTotal
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Terjadi kendala saat memproses transaksi: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$barangList = $pdo->query("
    SELECT b.*, s.nama_satuan,
           COALESCE((SELECT stock FROM kartu_stok WHERE idbarang = b.idbarang ORDER BY idkartu_stok DESC LIMIT 1), 0) AS cur_stock
    FROM barang b
    LEFT JOIN satuan s ON b.idsatuan = s.idsatuan
    ORDER BY b.nama ASC
")->fetchAll();

$activeMargin = getActiveMargin($pdo);
?>

<div class="c-card-header" style="margin-bottom: var(--space-md);">
  <div>
    <div class="mono" style="color: var(--color-accent-2); font-size: 0.75rem;">TERMINAL PENJUALAN TOKO</div>
    <h1 class="dsp" style="font-size: 2.2rem; margin: 0;">KASIR PENJUALAN (POS)</h1>
  </div>
  <span class="mono badge badge-active">Petugas Kasir: <?= htmlspecialchars($currentUser['username']) ?></span>
</div>

<?php if ($error): ?>
  <div class="c-card" style="background:#fecdd3; border-color:#881337; color:#881337; margin-bottom:var(--space-md); padding:0.75rem 1rem;">
    <strong>Perhatian:</strong> <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<?php if ($successReceipt): ?>
  <div class="c-card c-card--mustard" style="margin-bottom: var(--space-lg); border-left: 6px solid var(--color-ink);">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:var(--space-sm);">
      <div>
        <div class="mono" style="font-weight:700; font-size:0.75rem;">TRANSAKSI SELESAI</div>
        <h3 class="dsp" style="font-size:1.6rem; margin:0.2rem 0;">NOTA PEMBAYARAN #<?= $successReceipt['orderId'] ?></h3>
        <p style="margin:0; font-size:var(--text-sm);">Stok barang otomatis terpotong pada catatan inventori.</p>
      </div>
      <div>
        <button onclick="window.print()" class="btn btn-secondary btn-sm">Cetak Struk</button>
      </div>
    </div>

    <div style="background:#fff; color:#000; border:2px dashed var(--color-ink); max-width:380px; padding:1.2rem; margin-top:1rem; font-family:var(--font-label); font-size:var(--text-xs); box-shadow: 2px 2px 0 var(--color-ink);">
      <div style="text-align:center; border-bottom:1px dashed #000; padding-bottom:0.5rem; margin-bottom:0.5rem;">
        <div class="dsp" style="font-size:1.4rem;">CORE-FLOW STORE</div>
        <div>Bukti Pembayaran Kasir #<?= $successReceipt['orderId'] ?></div>
        <small style="color:#555;"><?= $successReceipt['timestamp'] ?> · Kasir: <?= htmlspecialchars($successReceipt['kasir']) ?></small>
      </div>
      <?php foreach ($successReceipt['items'] as $it): ?>
        <div style="display:flex; justify-content:space-between; margin:0.25rem 0;">
          <span><?= htmlspecialchars($it['nama']) ?> x <?= $it['qty'] ?></span>
          <span><?= formatRupiah($it['subtotal']) ?></span>
        </div>
      <?php endforeach; ?>
      <div style="border-top:1px dashed #000; margin-top:0.5rem; padding-top:0.5rem;">
        <div style="display:flex; justify-content:space-between;">
          <span>Subtotal:</span>
          <span><?= formatRupiah($successReceipt['subtotal']) ?></span>
        </div>
        <div style="display:flex; justify-content:space-between;">
          <span>Margin Toko (<?= $successReceipt['persenMargin'] ?>%):</span>
          <span>+<?= formatRupiah($successReceipt['margin']) ?></span>
        </div>
        <div style="display:flex; justify-content:space-between;">
          <span>PPN (11%):</span>
          <span>+<?= formatRupiah($successReceipt['ppn']) ?></span>
        </div>
        <div style="display:flex; justify-content:space-between; font-weight:800; font-size:1rem; border-top:1px dashed #000; margin-top:0.35rem; padding-top:0.35rem;">
          <span>Total Akhir:</span>
          <span><?= formatRupiah($successReceipt['grandTotal']) ?></span>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<form method="POST" action="pos.php">
  <input type="hidden" name="action" value="checkout">

  <div class="pos-container">
    <div>
      <div class="mono" style="margin-bottom:var(--space-xs); font-weight:700; font-size:0.8rem;">
        PILIH PRODUK &amp; TENTUKAN JUMLAH:
      </div>
      <div class="product-grid">
        <?php foreach ($barangList as $b): ?>
          <div class="product-card">
            <div>
              <span class="mono badge <?= $b['cur_stock'] > 20 ? 'badge-inflow' : 'badge-outflow' ?>">
                Stok: <?= $b['cur_stock'] ?>
              </span>
              <h3 class="product-card__name" style="margin-top:0.4rem; font-size:1.2rem;"><?= htmlspecialchars($b['nama']) ?></h3>
              <span class="mono" style="color:var(--color-muted); font-size:0.75rem;"><?= htmlspecialchars($b['nama_satuan'] ?? '-') ?></span>
            </div>
            <div style="margin-top:0.6rem;">
              <div class="product-card__price"><?= formatRupiah($b['harga']) ?></div>
              <div style="display:flex; align-items:center; gap:0.4rem; margin-top:0.4rem;">
                <label class="mono" style="font-size:0.75rem;">Qty:</label>
                <input type="number" name="items[<?= $b['idbarang'] ?>]" value="0" min="0" max="<?= max(0, $b['cur_stock']) ?>" class="form-control" style="padding:0.3rem; text-align:center; font-weight:800;" <?= $b['cur_stock'] <= 0 ? 'disabled' : '' ?>>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div>
      <div class="c-card c-card--oxblood" style="padding: var(--space-lg);">
        <h3 class="dsp" style="font-size:1.5rem; margin:0 0 var(--space-sm); color:var(--color-paper);">
          KETERANGAN TRANSAKSI
        </h3>
        <p style="font-size:var(--text-sm); margin-bottom:var(--space-md); color:var(--color-paper-2);">
          Isi jumlah barang yang dibeli oleh pelanggan pada kolom Qty, kemudian klik tombol konfirmasi di bawah untuk menyelesaikan pembayaran.
        </p>

        <div style="border-top:1px dashed var(--color-paper-3); padding-top:var(--space-sm); font-size:var(--text-sm);">
          <div style="display:flex; justify-content:space-between; margin-bottom:0.3rem;">
            <span>Margin Penjualan:</span>
            <span class="mono" style="color:var(--color-accent); font-weight:800;"><?= $activeMargin ?>%</span>
          </div>
          <div style="display:flex; justify-content:space-between;">
            <span>Tarif PPN:</span>
            <span class="mono" style="color:var(--color-accent); font-weight:800;">11%</span>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:var(--space-lg); font-size:1.15rem; padding:0.7rem;">
          Bayar &amp; Selesaikan Transaksi &rarr;
        </button>
      </div>
    </div>
  </div>
</form>

<?php include __DIR__ . '/includes/footer.php'; ?>
