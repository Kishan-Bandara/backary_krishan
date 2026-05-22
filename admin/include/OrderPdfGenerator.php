<?php
/**
 * OrderPdfGenerator - Generates PDF documents for orders
 * 
 * Uses Dompdf to convert HTML to PDF.
 * Supports: Cart Orders, Standing Orders
 */

// autoload composer packages; support relocated vendor folder
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    $autoload = __DIR__ . '/../DB Migration/vendor/autoload.php';
}
if (!file_exists($autoload)) {
    throw new Exception('Composer autoload.php not found; please run composer install.');
}
require_once($autoload);
require_once(__DIR__ . '/database.php');

use Dompdf\Dompdf;
use Dompdf\Options;

class OrderPdfGenerator
{
    private $dompdf;

    public function __construct()
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $options->set('isFontSubsettingEnabled', true);

        $this->dompdf = new Dompdf($options);
    }

    /**
     * Generate PDF for a cart/regular order
     * 
     * @param array $data Order data with invoice, customer, items, settings, etc.
     * @return string|false PDF content as string or false on failure
     */
    public function generate(array $data)
    {
        try {
            $html = $this->buildOrderHtml($data);
            
            $this->dompdf->loadHtml($html);
            $this->dompdf->setPaper('A4', 'portrait');
            $this->dompdf->render();

            return $this->dompdf->output();
        } catch (Exception $e) {
            error_log('OrderPdfGenerator Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate PDF for a standing order summary
     * 
     * @param array $data Standing order data
     * @return string|false PDF content as string or false on failure
     */
    public function generateStandingOrder(array $data)
    {
        try {
            $html = $this->buildStandingOrderHtml($data);

            $this->dompdf = new Dompdf($this->dompdf->getOptions());
            $this->dompdf->loadHtml($html);
            $this->dompdf->setPaper('A4', 'landscape');
            $this->dompdf->render();

            return $this->dompdf->output();
        } catch (Exception $e) {
            error_log('OrderPdfGenerator Standing Order Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build HTML for order PDF
     */
    private function buildOrderHtml(array $data): string
    {
        $invoice         = $data['invoice'] ?? [];
        $customer        = $data['customer'] ?? [];
        $items           = $data['items'] ?? [];
        $settings        = $data['settings'] ?? [];
        $invoiceSettings = $data['invoiceSettings'] ?? [];
        $curr            = $data['currencySymbol'] ?? '$';
        $orderTypeLabel  = $data['orderTypeLabel'] ?? 'Order';

        $companyName    = $invoiceSettings['receipt_name'] ?? $settings['SiteName'] ?? 'Bakery';
        $companyEmail   = $invoiceSettings['receipt_email'] ?? $settings['system_email'] ?? '';
        $companyPhone   = $invoiceSettings['receipt_phone'] ?? $settings['contactUs'] ?? '';
        $companyAddress = $invoiceSettings['receipt_address'] ?? $settings['address'] ?? '';

        $invoiceCode   = htmlspecialchars($invoice['invoice_h_code'] ?? '');
        $invoiceDate   = $invoice['invoice_h_datetime'] ?? date('Y-m-d H:i:s');
        $deliveryDate  = $invoice['invoice_h_delivery_date'] ?? '';
        $deliveryAddr  = htmlspecialchars($invoice['invoice_h_delivery_address'] ?? '');
        $deliveryTime  = htmlspecialchars($invoice['invoice_h_delivery_time'] ?? '');
        $custName      = htmlspecialchars($customer['customer_name'] ?? '');
        $custEmail     = htmlspecialchars($customer['customer_email'] ?? '');
        $custPhone     = htmlspecialchars($customer['customer_tell'] ?? $customer['customer_mobile'] ?? '');
        $custAddress   = htmlspecialchars($customer['customer_address'] ?? '');

        $netValue      = (float)($invoice['invoice_h_net_value'] ?? 0);
        $discount      = (float)($invoice['invoice_h_coupon_value'] ?? 0);
        $delivery      = (float)($invoice['invoice_h_delivery_cost'] ?? 0);
        $grossValue    = (float)($invoice['invoice_h_gross_value'] ?? 0);

        // Build items rows
        $itemsHtml = '';
        $i = 0;
        foreach ($items as $item) {
            $i++;
            $itemName  = htmlspecialchars($item['item_name'] ?? '');
            $qty       = (float)($item['invoice_d_qty'] ?? 0);
            $price     = (float)($item['invoice_d_item_price'] ?? 0);
            $discVal   = (float)($item['invoice_d_discount_value'] ?? 0);
            $total     = (float)($item['invoice_d_item_total'] ?? 0);

            if ($discVal > 0) {
                $effectivePrice = $price - ($price * $discVal / 100);
            } else {
                $effectivePrice = $price;
            }
            $lineTotal = $effectivePrice * $qty;

            $bg = ($i % 2 === 0) ? '#f8f9fa' : '#ffffff';

            $itemsHtml .= "
            <tr style=\"background-color: {$bg};\">
                <td style=\"padding: 10px 12px; border-bottom: 1px solid #e9ecef; text-align: center; color: #495057;\">{$i}</td>
                <td style=\"padding: 10px 12px; border-bottom: 1px solid #e9ecef; color: #212529; font-weight: 500;\">{$itemName}</td>
                <td style=\"padding: 10px 12px; border-bottom: 1px solid #e9ecef; text-align: right; color: #495057;\">{$curr} " . number_format($effectivePrice, 2) . "</td>
                <td style=\"padding: 10px 12px; border-bottom: 1px solid #e9ecef; text-align: center; color: #495057;\">{$qty}</td>
                <td style=\"padding: 10px 12px; border-bottom: 1px solid #e9ecef; text-align: right; color: #212529; font-weight: 600;\">{$curr} " . number_format($lineTotal, 2) . "</td>
            </tr>";
        }

        $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 0; }
    .container { max-width: 750px; margin: 0 auto; padding: 30px; }
    .header { border-bottom: 3px solid #d4a762; padding-bottom: 20px; margin-bottom: 25px; }
    .company-name { font-size: 22px; font-weight: 700; color: #2c3e50; margin: 0; }
    .company-detail { font-size: 11px; color: #6c757d; line-height: 1.6; }
    .badge { display: inline-block; background: #d4a762; color: #fff; font-size: 11px; font-weight: 600; padding: 4px 12px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-grid { width: 100%; margin-bottom: 25px; }
    .info-grid td { vertical-align: top; padding: 0; }
    .info-box { background: #f8f9fa; border-radius: 8px; padding: 15px; border: 1px solid #e9ecef; }
    .info-box h4 { margin: 0 0 8px 0; font-size: 12px; color: #d4a762; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-box p { margin: 2px 0; font-size: 11px; color: #495057; line-height: 1.5; }
    .info-box .value { color: #212529; font-weight: 500; }
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
    .items-table thead th { background: #2c3e50; color: #fff; padding: 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
    .items-table thead th:first-child { border-radius: 6px 0 0 0; }
    .items-table thead th:last-child { border-radius: 0 6px 0 0; }
    .totals-box { float: right; width: 280px; }
    .totals-table { width: 100%; border-collapse: collapse; }
    .totals-table td { padding: 8px 12px; font-size: 12px; }
    .totals-table .label { color: #6c757d; text-align: left; }
    .totals-table .value { text-align: right; color: #212529; }
    .totals-table .grand { border-top: 2px solid #d4a762; }
    .totals-table .grand td { font-size: 15px; font-weight: 700; color: #2c3e50; padding-top: 12px; }
    .footer { clear: both; border-top: 1px solid #e9ecef; padding-top: 20px; margin-top: 40px; text-align: center; font-size: 10px; color: #adb5bd; }
</style>
</head>
<body>
<div class="container">

    <!-- Header -->
    <table class="header" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="60%">
                <p class="company-name">' . htmlspecialchars($companyName) . '</p>
                <span class="company-detail">
                    ' . (!empty($companyAddress) ? nl2br(htmlspecialchars($companyAddress)) . '<br>' : '') . '
                    ' . (!empty($companyPhone) ? 'Phone: ' . htmlspecialchars($companyPhone) . '<br>' : '') . '
                    ' . (!empty($companyEmail) ? 'Email: ' . htmlspecialchars($companyEmail) : '') . '
                </span>
            </td>
            <td width="40%" style="text-align: right; vertical-align: top;">
                <span class="badge">' . htmlspecialchars($orderTypeLabel) . ' Confirmation</span>
                <br><br>
                <span style="font-size: 13px; font-weight: 600; color: #2c3e50;">Invoice: ' . $invoiceCode . '</span><br>
                <span style="font-size: 11px; color: #6c757d;">Date: ' . date('d M Y', strtotime($invoiceDate)) . '</span>
            </td>
        </tr>
    </table>

    <!-- Customer & Delivery Info -->
    <table class="info-grid" cellpadding="0" cellspacing="0">
        <tr>
            <td width="48%">
                <div class="info-box">
                    <h4>Customer Details</h4>
                    <p><span class="value">' . $custName . '</span></p>
                    ' . (!empty($custEmail) ? '<p>Email: <span class="value">' . $custEmail . '</span></p>' : '') . '
                    ' . (!empty($custPhone) ? '<p>Phone: <span class="value">' . $custPhone . '</span></p>' : '') . '
                    ' . (!empty($custAddress) ? '<p>Address: <span class="value">' . $custAddress . '</span></p>' : '') . '
                </div>
            </td>
            <td width="4%"></td>
            <td width="48%">
                <div class="info-box">
                    <h4>Delivery Information</h4>
                    <p>Date: <span class="value">' . (!empty($deliveryDate) ? date('d M Y', strtotime($deliveryDate)) : '-') . '</span></p>
                    ' . (!empty($deliveryTime) ? '<p>Time: <span class="value">' . $deliveryTime . '</span></p>' : '') . '
                    <p>Address: <span class="value">' . (!empty($deliveryAddr) ? $deliveryAddr : '-') . '</span></p>
                </div>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">#</th>
                <th style="text-align: left;">Item</th>
                <th style="width: 100px; text-align: right;">Unit Price</th>
                <th style="width: 70px; text-align: center;">Qty</th>
                <th style="width: 110px; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            ' . $itemsHtml . '
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-box">
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">' . $curr . ' ' . number_format($netValue, 2) . '</td>
            </tr>
            ' . ($discount > 0 ? '<tr><td class="label">Discount</td><td class="value" style="color: #e74c3c;">- ' . $curr . ' ' . number_format($discount, 2) . '</td></tr>' : '') . '
            ' . ($delivery > 0 ? '<tr><td class="label">Delivery Charge</td><td class="value">' . $curr . ' ' . number_format($delivery, 2) . '</td></tr>' : '') . '
            <tr class="grand">
                <td class="label" style="font-weight: 700;">Total Amount</td>
                <td class="value">' . $curr . ' ' . number_format($grossValue, 2) . '</td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>This is an electronically generated document. No signature is required.</p>
        <p>' . htmlspecialchars($companyName) . ' &bull; ' . date('d M Y H:i:s') . '</p>
    </div>

</div>
</body>
</html>';

        return $html;
    }

    /**
     * Build HTML for Standing Order PDF (landscape with weekly schedule)
     */
    private function buildStandingOrderHtml(array $data): string
    {
        $so              = $data['standingOrder'] ?? [];
        $customer        = $data['customer'] ?? [];
        $items           = $data['items'] ?? [];
        $shipping        = $data['shipping'] ?? [];
        $settings        = $data['settings'] ?? [];
        $invoiceSettings = $data['invoiceSettings'] ?? [];
        $curr            = $data['currencySymbol'] ?? '$';

        $companyName    = $invoiceSettings['receipt_name'] ?? $settings['SiteName'] ?? 'Bakery';
        $companyEmail   = $invoiceSettings['receipt_email'] ?? $settings['system_email'] ?? '';
        $companyPhone   = $invoiceSettings['receipt_phone'] ?? $settings['contactUs'] ?? '';
        $companyAddress = $invoiceSettings['receipt_address'] ?? $settings['address'] ?? '';

        $custName    = htmlspecialchars($customer['customer_name'] ?? '');
        $custEmail   = htmlspecialchars($customer['customer_email'] ?? '');
        $dateFrom    = $so['date_from'] ?? '';
        $dateTo      = $so['date_to'] ?? '';
        $deliveryAmt = (float)($so['DeliveryAmount'] ?? 0);

        $shippingLabel = '';
        if ($shipping) {
            $parts = array_filter([
                $shipping['address_label'] ?? '',
                $shipping['address_line_1'] ?? '',
                $shipping['address_line_2'] ?? '',
                $shipping['city'] ?? ''
            ]);
            $shippingLabel = htmlspecialchars(implode(', ', $parts));
        }

        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $dayColumns = ['mon_qty', 'tue_qty', 'wed_qty', 'thu_qty', 'fri_qty', 'sat_qty', 'sun_qty'];

        // Build items table
        $itemsHtml = '';
        $i = 0;
        $dayTotals = [0, 0, 0, 0, 0, 0, 0];
        $grandTotalQty = 0;

        foreach ($items as $item) {
            $i++;
            $itemName = htmlspecialchars($item['item_name'] ?? '');
            $price    = (float)($item['item_normal_selling_price'] ?? 0);
            $bg       = ($i % 2 === 0) ? '#f8f9fa' : '#ffffff';
            $weeklyQty = 0;

            $itemsHtml .= "<tr style=\"background-color: {$bg};\">";
            $itemsHtml .= "<td style=\"padding: 8px 10px; border-bottom: 1px solid #e9ecef; text-align: center;\">{$i}</td>";
            $itemsHtml .= "<td style=\"padding: 8px 10px; border-bottom: 1px solid #e9ecef; font-weight: 500;\">{$itemName}</td>";
            $itemsHtml .= "<td style=\"padding: 8px 10px; border-bottom: 1px solid #e9ecef; text-align: right;\">{$curr} " . number_format($price, 2) . "</td>";

            for ($d = 0; $d < 7; $d++) {
                $qty = (float)($item[$dayColumns[$d]] ?? 0);
                $dayTotals[$d] += $qty;
                $weeklyQty += $qty;
                $cellStyle = $qty > 0 ? 'color: #212529; font-weight: 600;' : 'color: #ccc;';
                $itemsHtml .= "<td style=\"padding: 8px 10px; border-bottom: 1px solid #e9ecef; text-align: center; {$cellStyle}\">" . ($qty > 0 ? $qty : '-') . "</td>";
            }

            $weeklyTotal = $weeklyQty * $price;
            $grandTotalQty += $weeklyQty;
            $itemsHtml .= "<td style=\"padding: 8px 10px; border-bottom: 1px solid #e9ecef; text-align: center; font-weight: 600;\">{$weeklyQty}</td>";
            $itemsHtml .= "<td style=\"padding: 8px 10px; border-bottom: 1px solid #e9ecef; text-align: right; font-weight: 600;\">{$curr} " . number_format($weeklyTotal, 2) . "</td>";
            $itemsHtml .= "</tr>";
        }

        // Totals row
        $itemsHtml .= '<tr style="background: #2c3e50; color: #fff; font-weight: 600;">';
        $itemsHtml .= '<td colspan="3" style="padding: 10px 12px; text-align: right;">TOTALS</td>';
        for ($d = 0; $d < 7; $d++) {
            $itemsHtml .= '<td style="padding: 10px 12px; text-align: center;">' . ($dayTotals[$d] > 0 ? $dayTotals[$d] : '-') . '</td>';
        }
        $itemsHtml .= '<td style="padding: 10px 12px; text-align: center;">' . $grandTotalQty . '</td>';
        $itemsHtml .= '<td style="padding: 10px 12px; text-align: right;"></td>';
        $itemsHtml .= '</tr>';

        // Day headers
        $dayHeaders = '';
        foreach ($days as $day) {
            $dayHeaders .= "<th style=\"width: 55px; text-align: center;\">{$day}</th>";
        }

        $html = '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
    .container { max-width: 1050px; margin: 0 auto; padding: 20px; }
    .header { border-bottom: 3px solid #d4a762; padding-bottom: 15px; margin-bottom: 20px; }
    .company-name { font-size: 20px; font-weight: 700; color: #2c3e50; margin: 0; }
    .company-detail { font-size: 10px; color: #6c757d; }
    .badge { display: inline-block; background: #27ae60; color: #fff; font-size: 11px; font-weight: 600; padding: 4px 12px; border-radius: 4px; text-transform: uppercase; }
    .info-grid { width: 100%; margin-bottom: 20px; }
    .info-grid td { vertical-align: top; }
    .info-box { background: #f8f9fa; border-radius: 6px; padding: 12px; border: 1px solid #e9ecef; }
    .info-box h4 { margin: 0 0 6px 0; font-size: 11px; color: #d4a762; text-transform: uppercase; letter-spacing: 0.5px; }
    .info-box p { margin: 2px 0; font-size: 10px; color: #495057; }
    .info-box .value { color: #212529; font-weight: 500; }
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 10px; }
    .items-table thead th { background: #2c3e50; color: #fff; padding: 10px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.3px; font-weight: 600; }
    .items-table thead th:first-child { border-radius: 6px 0 0 0; }
    .items-table thead th:last-child { border-radius: 0 6px 0 0; }
    .footer { border-top: 1px solid #e9ecef; padding-top: 15px; margin-top: 30px; text-align: center; font-size: 9px; color: #adb5bd; }
</style>
</head>
<body>
<div class="container">

    <!-- Header -->
    <table class="header" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="60%">
                <p class="company-name">' . htmlspecialchars($companyName) . '</p>
                <span class="company-detail">
                    ' . (!empty($companyPhone) ? 'Phone: ' . htmlspecialchars($companyPhone) . ' | ' : '') . '
                    ' . (!empty($companyEmail) ? 'Email: ' . htmlspecialchars($companyEmail) : '') . '
                </span>
            </td>
            <td width="40%" style="text-align: right; vertical-align: top;">
                <span class="badge">Standing Order</span>
                <br><br>
                <span style="font-size: 11px; color: #6c757d;">
                    ' . (!empty($dateFrom) ? 'From: ' . date('d M Y', strtotime($dateFrom)) : '') . '
                    ' . (!empty($dateTo) ? '&nbsp; To: ' . date('d M Y', strtotime($dateTo)) : '') . '
                </span>
            </td>
        </tr>
    </table>

    <!-- Customer & Delivery Info -->
    <table class="info-grid" cellpadding="0" cellspacing="0">
        <tr>
            <td width="48%">
                <div class="info-box">
                    <h4>Customer</h4>
                    <p><span class="value">' . $custName . '</span></p>
                    ' . (!empty($custEmail) ? '<p>Email: <span class="value">' . $custEmail . '</span></p>' : '') . '
                </div>
            </td>
            <td width="4%"></td>
            <td width="48%">
                <div class="info-box">
                    <h4>Delivery</h4>
                    ' . (!empty($shippingLabel) ? '<p>Address: <span class="value">' . $shippingLabel . '</span></p>' : '') . '
                    ' . ($deliveryAmt > 0 ? '<p>Delivery Charge: <span class="value">' . $curr . ' ' . number_format($deliveryAmt, 2) . '</span></p>' : '') . '
                </div>
            </td>
        </tr>
    </table>

    <!-- Weekly Schedule Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30px; text-align: center;">#</th>
                <th style="text-align: left;">Item</th>
                <th style="width: 80px; text-align: right;">Price</th>
                ' . $dayHeaders . '
                <th style="width: 55px; text-align: center;">Weekly</th>
                <th style="width: 80px; text-align: right;">Weekly Total</th>
            </tr>
        </thead>
        <tbody>
            ' . $itemsHtml . '
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p>This is an electronically generated document. No signature is required.</p>
        <p>' . htmlspecialchars($companyName) . ' &bull; Generated: ' . date('d M Y H:i:s') . '</p>
    </div>

</div>
</body>
</html>';

        return $html;
    }
}
