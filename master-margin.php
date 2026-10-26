<?php
// Backward compatibility wrapper for master-margin.php
$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: margin-penjualan.php" . $queryString, true, 301);
exit;
