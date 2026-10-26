<?php
// Alias for produk.php
$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: produk.php" . $queryString, true, 301);
exit;
