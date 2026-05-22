<?php
require_once __DIR__ . '/../include/database.php';

$db = new Database();
$grns = $db->getRows('SELECT grn_h_id FROM grn_hedder');
$updated = 0;
foreach ($grns as $grn) {
    $id = $grn['grn_h_id'];
    $totals = $db->getRow('SELECT COALESCE(SUM(grn_d_total),0) AS net, COALESCE(SUM(grn_d_vat),0) AS vat FROM grn_details WHERE grn_h_id = ?', [$id]);
    $net = (float) ($totals['net'] ?? 0);
    $vat = (float) ($totals['vat'] ?? 0);
    $gross = $net + $vat;
    $db->updateRow('UPDATE grn_hedder SET grn_h_net_value = ?, grn_h_vat_value = ?, grn_h_gross_value = ? WHERE grn_h_id = ?', [$net, $vat, $gross, $id]);
    $updated++;
}

echo "Updated totals for $updated GRN(s)\n";
