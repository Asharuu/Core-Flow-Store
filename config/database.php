<?php
if (session_status() === PHP_SESSION_NONE) {
    if (isset($_ENV['VERCEL']) || getenv('VERCEL')) {
        @session_save_path('/tmp');
    }
    session_start();
}

$db_host = getenv('DB_HOST') ?: (getenv('MYSQL_HOST') ?: '127.0.0.1');
$db_port = getenv('DB_PORT') ?: (getenv('MYSQL_PORT') ?: '3306');
$db_name = getenv('DB_NAME') ?: (getenv('MYSQL_DATABASE') ?: 'uts_pbd_026');
$db_user = getenv('DB_USER') ?: (getenv('MYSQL_USER') ?: 'root');
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQL_PASSWORD') !== false ? getenv('MYSQL_PASSWORD') : '');

// Dukungan connection string jika menggunakan provider cloud (misal DATABASE_URL)
if (getenv('DATABASE_URL')) {
    $db_parts = parse_url(getenv('DATABASE_URL'));
    if (!empty($db_parts['host'])) {
        $db_host = $db_parts['host'];
        $db_port = $db_parts['port'] ?? 3306;
        $db_user = $db_parts['user'] ?? $db_user;
        $db_pass = $db_parts['pass'] ?? $db_pass;
        $db_name = ltrim($db_parts['path'] ?? '', '/') ?: $db_name;
    }
}

try {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if ($db_host !== '127.0.0.1' && $db_host !== 'localhost') {
        $options[PDO::MYSQL_ATTR_SSL_CA] = true;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO("mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, $options);
} catch (PDOException $e) {
    $isVercel = isset($_ENV['VERCEL']) || getenv('VERCEL');
    $errorMsg = htmlspecialchars($e->getMessage());

    if ($isVercel) {
        die("
        <div style='background:#fef2f2; border:3px solid #b91c1c; padding:2rem; font-family:sans-serif; max-width:680px; margin:3rem auto; border-radius:8px;'>
            <h2 style='color:#b91c1c; margin-top:0;'>⚠️ Gagal Terhubung ke Database Cloud</h2>
            <p>Aplikasi sedang berjalan di <strong>Vercel (Production)</strong>, namun belum terhubung ke database MySQL/MariaDB cloud.</p>
            <p>Silakan tambahkan <strong>Environment Variables</strong> di dashboard Vercel (<em>Settings &rarr; Environment Variables</em>):</p>
            <ul style='background:#fff; padding:1rem 2rem; border:1px solid #fca5a5;'>
                <li><code>DB_HOST</code> (Host database cloud)</li>
                <li><code>DB_PORT</code> (Port MySQL, default: 3306)</li>
                <li><code>DB_NAME</code> (Nama database)</li>
                <li><code>DB_USER</code> (Username database)</li>
                <li><code>DB_PASS</code> (Password database)</li>
            </ul>
            <p><small style='color:#7f1d1d;'>Detail Error: {$errorMsg}</small></p>
        </div>
        ");
    } else {
        die("
        <div style='background:#fecdd3; border:3px solid #881337; padding:2rem; font-family:sans-serif; max-width:600px; margin:3rem auto;'>
            <h2 style='color:#881337; margin-top:0;'>⚠️ Gagal Terhubung ke MariaDB Laragon</h2>
            <p>Pastikan servis <strong>MySQL</strong> di Laragon sudah di-klik <strong>Start</strong> dan database <code>{$db_name}</code> sudah ada.</p>
            <p><small style='color:#881337;'>Pesan Error: {$errorMsg}</small></p>
        </div>
        ");
    }
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
