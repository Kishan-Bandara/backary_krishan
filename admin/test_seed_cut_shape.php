<?php
/**
 * Seed data for Cut & Shape Report testing.
 * Creates standing orders + cart (late) orders for today (2026-04-16 Thursday).
 * Run once: php test_seed_cut_shape.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) session_start();
include('include/database.php');

$db  = new Database();
$pdo = $db->getConnection();

$today = date('Y-m-d');           // 2026-04-16
$dayCol = strtolower(date('D'));   // thu

echo "Seeding Cut & Shape test data for {$today} ({$dayCol})..." . PHP_EOL;

// ─── 1. Standing Orders ───────────────────────────────────
// Create 3 standing orders for 3 customers, covering today.
// Items used (group 1 = Selling Products):
//   59  Frozen Buckwheat & Chia Loaf Sliced   (580g)
//   62  Frozen Fruit Loaf Sliced              (840g)
//   68  Buckwheat and Chia Loaf               (580g)
//   69  Fruit Loaf                            (840g)
//   43  Plain Rolls (Pack of 6)               (330g)

$standingOrders = [
    // Customer 1 (Nine Yards) — addr 4
    [
        'customer_id' => 1,
        'shipping_address_id' => 4,
        'items' => [
            ['item_id' => 59, 'thu_qty' => 125],  // Frozen Buckwheat & Chia Loaf
            ['item_id' => 62, 'thu_qty' => 300],  // Frozen Fruit Loaf
            ['item_id' => 68, 'thu_qty' => 75],   // Buckwheat and Chia Loaf
        ],
    ],
    // Customer 3 (Small Cups Coffee) — addr 5
    [
        'customer_id' => 3,
        'shipping_address_id' => 5,
        'items' => [
            ['item_id' => 59, 'thu_qty' => 200],  // Frozen Buckwheat & Chia Loaf
            ['item_id' => 69, 'thu_qty' => 100],  // Fruit Loaf
            ['item_id' => 43, 'thu_qty' => 50],   // Plain Rolls
        ],
    ],
    // Customer 4 (Zoux Bar) — no shipping address, use NULL
    [
        'customer_id' => 4,
        'shipping_address_id' => null,
        'items' => [
            ['item_id' => 62, 'thu_qty' => 80],   // Frozen Fruit Loaf
            ['item_id' => 43, 'thu_qty' => 25],   // Plain Rolls
        ],
    ],
];

$createdSOIds = [];
foreach ($standingOrders as $so) {
    $db->insertRow(
        "INSERT INTO standing_order (customer_id, shipping_address_id, active, date_from, date_to, created_at, updated_at)
         VALUES (?, ?, 1, ?, ?, NOW(), NOW())",
        [$so['customer_id'], $so['shipping_address_id'], '2026-04-01', '2026-05-31']
    );
    $soRow = $db->getRow("SELECT id FROM standing_order WHERE customer_id = ? ORDER BY id DESC LIMIT 1", [$so['customer_id']]);
    $soId = (int)$soRow['id'];
    $createdSOIds[] = $soId;
    echo "  Created standing_order #{$soId} for customer {$so['customer_id']}" . PHP_EOL;

    foreach ($so['items'] as $item) {
        $db->insertRow(
            "INSERT INTO standing_order_item (standing_order_id, item_id, mon_qty, tue_qty, wed_qty, thu_qty, fri_qty, sat_qty, sun_qty, created_at, updated_at)
             VALUES (?, ?, 0, 0, 0, ?, 0, 0, 0, NOW(), NOW())",
            [$soId, $item['item_id'], $item['thu_qty']]
        );
        echo "    + item {$item['item_id']} thu_qty={$item['thu_qty']}" . PHP_EOL;
    }
}

// ─── Standing order totals ────────────────────────────────
// item 59: 125 + 200 = 325
// item 62: 300 + 80  = 380
// item 68: 75        = 75
// item 69: 100       = 100
// item 43: 50 + 25   = 75
// Grand standing total: 955

// ─── 2. Cart Orders (Late Orders) ────────────────────────
// Some customers phoned in extra/modified qty via cart order.
// These are invoice_hedder + invoice_details with order_type='CART'.

$cartOrders = [
    // Customer 1 adds 160 more Frozen Fruit Loaf
    [
        'customer_id' => 1,
        'items' => [
            ['item_id' => 62, 'qty' => 160, 'price' => 5.50],
        ],
    ],
    // Customer 3 adds 100 Frozen Buckwheat & Chia Loaf and 35 Plain Rolls
    [
        'customer_id' => 3,
        'items' => [
            ['item_id' => 59, 'qty' => 100, 'price' => 4.20],
            ['item_id' => 43, 'qty' => 35,  'price' => 3.80],
        ],
    ],
];

$createdInvIds = [];
foreach ($cartOrders as $cart) {
    $netValue = 0;
    foreach ($cart['items'] as $item) {
        $netValue += $item['qty'] * $item['price'];
    }

    $db->insertRow(
        "INSERT INTO invoice_hedder (invoice_h_customer_id, invoice_h_date, invoice_h_datetime, invoice_h_delivery_date,
         invoice_h_net_value, invoice_h_gross_value, invoice_h_status, order_type, invoice_h_order_note, add_by, updated_at)
         VALUES (?, ?, NOW(), ?, ?, ?, 1, 'CART', 'Cart Order', 'seed', NOW())",
        [$cart['customer_id'], $today, $today, $netValue, $netValue]
    );
    $invRow = $db->getRow("SELECT invoice_h_id FROM invoice_hedder WHERE invoice_h_customer_id = ? ORDER BY invoice_h_id DESC LIMIT 1", [$cart['customer_id']]);
    $invId = (int)$invRow['invoice_h_id'];
    $createdInvIds[] = $invId;
    echo "  Created CART invoice #{$invId} for customer {$cart['customer_id']}" . PHP_EOL;

    foreach ($cart['items'] as $item) {
        $total = $item['qty'] * $item['price'];
        $db->insertRow(
            "INSERT INTO invoice_details (invoice_h_id, invoice_d_item_id, invoice_d_qty, invoice_d_balance, invoice_d_item_price,
             invoice_d_vat, invoice_d_vat_rate, invoice_d_discount_value, invoice_d_discount_type, invoice_d_discount_total,
             invoice_d_item_total, order_note, is_cart_item)
             VALUES (?, ?, ?, ?, ?, 'N', 0, 0, 0, 0, ?, 'Cart Order', 1)",
            [$invId, $item['item_id'], $item['qty'], $item['qty'], $item['price'], $total]
        );
        echo "    + item {$item['item_id']} qty={$item['qty']} (late/cart)" . PHP_EOL;
    }
}

// ─── Cart order totals ────────────────────────────────────
// item 62: +160
// item 59: +100
// item 43: +35

echo PHP_EOL . "=== EXPECTED CUT & SHAPE REPORT ===" . PHP_EOL;
echo "Group: Selling Products" . PHP_EOL;
echo str_pad("Item", 45) . str_pad("Standing", 12) . str_pad("Cart/Late", 12) . str_pad("Total", 10) . PHP_EOL;
echo str_repeat('-', 79) . PHP_EOL;

$expected = [
    ['Frozen Buckwheat & Chia Loaf Sliced', 325, 100],
    ['Frozen Fruit Loaf Sliced',            380, 160],
    ['Buckwheat and Chia Loaf',             75,  0],
    ['Fruit Loaf',                          100, 0],
    ['Plain Rolls (Pack of 6)',             75,  35],
];
$origTotal = 0; $grandTotal = 0;
foreach ($expected as $e) {
    $origTotal += $e[1];
    $grandTotal += $e[1] + $e[2];
    echo str_pad($e[0], 45) . str_pad($e[1], 12) . str_pad($e[2], 12) . str_pad($e[1]+$e[2], 10) . PHP_EOL;
}
echo str_repeat('-', 79) . PHP_EOL;
echo str_pad("TOTALS", 45) . str_pad($origTotal, 12) . str_pad($grandTotal - $origTotal, 12) . str_pad($grandTotal, 10) . PHP_EOL;

echo PHP_EOL . "Seed data created successfully!" . PHP_EOL;
echo "Standing order IDs: " . implode(', ', $createdSOIds) . PHP_EOL;
echo "Cart invoice IDs: " . implode(', ', $createdInvIds) . PHP_EOL;
echo PHP_EOL . "Now open: http://localhost/bakery/admin/cut-shape-report.php?date={$today}" . PHP_EOL;
