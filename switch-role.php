<?php
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = trim($_GET['role'] ?? 'Admin');
$redirect = $_GET['redirect'] ?? 'index.php';

// Bersihkan parameter status lama dari URL redirect
$redirect = preg_replace('/([?&])role_status=[^&]*(&|$)/', '$1', $redirect);
$redirect = preg_replace('/([?&])role_name=[^&]*(&|$)/', '$1', $redirect);
$redirect = preg_replace('/([?&])role_err=[^&]*(&|$)/', '$1', $redirect);
$redirect = rtrim($redirect, '?&');
if ($redirect === '') {
    $redirect = 'index.php';
}

$roleMapping = [
    'Admin'           => ['iduser' => 1, 'username' => 'admin_utama',      'idrole' => 1, 'role_name' => 'Admin'],
    'Staff Pengadaan' => ['iduser' => 2, 'username' => 'ashar_pengadaan',  'idrole' => 2, 'role_name' => 'Staff Pengadaan'],
    'Staff Gudang'    => ['iduser' => 3, 'username' => 'altaf_gudang',     'idrole' => 3, 'role_name' => 'Staff Gudang'],
    'Kasir'           => ['iduser' => 4, 'username' => 'rahel_kasir',      'idrole' => 4, 'role_name' => 'Kasir'],
    'Supervisor'      => ['iduser' => 5, 'username' => 'hikam_supervisor', 'idrole' => 5, 'role_name' => 'Supervisor'],
];

if (isset($roleMapping[$role])) {
    $_SESSION['user'] = $roleMapping[$role];
    $_SESSION['role'] = $role;
} else {
    // Cari role dinamis di database
    try {
        $stmtRole = $pdo->prepare("SELECT idrole, nama_role FROM role WHERE nama_role = ?");
        $stmtRole->execute([$role]);
        $roleData = $stmtRole->fetch();

        if ($roleData) {
            $stmtUser = $pdo->prepare("SELECT iduser, username, idrole FROM user WHERE idrole = ? ORDER BY iduser ASC LIMIT 1");
            $stmtUser->execute([$roleData['idrole']]);
            $userData = $stmtUser->fetch();

            if (!$userData) {
                $iduser = getNextId($pdo, 'user', 'iduser');
                $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $roleData['nama_role']));
                $username = trim($username, '_') ?: 'user_' . $iduser;
                $password = $username . '123';
                $stmtInsert = $pdo->prepare("INSERT INTO user (iduser, username, password, idrole) VALUES (?, ?, ?, ?)");
                $stmtInsert->execute([$iduser, $username, $password, $roleData['idrole']]);
                $userData = [
                    'iduser'   => $iduser,
                    'username' => $username,
                    'idrole'   => $roleData['idrole']
                ];
            }

            $_SESSION['user'] = [
                'iduser'    => $userData['iduser'],
                'username'  => $userData['username'],
                'idrole'    => $roleData['idrole'],
                'role_name' => $roleData['nama_role']
            ];
            $_SESSION['role'] = $roleData['nama_role'];
        }
    } catch (Exception $e) {
        // Fallback jika query gagal
    }
}

header('Location: ' . $redirect);
exit;
