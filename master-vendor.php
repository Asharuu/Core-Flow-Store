<?php
// Backward compatibility wrapper for master-vendor.php
$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: vendor.php" . $queryString, true, 301);
exit;
