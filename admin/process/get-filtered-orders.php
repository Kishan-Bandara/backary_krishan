<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../include/database.php');
include('../include/check_login.php');

$filter = isset($_POST['filter']) ? $_POST['filter'] : 'all';

function getFilteredContent($filter = 'all') {
    $db = new Database();
    $params = [];
    if (isSuperAdmin()) {
        $query = 'SELECT * FROM invoice_hedder WHERE 1=1';
    } else {
        $query = 'SELECT * FROM invoice_hedder WHERE invoice_h_location = ?';
        $params[] = $_SESSION['location'];
    }

    switch($filter) {
        case 'today_deliveries':
            $query .= ' AND invoice_h_delivery_date = CURDATE()';
            break;
        case 'pending':
            $query .= ' AND invoice_h_status = 0';
            break;
        case 'accepted':
            $query .= ' AND invoice_h_status = 1';
            break;
        case 'cancelled':
            $query .= ' AND invoice_h_status = -1';
            break;
        case 'all':
        default:
            // No additional filter
            break;
    }

    $query .= ' ORDER BY invoice_h_id DESC';
    return $db->getRows($query, $params);
}

$data = getFilteredContent($filter);

if (empty($data)) {
    echo '<tr><td colspan="8" class="text-center text-muted">No orders found for the selected filter.</td></tr>';
    exit;
}

foreach($data as $query_invoice_h) {
    $invoice_h_id = $query_invoice_h['invoice_h_id'];
    $customer_id = $query_invoice_h['invoice_h_customer_id'];
    $customer_note = $query_invoice_h['invoice_h_order_note'];
    $query_invoice_h_customer = $db->getRow('SELECT * FROM customer WHERE customer_id = ?',[$customer_id]);
    $query_invoice_amount = $db->getRow('SELECT * FROM invoice_hedder WHERE invoice_h_id = ?',[$invoice_h_id]);
    $net_value = $query_invoice_amount['invoice_h_net_value'];
    $invoice_status = $query_invoice_h['invoice_h_status'];
    $query_customer_amount = $db->getRow('SELECT SUM(amount) as customer_amount FROM customer_balance WHERE invoice_h_id = ?',[$invoice_h_id]);
    $amount = $query_customer_amount['customer_amount'];

    if($amount) {
        $amount = $amount;
    } else {
        $amount = 0;
    }

    $style = "";
    $status = "";
    if($net_value == $amount || $amount > $net_value ) {
        $style = "lbl_Payment_status_paid";
        $status = "Paid";
    } elseif ($net_value > $amount && $amount != 0) {
        $style = "lbl_Payment_status_partial";
        $status = "Partial";
    } elseif ($amount == 0) {
        $style = "lbl_Payment_status_pending";
        $status = "Pending";
    } else {
        $style = "lbl_Payment_status_pending";
        $status = "Error";
    }

    #order Status
    if (((isset($query_invoice_h_customer['account_hold']) && (int)$query_invoice_h_customer['account_hold'] === 1)
        || (isset($query_invoice_h_customer['locked']) && (int)$query_invoice_h_customer['locked'] === 1))
        && $invoice_status !== -1) {
        $order_status = "Hold";
    } elseif($invoice_status == 1) {
        $order_status = "Accept";
    } elseif($invoice_status == 0) {
        $order_status = "Pending";
    } elseif($invoice_status == -1) {
        $order_status = "Canceled";
    } else {
        $order_status = "Something Wrong";
    }

    ?>
    <tr>
        <th></th>
        <td><a href="../invoice.php?id=<?php echo $invoice_h_id;?>" target="_blank"><?php echo $query_invoice_h['invoice_h_code'];?></a></td>
        <td><?php echo $query_invoice_h_customer['customer_name'];?></td>
        <td><?php echo $query_invoice_h['invoice_h_datetime'];?></td>
        <td><?php include('../currency.php');?> <?php echo number_format($query_invoice_h['invoice_h_gross_value'],2);?></td>
        <td><small><b><?php echo $order_status;?></b></small><br></td>
        <td><span class="<?php echo $style; ?>"><?php echo $status;?></span></td>
        <td>
            <div style="float:left;"></div>
            <?php if($invoice_status == 0) { ?>
                <div class="btn-group btn-group-xs btn-group-solid">
                    <button type="button" onclick="myFunction(this)" value="<?php echo $invoice_h_id; ?>" data-toggle="modal" data-target="#myModal" class="btn dark btn-outline sbold uppercase addStatus">Order Status</button>
                </div>
            <?php } ?>
            <div>
                <?php if($customer_note) { ?>
                    <div class="btn-group btn-group-xs btn-group-solid" data-toggle="modal" data-target="#orderNote">
                        <button type="button" class="btn blue btn-outline btnorderNote" data-order-note="<?php echo htmlspecialchars($customer_note); ?>" data-order-id="<?php echo $invoice_h_id; ?>">Note</button>
                    </div>
                <?php } ?>
            </div>
            <div>
                <a href="../order-detail.php?order_id=<?php echo $invoice_h_id;?>" target="_blank">
                    <div class="btn-group btn-group-xs btn-group-solid">
                        <button type="button" class="btn green btn-outline">View Details</button>
                    </div>
                </a>
                <a href="../payment_note.php?id=<?php echo $invoice_h_id;?>" target="_blank" style="display:none;">
                    <div class="btn-group btn-group-xs btn-group-solid">
                        <button type="button" class="btn blue btn-outline">Make a Payment</button>
                    </div>
                </a>
            </div>
        </td>
    </tr>
    <?php
}
?>



