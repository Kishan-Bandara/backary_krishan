<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');

header('Content-Type: application/json');

$productId = (int) ($_POST['product_id'] ?? 0);
$locationId = (int) ($_POST['location_id'] ?? 0);

if ($productId <= 0 || $locationId <= 0) {
    echo json_encode(['qty' => 0]);
    exit;
}

$db = new Database();
$row = $db->getRow('SELECT SUM(ft_blanace) AS qty FROM fifo WHERE ft_item = ? AND ft_location = ? AND ft_type = 1', [$productId, $locationId]);
$qty = (float) ($row['qty'] ?? 0);

echo json_encode(['qty' => $qty]);
