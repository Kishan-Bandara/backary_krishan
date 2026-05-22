<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);

session_start();
require_once 'include/database.php';
require_once 'get_url.php';
include('include/check_login.php');
include('include/time_zone.php');
function filter($var)
{

  return preg_replace('[0-9]',' ' , $var);
}
if($_SESSION['userlevel'] != 1)
{

   redirect('login.php');

}


?>
<?php

if(isset($_GET['receipt_id']))
{
    $get_receipt_id = $_GET['receipt_id'];
    $db = new Database();


    $query_get_receipt_id = $db->getRow('SELECT * FROM receipt_hedder WHERE receipt_h_id = ?',[$get_receipt_id]);

    $receipt_date = $query_get_receipt_id['receipt_h_date'];
    $receipt_refNo = $query_get_receipt_id['receipt_h_code'];
    $receipt_name = $query_get_receipt_id['receipt_h_client_name'];
    $receipt_name = $query_get_receipt_id['receipt_h_client_name'];
    $receipt_address = $query_get_receipt_id['receipt_h_client_address'];
    $receipt_email = $query_get_receipt_id['receipt_h_client_mail'];
    $receipt_tax = $query_get_receipt_id['receipt_h_tax'];
    $receipt_discount = $query_get_receipt_id['receipt_h_discount'];
    $receipt_tot = $query_get_receipt_id['receipt_h_total_amount'];
    $receipt_sub_total = $receipt_tot + $receipt_discount - $receipt_tax;
    $receupt_status = $query_get_receipt_id['receipt_h_status'];

    if($receupt_status == 1)
    {

      redirect('view-receipts.php');
    }
    else if($receupt_status == -2)
    {

      redirect('view-receipts.php');
    }

    #get receipt details

 //Database eken Table ekata Values daaganna Function eka
function getContent() {
    $db = new Database();
    $receipt_h_id = $_GET['receipt_id'];
    $query = $db->getRows('SELECT * FROM receipt_details WHERE receipt_h_id = ?',[$receipt_h_id]);
    return $query;
    $db->Disconnect();
}


}
else
{

  redirect('login.php');
}

function getpaymentFor()
{
$db = new Database();
$output = "";
$get_query  = $db->getRows('SELECT * FROM client_required_master');

$data = $get_query;

 foreach($data as $get_query) 
            {   
                $output .= '<option value="'.$get_query['id'].'">'.$get_query['name'].'</option>';

            }


return $output;
$db->Disconnect();
}


function paymentPeriod()
{
$db = new Database();
$output = "";
$get_pay_period_query  = $db->getRows('SELECT * FROM payment_time_period_master');

$data = $get_pay_period_query;

 foreach($data as $get_pay_period_query) 
            {   
                $output .= '<option value="'.$get_pay_period_query['id'].'">'.$get_pay_period_query['name'].'</option>';

            }


return $output;
$db->Disconnect();
}


function Bank()
{
$db = new Database();
$output = "";
$get_banks_query  = $db->getRows('SELECT * FROM bank_details_master');

$data = $get_banks_query;

 foreach($data as $get_banks_query) 
            {   
                $output .= '<option value="'.$get_banks_query['id'].'">'.$get_banks_query['name'].'</option>';

            }


return $output;
$db->Disconnect();
}



   function paymentType()
                    {
                        $get_receipt_id = $_GET['receipt_id'];
                        $db = new Database();
                        $get_real_receipt_id = $db->getRow('SELECT * FROM receipt_hedder WHERE receipt_h_id = ? ',[$get_receipt_id]);
                        $receipt_payment_type = $get_real_receipt_id['receipt_h_payment_type'];

                        if($receipt_payment_type)
                        {
                            
                               $output = '';  
      
      $query = $db->getRows('SELECT * FROM payment_types_master');
      $data = $query;


      $output = '<option value="">select payment type</option>';
        foreach($data as $query) 
            {   
                $sel = "";
                $id = $query['id']; 
                if($id == $receipt_payment_type)
                {

                    $sel="selected";
                }



                
                $output .= '<option '.$sel.' value="'.$query['id'].'">'.$query['type'].'</option>';

            }
            return $output;   


                        }


$db->Disconnect();
                    }
?>
<!DOCTYPE html>
<html lang="en">

<!-- Mirrored from tectonic.kaijuthemes.com/index-horizontal.html by HTTrack Website Copier/3.x [XR&CO'2010], Fri, 05 Aug 2016 05:42:12 GMT -->
<head>
    <meta charset="utf-8">
    <title>Create a receipt </title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-touch-fullscreen" content="yes">
    <meta name="description" content="">
    <meta name="author" content="">

 	<?php include('common/styles.php');?>
  <link type="text/css" href="assets/plugins/gridforms/gridforms/gridforms.css" rel="stylesheet">                   <!-- Gridforms -->
    <link type="text/css" href="assets/css/celander_jquery-ui.css" rel="stylesheet">                   <!-- celender -->
    <link type="text/css" href="assets/plugins/jquery-notific8/jquery.notific8.css" rel="stylesheet">   <!-- Notific8 Plugin -->


      <!-- The following CSS are included as plugins and can be removed if unused-->
    
<!-- <link type="text/css" href="assets/plugins/jsgrid/css/demos.css" rel="stylesheet">
<link type="text/css" href="assets/plugins/jsgrid/css/jsgrid.css" rel="stylesheet">
<link type="text/css" href="assets/plugins/jsgrid/css/theme.css" rel="stylesheet"> -->


</head>
<body class="infobar-offcanvas layout-horizontal">
  


<?php include('common/hedder.php');?>
  <div id="wrapper">
    <div id="layout-static">
      <div class="static-leftbar-wrapper leftbar-default">
        <div class="static-leftbar">
          <div class="leftbar">

	<?php include('common/manu.php'); ?>
	</div>
        </div>
      </div>
      <div class="static-content-wrapper">
        <div class="static-content">
          <div class="page-content">

            <div class="container-fluid" style="float: none; margin-left: auto;margin-right: auto; width: 70%;">
                <!--start Grid -->
                  <div class="panel panel-default alt">
  <div class="panel-heading">
    <h2>Make a Receipt</h2>
   
  </div>
  <div class="panel-body">
    <div id="faLoadingContainer">
      <form class="grid-form" method="POST" id="frn-receipt">
        <input type="hidden" name="receipt_id" value="<?php echo $get_receipt_id; ?>">
      <fieldset>
        <legend>Receipt Details</legend>
        <div data-row-span="2">
          <div data-field-span="1" style="height: 64px;">
            <label>Date Status</label>
          <input type="text" class="form-control" id="datepicker" name="datepicker" value="<?php echo $receipt_date; ?>">
          </div>
          <div data-field-span="1" style="height: 64px;">
            <label>Receipt Number</label>
            <input type="text" value="<?php echo $receipt_refNo; ?>" name="refNo" id="refNo" readonly>
          </div>
        </div>

        <div data-row-span="1">
          <div data-field-span="1" class="" style="height: 63px;">
             <label>Payment Type</label>
           <select style="width:400px;" id="drpType" name="drpType">
                <?php echo paymentType(); ?>
            
            </select>
          
          </div>
        </div>
      </fieldset>

      <br><br>


            <fieldset>
        <legend> Personal Details (Receiptholder details)</legend>

        <div data-row-span="4">
          <div data-field-span="1" style="height: 64px;">
            <label>Title</label>
            <select style="width:auto; font-size:12px;" id="drptitle" name="drptitle">

            <option value="1" title="Mr">Mr.</option>
              <option value="2" title="Mrs">Mrs.</option>
              <option value="3" title="Ms">Ms.</option>
              <option value="4" title="Dr">Dr.</option>
              <option value="5" title="Rev">Rev.</option>
            </select>
          </div>
          <div data-field-span="3" style="height: 64px;">
            <label>Full Name</label>
            <input type="text" name="client_name" id="client_name" value="<?php echo $receipt_name; ?>">
          </div>
        </div>

        <div data-row-span="4">
          <div data-field-span="2" data-field-error="Please enter a valid email address" style="height:auto;">
            <label>RESIDENCE Address</label>
            <textarea name="address" id="address"><?php echo $receipt_address; ?></textarea>
            
          </div>
          
          <div data-field-span="2" style="height: 63px;">
            <label>E-mail</label>
           <input type="text" name="email" id="email" value="<?php echo $receipt_email; ?>">
          </div>
        </div>

        <br>

      </fieldset>
      <br>
<fieldset>
       

     <div class="portlet-body flip-scroll">
                                    <table class="table table-bordered table-striped table-condensed flip-content" style="width:100%; float:right;">
                                        <thead class="flip-content">
                                            <tr>
                                               
                                                <th class="numeric"> Description </th>
                                                <th class="numeric" style="width:20%"> Amount </th>
                                              
                                            </tr>
                                        </thead>
                                        <tbody>
                                           <?php $data = getContent();
                                           $i == 0;
                                        foreach($data as $query) {
                                          $receipt_detail_id = $query['receipt_d_id']; 
                                          $i++;
                                           ?> 
                         
                                            <tr>
                                               
                                                <td class="numeric"><input type="text" style="font-size:12px; font-weight:bold;" name="item_description<?php echo $i;?>" value="<?php echo $query['receipt_d_description']; ?>"> </td>
                                                <td class="numeric"><div class="input-group input-medium">
                                                    <span class="input-group-addon">
                                                        <i class=" "><b>Rs</b></i>
                                                    </span>
                                                    <input type="text" style="font-size:12px; font-weight:bold;" class="form-control autoprice"  id="feild<?php echo $i;?>" name ="item_amount<?php echo $i;?>" placeholder="0.00" value="<?php echo $query['receipt_d_amount']; ?>"> </div></td>
                                                    <input type="hidden" name="receipt_detail_row_id<?php echo $i;?>" value="<?php echo $receipt_detail_id; ?>">
                                                
                                            </tr>
                                            <?php } ?>

                                                                                         <tr>
                                           
                                        </tr></tbody>
                                   <tfoot>
                                    <tr> 
                                        <td style="text-align:right;  font-weight:bold;" colspan="1">Sub Total</td>
                                         <td style="text-align:right; font-weight:bold;"><div class="input-group input-medium">
                                                    <span class="input-group-addon">
                                                        <i class=" "><b>Rs</b></i>
                                                    </span>
                                                    <input type="text" style="font-size:12px; font-weight:bold;" class="form-control other" readonly  name="sub" id="sub"  value="<?php echo $receipt_sub_total;?>"> </div></td>
                                    </tr>
                                     <tr> 
                                        <td style="text-align:right;  font-weight:bold;" colspan="1">CC-charges+</td>
                                        <input type="hidden" name="txtTax" id="txtTax" value="">
                                         <td style="text-align:right; font-weight:bold;"><div class="input-group input-medium">
                                                    <span class="input-group-addon">
                                                        <i class="fa "><b>+</b></i>
                                                    </span>
                                                   
                                                    <input type="text" style="font-size:12px; font-weight:bold;" class="form-control" name="txtTaxval" id="txtTaxval" readonly value="<?php echo $receipt_tax;?>"> </div></td>
                                    </tr>

                                      <tr> 
                                        <td style="text-align:right;  font-weight:bold;" colspan="1">Discount</td>
                                         <td style="text-align:right; font-weight:bold;"><div class="input-group input-medium">
                                                    <span class="input-group-addon">
                                                        <i class="fa "><b>Rs</b></i>
                                                    </span>
                                                    <input type="text" style="font-size:12px; font-weight:bold;" class="form-control other" name="txtDiscount" id="txtDiscount"  value="<?php echo $receipt_discount;?>"> </div></td>
                                    </tr>
                                     <tr> 
                                        <td style="text-align:right;  font-weight:bold;" colspan="1">Total Amount</td>
                                         <td style="text-align:right; font-weight:bold;"><div class="input-group input-medium">
                                                    <span class="input-group-addon">
                                                        <i class="fa "><b>Rs</b></i>
                                                    </span>
                                                    <input type="text" style="font-size:12px; font-weight:bold;" class="form-control other"  readonly name="Total" id="Total" value="<?php echo $receipt_tot;?>"> </div></td>
                                    </tr>

              
                                   </tfoot>


                                    </table>

                                </div>
       </fieldset>
     
              <hr>
              <div data-row-span="2">
          <div data-field-span="1" style="height: 68px;">
            <label>Tour id</label>
          <input type="text" value="" name="txtClientid" id="txtClientid">
          </div>
      
        </div>
        <div data-row-span="4">
          <div data-field-span="1" style="height: 63px;">
            <label>Payment For</label>
            <select style="width:200px;" id="drpPaymentMode" name="drpPaymentMode">
                <?php echo getpaymentFor(); ?>            
            </select>
          </div>
          <div data-field-span="1" style="height: 63px;">
            <label>Payment Peroid</label>
            <select style="width:200px;" id="drpPaymentperiod" name="drpPaymentperiod">
                <?php echo paymentPeriod(); ?>
            </select>
          </div>
          <div data-field-span="1" style="height: 63px;">
            <label>Banking Details</label>
            <select style="width:200px;" id="drpBanks" name="drpBanks">
               <?php echo Bank(); ?>            
            </select>
          </div>
       
        </div>
         <div class="clearfix pt20">
        <div class="pull-right">
           <button class="btn-primary btn btn-approve" type="submit"  name="btn-save" id="btn-save" value="1">Approve</button>
           <button class="btn-primary btn btn-reject" type="submit" name="btn-reject" id="btn-reject" value="-2">Reject</button>
         <!--  <a data-toggle="modal" href="#myModal" class="btn btn-primary btn-lg btnviewreceipt">Go Receipt</a> -->
          <button class="btn-default btn">Cancel</button>
        </div>
      </div>
    </form>
  </div>

  </div>

</div>
                <!-- end grid -->

            </div> <!-- .container-fluid -->
          </div> <!-- #page-content -->
        </div>
       <?php include('common/footer.php');?>
      </div>
    </div>
  </div>

     <!-- Load site level scripts -->
<script type="text/javascript" src="assets/js/jquery-1.10.2.min.js"></script>               <!-- Load jQuery -->
<script type="text/javascript" src="assets/js/jqueryui-1.9.2.min.js"></script>              <!-- Load jQueryUI -->

<script type="text/javascript" src="assets/js/bootstrap.min.js"></script>                 <!-- Load Bootstrap -->

<script type="text/javascript" src="assets/plugins/easypiechart/jquery.easypiechart.js"></script>     <!-- EasyPieChart-->
<script type="text/javascript" src="assets/plugins/sparklines/jquery.sparklines.min.js"></script>     <!-- Sparkline -->
<script type="text/javascript" src="assets/plugins/jstree/dist/jstree.min.js"></script>         <!-- jsTree -->

<script type="text/javascript" src="assets/plugins/codeprettifier/prettify.js"></script>        <!-- Code Prettifier  -->
<script type="text/javascript" src="assets/plugins/bootstrap-switch/bootstrap-switch.js"></script>    <!-- Swith/Toggle Button -->

<script type="text/javascript" src="assets/plugins/bootstrap-tabdrop/js/bootstrap-tabdrop.js"></script>  <!-- Bootstrap Tabdrop -->

<script type="text/javascript" src="assets/plugins/iCheck/icheck.min.js"></script>              <!-- iCheck -->

<script type="text/javascript" src="assets/js/enquire.min.js"></script>                   <!-- Enquire for Responsiveness -->

<script type="text/javascript" src="assets/plugins/bootbox/bootbox.js"></script>              <!-- Bootbox -->

<script type="text/javascript" src="assets/plugins/nanoScroller/js/jquery.nanoscroller.min.js"></script> <!-- nano scroller -->

<script type="text/javascript" src="assets/plugins/jquery-mousewheel/jquery.mousewheel.min.js"></script>  <!-- Mousewheel support needed for jScrollPane -->

<script type="text/javascript" src="assets/js/application.js"></script>
<script type="text/javascript" src="assets/demo/demo.js"></script>
<script type="text/javascript" src="assets/demo/demo-switcher.js"></script>

<script type="text/javascript" src="assets/plugins/pulsate/jQuery.pulsate.min.js"></script>
<script type="text/javascript" src="assets/plugins/localisation/js/jquery.localize.min.js"></script>
<script type="text/javascript" src="assets/plugins/localisation/js/demo-localisation.js"></script>
<script type="text/javascript" src="assets/demo/demo-jqueryui.js"></script>

<!-- End loading site level scripts -->

<!-- Load page level scripts-->
    
<script type="text/javascript" src="assets/plugins/gridforms/gridforms/gridforms.js"></script>                  <!-- Gridforms -->
<script type="text/javascript" src="assets/plugins/jquery-notific8/jquery.notific8.js"></script>    <!-- Notific8 Plugin -->


<script type="text/javascript" src="assets/plugins/celender/jquery-ui.js"></script>             <!-- celender Picker -->

<!-- Auto Numaric Function -->
         <script src="assets/plugins/autoNumaric/autoNumeric.js" type="text/javascript"></script>



</body>

</html>
<!-- Payment Type change funtion -->
<script>
$(document).ready(function(){
    $('#drpType').change(function(){
         
      getTax();

            

       
    });
 getTax();

});

function getTax()
{


      var payType = $('#drpType').val();
   
            $.ajax({
                url: 'process/receipt-tax-data-process.php',
                type: 'POST',
                data: {payType: payType} ,
                
                success: function(result) {
                    document.getElementById("txtTax").value = result;
                    sum();
                }

            });
}
</script>

<!-- get sub Total -->
<script>
  function sum() {
    var tot = 0.00;
$('.autoprice').each(function(){
    tot += parseFloat($(this).val().split(',').join('').trim() == '' ? 0.00 : $(this).val().split(',').join('').trim());
});
           document.getElementById('sub').value = tot.toFixed(2);
      
       total();

   }


   $(document).ready(function(){
    $('input.autoprice').change(function(){

      sum();

    });

});


   $(document).ready(function(){
    $('#txtDiscount').change(function(){

     total();

    });

});




      function total() {

        var subTotal  = document.getElementById('sub').value.split(',').join('').trim();
        var tax = document.getElementById('txtTax').value.split(',').join('').trim();
        var discount = document.getElementById('txtDiscount').value.split(',').join('').trim();


        if (subTotal == "")
            subTotal = 0.00;
        if (tax == "")
            tax = 0.00;
        if(discount == "")
            discount = 0.00;

        var taxVal = parseFloat(subTotal) * parseFloat(tax)/100;

        var total = parseFloat(subTotal) + parseFloat(taxVal);
        var total = total - parseFloat(discount);
        
        document.getElementById('txtTaxval').value = taxVal;

         if (!isNaN(total)) {
               document.getElementById('Total').value = total;
           }

           $('.other').each(function() {
    $(this).focus();
    $(this).focusout();
});
   }

</script>

<!-- datepicker -->
 <script>
  $(function() {
    $( "#datepicker" ).datepicker( {
                    changeMonth: true,
                    changeYear: true,
                    timeFormat: 'hh-mm-sec',
                    showSecond: false,
                    dateFormat:'yy-mm-dd',
                    separator: ''
                });
  });
  </script>


<!-- Receipt on save -->

<script>

   $(document).on('click', '.btn-approve', function() {

      $(document).on('submit','#frn-receipt', function()
    {

           var data = $(this).serialize();

           $.ajax({
           type : 'POST',
           url  : 'process/save-review-receipt-data-process.php',
           data : data,
           beforeSend: function()
           { 
            $("#btn-save").html('Processing ...');

           },
           success :  function(response) {
             location.reload();
            alert(response);

           }
            });
    return false;



    });

    });

</script>

<script>

   $(document).on('click', '.btn-reject', function() {

      $(document).on('submit','#frn-receipt', function()
    {

           var data = $(this).serialize();

           $.ajax({
           type : 'POST',
           url  : 'process/riject-review-receipt-data-process.php',
           data : data,
           beforeSend: function()
           { 
            $("#btn-reject").html('Processing ...');

           },
           success :  function(response) {
            location.reload();
            alert('reject');
            $("#btn-reject").html('Submit');

           }
            });
    return false;



    });

    });

</script>

 <!-- auto price set -->
<script type="text/javascript">
jQuery(function($) {
    $('.autoprice').autoNumeric('init');
    $('.other').autoNumeric('init');
});


</script>



