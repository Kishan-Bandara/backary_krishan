<?php
/**
 * Email Template: Order Confirmation
 * 
 * Variables available:
 *   $invoice, $customer, $items, $settings, $invoiceSettings,
 *   $currencySymbol, $orderType, $orderTypeLabel
 */

$companyName    = $invoiceSettings['receipt_name'] ?? $settings['SiteName'] ?? 'Bakery';
$companyEmail   = $invoiceSettings['receipt_email'] ?? $settings['system_email'] ?? '';
$companyPhone   = $invoiceSettings['receipt_phone'] ?? $settings['contactUs'] ?? '';
$companyAddress = $invoiceSettings['receipt_address'] ?? $settings['address'] ?? '';

$invoiceCode  = htmlspecialchars($invoice['invoice_h_code'] ?? '');
$deliveryDate = $invoice['invoice_h_delivery_date'] ?? '';
$deliveryAddr = htmlspecialchars($invoice['invoice_h_delivery_address'] ?? '');
$deliveryTime = htmlspecialchars($invoice['invoice_h_delivery_time'] ?? '');
$custName     = htmlspecialchars($customer['customer_name'] ?? '');

$netValue   = (float)($invoice['invoice_h_net_value'] ?? 0);
$discount   = (float)($invoice['invoice_h_coupon_value'] ?? 0);
$delivery   = (float)($invoice['invoice_h_delivery_cost'] ?? 0);
$grossValue = (float)($invoice['invoice_h_gross_value'] ?? 0);
$curr       = $currencySymbol;

$accentColor = '#d4a762';
$darkColor   = '#2c3e50';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: 'Segoe UI', Arial, Helvetica, sans-serif;">

<!-- Wrapper -->
<table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f7; padding: 30px 0;">
<tr><td align="center">
<table width="620" cellpadding="0" cellspacing="0" style="max-width: 620px; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">

    <!-- Top Accent Line -->
    <tr>
        <td style="height: 5px; background: linear-gradient(90deg, <?php echo $accentColor; ?>, #e8c98e);"></td>
    </tr>

    <!-- Header -->
    <tr>
        <td style="padding: 30px 40px 20px; background: <?php echo $darkColor; ?>;">
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
                        <span style="display: inline-block; background: <?php echo $accentColor; ?>; color: #fff; padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                            <?php echo htmlspecialchars($orderTypeLabel); ?> Confirmed
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Greeting -->
    <tr>
        <td style="padding: 30px 40px 10px;">
            <h2 style="margin: 0 0 8px; font-size: 20px; color: <?php echo $darkColor; ?>;">
                Hello <?php echo $custName; ?>! 👋
            </h2>
            <p style="margin: 0; font-size: 14px; color: #6c757d; line-height: 1.6;">
                Thank you for your order. Here's a summary of your <?php echo strtolower($orderTypeLabel); ?>. 
                A detailed PDF is attached for your records.
            </p>
        </td>
    </tr>

    <!-- Order Summary Card -->
    <tr>
        <td style="padding: 20px 40px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background: #f8f9fa; border-radius: 10px; border: 1px solid #e9ecef;">
                <tr>
                    <td style="padding: 20px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="50%" style="vertical-align: top;">
                                    <p style="margin: 0 0 4px; font-size: 10px; color: <?php echo $accentColor; ?>; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Invoice Number</p>
                                    <p style="margin: 0 0 15px; font-size: 16px; color: <?php echo $darkColor; ?>; font-weight: 700;"><?php echo $invoiceCode; ?></p>
                                    
                                    <p style="margin: 0 0 4px; font-size: 10px; color: <?php echo $accentColor; ?>; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Delivery Date</p>
                                    <p style="margin: 0; font-size: 14px; color: <?php echo $darkColor; ?>; font-weight: 600;">
                                        <?php echo !empty($deliveryDate) ? date('l, d M Y', strtotime($deliveryDate)) : '-'; ?>
                                    </p>
                                </td>
                                <td width="50%" style="vertical-align: top;">
                                    <?php if (!empty($deliveryTime)) { ?>
                                    <p style="margin: 0 0 4px; font-size: 10px; color: <?php echo $accentColor; ?>; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Delivery Time</p>
                                    <p style="margin: 0 0 15px; font-size: 14px; color: <?php echo $darkColor; ?>;"><?php echo $deliveryTime; ?></p>
                                    <?php } ?>
                                    
                                    <?php if (!empty($deliveryAddr)) { ?>
                                    <p style="margin: 0 0 4px; font-size: 10px; color: <?php echo $accentColor; ?>; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Delivery Address</p>
                                    <p style="margin: 0; font-size: 13px; color: <?php echo $darkColor; ?>;"><?php echo $deliveryAddr; ?></p>
                                    <?php } ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Items Table -->
    <tr>
        <td style="padding: 10px 40px 20px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="padding: 12px 10px; background: <?php echo $darkColor; ?>; color: #fff; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; border-radius: 6px 0 0 0;">Item</th>
                        <th style="padding: 12px 10px; background: <?php echo $darkColor; ?>; color: #fff; font-size: 11px; text-transform: uppercase; text-align: center;">Qty</th>
                        <th style="padding: 12px 10px; background: <?php echo $darkColor; ?>; color: #fff; font-size: 11px; text-transform: uppercase; text-align: right;">Price</th>
                        <th style="padding: 12px 10px; background: <?php echo $darkColor; ?>; color: #fff; font-size: 11px; text-transform: uppercase; text-align: right; border-radius: 0 6px 0 0;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $idx = 0; foreach ($items as $item) { $idx++;
                        $qty = (float)($item['invoice_d_qty'] ?? 0);
                        $price = (float)($item['invoice_d_item_price'] ?? 0);
                        $discVal = (float)($item['invoice_d_discount_value'] ?? 0);
                        $effectivePrice = ($discVal > 0) ? $price - ($price * $discVal / 100) : $price;
                        $lineTotal = $effectivePrice * $qty;
                        $bg = ($idx % 2 === 0) ? '#f8f9fa' : '#ffffff';
                    ?>
                    <tr>
                        <td style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; background: <?php echo $bg; ?>; font-size: 13px; color: #212529;">
                            <?php echo htmlspecialchars($item['item_name'] ?? ''); ?>
                        </td>
                        <td style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; background: <?php echo $bg; ?>; font-size: 13px; text-align: center; color: #495057;">
                            <?php echo $qty; ?>
                        </td>
                        <td style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; background: <?php echo $bg; ?>; font-size: 13px; text-align: right; color: #495057;">
                            <?php echo $curr . ' ' . number_format($effectivePrice, 2); ?>
                        </td>
                        <td style="padding: 12px 10px; border-bottom: 1px solid #f0f0f0; background: <?php echo $bg; ?>; font-size: 13px; text-align: right; font-weight: 600; color: #212529;">
                            <?php echo $curr . ' ' . number_format($lineTotal, 2); ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </td>
    </tr>

    <!-- Totals -->
    <tr>
        <td style="padding: 0 40px 30px;">
            <table width="280" cellpadding="0" cellspacing="0" align="right" style="border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; font-size: 13px; color: #6c757d;">Subtotal</td>
                    <td style="padding: 8px 0; font-size: 13px; text-align: right; color: #212529;"><?php echo $curr . ' ' . number_format($netValue, 2); ?></td>
                </tr>
                <?php if ($discount > 0) { ?>
                <tr>
                    <td style="padding: 8px 0; font-size: 13px; color: #6c757d;">Discount</td>
                    <td style="padding: 8px 0; font-size: 13px; text-align: right; color: #e74c3c;">- <?php echo $curr . ' ' . number_format($discount, 2); ?></td>
                </tr>
                <?php } ?>
                <?php if ($delivery > 0) { ?>
                <tr>
                    <td style="padding: 8px 0; font-size: 13px; color: #6c757d;">Delivery</td>
                    <td style="padding: 8px 0; font-size: 13px; text-align: right; color: #212529;"><?php echo $curr . ' ' . number_format($delivery, 2); ?></td>
                </tr>
                <?php } ?>
                <tr>
                    <td colspan="2" style="border-top: 2px solid <?php echo $accentColor; ?>; padding-top: 12px;"></td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; font-size: 18px; font-weight: 700; color: <?php echo $darkColor; ?>;">Total</td>
                    <td style="padding: 4px 0; font-size: 18px; font-weight: 700; text-align: right; color: <?php echo $darkColor; ?>;"><?php echo $curr . ' ' . number_format($grossValue, 2); ?></td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- PDF Notice -->
    <tr>
        <td style="padding: 0 40px 25px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background: #eaf7f0; border-radius: 8px; border: 1px solid #c3e6cb;">
                <tr>
                    <td style="padding: 15px 20px;">
                        <p style="margin: 0; font-size: 13px; color: #155724;">
                            📎 <strong>A detailed PDF copy of this <?php echo strtolower($orderTypeLabel); ?> is attached to this email.</strong>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="padding: 25px 40px; background: #f8f9fa; border-top: 1px solid #e9ecef;">
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
