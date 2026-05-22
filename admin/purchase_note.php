<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('get_url.php');


?>
<?php
    if(isset($_GET['id']))
    {
        $id = $_GET['id'];

        if(!empty($id) && $id > 0)
        {
            $db = new Database();
            $query_grn_h_id = $db->getRow('SELECT * FROM grn_hedder WHERE grn_h_id = ?',[$id]);
            $grn_h_id = $query_grn_h_id['grn_h_id'];
            $grn_h_ref_no = $query_grn_h_id['grn_h_code'];
            $grn_h_date = $query_grn_h_id['grn_h_date'];
            $grn_h_total_amount = $query_grn_h_id['grn_h_net_value'];


            if($id == $grn_h_id)
            {

                //supplier_details
                $supplier = $query_grn_h_id['grn_h_supplier_id'];
                $query_supplier_id = $db->getRow('SELECT * FROM supplier WHERE supplier_id = ?',[$supplier]);
                $supplier_name = $query_supplier_id['supplier_name'];
                $supplier_number = $query_supplier_id['supplier_contact_no'];

                //stock house
                $location_id = $query_grn_h_id['grn_h_location'];
                $query_location_id = $db->getRow('SELECT * FROM location_master WHERE id = ?',[$location_id]);
                $location_name = $query_location_id['name'];
                $location_phone = $query_location_id['phone_no'];

                 //wena location ekaka ekaknam redirect karnawa.
                if($_SESSION['location'] != $location_id)
                        {
                            redirect('index.php');
                        }


                      //item tika loop karanwa

              function getContent() {
                $id = $_GET['id'];
                $db = new Database();
                $query = $db->getRows('SELECT * FROM item_master itm JOIN grn_details grn  ON grn.grn_d_item_id = itm.item_id WHERE grn.grn_h_id = ?',[$id]);
                return $query;
            }

                             //item tika loop karanwa

              function getRecordes() {
                $id = $_GET['id'];
                $db = new Database();
                $query_get_suppler_records = $db->getRows('SELECT * FROM supplier_balance WHERE grn_h_id = ?',[$id]);
                return $query_get_suppler_records;
            }

                    // amount eka gannawa 
                       
                        $PaidBalance = "";
                         // $id = $_GET['id'];      
                            //$db = new Database();
                            $query_get_amount = $db->getRow('SELECT SUM(amount) as supplier_amount FROM supplier_balance WHERE grn_h_id = ?',[$id]);
                            $get_amount = $query_get_amount['supplier_amount'];
                         if($get_amount)
                            {
                                $PaidBalance = $get_amount;

                            }
                            else
                            {

                                $PaidBalance = "0.00";
                            }
               
                           
            }           
            else
            {

                
                header('location:purchase-history.php');

            }


        }
        else
        {

             
             header('location:purchase-history.php');
        }


    }
    else
    {

       
        header('location:purchase-history.php');
    }

function load_payment_method()  
 {  
      
      $output = '';  
      $db = new Database();
      $query = $db->getRows('SELECT * FROM payment_method');
      $data = $query;
        foreach($data as $query) 
            {   

                $id = $query['id']; 
                $output .= '<option value="'.$query['type'].'">'.$query['type'].'</option>';

            }
            return $output;  
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
        <title>Purchase Note</title>
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
                                <a href="#">Home</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <a href="#">Purchase</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <span>View</span>
                            </li>
                        </ul>
                      
                    </div>
                    <!-- END PAGE BAR -->
                    <div class="alert <?php echo $MessageClass; ?> alert-dismissable">
                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                                        <?php echo $CompanyMessage; ?>
                                    </div>
                    <!-- END PAGE HEADER-->
                  
                    <div class="row">
                        <div class="col-md-12">
                    <div class="mt-element-step">
                         <div class="row step-thin">
                                  
                                            <div class="col-md-4 bg-grey mt-step-col active">
                                                <div class="mt-step-number bg-white font-grey" style="margin-right:10px;"><img src="assets/img/supplier_img.png"></div>
                                                <div class="mt-step-title uppercase font-grey-cascade">Supplier</div>
                                                <div class="mt-step-content font-grey-cascade"><b><?php echo $supplier_name; ?></b></div>
                                                <div class="mt-step-content font-grey-cascade"><small><?php echo $supplier_number;?></small></div>
                                            </div>
                                            <div class="col-md-4 bg-grey done mt-step-col active">
                                                <div class="mt-step-number bg-white font-grey" style="margin-right:10px;"><img src="assets/img/location.png"></div>
                                                <div class="mt-step-title uppercase font-grey-cascade">Stock House</div>
                                                <div class="mt-step-content font-grey-cascade"><b><?php echo $location_name;?></b></div>
                                                <div class="mt-step-content font-grey-cascade"><small><?php echo $location_phone; ?></small></div>
                                            </div>
                                            <div class="col-md-4 bg-grey  mt-step-col active " >
                                                <div class="mt-step-number bg-white font-grey"style="margin-right:10px;"><img src="assets/img/note.png"></div>
                                                <div class="mt-step-title uppercase font-grey-cascade"><?php echo $grn_h_ref_no;?></div>
                                                <div class="mt-step-content font-grey-cascade">Date: <?php echo $grn_h_date; ?></div>
                                            </div>
                                        </div>
                                    </div>
                            <div class="portlet light bordered" style="margin-top:20px;">
                                <div class="portlet-title">  </div>
                         <!-- table1 -->
                            <div class="portlet-body flip-scroll">
                                    <table class="table table-bordered table-striped table-condensed flip-content">
                                        <thead class="flip-content">
                                            <tr>
                                                <th> Code </th>
                                                <th> Description </th>
                                                <th class="numeric"> Unit Cost </th>
                                                <th class="numeric"> Quantity </th>
                                                <th class="numeric"> +GST </th>
                                                <th class="numeric"> Subtotal </th>
                                              
                                            </tr>
                                        </thead>
                                        <tbody>
                                             <?php $data = getContent();
                                        $total_amount = 0.00; $total_vat = 0.00; $total_with_vat = 0.00;
                                        foreach($data as $query) 
                                            {
                                                $typeid = $query['item_id']; 
                                                $item_name = $query['item_name']; 
                                                $base_unit_cost = isset($query['grn_d_rate']) && $query['grn_d_rate'] !== null
                                                    ? (float) $query['grn_d_rate']
                                                    : (float) ($query['item_purchase_price'] ?? 0);
                                                $item_vat = isset($query['grn_d_vat_rate']) && $query['grn_d_vat_rate'] !== null
                                                    ? (float) $query['grn_d_vat_rate']
                                                    : (float) ($query['item_vat'] ?? 0);
                                                $qty = (float)$query['grn_d_qty'];

                                                // Use saved GRN line values when present so PO overrides carry through.
                                                $line_amount = isset($query['grn_d_total']) && $query['grn_d_total'] !== null
                                                    ? (float) $query['grn_d_total']
                                                    : ($base_unit_cost * $qty); // excl VAT
                                                $line_vat = isset($query['grn_d_vat']) && $query['grn_d_vat'] !== null
                                                    ? (float) $query['grn_d_vat']
                                                    : (($base_unit_cost * ($item_vat / 100)) * $qty); // VAT for the line
                                                $line_total = $line_amount + $line_vat; // incl VAT
                                                $unit_cost = $qty > 0 ? ($line_amount / $qty) : $base_unit_cost;

                                                $total_amount += $line_amount;
                                                $total_vat += $line_vat;
                                                $total_with_vat += $line_total;

                                                ?> 
                                            <tr>
                                                <td> No# </td>
                                                <td> <?php echo $query['item_name']; ?> </td>
                                                <td class="numeric"><?php include('currency.php');?> <?php echo number_format($unit_cost,2); ?> </td>
                                                <td class="numeric"> <?php echo $qty; ?> </td>
                                                <td class="numeric"><?php include('currency.php');?> <?php echo number_format($line_vat,2); ?> </td>
                                                <td class="numeric"><?php include('currency.php');?> <?php echo number_format($line_total,2); ?></td>
                                             
                                                
                                            </tr>
                                             <?php } ?>
                                            <tr>
                                           
                                        </tbody>
                                   <tfoot>
                                    <tr> 
                                        <td style="text-align:right;  font-weight:bold;" colspan="5">Total (Excl. GST)</td>
                                         <td style="text-align:right; font-weight:bold;"><?php include('currency.php');?> <?php echo number_format($total_amount,2); ?> </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align:right;  font-weight:bold;" colspan="5">Total GST</td>
                                         <td style="text-align:right; font-weight:bold;"><?php include('currency.php');?> <?php echo number_format($total_vat,2); ?> </td>
                                    </tr>
                                     <tr> 
                                        <td style="text-align:right;  font-weight:bold;" colspan="5">Grand Total</td>
                                         <td style="text-align:right; font-weight:bold;"><?php include('currency.php');?> <?php echo number_format($total_with_vat,2); ?> </td>
                                    </tr>
                                     <tr> 
                                        <td style="text-align:right;  font-weight:bold;" colspan="5">Paid</td>
                                         <td style="text-align:right; font-weight:bold;"><?php include('currency.php');?> <?php echo number_format($PaidBalance,2);  ?></td>
                                    </tr>
                                     <tr> 
                                        <td style="text-align:right;  font-weight:bold;" colspan="5">Balance</td>
                                         <td style="text-align:right; font-weight:bold;"><?php include('currency.php');?> <?php echo number_format(($total_with_vat-$PaidBalance),2); ?> </td>
                                    </tr>
              
                                   </tfoot>


                                    </table>
<div class="clearfix" style="text-align:right">
                                                        
                                                            <p>
                                                             
                                                                <button type="button" data-toggle="modal" data-target="#myModal" class="btn blue-madison"><i class="fa fa-money"></i> Make a Payment</button>

                                                            </p>
                                                        </div> 
                                </div>
                         <!--end table1 -->
                     </div>
                     <div class="portlet-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th> Date </th>
                                                    <th> Paid via </th>
                                                    <th> Paid by </th>
                                                    <th> Amount </th>
                                                   
                                                </tr>
                                            </thead>
                                            <tbody>
                                                  <?php $data = getRecordes();
                                        foreach($data as $query_get_suppler_records) 
                                            {


                                                $query_paid_by = $db->getRow('SELECT * FROM users WHERE userid = ?',[$query_get_suppler_records['makeBy']]);
                                                 $query_paid_by = $query_paid_by['first_name']." ".$query_paid_by['last_name'];
                                            ?>
                                                <tr>
                                                    <td> <?php echo $query_get_suppler_records['amountDate']; ?> </td>
                                                    <td> <?php echo $query_get_suppler_records['method']; ?> </td>
                                                    <td> <?php echo $query_paid_by; ?> </td>
                                                    <td><?php include('currency.php');?>  <?php echo $query_get_suppler_records['amount']; ?> </td>
                                                    
                                                </tr>
                                                <?php } ?>
                                        
                                            </tbody>
                                        </table>
                                    </div>
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
        
        <!-- celander -->
        <script src="assets/global/plugins/celander/jquery-ui.js" type="text/javascript"></script>


         <!-- Auto Numaric Function -->
         <script src="assets/global/plugins/numaricFunction/autoNumeric.js" type="text/javascript"></script>
          <!-- Notification function -->
        <script src="assets/global/plugins/notification/jquery.bootstrap-growl.js"></script>
   

</script>

<!-- make payment -->

<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">ADD PAYMENTS</h4>
      </div>
      <form method="POST" id="frmAddamount">
      <div class="modal-body">

        <div class="row">
            <div class="col-md-12">
                  <div> 
                  <small>  Please fill in the information below otherwise you can't change or delete payments recorded according to payment update Rules. </small>
                  </div>  
                    <div class="row" style="margin-top:10px;">
  <div class="col-xs-6"><div class="form-group">
<label for="reference_no">Date</label> <input type="text" id="datepicker" class="form-control" name="date">
</div></div>
  <div class="col-xs-6"><div class="form-group">
<label for="reference_no">RefNo#</label> <input type="text"  class="form-control" name="refNo">
</div></div>
</div>
<div class="row" style="margin-top:10px;">
  <div class="col-xs-6"><div class="form-group">
<label for="reference_no">Payment Method</label>  <select name="payment_method" class="form-control" >
                    <option value="">select payment method </option>
                    <?php echo load_payment_method();?>
                </select>
</div></div>
  <div class="col-xs-6"><div class="form-group">
<label for="reference_no">Amount</label> <input type="text" class="form-control autoprice" name="amount">
</div></div>
</div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Add Payment</button>
      </div>
      <input type="hidden"  class="form-control" name="grnID" value="<?php echo $grn_h_id;?>">
  </form>
    </div>
  </div>
</div>

</body>
<script type="text/javascript">
$(document).ready(function()
{
 $(document).on('submit', '#frmAddamount', function()
 {
  
  var data = $(this).serialize();
  
  
  $.ajax({
  
 type : 'POST',
 url  : 'process/add-payment-process.php',
  data : data,
  success :  function(data)
       {
        $(function () {
                    setTimeout(function() {
                    $.bootstrapGrowl(data, { 
                        type: 'success',
                        align: 'right'
                    });

                }, 1000);
                
               clearInput();

            });

      
       }
  });
  return false;
 });
 
});

function clearInput() {
  $("#frmAddamount :input").each( function() {
     $(this).val('');

  });

 
}

</script>
<!-- datepicker -->
 <script>
  $(function() {
    $( "#datepicker" ).datepicker( {
                    changeMonth: true,
                    changeYear: true,
                    timeFormat: 'hh/mm',
                    showSecond: false,
                    dateFormat:'yy/mm/dd',
                    separator: ''
                });
  });
  </script>
  <!-- auto price set -->
<script type="text/javascript">
jQuery(function($) {
    $('.autoprice').autoNumeric('init');
});
</script>
</html>



