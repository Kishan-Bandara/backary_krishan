<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');

$product_id = $_GET['pid'];

if(!empty($product_id) && $product_id > 0)
{

                        $get_product_id = $_GET['pid'];
                        $db = new Database();
                        $query_get_qty = $db->getRow('SELECT SUM(ft_qty) as qty FROM fifo WHERE ft_item = ?',[$get_product_id]);
                        $query_get_code = $db->getRow('SELECT * FROM item_master WHERE item_id = ?',[$get_product_id]);
                        $product_code = $query_get_code['item_code'];
                        $product_cat_id = $query_get_code['item_category'];
                        $product_qty = $query_get_qty['qty'];

                        $query_get_cat = $db->getRow('SELECT * FROM category_master WHERE category_id = ?',[$product_cat_id]);
                        $product_cat = $query_get_cat['category_name'];



                   

}
else
{

    $message = "Product Id Error!";
}
?>

<html>
<head>
        <meta charset="utf-8" />
        <title>Print Barcodes</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1" name="viewport" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php include('common/head.php'); ?>

        <style>
             body {
            font-size: 13px;
            text-align: center;
            color: #000;
            background: #FFF;
        }
        .wrapper {
            max-width: 1000px;
            margin: 0 auto;
        }

        .contanior
        {
            min-height: 20px;
    padding: 19px;
    margin-bottom: 20px;
    background-color: #f5f5f5;
    border: 1px solid #e3e3e3;
    border-radius: 4px;
    -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.05);
    box-shadow: inset 0 1px 1px rgba(0,0,0,.05);">
            <span style="margin-top:15px; display: blo    min-height: 20px;
    padding: 19px;
    margin-bottom: 20px;
    background-color: #f5f5f5;
    border: 1px solid #e3e3e3;
    border-radius: 4px;
    -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.05);
    box-shadow: inset 0 1px 1px rgba(0,0,0,.05);ck;
        }
        </style>
       </head>

<body>
<div class="wrapper">

    <div class="table-responsive">
        <div class="contanior">
               <div class="btn-group">
                  <a class="btn btn-primary" href="#" onclick="window.print(); return false;"><i class="fa fa-print"></i> Print</a>
                  <a class="btn btn-danger" onclick="javascript:window.close()"><i class="fa fa-times"></i> Close</a>
               </div>
            </span>
         </div>
  <table class="table">
   <tbody>
    <tr>
        <?php 
             if(!empty($product_qty) && $product_qty > 0)
                        {

                            for($i=0;$i<$product_qty;$i++)
                            {

                                   if($i%6 == 0)
                                   {

                                  echo "<tr>".PHP_EOL;
                                   }
                                   
                                    echo ' <td style="height:50px;">
                     
                <table class="table-barcode">
                        <tbody>
                           <tr>
                              <td colspan="2" class="bold" style="text-align:center;font-size: 8px;">'.$product_cat.'</td>
                           </tr>
                           <tr>
                              <td colspan="2" class="text-center bc"><img style="width:150px;" src="barcode_process.php?pid='.$product_code.'" alt="'.$product_code.'"></td>
                           </tr>

                        </tbody>

                     </table>
                  </td>'.PHP_EOL;
                 


                            }


                          }
                          else
                          {

                            echo"<div style='margin-top:10px'>you should need to purchase items.<br><small><i> Stock managment system</i></small></div>";
                          }
        ?>



    </tr>
   </tbody>
  </table>
</div>

</div>

</body>
</html>



