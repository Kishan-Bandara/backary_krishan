<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('time_zone.php');

$nowDateTime = date("Y-m-d h:i:s");
?>
<?php
$db = new Database();
$add_by = str_replace(",", "",$_SESSION['userid']) ;
$query_sales_rep = $db->getRow('SELECT * FROM users WHERE userid = ?',[$add_by]);
$sales_rep = $query_sales_rep['first_name']." ".$query_sales_rep['last_name'];

function load_invoice(){

     $output = '';  
      $db = new Database();
      $query = $db->getRows('SELECT * FROM invoice_hedder WHERE invoice_h_status = ?',[1]);
      $data = $query;
     
        foreach($data as $query) 
            {   

               
                $output .= '<option value="'.$query['invoice_h_id'].'">'.$query['invoice_h_code'].'</option>';

            }
            return $output;  
}

function load_items(){

     $output = '';  
      $db = new Database();
      $query = $db->getRows('SELECT * FROM item_master');
      $data = $query;
     
        foreach($data as $query) 
            {   

               
                $output .= '<option value="'.$query['item_id'].'">'.$query['item_code'].'</option>';

            }
            return $output;  
}

function getReferance(){

//parana id eka search karala aluth id ekak hadagannawa.
$db = new Database();
$getpid = $db->getRow('SELECT max(sales_return_h_id) as sales_return_h_id FROM sales_return_hedder');
$randomNo = rand(1000,9999);

$oldpid = $getpid['sales_return_h_id'];
if($getpid > 0)
{

$newpid =  $oldpid + 1 ; 
}

// product code ekak hadagannawa

echo $refaranceCode = "SR000".$newpid;

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
        <title>Sales Return</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>
        <!-- BEGIN PAGE LEVEL PLUGINS -->
        <link href="assets/global/plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/global/plugins/select2/css/select2-bootstrap.min.css" rel="stylesheet" type="text/css" />
        <!-- BEGIN PAGE LEVEL PLUGINS -->
        <link href="assets/global/plugins/jquery-notific8/jquery.notific8.css" rel="stylesheet" type="text/css" />
        <!-- END PAGE LEVEL PLUGINS -->
        <!-- END PAGE LEVEL PLUGINS -->
       </head>
    <!-- END HEAD -->

    <body class="page-sidebar-closed-hide-logo page-content-white page-sidebar-closed">
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
                                <a href="#">Tables</a>
                                <i class="fa fa-circle"></i>
                            </li>
                            <li>
                                <span>Datatables</span>
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
                         <div class="portlet box blue">
                                            <div class="portlet-title">
                                                <div class="caption">
                                                    <i class="fa fa-gift"></i>Sales Return</div>
                                                <div class="tools">
                                                    <a href="javascript:;" class="collapse" data-original-title="" title=""> </a>
                                                    <a href="#portlet-config" data-toggle="modal" class="config" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="reload" data-original-title="" title=""> </a>
                                                    <a href="javascript:;" class="remove" data-original-title="" title=""> </a>
                                                </div>
                                            </div>
                                            <div class="portlet-body form">
                                                <!-- BEGIN FORM-->
                                                <form class="form-horizontal" role="form" id="frn-submit">
                                                    <div class="form-body">
                                                      <h3 class="form-section">Sales Return Details</h3>

                                                      <div class="row">
                                                        <div class="col-md-6 col-sm-6">
                                                                
                                                           <div class="form-group">
                                                            <label class="col-md-3 control-label">Invoice No</label>
                                                            <div class="col-md-4">
                                                                <select class="form-control select2me select2-hidden-accessible" name="invoice" id="invoice" tabindex="-1" aria-hidden="true">
                                                                  
                                                                   <option value=""></option>
                                                                   <?php echo load_invoice(); ?>
                                                                 </select>
                                                            </div>
                                                        </div>
                                                           <div class="form-group">
                                                            <label class="col-md-3 control-label">Invoice Date</label>
                                                            <div class="col-md-4">
                                                                <input type="text" id="invDate" name="invDate" class="form-control" readonly="">
                                                            </div>
                                                        </div>
                                                         <div class="form-group">
                                                            <label class="col-md-3 control-label">Location</label>
                                                            <div class="col-md-4">
                                                                <input type="text" id="invLocation" name="invLocation" value="" class="form-control" readonly="">
                                                            </div>
                                                        </div>

                                                         <div class="form-group">
                                                            <label class="col-md-3 control-label">Payment Term</label>
                                                            <div class="col-md-4">
                                                                <input type="text" id="invPayment" name="invPayment" value="" class="form-control" readonly="">
                                                            </div>
                                                        </div>



                                                        </div> 

                                                        <div class="col-md-6 col-sm-6">
                                                            <div class="form-group">
                                                            <label class="col-md-3 control-label">Sales Return No</label>
                                                            <div class="col-md-4">
                                                                <input type="text" id="return_no" name="return_no" class="form-control" readonly="" value="<?php echo getReferance(); ?>">
                                                            </div>
                                                        </div>

                                                         <div class="form-group">
                                                            <label class="col-md-3 control-label">Return Date</label>
                                                            <div class="col-md-4">
                                                                <input type="text" id="return_date" value="<?php echo $nowDateTime; ?>" name="return_date" class="form-control" >
                                                            </div>
                                                        </div>

                                                         <div class="form-group">
                                                            <label class="col-md-3 control-label">Customer Name</label>
                                                            <div class="col-md-4">
                                                                <input type="text" id="customer_name" name="customer_name" class="form-control" readonly="">
                                                            </div>
                                                        </div>
                                                         <div class="form-group">
                                                            <label class="col-md-3 control-label">Sales Representative</label>
                                                            <div class="col-md-4">
                                                                <input type="text" value="<?php echo $sales_rep; ?>" id="return_added" name="return_added" class="form-control" readonly="">
                                                            </div>
                                                        </div>

                                                        </div> 

                                                      </div>
                                                      <hr/>
                                                        <div class="row">
                                                        <div class="col-md-6 col-sm-6">
                                                                
                                                           <div class="form-group">
                                                            <label class="col-md-5 control-label">Item</label>
                                                            <div class="col-md-7">
                                                                <select class="form-control select2me select2-hidden-accessible" name="drpitemloop" id="drpitemloop" tabindex="-1" aria-hidden="true">
                                                                  
                                                                  
                                                                   
                                                                 </select>
                                                            </div>
                                                        </div>
                                                           
                                                      
                                                        </div>
                                                         <!--   <div class="col-md-4 col-sm-4">
                                                                
                                                           <div class="form-group">
                                                            <label class="col-md-3 control-label">Description</label>
                                                            <div class="col-md-8">
                                                                <input type="text" id="item_name" name="item_name" class="form-control" readonly="">
                                                            </div>
                                                        </div>
                                                           
                                                      
                                                        </div> --> 
                                                        <div class="col-md-2 col-sm-2">
                                                                
                                                           <div class="form-group">
                                                            <label class="col-md-2 control-label">Qty</label>
                                                            <div class="col-md-7">
                                                                <input type="text"   id="itm_qty" value="1" name="itm_qty" class="form-control">
                                                            </div>
                                                        </div>
                                                           
                                                      
                                                        </div>
                                                        <div class="col-md-2 col-sm-2">
                                                                
                                                           <div class="form-group">
                                                            <label class="col-md-2 control-label">Rate</label>
                                                            <div class="col-md-8">
                                                                <input type="text" id="itm_price" name="itm_price" value="0.00" class="form-control">
                                                            </div>
                                                        </div>
                                                           
                                                      
                                                        </div>
                                                            <div class="col-md-2 col-sm-2">
                                                                
                                                          <button type="button" class="btn green" name="addItm" id="addItm">
                                                                            <i class="fa fa-pencil"></i>Add</button>
                                                      
                                                        </div>
 

                                                       
                                                      </div>
                                                        
                                                          <div class="row">
                                                            <div class="col-md-12">
                                                                <table class="table table-bordered table-hover myDatatable" id="myDatatable">
    <thead><!-- Table head -->
    <tr>
        <!-- <th class="active ">Sl</th>-->
        <th class="active col-sm-4">Product</th>
        <th class="active col-sm-1">Qty</th>
        <th class="active col-sm-2">Unit Price</th>
        <th class="active col-sm-1">Tax</th>
        <th class="active col-sm-2">Total</th>
        <th class="active col-sm-1">Action</th>

    </tr>
    </thead><!-- / Table head -->
    <tbody><!-- / Table body -->
            <!-- / start cart loop -->
       
            <!-- / end cart loop -->



            <tfoot> 
        <!--get all sub category if not this empty-->
       
         <tr>
            <td colspan="3" class="text-right active">
                <strong>Grand Total: </strong>
            </td>
            <td colspan="4" class="text-left active ">
               <strong id='grandTot'>0.00</strong> 
               <input type="hidden" name="grandTotTotHidden" id="grandTotTotHidden">
               
            </td>

        </tr>

        <tr>
            <td colspan="3" class="text-right active">
                <strong>Note</strong>
            </td>
            <td colspan="4" class="text-left active">
               
                <textarea  class="form-control" id="return_note" name="return_note"> </textarea>
            </td>
        </tr>

       
     
            <td colspan="4" class="text-right active">

            </td>
            <td colspan="4" class="text-left active">
                <button type="submit" id="btn-save"  name="btn-save"class="btn bbtn btn-primary btn-block ">Save</button>
                
            </td>
        </tr>
</tfoot>
        </tbody><!-- / Table body -->
</table> <!-- / Table -->
                                                            </div>
                                                            </div>
                                                        </div>
                                                        <!--/row-->
                                                   
                                                        <!--/row-->
                                                      
                                                
                                                        <!--/row-->
                                         
                                                
                                                </form>
                                                <!-- END FORM-->
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
        <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
        <!-- END PAGE LEVEL PLUGINS -->
        <!-- BEGIN THEME GLOBAL SCRIPTS -->
        <script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
        <!-- END THEME GLOBAL SCRIPTS -->
        <!-- BEGIN PAGE LEVEL SCRIPTS -->
        <script src="assets/pages/scripts/table-datatables-responsive.min.js" type="text/javascript"></script>
        <script src="assets/global/plugins/fuelux/js/spinner.min.js" type="text/javascript"></script>
        <!-- END PAGE LEVEL SCRIPTS -->
        <!-- BEGIN THEME LAYOUT SCRIPTS -->
        <script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
        <script src="assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>
        <script src="assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>
        <!-- END THEME LAYOUT SCRIPTS -->
 
       <!-- Auto Numaric Function -->
         <script src="assets/global/plugins/numaricFunction/autoNumeric.js" type="text/javascript"></script>
         
         <!-- Notification function -->
        <script src="assets/global/plugins/notification/jquery.bootstrap-growl.js"></script>
        <script src="assets/global/plugins/select2/js/select2.full.min.js" type="text/javascript"></script>
         <!-- BEGIN PAGE LEVEL SCRIPTS -->
        
        <script src="assets/global/plugins/jquery-notific8/jquery.notific8.js" type="text/javascript"></script>

            <script type="text/javascript" src="assets/global/plugins/celander/moment.js"></script>
    <script type="text/javascript" src="assets/global/plugins/celander/bootstrap-datetimepicker.min.js"></script>
    <script type="text/javascript" src="assets/global/plugins/celander/datepicker.js"></script>

        <!-- END PAGE LEVEL SCRIPTS -->

</body>

</html>
   <script type="text/javascript">
            // When the document is ready
            $(document).ready(function () {
                
                $('#return_date').datepicker({
                    format: "yyyy-mm-dd"
                });  
            
            });
        </script>
<script>  
 $(document).ready(function(){  
      $('#invoice').change(function(){  
           var invoice_id = $(this).val();
           
         
        

            $.ajax({  
                url:"process/sales_return_get_invoice_details.php",  
                method:"POST",  
                data:{invoice_id:invoice_id},  
                dataType:"text",  
                success:function(data)  
                {   
                    var jsonobj = JSON.parse(data);

                     $('#invDate').val(jsonobj.invoice_date);  
                     $("#invLocation").val(jsonobj.invoice_location);  
                     $('#invPayment').val(jsonobj.invoice_pay_type);
                     $('#drpitemloop').html(jsonobj.invoice_item);  

                    
                }  
           }); 

       
      });  
 });  
 </script>

 <script>  
 $(document).ready(function(){  
      $('#drpitemloop').change(function(){  
           var item_id = $(this).val();
         
           $.ajax({  
                url:"process/sales_return_get_item_details.php",  
                method:"POST",  
                data:{item_id:item_id},  
                dataType:"text",  
                success:function(data)  
                {   
                    var jsonobj = JSON.parse(data);

                     $('#item_name').val(jsonobj.item_name);  
                     

                    
                }  
           });  
      });  
 });  
 </script>










 <script>

    $('#addItm').click(function() {


    var item_id = $("#drpitemloop").val();
    var item_qty = $("#itm_qty").val();
    var item_price = $("#itm_price").val();
    var invoice_id = $("#invoice").val();
    var added_item = $("#added_item_id").val();


    function hasItemAdded(item_id){
        var hasAdded = false;
        $('#myDatatable tbody tr td input[name="item_id[]"]').each(function() {
            if($(this).val() == String(item_id)) {
                hasAdded = true;
            }
        })
        return hasAdded;
    }


            $.ajax({  
                url:"process/sales_return_load_item.php",  
                method:"POST",  
                data:{item_id:item_id,item_qty:item_qty,item_price:item_price,invoice_id:invoice_id,added_item:added_item },  
                dataType:"text",  
                success:function(data)  
                {   
                     var jsonobj = JSON.parse(data);

                    


                     if(jsonobj.item_qty_status == true){


                         if(!hasItemAdded(item_id)) {
      $("#myDatatable tbody").append('<tr class="custom-tr" id="'+item_id+'">'+
            
            '<td class="vertical-td"><input type="hidden" value="'+jsonobj.invoice_d_id+'" name="item_d_id[]" ><input type="hidden" value="'+item_id+'" name="item_id[]" ><a data-toggle="modal" data-target="#myModal" ><input type="hidden" value="'+jsonobj.item_name+'" name="item_name[]">'+jsonobj.item_name+'</a></td>' +
            '<td class="vertical-td">'+

                '<input type="text" name="qty[]" readonly  value="'+jsonobj.item_qty+'" id="qty" class="form-control">'+

            '</td>'+
            '<td class="vertical-td">'+

                '<input type="text" readonly name="price[]" value="'+jsonobj.item_price+'" id="price" class="form-control">'+
                '<input type="hidden" name="itmVat[]" id="itmVat" data-vat="' + jsonobj.item_vat_has + '" value="' + item_id + '" class="form-control">'+
 
            '</td>'+
            '<td class="vertical-td">'+

                '<input type="text" readonly name="vatRate[]" value="'+jsonobj.item_vat_rate+'" id="vatRate" class="form-control">'+
                
 
            '</td>'+
            '<td class="vertical-td itmTot" ><input type="hidden" value="'+jsonobj.item_net_value+'" name="itmGrandTot[]" id="itmGrandTot">'+jsonobj.item_net_value+'</td>'+

            '<td class="vertical-td">'+
                '<a href="" class="btn btn-danger btn-xs" title="" data-toggle="tooltip" data-placement="top"  data-row-id="'+item_id+'" onclick="return confirm("Are you sure want to delete this record ?");" data-original-title="Delete"><i class="fa fa-trash-o"></i></a></td>'+

        '</tr>');

             document.getElementById("itm_qty").className  = "form-control";

             getGradTot();

      }


                     }else{

                        document.getElementById("itm_qty").className  = "form-control danger-text-box";
                     }
                   

                         
                  
                     

                    
                }  
           });  

 getGradTot();

    });



function getGradTot() {
  var tot = 0;
$("input[id='itmGrandTot']").each(function() {
    tot += parseFloat($(this).val());
    document.getElementById("grandTot").innerHTML = tot.toFixed(2);
     $('#grandTotTotHidden').val(tot.toFixed(2));  
});
}



 $(document).ready(function(){  
      $('#invoice').change(function(){  
           var invoice_id = $(this).val();
         
           $.ajax({  
                url:"process/sales_return_get_invoice_details.php",  
                method:"POST",  
                data:{invoice_id:invoice_id},  
                dataType:"text",  
                success:function(data)  
                {   
                    var jsonobj = JSON.parse(data);

                    
                     $('#invDate').val(jsonobj.invoice_date);  
                     $("#invLocation").val(jsonobj.invoice_location);  
                     $('#invPayment').val(jsonobj.invoice_pay_type);
                      $('#drpitemloop').html(jsonobj.invoice_item); 
                      $('#customer_name').val(jsonobj.invoice_customer);   

                    
                }  
           });  
      });  
 }); 


$('document').ready(function()
{ 

  $(document).on('submit','#frn-submit', function()
    {

       var data = $(this).serialize();
      
    
   $.ajax({
    
   type : 'POST',
   url  : 'process/add-sales-return-process.php',
   data : data,
   beforeSend: function()
   { 
    
    $("#btn-save").html('Processing ...');

   },

   success :  function(response)
      {      
    var jsonobj = JSON.parse(response);
     $("#btn-save").html('Save');

        // all options set
$.notific8(jsonobj.message_d, {
  life: 5000,
  heading: jsonobj.message_h,
  theme: jsonobj.message_theme,
  sticky: false,
  horizontalEdge: 'top',
  verticalEdge: 'right',
  zindex: 99999
});

    if(jsonobj.redirect_srn_note == true){

         setTimeout(function () {
       window.location.href = 'sales-return-note.php?id='+jsonobj.new_srn_id; 
    }, 2000); 

    }

     }
   });
    return false;

    });


});


 </script>



