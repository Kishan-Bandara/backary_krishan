<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../include/database.php');

$customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$dateStr = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
if($customerId <= 0){ echo json_encode(['status'=>'error','message'=>'Missing customer']); exit; }

try{
    $date = new DateTime($dateStr);
} catch(Exception $e){ echo json_encode(['status'=>'error','message'=>'Invalid date']); exit; }

$dayIndex = (int)$date->format('N'); // 1=Mon .. 7=Sun
$cols = [1=>'mon_qty',2=>'tue_qty',3=>'wed_qty',4=>'thu_qty',5=>'fri_qty',6=>'sat_qty',7=>'sun_qty'];
$col = $cols[$dayIndex];

try{
    $db = new Database();
    $so = $db->getRow('SELECT id FROM standing_order WHERE customer_id = ? AND active = 1 LIMIT 1', [$customerId]);
    if(!$so || !isset($so['id'])){ echo json_encode(['status'=>'success','data'=>['date'=>$date->format('Y-m-d'),'items'=>[],'total_qty'=>0,'total_cost'=>0]]); exit; }
    $soId = (int)$so['id'];
    $rows = $db->getRows("SELECT soi.item_id, soi.$col AS qty FROM standing_order_item soi WHERE soi.standing_order_id = ? AND soi.$col > 0", [$soId]);

    $items = []; $totalQty = 0.0; $totalCost = 0.0;
    foreach($rows as $r){
        $itemId = (int)$r['item_id'];
        $qty = (float)$r['qty'];
        // Resolve price: customer override > item normal price (only if mapping table exists)
        $has_pcm = (bool) $db->getRow("SHOW TABLES LIKE 'product_customer_price_mapping'");
        $price = 0.0;
        if ($has_pcm) {
            $priceRow = $db->getRow('SELECT price FROM product_customer_price_mapping WHERE product_id = ? AND customer_id = ? LIMIT 1', [$itemId, $customerId]);
            if($priceRow && isset($priceRow['price'])){
                $price = (float)$priceRow['price'];
            }
        }
        if ($price <= 0) {
            $im = $db->getRow('SELECT item_normal_selling_price FROM item_master WHERE item_id = ? LIMIT 1', [$itemId]);
            $price = $im && isset($im['item_normal_selling_price']) ? (float)$im['item_normal_selling_price'] : 0.0;
        }
        $line = $qty * $price;
        $items[] = [ 'item_id'=>$itemId, 'qty'=>$qty, 'unit_price'=>$price, 'line_total'=>$line ];
        $totalQty += $qty; $totalCost += $line;
    }

    echo json_encode(['status'=>'success','data'=>['date'=>$date->format('Y-m-d'),'items'=>$items,'total_qty'=>$totalQty,'total_cost'=>$totalCost]]);
} catch(Exception $e){
    echo json_encode(['status'=>'error','message'=>'Preview failed: '.$e->getMessage()]);
}



