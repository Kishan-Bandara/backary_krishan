<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('include/database.php');
include('include/check_login.php');
function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}




//getting value into the Textboxes

$getCustomerID = filter($_GET['customerID']);

$message = "";
$css = "";

//new customer update status 


//checking customer id fake or not
$db = new Database();
    $getrealcustomerid= $db->getRow('SELECT * FROM customer WHERE customer_id = ? ',[$getCustomerID]);
    $customer_real_id = $getrealcustomerid['customer_id'];

if($getCustomerID > 0 && $getCustomerID == $customer_real_id)
{
    $db = new Database();
    $getcustomerdetails = $db->getRow('SELECT * FROM customer WHERE customer_id = ? ',[$getCustomerID]);



$customer_id = $getcustomerdetails['customer_id'];
$customer_name = $getcustomerdetails['customer_name'];
$customer_number = $getcustomerdetails['customer_tell'];
$customer_address = $getcustomerdetails['customer_address'];
$customer_note = $getcustomerdetails['customer_note'];
$customer_discount = $getcustomerdetails['customer_discount'];
$customer_outstanding = $getcustomerdetails['customer_outstanding_balance'];
$home_city = "";


if($getcustomerdetails['new_customer'] == 0){
    
    $status_update_query = $db->updateRow('UPDATE customer SET new_customer = 1 WHERE customer_id = ?',[$getCustomerID]);
    
}


   #home address
        
        try {

            $query_for_home = $db->getRow('SELECT *  FROM shipping_address WHERE fk_customer_id = ? AND fk_delivery_method = 1',[$customer_id]);

            $home_name = $query_for_home['name'];
            $home_city_id = $query_for_home['fk_city'];
            $home_address = $query_for_home['address'];
            $home_phone = $query_for_home['contact_no'];
            

            if($home_city_id){

                $query_home_city = $db->getRow('SELECT * FROM city_master WHERE id = ?',[$home_city_id]);
                $home_city = $query_home_city['city'];

            }
        } catch (Exception $e) {

            echo $e;
            
        }

        #office address
        
        try {

            $query_for_office = $db->getRow('SELECT * FROM shipping_address WHERE fk_customer_id = ? AND fk_delivery_method = 2',[$customer_id]);

            $office_name = $query_for_office['name'];
            $office_city_id = $query_for_office['fk_city'];
            $office_address = $query_for_office['address'];
            $office_phone = $query_for_office['contact_no'];
            

            if($office_city_id){

                $query_office_city = $db->getRow('SELECT * FROM city_master WHERE id = ?',[$office_city_id]);
                $office_city = $query_office_city['city'];

            }else{

                $office_city = "";
            }
        } catch (Exception $e) {
            
        }

}
else
{

header('location:index.php');
exit();

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
        <title>Customer</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>
       </head>
    <!-- END HEAD -->
<!-- textboxes filter only numbers  -->
  <SCRIPT language=Javascript>
       <!--
       function isNumberKey(evt)
       {
          var charCode = (evt.which) ? evt.which : evt.keyCode;
          if (charCode != 46 && charCode > 31 
            && (charCode < 48 || charCode > 57))
             return false;

          return true;
       }
       //-->
    </SCRIPT>
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
                                <a href="index.php">Home</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <a href="#">Customer</a>
                               
                            </li>
                         
                        </ul>
                      
                    </div>
                    <!-- END PAGE BAR -->
                    <!-- BEGIN PAGE TITLE-->
                    <div class="alert <?php echo $MessageClass; ?> alert-dismissable">
                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                                        <?php echo $CompanyMessage; ?>
                                    </div>
                    <!-- END PAGE TITLE-->
                    <!-- END PAGE HEADER-->
                  

                    <div class="portlet-body">
                                <div class="tabbable-custom ">
                                        <ul class="nav nav-tabs ">
                                            <li class="active">
                                                <a href="#tab_5_1" data-toggle="tab" aria-expanded="true">Customer Details </a>
                                            </li>
                                            <li class="">
                                                <a href="#tab_5_2" data-toggle="tab" aria-expanded="false">Customer Sales </a>
                                            </li>
                                            
                                        </ul>
                                        <div class="tab-content">
                                            <div class="tab-pane active" id="tab_5_1">
                                                                                                                          
                                                       <div class="row">
                        <div class="col-md-12">
              <div class="portlet box green">
                                <div class="portlet-title">
                                    <div class="caption">
                                        Personal Details </div>
                                  
                                </div>
                                <div class="portlet-body flip-scroll">
                                    <table class="table table-bordered table-striped table-condensed flip-content">
                                       
                                        <tbody>
                                            <tr>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> Customer Name </td>
                                                <td style="height:50px; line-height:30px;font-size:16px;"><?php echo $customer_name; ?> </td>
                                      
                                            </tr>
                                                  <tr>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> Customer Email </td>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> <?php echo $getcustomerdetails['customer_email']; ?> </td>
                                      
                                            </tr>
                                                  <tr>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> Customer Nic </td>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> <?php echo $getcustomerdetails['customer_nic']; ?> </td>
                                      
                                            </tr>
                                                  <tr>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> Mobile  </td>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> <?php echo $getcustomerdetails['customer_mobile']; ?> </td>
                                      
                                            </tr>
                                                  <tr>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> Landline </td>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> <?php echo $getcustomerdetails['customer_tell']; ?> </td>
                                      
                                            </tr>
                                               <tr>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> Customer Note </td>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> <?php echo $getcustomerdetails['customer_note']; ?> </td>
                                      
                                            </tr>
                                        
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        
                    </div>
                  
                </div>
                <!-- END CONTENT BODY -->
                <div class="row">
                             <div class="col-md-6">
              <div class="portlet box green">
                                <div class="portlet-title">
                                    <div class="caption">
                                       Home Address </div>
                                   
                                </div>
                                <div class="portlet-body flip-scroll">
                                    <table class="table table-bordered table-striped table-condensed flip-content">
                              
                                        <tbody>
                                            <tr>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> Contact Name </td>
                                                <td style="height:50px; line-height:30px;font-size:16px;"><?php echo $home_name; ?> </td>
                                      
                                            </tr>
                                                  <tr>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> Contact Number </td>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> <?php echo $home_phone; ?> </td>
                                      
                                            </tr>
                                                  <tr>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> Home Address </td>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> <?php echo $home_address ?> </td>
                                      
                                            </tr>
                                                  <tr>
                                                <td style="height:50px; line-height:30px;font-size:16px;">  City  </td>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> <?php if($home_city){ echo $home_city;} ?> </td>
                                      
                                           
                                        
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        
                    </div>


                             <div class="col-md-6">
              <div class="portlet box green">
                                <div class="portlet-title">
                                    <div class="caption">
                                       Office Address</div>
                                   
                                </div>
                                <div class="portlet-body flip-scroll">
                                    <table class="table table-bordered table-striped table-condensed flip-content">
                              
                                        <tbody>
                                            <tr>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> Office Name </td>
                                                <td style="height:50px; line-height:30px;font-size:16px;"><?php echo $office_name; ?> </td>
                                      
                                            </tr>
                                                  <tr>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> Office Number </td>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> <?php echo $office_phone; ?> </td>
                                      
                                            </tr>
                                                  <tr>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> Office Address </td>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> <?php echo $office_address ?> </td>
                                      
                                            </tr>
                                                  <tr>
                                                <td style="height:50px; line-height:30px;font-size:16px;">  City  </td>
                                                <td style="height:50px; line-height:30px;font-size:16px;"> <?php echo $office_city; ?> </td>
                                      
                                           
                                        
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        
                    </div>
                 </div>




                                            </div>
                                            <div class="tab-pane" id="tab_5_2">
                                                <?php 
                                                  function getContent() {
                                                    $getCustomerID = filter($_GET['customerID']);
                                                        $db = new Database();
                                                        $query_invoice_h = $db->getRows('SELECT * FROM invoice_hedder WHERE  invoice_h_customer_id = ? ORDER BY invoice_h_id DESC',[$getCustomerID]);
                                                        return $query_invoice_h;
                                                    }

                                                ?>
                                                      <div class="portlet-body">
                                    <table class="table table-striped table-bordered table-hover dt-responsive" width="100%" id="sample_2">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th class="all">Invoice Date</th>
                                                <th class="all">Invoice No.</th>
                                                <th class="all">Customer Name</th>
                                                <th class="all">Grand Total</th>
                                                <th class="all">Payment Status</th>
                                                <th class="all">Action</th>
                                              
                                                
                                                
                                            
                                            </tr>
                                        </thead>
                                        <tbody>
                                              <?php $data = getContent();
                                        foreach($data as $query_invoice_h)
                                         { 
                                            $invoice_h_id = $query_invoice_h['invoice_h_id'];
                                            $invoice_code = $query_invoice_h['invoice_h_code'];
                                            $customer_id = $query_invoice_h['invoice_h_customer_id'];
                                            $query_invoice_h_customer = $db->getRow('SELECT * FROM customer WHERE customer_id = ?',[$customer_id]);
                                            $query_invoice_amount = $db->getRow('SELECT *  FROM invoice_hedder WHERE invoice_h_id = ?',[$invoice_h_id]);
                                            $gross_value = $query_invoice_amount['invoice_h_gross_value'];
                                            $invoice_status = $query_invoice_h['invoice_h_status'];
                                            $query_customer_amount = $db->getRow('SELECT SUM(amount) as customer_amount FROM customer_balance WHERE invoice_h_id = ?',[$invoice_h_id]);
                                            $amount = $query_customer_amount['customer_amount'];
                                            
                                           if($amount)
                                                {

                                                    $amount = $amount;
                                                }
                                                else
                                                {
                                                    $amount = 0;

                                                }

                                                $style = "";
                                                $status = "";
                                            if($gross_value == $amount || $amount > $gross_value )
                                            {

                                                $style = "lbl_Payment_status_paid";
                                                $status = "Paid";

                                            }
                                            elseif ($gross_value > $amount && $amount != 0) {
                                                
                                                $style = "lbl_Payment_status_partial";
                                                $status = "Partial";
                                            }
                                            elseif ($amount == 0) {
                                                 $style = "lbl_Payment_status_pending";
                                                 $status = "Pending";
                                            }
                                            else
                                            {

                                                 $style = "lbl_Payment_status_pending";
                                                 $status = "Error";
                                            }

                                                #order Status

                                                if($invoice_status == 1)
                                                {
                                                    $order_status = "Acepct";

                                                }
                                                elseif($invoice_status == 0)
                                                {
                                                    $order_status = "Pending";

                                                }
                                                elseif($invoice_status == -1)
                                                {

                                                    $order_status = "Canceled";
                                                }
                                                else
                                                {
                                                    $order_status = "Something Wrong";

                                                }



                                         ?> 
                                             <tr>
                                                <th></th>

                                                <td><?php echo $query_invoice_h['invoice_h_datetime'];?></td>
                                                <td><?php echo $invoice_code; ?></td>
                                                <td><?php echo $query_invoice_h_customer['customer_name']; ?></td>
                                                <td><?php echo "LKR ".number_format($gross_value,2); ?></td>
                                                <td><span class="<?php echo $style; ?>"><?php echo "$status";?> </span></td>
                                                <td><a href="invoice.php?id=526"><div class="btn-group btn-group-xs btn-group-solid"><button type="button" class="btn dark btn-outline sbold uppercase">invoice Note</button></div></a></td>
                                                
                                                
                                            </tr>
                                        
                                           
                                            <?php }
                                            ?>
                                            
                                        </tbody>                                   
                                    </table>
                                </div>
                                            </div>
                                           
                                        </div>
                                    </div>

                             </div>









             
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

</body>

</html>



