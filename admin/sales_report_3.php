<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');

#currency
$getcurrency = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1 ',["Y"]);
$currency = $getcurrency['currency'];


function getContent() {

if(!empty($_GET['daterange'])){


  $search_name = $_GET['daterange'];

$orderdate = explode('-',$search_name);
$start_date = $orderdate[0];
$end_date   = $orderdate[1];


$start_date_order = explode('/',$start_date);
$end_date_order = explode('/',$end_date);

$start_month = $string = str_replace(' ', '', $start_date_order[0]);
$start_day = $string = str_replace(' ', '', $start_date_order[1]);
$start_year = $string = str_replace(' ', '', $start_date_order[2]);

$end_month = $string = str_replace(' ', '', $end_date_order[0]);
$end_day = $string = str_replace(' ', '', $end_date_order[1]);
$end_year = $string = str_replace(' ', '', $end_date_order[2]);

$real_start_date = $start_year."-".$start_month."-".$start_day;
$real_end_date = $end_year."-".$end_month."-".$end_day;


/*$real_start_date = "2016-07-01";
$real_end_date = "2016-12-13";*/


   $db = new Database();

   $query = $db->getRows('SELECT invoice_hedder.invoice_h_code as code, invoice_hedder.invoice_h_gross_value ,invoice_hedder.invoice_h_date ,invoice_hedder.invoice_h_customer_id ,invoice_hedder.invoice_h_coupon_rate , invoice_hedder.invoice_h_net_value , invoice_hedder.invoice_h_coupon_rate , invoice_hedder.invoice_h_coupon_type ,
   invoice_details.invoice_d_item_id , sum(invoice_details.invoice_d_qty) as  invoice_d_qty, invoice_details.invoice_d_item_price 
   FROM invoice_details  INNER JOIN invoice_hedder
   ON invoice_hedder.invoice_h_id = invoice_details.invoice_h_id 
   WHERE invoice_hedder.invoice_h_status = 1 AND invoice_hedder.invoice_h_date BETWEEN CAST(? AS DATE) AND CAST(? AS DATE) AND invoice_h_location = ?
   GROUP BY invoice_hedder.invoice_h_id',[$real_start_date,$real_end_date,$_SESSION['location']]);

  return $query;
  }
}
      
   

#currency
$getcurrency = $db->getRow('SELECT * FROM currency WHERE activated = ? LIMIT 1 ',["Y"]);
$currency = $getcurrency['currency'];

?>
<script type="text/javascript">
/*  function downloadCSV(csv, filename) {
    var csvFile;
    var downloadLink;

    // CSV file
    csvFile = new Blob([csv], {type: "text/csv"});

    // Download link
    downloadLink = document.createElement("a");

    // File name
    downloadLink.download = filename;

    // Create a link to the file
    downloadLink.href = window.URL.createObjectURL(csvFile);

    // Hide download link
    downloadLink.style.display = "none";

    // Add the link to DOM
    document.body.appendChild(downloadLink);

    // Click download link
    downloadLink.click();
}

function exportTableToCSV(filename) {
    var csv = [];
    var rows = document.querySelectorAll("table tr");
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");
        
        for (var j = 0; j < cols.length; j++) 
            row.push(cols[j].innerText);
        
        csv.push(row.join(","));        
    }

    // Download CSV file
    downloadCSV(csv.join("\n"), filename);
}

*/
</script>
<!DOCTYPE html>

<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
    <!--<![endif]-->
    <!-- BEGIN HEAD -->


<head>
        <meta charset="utf-8" />
        <title>Sales Report</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>
        <link href="assets/global/plugins/bootstrap-daterangepicker/daterangepicker.min.css" rel="stylesheet" type="text/css" />
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
                                <a href="#">Report</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <span>Sales Report</span>
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
                  
                    <div class="row">
                        <div class="col-md-12">
                       
                
                  
                  <div class="col-md-3" style=" padding: 15px;" >
                    <div class="input-group search-page" style="background: #f7f9fa; margin: -16px 0px 8px; padding: 16px; width: 100%;border-radius: 9px;">
                        <form method="GET" name="frn-src" id="frn-src" action="">
              <div class="input-group">
                <span class="input-group-addon"><i class="ion-calendar"></i></span>
                <input type="text" class="form-control" id="daterangepicker1" name="daterange">
                  <span class="input-group-btn">
                        <button type="submit" class="btn btn-primary" name="sub" id="sub"><i class="fa fa-search"></i></button>
                    </span>
              </div>
                                                </form>
                 </div>

                      




                 
                  </div>


                    <div class="col-md-3" style=" padding: 15px; width:200px; height:100px;margin-right:5px;   border: 1px solid #e0e0e0; background-color: #f7f9fa; " >
                      <h3> Net Value</h3>
                      <div id="netamount" class="netamount"> </div>
                      
                  </div>
               
                   <div class="col-md-3" style=" padding: 15px; width:200px; height:100px;margin-right:5px;     border: 1px solid #e0e0e0; background-color: #f7f9fa;" >
                  
                          <h3> Gross Value</h3>
                      <div id="grossamount" class="grossamount"> </div>




                 
                  </div>
               
                  <div class="col-md-3" style=" padding: 15px; width:200px; height:100px;     border: 1px solid #e0e0e0; background-color: #f7f9fa;"  >
                 
                   <h3> Discount Value</h3>
                      <div id="discountamount"> </div>
                  </div>

                
                <div class="row">
  <div class="col-md-12">
    <div class="panel panel-default alt with-footer">
      <div class="panel-heading">
        <h3>Sales Reports</h3> <button class="btn btn-primary" onclick="exportTableToCSV('Profit.csv')">CSV Output</button>
        <div class="panel-ctrls">
        <div id="example_filter" class="dataTables_filter pull-right"><label class="panel-ctrls-center"></label></div><i class="separator"></i><div class="dataTables_length pull-left" id="example_length"><a class="btn btn-social btn-instagram" id="url_selector"><i class="ion-printer"></i></a><label class="panel-ctrls-center"></label></div></div>
      </div>
      <div class="panel-body p-n">
        <div id="example_wrapper" class="dataTables_wrapper form-inline no-footer"><div class="row"><div class="col-sm-6"></div><div class="col-sm-6"></div></div>
        <div class="table-responsive" id="">
           

         

                                         <table class="table table-striped table-bordered table-hover table-checkable" id="myDatatable">
                                            <thead>
                                                <tr role="row" class="heading">
                                                <th width="2%">
                                                        <input type="checkbox" class="group-checkable"> </th>
                                                <th class="all">Invoice No</th>
                                                <th class="all">Customer Name</th>
                                                <th class="all">Date</th>
                                                <th class="all">Net Amount</th>
                                                <th class="all">Discount</th>

                                                <th class="all">Total Amount</th>
                                              
                                                </tr>

                                                </thead>

                                        <tbody>
                                            <?php 
                                            if(!empty($_GET['daterange'])){

                                            $data = getContent();
                                        foreach($data as $query) 
                                            { 
                                               
         $item_id = $query['invoice_d_item_id'];
         $customer_id = $query['invoice_h_customer_id'];
         $invoice_code = $query['code'];
         $invoice_net_value = $query['invoice_h_net_value'];
         $invoice_h_discount = $query['invoice_h_coupon_rate'];
         $invoice_h_discount_type = $query['invoice_h_coupon_type'];
         $invoice_gross_value = $query['invoice_h_gross_value'];
         $invoice_date = $query['invoice_h_date'];
         
         if($invoice_h_discount_type == "PCT"){

          $dicount_rate =  $invoice_h_discount." %";

         }else if($invoice_h_discount_type == "SUM"){

          $dicount_rate =  "- ".$currency." ".$invoice_h_discount;
         }else{

          $dicount_rate = "0.00";

         }

         $query_customer = $db->getRow('SELECT * FROM  customer WHERE customer_id = ?',[$customer_id]);
         $query_item = $db->getRow('SELECT * FROM  item_master WHERE item_id = ?',[$item_id]);
          
         $item_name = $query_item['item_name'];
         $item_price = $query_item['item_normal_selling_price'];
         $selling_price = $query['invoice_d_item_price'];
         $sold_qty = $query['invoice_d_qty'];
         
         $item_code = $query_item['item_code'];
         $item_sub_total = $sold_qty *  $item_price;
         $customer_name = $query_customer['customer_name'];

                                                ?> 

                                                 <tr role="row" class="filter custom-tr">
                                                    <td> </td>
                                                    <td class="vertical-td"> 
                                                       <?php echo $invoice_code; ?> </td>
                                                    <td class="vertical-td">
                                                        
                                                       <?php echo $customer_name; ?>
                                                    </td>
                                                    <td class="vertical-td">
                                                        
                                                       <?php echo $invoice_date; ?>
                                                    </td>
                                                    <td class="vertical-td">
                                                        <?php echo $currency." <span class='net_value'>".$invoice_net_value."</span>"; ?> </span> </td>
                                                    

                                                       <td class="vertical-td">
                                                          
                                                          <span id="item_sub_total"> <?php echo $dicount_rate; ?></span>

                                                        </td>
                                                    <td>
                                                       
                                                        <span id="unit_qty"><?php echo $currency." <span class='gross_value'>".$invoice_gross_value."</span>"; ?> </span>
                                                      </td>
                                                  


                                                      

                                                <?php } }?>
                                           
                                          
                                        </tbody>
                                    </table>



                </div>
                
      </div>
      </div>
    </div>
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


     <!-- BEGIN PAGE LEVEL PLUGINS -->
        <script src="assets/global/plugins/moment.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/bootstrap-daterangepicker/daterangepicker.min.js" type="text/javascript"></script>
       
        <!-- END PAGE LEVEL PLUGINS -->
    
     <!-- BEGIN PAGE LEVEL SCRIPTS -->
        <script src="assets/pages/scripts/demo-pickers.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL SCRIPTS -->
</body>

</html>
<!-- search query goto the page  -->
<script>


   



var net_sum = 0;
$('.net_value').each(function(){
    net_sum += parseFloat($(this).text()); 
});




var gross_sum = 0;
$('.gross_value').each(function(){
    gross_sum += parseFloat($(this).text());  
});


 


    document.getElementById('netamount').innerHTML= "LKR "+net_sum.toFixed(2);
    document.getElementById('grossamount').innerHTML= "LKR "+gross_sum.toFixed(2);
document.getElementById('discountamount').innerHTML= "LKR "+(gross_sum-net_sum).toFixed(2);  

</script>



