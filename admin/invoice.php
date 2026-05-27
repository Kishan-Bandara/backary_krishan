<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');

// Default values to avoid undefined variable warnings
$invoice_code = '';
$pay_type = '';
$customer_id = 0;
$date = null;
$net_value = 0.0;
$vat_value = 0.0;
$gross_value = 0.0;
$address = '-';
$receipt_payment_status = 'Pending';
$payment_cheque_ref = '';
$payment_card_ref = '';
$copun_discount = 0.0;
$approved_date = null;
$receipt_deliver_mode = '';
$receipt_deliver_date = '';
$receipt_deliver_contact_number = '-';
$receipt_coupon_discount = 0.0;
$customer_name = '';
$customer_address = '';
$customer_tell = '';
$customer_No = '';
$customer_email = '';
$purchase_order_no = '';
$get_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Global helper for retrieving invoice items (safe if id missing)
function getContent($id = null) {
    if (empty($id)) return [];
    $db = new Database();
    return $db->getRows('SELECT * FROM item_master itm JOIN invoice_details inv ON inv.invoice_d_item_id = itm.item_id WHERE inv.invoice_h_id = ?', [$id]);
}

?>
<?php

if(isset($_GET['id']))
{

    $get_id = $_GET['id'];

     if(!empty($get_id) && $get_id > 0)
     {

    $db = new Database();
    $query_vat_id = $db->getRow('SELECT * FROM product_vat_master ORDER BY id DESC LIMIT 1');
        if($query_vat_id)
        {
            $item_vat_value = $query_vat_id['rate'];

        }
    $query_invoice_h_id = $db->getRow('SELECT * FROM invoice_hedder WHERE invoice_h_id = ?',[$get_id]);
    $invoice_h_id = $query_invoice_h_id['invoice_h_id'];

        if($invoice_h_id == $get_id)
        {

            $invoice_code = $query_invoice_h_id['invoice_h_code'];
            $pay_type = $query_invoice_h_id['invoice_h_pay_type'];
            $customer_id = $query_invoice_h_id['invoice_h_customer_id'];
            $date = $query_invoice_h_id['invoice_h_datetime'];
            $net_value = $query_invoice_h_id['invoice_h_net_value'];
            $vat_value = $query_invoice_h_id['invoice_h_vat_value'];
            $gross_value = $query_invoice_h_id['invoice_h_gross_value'];
            $address = $query_invoice_h_id['invoice_h_delivery_address'];
            $receipt_payment_status = $query_invoice_h_id['invoice_h_status'];
            $payment_cheque_ref =   $query_invoice_h_id['invoice_h_check_Ref'];
            $payment_card_ref =   $query_invoice_h_id['invoice_h_card_Ref'];
            $purchase_order_no = trim((string)($query_invoice_h_id['invoice_h_purchase_order_no'] ?? ''));
            if ($purchase_order_no === '') {
                $purchase_order_no = trim((string)($query_invoice_h_id['invoice_h_check_Ref'] ?? ''));
            }
            $copun_discount =   $query_invoice_h_id['invoice_h_coupon_value'];
            $approved_date = $query_invoice_h_id['invoice_h_approve_date'];
            $receipt_deliver_mode = $query_invoice_h_id['invoice_h_delivery_mode'];
            $receipt_deliver_date = $query_invoice_h_id['invoice_h_delivery_date'];
            $receipt_deliver_contact_number = $query_invoice_h_id['invoice_h_delivery_contact_no'];
            $receipt_coupon_discount = $query_invoice_h_id['invoice_h_coupon_value'];


            if($receipt_deliver_contact_number){

                $receipt_deliver_contact_number = $receipt_deliver_contact_number;
            }else{

                $receipt_deliver_contact_number = "-";
            }

            if($address){

                $address = $address;
            }else{

                $address = "-";
            }

           if( $receipt_payment_status == 0){

             $receipt_payment_status = "Pending";

          } else if( $receipt_payment_status == 1){

             $receipt_payment_status = "Paid";
          } else {

             $receipt_payment_status = "Canceled";
          }


                if($receipt_deliver_mode){
          try {

            $query_get_deliver_mode = $db->getRow('SELECT * FROM delivery_master WHERE id = ?',[$receipt_deliver_mode]);

            $receipt_deliver_mode = $query_get_deliver_mode['method'];
            
          } catch (Exception $e) {
            
            echo 'Message: ' .$e->getMessage();
          }


        }
                 //customer hoyagannawa

            if($customer_id)
            {
                $query_customer_id = $db->getRow('SELECT * FROM customer WHERE customer_id = ?',[$customer_id]);
                $customer_name = $query_customer_id['customer_name'];
                $customer_address = $query_customer_id['customer_address'];
                $customer_tell = $query_customer_id['customer_tell'];
                $customer_email = $query_customer_id['customer_email'] ?? '';
                if (empty($customer_tell)) {
                    $customer_tell = $query_customer_id['customer_mobile'] ?? '';
                }
                $customer_No = "";

            }

            // paytype eka hoyagannawa
             if($pay_type)
            {
                $query_paytype = $db->getRow('SELECT * FROM payment_method WHERE id = ?',[$pay_type]);
                $pay_type = $query_paytype['type'];

            }

            //payment referance no eka
            if($query_invoice_h_id['invoice_h_pay_type'] == 2){

                $pay_refrance = "Payment Reference: ".$payment_cheque_ref;

            }elseif($query_invoice_h_id['invoice_h_pay_type'] == 3){

                $pay_refrance = "Payment Reference: ".$payment_card_ref;

            }else{

                $pay_refrance = "";
            }





        }
        else
        {
            echo "Invoice ID did not match.";

        }

     }
     else
     {

        echo"wrong invoice ID.";

     }

}

               // Safely parse date/time values only when provided
               $year = $month = $day = $hour = $minut = $sec = '';
               if (!empty($date) && strtotime($date) !== false) {
                   $time = strtotime($date);
                   $year = date('Y', $time);
                   $month = date('M', $time);
                   $day = date('d', $time);
                   $hour = date('h', $time);
                   $minut = date('i', $time);
                   $sec = date('s', $time);
               }

               $approved_year = $approved_month = $approved_day = $approved_hour = $approved_minut = $approved_sec = '';
               if (!empty($approved_date) && strtotime($approved_date) !== false) {
                   $approved_time = strtotime($approved_date);
                   $approved_year = date('Y', $approved_time);
                   $approved_month = date('M', $approved_time);
                   $approved_day = date('d', $approved_time);
                   $approved_hour = date('h', $approved_time);
                   $approved_minut = date('i', $approved_time);
                   $approved_sec = date('s', $approved_time);
               }

// Load invoice/receipt settings (logo, name, contact) if available
$invoice_logo = 'assets/layouts/layout/img/logo.avif';
$receipt_name = '';
$receipt_email = '';
$receipt_phone = '';
$receipt_address = '';
try {
    $s = $db->getRow('SELECT * FROM invoice_settings WHERE id = 1');
    if ($s) {
        if (!empty($s['invoice_logo']) && file_exists($s['invoice_logo'])) {
            $invoice_logo = $s['invoice_logo'];
        }
        $receipt_name = $s['receipt_name'] ?? '';
        $receipt_email = $s['receipt_email'] ?? '';
        $receipt_phone = $s['receipt_phone'] ?? '';
        $receipt_address = $s['receipt_address'] ?? '';
    }
} catch(Exception $e) {
    // Ignore - use defaults
}

?>
<!DOCTYPE html>

<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
    <!--<![endif]-->
    <!-- BEGIN HEAD -->


<head>
        <meta charset="utf-8" />
        <title>Invoice</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>
       </head>
    <!-- END HEAD -->

    <body class="page-sidebar-closed-hide-logo page-content-white">
      <?php include('common/manubar.php'); ?>
        <!-- BEGIN HEADER & CONTENT DIVIDER -->
        <div class="clearfix"> </div>
        <!-- END HEADER & CONTENT DIVIDER -->
        <!-- BEGIN CONTAINER -->
        <div class="page-container">
             <div class="page-sidebar-wrapper">
           <?php include('common/sidebar.php'); ?>
            
            </div>
            <!-- END SIDEBAR -->
            <!-- BEGIN CONTENT -->
            <div class="page-content-wrapper">
                <!-- BEGIN CONTENT BODY -->
                <div class="page-content">
                    <!-- BEGIN PAGE HEADER-->
          
                    <!-- BEGIN PAGE BAR -->
                    <div class="page-bar">
                        <ul class="page-breadcrumb">
                            <li>
                                <a href="index-2.html">Home</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <a href="#">Purchase</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <span>Invoice</span>
                            </li>
                        </ul>
                      
                    </div>
                    <!-- END PAGE BAR -->
                   
                    <!-- END PAGE HEADER-->
                  
                    <div class="row">
                        <div class="col-md-12">
                      

                            <div class="">
     
      <div class="row pad-top-botm ">
         <div class="col-lg-8 col-md-8 col-sm-8 ">
            <?php if (!empty($invoice_logo) && file_exists($invoice_logo)) { ?>
                <img src="<?php echo htmlspecialchars($invoice_logo); ?>?t=<?php echo time(); ?>" style="padding-bottom:20px; max-width:100%; width:400px;"> 
            <?php } else { ?>
                <img src="assets/layouts/layout/img/logo.avif" style="padding-bottom:20px; width:400px;"> 
            <?php } ?>
         </div>
          <div class="col-lg-4 col-md-4 col-sm-4">

               <strong><?php echo htmlspecialchars($receipt_name ?: 'Your Business Name'); ?></strong>
             <br>
             <?php if ($receipt_email) { ?><strong>Email : </strong> <?php echo htmlspecialchars($receipt_email); ?><br><?php } ?>
             <?php if ($receipt_phone) { ?><strong>Contact us : </strong> <?php echo htmlspecialchars($receipt_phone); ?><br><?php } ?>
             <?php if ($receipt_address) { ?><small><?php echo nl2br(htmlspecialchars($receipt_address)); ?></small><?php } ?>
         </div>
     </div>
     <div class="row text-center contact-info">
         <div class="col-lg-12 col-md-12 col-sm-12">
             <hr>
            <p style="text-align:left;"> <span style="font-size: 14px; padding: 0px 50px 0px 50px;">
                 <strong>Email : </strong>  <?php echo htmlspecialchars($customer_email ?: '-'); ?>
             </span>
             
              <span >
              <strong>Contact us : </strong>  <?php echo htmlspecialchars($customer_tell ?: '-'); ?>
             </span> </p>
             <hr>
         </div>
     </div>
     <div class="row pad-top-botm client-info" style="padding-bottom: 40px;">
         <div class="col-lg-6 col-md-6 col-sm-6">
         <h4>  <strong>Delivery Information</strong></h4>
           <strong id="deliver_contact_name"><?php echo $customer_name; ?></strong>
           <br>
            <b>Delivery Mode :</b> <span id="deliver_mode"><?php echo $receipt_deliver_mode; ?> </span>
             <br>
                  <b>Address :</b> <span id="deliver_address"><?php echo $address; ?> </span>
              <br>
               <b>Call :</b> <span id="deliver_no"><?php echo $receipt_deliver_contact_number; ?> </span>
               <span id="deliver_city"> </span>
             <br>
            
              <br>
               <b>Delivery Date :</b> <span id="deliver_date"><?php echo $receipt_deliver_date; ?> </span>
               <br>
               <b>Time Slot : </b><span><?php echo htmlspecialchars($query_invoice_h_id['invoice_h_delivery_time'] ?? ''); ?></span>
            
         </div>
           <div class="col-lg-2 col-md-2 col-sm-2">
            
              
         </div>
          <div class="col-lg-4 col-md-4 col-sm-4">
            
               <h4>  <strong>Payment Details </strong></h4>
            <b>Bill Amount : <span id="total_cost"><?php include('currency.php'); ?> <?php echo number_format((float)$gross_value,2); ?> </span> </b>
              <br>
               Bill Date :  <span id="bill_date"><?php echo $date; ?> </span>
              <br>
               <b>Payment Status :  <span id="inv_status"><?php echo $receipt_payment_status; ?> </span> </b>
                <br>
               <b>Invoice No#:  <span id="inv_status"><?php echo $invoice_code; ?> </span> </b>
               <br>
               <b>Purchase order# :  <span><?php echo htmlspecialchars($purchase_order_no !== '' ? $purchase_order_no : '-'); ?> </span> </b>
            
             
         </div>
     </div>
     <div class="row">
         <div class="col-lg-12 col-md-12 col-sm-12">
           <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th style="text-align:left;">#</th>
                                    <th style="text-align:left;">Item</th>
                                    <th style="text-align:left;">Unit Price</th>
                                    <th style="text-align:left;">Quantity</th>
                                    <!-- <th style="text-align:left;">Item Discount</th> -->
                                    <th style="text-align:left;">Sub Total</th>
                                    <th style="text-align:left;"></th>
                                </tr>
                            </thead>
                            <tbody id="data-details">
                                 <?php $data = getContent($get_id);
                                 $i = 0;
                                        foreach($data as $query) 
                                            {
                                                $i++;
                                                $typeid = $query['item_id']; 
                                            $item_name = $query['item_name']; 
                                            $item_price = $query['invoice_d_item_price'];
                                            $item_qty = $query['invoice_d_qty'];
                                            $invoice_d_item_total=$query['invoice_d_item_total'];
                                            $invoice_d_discount_value=$query['invoice_d_discount_value'];
                                            if($invoice_d_discount_value>0){
                                                $discount_value = ($item_price * $invoice_d_discount_value) / 100;
                                                $item_priceWithDiscount = $item_price- $discount_value;
                                            }else{
                                                $item_priceWithDiscount = $item_price;
                                            }
                                                ?> 

                                                <tr>
                                    <td><?php echo $i; ?></td>
                                    <td><?php echo $query['item_name']; ?><?php if (!empty($query['is_gift_item'])): ?> <span style="display:inline-block; margin-left:6px; padding:2px 7px; border-radius:10px; background:#fef3c7; color:#b45309; font-size:10px; font-weight:700; text-transform:uppercase;"><i class="fa fa-gift"></i> Gift</span><?php endif; ?></td>
                                    <td><?php include('currency.php');?> <?php echo number_format($item_priceWithDiscount,2); ?></td>
                                    <td><?php echo $item_qty; ?></td>
                                  <!-- <td> --> <?php /*if($query['invoice_d_discount_value']>0){echo "- ".$query['invoice_d_discount_value']." %";}else{ echo 0.00." %";}*/?><!-- </td> --> 
                                    <td style="text-align:right;"><?php include('currency.php');?> <?php echo number_format(($item_priceWithDiscount * $item_qty), 2); ?></td><td>
                                </td></tr>

                                 <?php } ?>
                            
                            </tbody>
                        </table>
               </div>
             <hr>
              <div class="ttl-amts" style="text-align: right;
    padding-right: 35px;
">
               <h5> Sub Total: <span id="sub_total"><?php include('currency.php');?> <?php echo number_format($net_value,2); ?> </span> </h5>
               <h5>  Discount  : <span id="all_discount"><?php include('currency.php');?> <?php echo number_format($copun_discount,2); ?></span> </h5>
             </div>
             <div class="ttl-amts" style="text-align: right;
    padding-right: 35px;
">

             </div>
             <hr>
              <div class="ttl-amts" style="text-align: right;
    padding-right: 35px;
">

                  <?php $deliveryCost = (float)($query_invoice_h_id['invoice_h_delivery_cost'] ?? 0); ?>
                  <h5> Delivery Charge: <span id="delivery_charge"><?php include('currency.php');?> <?php echo number_format($deliveryCost,2); ?> </span> </h5>
             </div>
             <hr>
              <div class="ttl-amts" style="text-align: right;
    padding-right: 35px;
">
                  <h4> <strong>Bill Amount : <span id="total"><?php include('currency.php');?> <?php echo number_format((float)$gross_value,2);?> </span></strong> </h4>
                 <br><br>  <a class="btn btn-lg blue hidden-print margin-bottom-5" onclick="javascript:window.print();"> Print
                                    <i class="fa fa-print"></i>
                                </a>
             </div>
         </div>
     </div>
      <div class="row">
         <div class="col-lg-12 col-md-12 col-sm-12">
            <strong> Important: </strong>
             <ol>
                  <li>
                   This is an electronically generated invoice so doesn't require any signature.

                 </li>
                 <li>
                     Please read all terms and policies on www.Morichmall.lk for returns and other issues.

                 </li>
             </ol>
             </div>
         </div>
 <!--      <div class="row pad-top-botm">
         <div class="col-lg-12 col-md-12 col-sm-12">
             <hr>
             <a href="#" class="btn btn-primary btn-lg">Print Invoice</a>
             &nbsp;&nbsp;&nbsp;
              <a href="#" class="btn btn-success btn-lg">Download In Pdf</a>

             </div>
         </div>
 </div> -->

              </div>

                        </div>
                        
                    </div>
                  
                </div>
                <!-- END CONTENT BODY -->
            </div>
            <!-- END CONTENT -->
          
        </div>
        <!-- END CONTAINER -->
    <?php include('common/footer.php');?>
        <!--[if lt IE 9]>
<script src="assets/global/plugins/respond.min.js"></script>
<script src="assets/global/plugins/excanvas.min.js"></script> 
<![endif]-->
        <!-- BEGIN CORE PLUGINS -->
        <script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
        <!-- END CORE PLUGINS -->
        <!-- BEGIN PAGE LEVEL PLUGINS -->
        <script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
        <script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL PLUGINS -->
        <!-- BEGIN THEME GLOBAL SCRIPTS -->
        <script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
        <!-- END THEME GLOBAL SCRIPTS -->
        <!-- BEGIN PAGE LEVEL SCRIPTS -->
        <script src="assets/pages/scripts/table-datatables-responsive.min.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL SCRIPTS -->
        <!-- BEGIN THEME LAYOUT SCRIPTS -->
        <script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
        <script src="assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>
        <script src="assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>
        <!-- END THEME LAYOUT SCRIPTS -->
    <script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','www.google-analytics.com/analytics.js','ga');
  ga('create', 'UA-37564768-1', 'keenthemes.com');
  ga('send', 'pageview');
</script>
</body>

</html>



