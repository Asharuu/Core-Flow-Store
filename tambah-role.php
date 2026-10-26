<?php
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaRole = trim($_POST['nama_role'] ?? '');
    $redirect = $_POST['redirect'] ?? 'index.php';

    // Bersihkan parameter status lama agar tidak menumpuk di URL
    $redirect = preg_replace('/([?&])role_status=[^&]*(&|$)/', '$1', $redirect);
    $redirect = preg_replace('/([?&])role_name=[^&]*(&|$)/', '$1', $redirect);
    $redirect = preg_replace('/([?&])role_err=[^&]*(&|$)/', '$1', $redirect);
    $redirect = rtrim($redirect, '?&');
    $sep = (strpos($redirect, '?') !== false) ? '&' : '?';

    if ($namaRole !== '') {
        try {
            // Cek apakah nama role sudah ada (case-insensitive)
            $stmtCek = $pdo->prepare("SELECT idrole, nama_role FROM role WHERE LOWER(nama_role) = LOWER(?)");
            $stmtCek->execute([$namaRole]);
            $existing = $stmtCek->fetch();

            if ($existing) {
                header("Location: {$redirect}{$sep}role_status=exists&role_name=" . urlencode($namaRole));
                exit;
            }

            // Dapatkan ID berikutnya dan simpan ke tabel role
            $idrole = getNextId($pdo, 'role', 'idrole');
            $stmt = $pdo->prepare("INSERT INTO role (idrole, nama_role) VALUES (?, ?)");
            $stmt->execute([$idrole, $namaRole]);

            // Buat akun user demo otomatis agar role langsung bisa diuji coba
            $iduser = getNextId($pdo, 'user', 'iduser');
            $baseUser = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $namaRole));
            $baseUser = trim($baseUser, '_');
            if ($baseUser === '') {
                $baseUser = 'role_' . $idrole;
            }

            $username = $baseUser;
            $stmtUserCek = $pdo->prepare("SELECT iduser FROM user WHERE username = ?");
            $stmtUserCek->execute([$username]);
            if ($stmtUserCek->fetch()) {
                $username = $baseUser . '_' . $idrole;
            }
            $password = $username . '123';

            $stmtUser = $pdo->prepare("INSERT INTO user (iduser, username, password, idrole) VALUES (?, ?, ?, ?)");
            $stmtUser->execute([$iduser, $username, $password, $idrole]);

            // Beralih sesi ke role baru yang baru saja dibuat
            $_SESSION['user'] = [
                'iduser'    => $iduser,
                'username'  => $username,
                'idrole'    => $idrole,
                'role_name' => $namaRole
            ];
            $_SESSION['role'] = $namaRole;

            header("Location: {$redirect}{$sep}role_status=success&role_name=" . urlencode($namaRole));
            exit;
        } catch (Exception $e) {
            header("Location: {$redirect}{$sep}role_status=error&role_err=" . urlencode($e->getMessage()));
            exit;
        }
    }
}

header('Location: index.php');
exit;
