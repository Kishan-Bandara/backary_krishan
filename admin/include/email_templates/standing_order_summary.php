<?php
/**
 * Email Template: Standing Order Summary
 * 
 * Variables available:
 *   $standingOrder, $customer, $items, $shipping, $settings, $invoiceSettings,
 *   $currencySymbol, $orderType, $orderTypeLabel
 */

$companyName    = $invoiceSettings['receipt_name'] ?? $settings['SiteName'] ?? 'Bakery';
$companyEmail   = $invoiceSettings['receipt_email'] ?? $settings['system_email'] ?? '';
$companyPhone   = $invoiceSettings['receipt_phone'] ?? $settings['contactUs'] ?? '';
$companyAddress = $invoiceSettings['receipt_address'] ?? $settings['address'] ?? '';

$custName    = htmlspecialchars($customer['customer_name'] ?? '');
$custEmail   = htmlspecialchars($customer['customer_email'] ?? '');
$dateFrom    = $standingOrder['date_from'] ?? '';
$dateTo      = $standingOrder['date_to'] ?? '';
$deliveryAmt = (float)($standingOrder['DeliveryAmount'] ?? 0);
$curr        = $currencySymbol;

$accentColor = '#27ae60';
$darkColor   = '#2c3e50';

$shippingLabel = '';
if ($shipping) {
    $parts = array_filter([
        $shipping['address_label'] ?? '',
        $shipping['address_line_1'] ?? '',
        $shipping['city'] ?? ''
    ]);
    $shippingLabel = htmlspecialchars(implode(', ', $parts));
}

$days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$dayColumns = ['mon_qty', 'tue_qty', 'wed_qty', 'thu_qty', 'fri_qty', 'sat_qty', 'sun_qty'];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: 'Segoe UI', Arial, Helvetica, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f7; padding: 30px 0;">
<tr><td align="center">
<table width="700" cellpadding="0" cellspacing="0" style="max-width: 700px; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">

    <!-- Top Accent -->
    <tr>
        <td style="height: 5px; background: linear-gradient(90deg, <?php echo $accentColor; ?>, #2ecc71);"></td>
    </tr>

    <!-- Header -->
    <tr>
        <td style="padding: 30px 35px 20px; background: <?php echo $darkColor; ?>;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <h1 style="margin: 0; font-size: 22px; color: #ffffff; font-weight: 700;"><?php echo htmlspecialchars($companyName); ?></h1>
                        <p style="margin: 5px 0 0; font-size: 12px; color: #8899aa;">
                            <?php if ($companyPhone) echo htmlspecialchars($companyPhone) . ' &bull; '; ?>
                            <?php if ($companyEmail) echo htmlspecialchars($companyEmail); ?>
                        </p>
                    </td>
                    <td style="text-align: right; vertical-align: top;">
                        <span style="display: inline-block; background: <?php echo $accentColor; ?>; color: #fff; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase;">
                            Standing Order
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Greeting -->
    <tr>
        <td style="padding: 30px 35px 10px;">
            <h2 style="margin: 0 0 8px; font-size: 20px; color: <?php echo $darkColor; ?>;">
                Hello <?php echo $custName; ?>! 📋
            </h2>
            <p style="margin: 0; font-size: 14px; color: #6c757d; line-height: 1.6;">
                Your standing order has been set up successfully. Below is your weekly delivery schedule.
                A detailed PDF is attached for your records.
            </p>
        </td>
    </tr>

    <!-- Schedule Info -->
    <tr>
        <td style="padding: 20px 35px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background: #f8f9fa; border-radius: 10px; border: 1px solid #e9ecef;">
                <tr>
                    <td style="padding: 20px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="33%" style="vertical-align: top;">
                                    <p style="margin: 0 0 4px; font-size: 10px; color: <?php echo $accentColor; ?>; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Start Date</p>
                                    <p style="margin: 0; font-size: 14px; color: <?php echo $darkColor; ?>; font-weight: 600;">
                                        <?php echo !empty($dateFrom) ? date('d M Y', strtotime($dateFrom)) : '-'; ?>
                                    </p>
                                </td>
                                <td width="33%" style="vertical-align: top;">
                                    <p style="margin: 0 0 4px; font-size: 10px; color: <?php echo $accentColor; ?>; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">End Date</p>
                                    <p style="margin: 0; font-size: 14px; color: <?php echo $darkColor; ?>; font-weight: 600;">
                                        <?php echo !empty($dateTo) ? date('d M Y', strtotime($dateTo)) : 'Ongoing'; ?>
                                    </p>
                                </td>
                                <td width="33%" style="vertical-align: top;">
                                    <?php if (!empty($shippingLabel)) { ?>
                                    <p style="margin: 0 0 4px; font-size: 10px; color: <?php echo $accentColor; ?>; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Delivery Address</p>
                                    <p style="margin: 0; font-size: 13px; color: <?php echo $darkColor; ?>;"><?php echo $shippingLabel; ?></p>
                                    <?php } ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Weekly Schedule Table -->
    <tr>
        <td style="padding: 10px 35px 20px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; font-size: 12px;">
                <thead>
                    <tr>
                        <th style="padding: 10px 8px; background: <?php echo $darkColor; ?>; color: #fff; font-size: 10px; text-transform: uppercase; text-align: left; border-radius: 6px 0 0 0;">Item</th>
                        <?php foreach ($days as $day) { ?>
                        <th style="padding: 10px 6px; background: <?php echo $darkColor; ?>; color: #fff; font-size: 10px; text-transform: uppercase; text-align: center; width: 50px;"><?php echo $day; ?></th>
                        <?php } ?>
                        <th style="padding: 10px 8px; background: <?php echo $darkColor; ?>; color: #fff; font-size: 10px; text-transform: uppercase; text-align: center; border-radius: 0 6px 0 0; width: 60px;">Weekly</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $idx = 0;
                    $dayTotals = [0, 0, 0, 0, 0, 0, 0];
                    foreach ($items as $item) { 
                        $idx++;
                        $bg = ($idx % 2 === 0) ? '#f8f9fa' : '#ffffff';
                        $weeklyQty = 0;
                    ?>
                    <tr>
                        <td style="padding: 10px 8px; border-bottom: 1px solid #f0f0f0; background: <?php echo $bg; ?>; font-weight: 500; color: #212529;">
                            <?php echo htmlspecialchars($item['item_name'] ?? ''); ?>
                        </td>
                        <?php for ($d = 0; $d < 7; $d++) {
                            $qty = (float)($item[$dayColumns[$d]] ?? 0);
                            $dayTotals[$d] += $qty;
                            $weeklyQty += $qty;
                            $cellColor = $qty > 0 ? '#212529' : '#ddd';
                            $fontWeight = $qty > 0 ? '600' : '400';
                        ?>
                        <td style="padding: 10px 6px; border-bottom: 1px solid #f0f0f0; background: <?php echo $bg; ?>; text-align: center; color: <?php echo $cellColor; ?>; font-weight: <?php echo $fontWeight; ?>;">
                            <?php echo $qty > 0 ? $qty : '-'; ?>
                        </td>
                        <?php } ?>
                        <td style="padding: 10px 8px; border-bottom: 1px solid #f0f0f0; background: <?php echo $bg; ?>; text-align: center; font-weight: 700; color: <?php echo $accentColor; ?>;">
                            <?php echo $weeklyQty; ?>
                        </td>
                    </tr>
                    <?php } ?>
                    <!-- Totals Row -->
                    <tr>
                        <td style="padding: 10px 8px; background: <?php echo $darkColor; ?>; color: #fff; font-weight: 600;">TOTAL</td>
                        <?php for ($d = 0; $d < 7; $d++) { ?>
                        <td style="padding: 10px 6px; background: <?php echo $darkColor; ?>; color: #fff; text-align: center; font-weight: 600;">
                            <?php echo $dayTotals[$d] > 0 ? $dayTotals[$d] : '-'; ?>
                        </td>
                        <?php } ?>
                        <td style="padding: 10px 8px; background: <?php echo $darkColor; ?>; color: #fff; text-align: center; font-weight: 700;">
                            <?php echo array_sum($dayTotals); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>

    <!-- PDF Notice -->
    <tr>
        <td style="padding: 0 35px 25px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background: #eaf7f0; border-radius: 8px; border: 1px solid #c3e6cb;">
                <tr>
                    <td style="padding: 15px 20px;">
                        <p style="margin: 0; font-size: 13px; color: #155724;">
                            📎 <strong>A detailed PDF copy of your standing order schedule is attached to this email.</strong>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="padding: 25px 35px; background: #f8f9fa; border-top: 1px solid #e9ecef;">
            <p style="margin: 0 0 5px; font-size: 12px; color: #adb5bd; text-align: center;">
                This is an automated email from <?php echo htmlspecialchars($companyName); ?>
            </p>
            <?php if (!empty($companyAddress)) { ?>
            <p style="margin: 0; font-size: 11px; color: #ced4da; text-align: center;">
                <?php echo htmlspecialchars($companyAddress); ?>
            </p>
            <?php } ?>
        </td>
    </tr>

</table>
</td></tr>
</table>

</body>
</html>
