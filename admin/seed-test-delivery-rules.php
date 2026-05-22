<?php
/**
 * One-off seeder to create sample Delivery Rules and assign them to a few
 * customer shipping addresses for testing the standing-order integration.
 *
 * SAFE TO RE-RUN: rules are upserted by name; shipping address assignments
 * are made only on addresses that currently have NO rule assigned (unless
 * you pass ?force=1 to overwrite the first N addresses regardless).
 *
 * Usage:  /admin/seed-test-delivery-rules.php
 *         /admin/seed-test-delivery-rules.php?force=1
 *
 * Delete this file after you finish testing.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once(__DIR__ . '/include/database.php');
include(__DIR__ . '/include/check_login.php');
require_once(__DIR__ . '/include/delivery_rules.php');

header('Content-Type: text/html; charset=utf-8');
$force = isset($_GET['force']) && $_GET['force'] === '1';

$db = new Database();
ensureDeliveryRulesSchema($db);

echo '<!doctype html><meta charset="utf-8"><title>Seed Test Delivery Rules</title>';
echo '<style>body{font-family:Segoe UI,Arial,sans-serif;padding:20px;max-width:980px;margin:auto;color:#222}'
    . 'h2{border-bottom:1px solid #ddd;padding-bottom:6px;margin-top:30px}'
    . 'table{border-collapse:collapse;width:100%;margin:8px 0 16px}'
    . 'th,td{border:1px solid #ddd;padding:6px 10px;font-size:13px;text-align:left}'
    . 'th{background:#f4f6f8}.ok{color:#2e7d32}.warn{color:#b26a00}.err{color:#b71c1c}'
    . '.box{padding:10px 14px;border:1px solid #e3e6ea;background:#fafbfc;border-radius:4px;margin:10px 0}'
    . '</style>';
echo '<h1>Seed Test Delivery Rules</h1>';
echo '<div class="box">';
echo '<strong>Mode:</strong> ' . ($force ? '<span class="warn">FORCE (will overwrite existing assignments)</span>' : 'Safe (only assigns to addresses without a rule)') . '<br>';
echo 'To force re-assignment append <code>?force=1</code>. Delete this file after testing.';
echo '</div>';

// -------------------------------------------------------------------------
// 1) Global delivery_rule_settings — make sure thresholds exist for testing
// -------------------------------------------------------------------------
echo '<h2>1. Global Delivery Settings</h2>';
$db->updateRow(
    'UPDATE delivery_rule_settings SET apply_to=?, weekly_avg_free_delivery=?, standing_order_daily_avg_min=?, min_cart_order=? WHERE id=1',
    ['gross', 500.00, 80.00, 30.00]
);
$cur = $db->getRow('SELECT * FROM delivery_rule_settings WHERE id=1');
echo '<table><tr><th>apply_to</th><th>weekly_avg_free_delivery</th><th>standing_order_daily_avg_min</th><th>min_cart_order</th></tr>';
echo '<tr><td>' . htmlspecialchars($cur['apply_to']) . '</td><td>$' . htmlspecialchars($cur['weekly_avg_free_delivery']) . '</td><td>$' . htmlspecialchars($cur['standing_order_daily_avg_min']) . '</td><td>$' . htmlspecialchars($cur['min_cart_order']) . '</td></tr></table>';

// -------------------------------------------------------------------------
// 2) Upsert sample Delivery Rules (named) with tiered fees
// -------------------------------------------------------------------------
echo '<h2>2. Sample Delivery Rules</h2>';

/**
 * Tier semantics: invoice_larger_than is the LOWER bound. Highest matching
 * tier (where total >= invoice_larger_than) wins.
 *
 * Standard:       $0+  => $15 ; $100+ => $10 ; $250+ => $5  ; $500+ => $0
 * Metro Express:  $0+  => $25 ; $150+ => $18 ; $400+ => $0
 * Flat Rate:      $0+  => $12  (single tier)
 */
$sampleRules = [
    [
        'name' => 'Standard Delivery',
        'tiers' => [
            ['invoice_larger_than' => 0,   'price' => 15.00],
            ['invoice_larger_than' => 100, 'price' => 10.00],
            ['invoice_larger_than' => 250, 'price' => 5.00],
            ['invoice_larger_than' => 500, 'price' => 0.00],
        ],
    ],
    [
        'name' => 'Metro Express',
        'tiers' => [
            ['invoice_larger_than' => 0,   'price' => 25.00],
            ['invoice_larger_than' => 150, 'price' => 18.00],
            ['invoice_larger_than' => 400, 'price' => 0.00],
        ],
    ],
    [
        'name' => 'Flat Rate $12',
        'tiers' => [
            ['invoice_larger_than' => 0, 'price' => 12.00],
        ],
    ],
];

$createdRuleIds = [];
foreach ($sampleRules as $idx => $r) {
    $existing = $db->getRow('SELECT id FROM delivery_rules WHERE name = ? LIMIT 1', [$r['name']]);
    if ($existing) {
        $ruleId = (int)$existing['id'];
        $db->updateRow('UPDATE delivery_rules SET is_active=1, sort_order=? WHERE id=?', [$idx, $ruleId]);
        $db->deleteRow('DELETE FROM delivery_rule_tiers WHERE rule_id=?', [$ruleId]);
        $status = 'updated';
    } else {
        $db->insertRow('INSERT INTO delivery_rules (name, sort_order, is_active) VALUES (?, ?, 1)', [$r['name'], $idx]);
        $ruleId = (int)$db->getConnection()->lastInsertId();
        $status = 'created';
    }
    $createdRuleIds[$r['name']] = $ruleId;
    foreach ($r['tiers'] as $ti => $t) {
        $db->insertRow(
            'INSERT INTO delivery_rule_tiers (rule_id, invoice_larger_than, price, sort_order) VALUES (?, ?, ?, ?)',
            [$ruleId, $t['invoice_larger_than'], $t['price'], $ti]
        );
    }
    echo '<div class="box"><span class="ok">[' . $status . ']</span> <strong>#' . $ruleId . ' ' . htmlspecialchars($r['name']) . '</strong>';
    echo '<table><tr><th>Invoice larger than</th><th>Delivery price</th></tr>';
    foreach ($r['tiers'] as $t) {
        echo '<tr><td>$' . number_format($t['invoice_larger_than'], 2) . '</td><td>$' . number_format($t['price'], 2) . '</td></tr>';
    }
    echo '</table></div>';
}

// -------------------------------------------------------------------------
// 3) Assign rules + per-address overrides to a few shipping addresses
// -------------------------------------------------------------------------
echo '<h2>3. Assign Rules to Shipping Addresses</h2>';

// Pick up to 6 shipping addresses (preferring is_default ones).
$candidateAddresses = $db->getRows(
    'SELECT sa.id, sa.customer_id, sa.address_label, sa.address_line_1, sa.delivery_rule_id, c.customer_name
     FROM customer_shipping_address sa
     LEFT JOIN customer c ON c.customer_id = sa.customer_id
     ORDER BY sa.is_default DESC, sa.id ASC
     LIMIT 6'
);

if (empty($candidateAddresses)) {
    echo '<p class="err">No shipping addresses found in <code>customer_shipping_address</code>. Add some customers/addresses first.</p>';
} else {
    // Rotation pattern: 3 rules + 3 override variants so each address gets a
    // different scenario worth testing.
    $ruleNames = array_keys($createdRuleIds);
    $assignmentPatterns = [
        // Address #1: Standard Delivery, no overrides (uses globals)
        ['rule' => 'Standard Delivery', 'so_daily_average' => null, 'min_cart_order_override' => null, 'weekly_avg_free_delivery_override' => null],
        // Address #2: Metro Express + custom SO daily avg threshold
        ['rule' => 'Metro Express',     'so_daily_average' => 120.00, 'min_cart_order_override' => null, 'weekly_avg_free_delivery_override' => null],
        // Address #3: Flat Rate + low weekly avg threshold to trigger free delivery quickly
        ['rule' => 'Flat Rate $12',     'so_daily_average' => null, 'min_cart_order_override' => null, 'weekly_avg_free_delivery_override' => 200.00],
        // Address #4: Standard + min cart override
        ['rule' => 'Standard Delivery', 'so_daily_average' => null, 'min_cart_order_override' => 50.00, 'weekly_avg_free_delivery_override' => null],
        // Address #5: Metro Express + all 3 overrides
        ['rule' => 'Metro Express',     'so_daily_average' => 200.00, 'min_cart_order_override' => 75.00, 'weekly_avg_free_delivery_override' => 1000.00],
        // Address #6: Flat Rate, no overrides
        ['rule' => 'Flat Rate $12',     'so_daily_average' => null, 'min_cart_order_override' => null, 'weekly_avg_free_delivery_override' => null],
    ];

    echo '<table><tr><th>Addr ID</th><th>Customer</th><th>Address Label</th><th>Before</th><th>Action</th><th>Rule</th><th>SO Daily Avg</th><th>Min Cart</th><th>Weekly Avg Free</th></tr>';
    $i = 0;
    $skippedExisting = 0;
    foreach ($candidateAddresses as $addr) {
        $pattern = $assignmentPatterns[$i % count($assignmentPatterns)];
        $hadRule = !empty($addr['delivery_rule_id']);
        if ($hadRule && !$force) {
            echo '<tr><td>' . (int)$addr['id'] . '</td><td>' . htmlspecialchars($addr['customer_name'] ?? '') . '</td><td>' . htmlspecialchars($addr['address_label'] ?? '') . '</td>'
                . '<td>rule #' . (int)$addr['delivery_rule_id'] . '</td>'
                . '<td><span class="warn">skipped (already assigned; use ?force=1)</span></td>'
                . '<td colspan="4">—</td></tr>';
            $skippedExisting++;
            $i++;
            continue;
        }

        $ruleId = $createdRuleIds[$pattern['rule']];
        $db->updateRow(
            'UPDATE customer_shipping_address SET delivery_rule_id=?, so_daily_average=?, min_cart_order_override=?, weekly_avg_free_delivery_override=? WHERE id=?',
            [
                $ruleId,
                $pattern['so_daily_average'],
                $pattern['min_cart_order_override'],
                $pattern['weekly_avg_free_delivery_override'],
                (int)$addr['id'],
            ]
        );

        echo '<tr><td>' . (int)$addr['id'] . '</td><td>' . htmlspecialchars($addr['customer_name'] ?? '') . '</td><td>' . htmlspecialchars($addr['address_label'] ?? '') . '</td>'
            . '<td>' . ($hadRule ? 'rule #' . (int)$addr['delivery_rule_id'] : '<em>none</em>') . '</td>'
            . '<td><span class="ok">' . ($hadRule ? 'overwritten' : 'assigned') . '</span></td>'
            . '<td>' . htmlspecialchars($pattern['rule']) . ' (#' . $ruleId . ')</td>'
            . '<td>' . ($pattern['so_daily_average']            !== null ? '$' . number_format($pattern['so_daily_average'], 2) : '—') . '</td>'
            . '<td>' . ($pattern['min_cart_order_override']     !== null ? '$' . number_format($pattern['min_cart_order_override'], 2) : '—') . '</td>'
            . '<td>' . ($pattern['weekly_avg_free_delivery_override'] !== null ? '$' . number_format($pattern['weekly_avg_free_delivery_override'], 2) : '—') . '</td>'
            . '</tr>';
        $i++;
    }
    echo '</table>';
    if ($skippedExisting > 0) {
        echo '<p class="warn">' . $skippedExisting . ' address(es) skipped because they already had a rule. Append <code>?force=1</code> to overwrite.</p>';
    }
}

// -------------------------------------------------------------------------
// 4) List standing orders for those customers so user can pick what to test
// -------------------------------------------------------------------------
echo '<h2>4. Standing Orders for Affected Customers</h2>';
$customerIds = array_unique(array_map(function ($a) { return (int)$a['customer_id']; }, $candidateAddresses));
$customerIds = array_filter($customerIds);

if (empty($customerIds)) {
    echo '<p class="warn">No customers to inspect.</p>';
} else {
    $placeholders = implode(',', array_fill(0, count($customerIds), '?'));
    $sos = $db->getRows(
        "SELECT so.id, so.customer_id, c.customer_name, so.shipping_address_id, sa.address_label,
                sa.delivery_rule_id, dr.name AS rule_name,
                so.active, so.DeliveryAmount, so.date_from, so.date_to
         FROM standing_order so
         LEFT JOIN customer c ON c.customer_id = so.customer_id
         LEFT JOIN customer_shipping_address sa ON sa.id = so.shipping_address_id
         LEFT JOIN delivery_rules dr ON dr.id = sa.delivery_rule_id
         WHERE so.customer_id IN ($placeholders)
         ORDER BY so.customer_id, so.id",
        array_values($customerIds)
    );

    if (empty($sos)) {
        echo '<p class="warn">No standing orders found for those customers. Create one via <a href="standing-order.php">standing-order.php</a> first, then run the generator at <a href="standing-order.php">standing-order.php</a> (Generate Invoices).</p>';
    } else {
        echo '<table><tr><th>SO ID</th><th>Active</th><th>Customer</th><th>Shipping Address</th><th>Assigned Rule</th><th>Legacy DeliveryAmount</th><th>date_from</th><th>date_to</th></tr>';
        foreach ($sos as $so) {
            echo '<tr><td>' . (int)$so['id'] . '</td>'
                . '<td>' . ((int)$so['active'] === 1 ? '<span class="ok">yes</span>' : '<span class="err">no</span>') . '</td>'
                . '<td>' . htmlspecialchars($so['customer_name'] ?? '') . '</td>'
                . '<td>' . htmlspecialchars(($so['address_label'] ?? '') . ' (#' . (int)$so['shipping_address_id'] . ')') . '</td>'
                . '<td>' . ($so['rule_name'] ? htmlspecialchars($so['rule_name']) . ' (#' . (int)$so['delivery_rule_id'] . ')' : '<em>none</em>') . '</td>'
                . '<td>$' . htmlspecialchars($so['DeliveryAmount']) . '</td>'
                . '<td>' . htmlspecialchars($so['date_from']) . '</td>'
                . '<td>' . htmlspecialchars($so['date_to']) . '</td>'
                . '</tr>';
        }
        echo '</table>';
    }
}

echo '<h2>Next Steps</h2>';
echo '<ol>';
echo '<li>Open <a href="manage-delivery-rules.php" target="_blank">Manage Delivery Rules</a> and confirm the three rules and global settings.</li>';
echo '<li>Open <a href="manage-customer.php" target="_blank">Manage Customers</a> &rarr; edit one of the listed customers and confirm the Delivery Rule + overrides on the shipping address card.</li>';
echo '<li>Go to <a href="standing-order.php" target="_blank">Standing Orders</a>, pick the standing orders listed above, and click <strong>Generate Invoices</strong>.</li>';
echo '<li>Open the newly generated invoices and verify <code>invoice_h_delivery_cost</code> reflects the tier match (or is 0 if free-delivery thresholds were met).</li>';
echo '<li>When done testing, <strong>delete this file</strong>: <code>admin/seed-test-delivery-rules.php</code>.</li>';
echo '</ol>';
