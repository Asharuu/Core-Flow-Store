<?php
require_once __DIR__ . '/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT u.*, r.nama_role FROM user u LEFT JOIN role r ON u.idrole = r.idrole WHERE u.username = ? AND u.password = ?");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user'] = [
                'iduser'    => $user['iduser'],
                'username'  => $user['username'],
                'idrole'    => $user['idrole'],
                'role_name' => $user['nama_role']
            ];
            $_SESSION['role'] = $user['nama_role'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Username atau kata sandi tidak ditemukan. Silakan periksa kembali.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <title>Masuk ke Sistem · Core-Flow Store</title>
  <link rel="stylesheet" href="tokens.css" />
  <link rel="stylesheet" href="styles.css" />
  <style>
    .login-wrapper {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background-color: var(--color-paper);
    }
    .login-container {
      max-width: 620px;
      margin: auto;
      padding: var(--space-xl) var(--page-gutter);
      width: 100%;
    }
    .account-picker-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: var(--space-sm);
      margin-top: var(--space-sm);
    }
    @media (min-width: 580px) {
      .account-picker-grid {
        grid-template-columns: repeat(5, 1fr);
      }
    }
    .account-chip {
      background: var(--color-paper);
      border: 2px solid var(--color-ink);
      box-shadow: 2px 2px 0 var(--color-ink);
      padding: 0.65rem 0.5rem;
      text-align: center;
      cursor: pointer;
      transition: transform var(--dur-micro), box-shadow var(--dur-micro), background var(--dur-micro);
    }
    .account-chip:hover {
      background: var(--color-accent);
      transform: translate(-2px, -2px);
      box-shadow: 4px 4px 0 var(--color-accent-2);
    }
    .account-chip .role-label {
      font-family: var(--font-display);
      font-weight: 800;
      font-size: 1.05rem;
      letter-spacing: 0.02em;
      color: var(--color-ink);
      text-transform: uppercase;
      line-height: 1;
      margin-bottom: 0.25rem;
    }
    .account-chip .user-handle {
      font-family: var(--font-label);
      font-size: 0.68rem;
      color: var(--color-muted);
      display: block;
      word-break: break-all;
    }
  </style>
</head>
<body class="login-wrapper">

  <header class="topbar">
    <div class="topbar__inner">
      <div class="brand-wrap">
        <a href="login.php" class="brand-logo">
          CORE-FLOW <span class="highlight">STORE</span>
        </a>
      </div>
      <div class="mono" style="color: var(--color-paper-3); font-size: 0.75rem;">
        PORTAL OPERASIONAL RETAIL
      </div>
    </div>
  </header>

  <main class="login-container">
    <div class="c-card c-card--oxblood" style="margin-bottom: var(--space-md); padding: var(--space-lg);">
      <div class="mono" style="color: var(--color-accent); margin-bottom: 0.2rem;">PORTAL KARYAWAN &amp; OPERASIONAL</div>
      <h1 class="dsp" style="font-size: 2.4rem; margin: 0; line-height: 0.95; color: var(--color-paper);">
        MASUK KE SISTEM
      </h1>
      <p style="margin: 0.5rem 0 0; font-size: var(--text-sm); color: var(--color-paper-2);">
        Silakan masukkan username dan kata sandi Anda untuk memulai shift kerja.
      </p>
    </div>

    <?php if ($error): ?>
      <div class="c-card" style="background:#fecdd3; border-color:#881337; color:#881337; margin-bottom:var(--space-md); padding:0.75rem 1rem;">
        <strong>Peringatan:</strong> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <div class="c-card" style="box-shadow: 4px 4px 0 var(--color-ink); padding: var(--space-lg);">
      <form id="login-form" method="POST" action="login.php">
        <div class="form-group">
          <label class="form-label" style="text-transform: none; font-size: 0.85rem; letter-spacing: normal;">Username</label>
          <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required value="admin_utama">
        </div>

        <div class="form-group">
          <label class="form-label" style="text-transform: none; font-size: 0.85rem; letter-spacing: normal;">Kata Sandi</label>
          <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan kata sandi" required value="admin123">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.15rem; padding: 0.65rem; margin-top: 0.5rem;">
          Masuk ke Sistem &rarr;
        </button>
      </form>

      <div style="margin-top: var(--space-lg); border-top: 1px dashed var(--color-rule); padding-top: var(--space-md);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
          <span class="mono" style="font-weight: 700; color: var(--color-ink-2); font-size: 0.75rem;">
            AKUN UJI COBA PERAN:
          </span>
          <span class="mono" style="font-size: 0.68rem; color: var(--color-muted);">
            Klik untuk isi instan
          </span>
        </div>

        <div class="account-picker-grid">
          <div class="account-chip" onclick="quickFill('admin_utama', 'admin123')">
            <div class="role-label">Admin</div>
            <span class="user-handle">admin_utama</span>
          </div>
          <div class="account-chip" onclick="quickFill('ashar_pengadaan', 'ashar123')">
            <div class="role-label">Pengadaan</div>
            <span class="user-handle">ashar_pengadaan</span>
          </div>
          <div class="account-chip" onclick="quickFill('altaf_gudang', 'altaf123')">
            <div class="role-label">Gudang</div>
            <span class="user-handle">altaf_gudang</span>
          </div>
          <div class="account-chip" onclick="quickFill('rahel_kasir', 'rahel123')">
            <div class="role-label">Kasir</div>
            <span class="user-handle">rahel_kasir</span>
          </div>
          <div class="account-chip" onclick="quickFill('hikam_supervisor', 'hikam123')">
            <div class="role-label">Supervisor</div>
            <span class="user-handle">hikam_supervisor</span>
          </div>
        </div>
      </div>
    </div>
  </main>

  <footer style="padding: var(--space-md) 0; text-align: center; font-size: var(--text-xs); color: var(--color-muted);" class="mono">
    &copy; <?= date('Y') ?> CORE-FLOW STORE · SISTEM INFORMASI OPERASIONAL
  </footer>

  <script>
    function quickFill(user, pass) {
      document.getElementById('username').value = user;
      document.getElementById('password').value = pass;
      document.getElementById('login-form').submit();
    }
  </script>
</body>
</html>
