<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = '127.0.0.1';
$db_port = '3306';
$db_name = 'uts_pbd_026';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("
    <div style='background:#fecdd3; border:3px solid #881337; padding:2rem; font-family:sans-serif; max-width:600px; margin:3rem auto;'>
        <h2 style='color:#881337; margin-top:0;'>⚠️ Gagal Terhubung ke MariaDB Laragon</h2>
        <p>Pastikan servis <strong>MySQL</strong> di Laragon sudah di-klik <strong>Start</strong> dan database <code>{$db_name}</code> sudah ada.</p>
        <p><small style='color:#881337;'>Pesan Error: " . htmlspecialchars($e->getMessage()) . "</small></p>
    </div>
    ");
}

function formatRupiah($val) {
    return 'Rp ' . number_format($val ?? 0, 0, ',', '.');
}

function getNextId($pdo, $table, $column) {
    $stmt = $pdo->query("SELECT MAX({$column}) AS max_id FROM {$table}");
    $row = $stmt->fetch();
    return ($row && $row['max_id']) ? ((int)$row['max_id'] + 1) : 1;
}

function getItemCurrentStock($pdo, $idbarang) {
    $stmt = $pdo->prepare("SELECT stock FROM kartu_stok WHERE idbarang = ? ORDER BY idkartu_stok DESC LIMIT 1");
    $stmt->execute([$idbarang]);
    $res = $stmt->fetchColumn();
    return ($res !== false) ? (int)$res : 0;
}

function getActiveMargin($pdo) {
    $stmt = $pdo->query("SELECT persen FROM margin_penjualan WHERE status = 1 ORDER BY updated_at DESC LIMIT 1");
    $res = $stmt->fetchColumn();
    return ($res !== false) ? (float)$res : 12.5;
}
