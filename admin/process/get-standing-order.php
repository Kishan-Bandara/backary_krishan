<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . '/../include/database.php');
include('../include/customer_access.php');

$customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
if($customerId <= 0){ echo json_encode(['status'=>'error','message'=>'Missing customer']); exit; }

try{
    $db = new Database();
    $customerEligibilityError = getCustomerOrderEligibilityError($db, $customerId, $eligibleCustomer);
    if ($customerEligibilityError !== null) {
        echo json_encode(['status'=>'error','message'=>$customerEligibilityError]);
        exit;
    }
    $so = $db->getRow('SELECT id, shipping_address_id, DeliveryAmount, RepeatInterval, RepeatUnit, date_from, date_to FROM standing_order WHERE customer_id = ? AND active = 1 LIMIT 1', [$customerId]);
    $customer = $db->getRow('SELECT RepeatInterval, RepeatUnit FROM customer WHERE customer_id = ?', [$customerId]);
    if(!$so || !isset($so['id'])){ 
        $defaultDelivery = 3.0;
        $repeatInterval = $customer ? $customer['RepeatInterval'] : null;
        $repeatUnit = $customer ? $customer['RepeatUnit'] : null;
        echo json_encode(['status'=>'success','data'=>['items'=>[],'standing_order_id'=>null,'delivery_amount'=>$defaultDelivery, 'repeat_interval'=>$repeatInterval, 'repeat_unit'=>$repeatUnit, 'shipping_address_id'=>null]]);
        exit; 
    }
    $soId = (int)$so['id'];
    $rows = $db->getRows('SELECT item_id, mon_qty, tue_qty, wed_qty, thu_qty, fri_qty, sat_qty, sun_qty FROM standing_order_item WHERE standing_order_id = ? ORDER BY id ASC', [$soId]);
    $items = [];
    foreach($rows as $r){
        $items[] = [
            'item_id' => (int)$r['item_id'],
            'qty' => [
                (float)$r['mon_qty'],
                (float)$r['tue_qty'],
                (float)$r['wed_qty'],
                (float)$r['thu_qty'],
                (float)$r['fri_qty'],
                (float)$r['sat_qty'],
                (float)$r['sun_qty'],
            ]
        ];
    }
    // Prioritize customer defaults over standing order values
    $repeatInterval = $customer['RepeatInterval'] ?? $so['RepeatInterval'] ?? null;
    $repeatUnit = $customer['RepeatUnit'] ?? $so['RepeatUnit'] ?? null;
    // Convert to lowercase to match frontend dropdown values
    if ($repeatUnit) {
        $repeatUnit = strtolower($repeatUnit);
    }
    echo json_encode(['status'=>'success','data'=>['items'=>$items,'standing_order_id'=>$soId,'delivery_amount'=>(float)$so['DeliveryAmount'], 'repeat_interval'=>$repeatInterval, 'repeat_unit'=>$repeatUnit, 'shipping_address_id'=>$so['shipping_address_id'], 'date_from'=>$so['date_from'], 'date_to'=>$so['date_to']]]);
} catch(Exception $e){
    echo json_encode(['status'=>'error','message'=>'Fetch failed: '.$e->getMessage()]);
}



