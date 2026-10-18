<?php
require_once __DIR__ . '/config/database.php';

$role = $_GET['role'] ?? 'Admin';
$redirect = $_GET['redirect'] ?? 'index.php';

$roleMapping = [
    'Admin'           => ['iduser' => 1, 'username' => 'admin_utama',     'idrole' => 1, 'role_name' => 'Admin'],
    'Staff Pengadaan' => ['iduser' => 2, 'username' => 'ashar_pengadaan', 'idrole' => 2, 'role_name' => 'Staff Pengadaan'],
    'Staff Gudang'    => ['iduser' => 3, 'username' => 'altaf_gudang',    'idrole' => 3, 'role_name' => 'Staff Gudang'],
    'Kasir'           => ['iduser' => 4, 'username' => 'rahel_kasir',     'idrole' => 4, 'role_name' => 'Kasir'],
    'Supervisor'      => ['iduser' => 5, 'username' => 'hikam_supervisor', 'idrole' => 5, 'role_name' => 'Supervisor'],
];

if (isset($roleMapping[$role])) {
    $_SESSION['user'] = $roleMapping[$role];
    $_SESSION['role'] = $role;
}

header('Location: ' . $redirect);
exit;
