<?php
$pdo = new PDO('mysql:host=localhost;dbname=bakery', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$sql = file_get_contents(__DIR__ . '/2026-04-24_create_shipping_address_day_route.sql');
$pdo->exec($sql);
echo 'Migration executed OK';
