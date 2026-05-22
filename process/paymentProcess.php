<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');
include('../include/check_login.php');
$db = new database();

//load RSA library

date_default_timezone_set("Asia/Colombo");



function filter($var)
{

  return preg_replace('/ [^a-za-z0-9\s@.]/', ' ', $var);
}

$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d h:i:s");
$_SESSION['is_Online_Payment'] = false;
$order_id = $_SESSION['order_id'];
$_SESSION['online_pay_amount'] = 0;
$order_confirm = false;


$merchant_id         = $_POST['merchant_id'];
$order_id            = $_POST['order_id'];
$payhere_amount      = $_POST['payhere_amount'];
$payhere_currency    = $_POST['payhere_currency'];
$status_code         = $_POST['status_code'];
$md5sig              = $_POST['md5sig'];
$merchant_secret = 'MjMwOTI4MDM1MzM1MTY4NTExNzMyMzI0NzUxOTA4NjMxMDA5NTI0'; // Replace with your Merchant Secret (Can be found on your PayHere account's Settings page)

$local_md5sig = strtoupper(
    md5(
        $merchant_id . 
        $order_id . 
        $payhere_amount . 
        $payhere_currency . 
        $status_code . 
        strtoupper(md5($merchant_secret)) 
    ) 
);
       



if($order_id){


$query_get_order_id = $db->getRow('SELECT * FROM invoice_hedder WHERE invoice_h_id = ?',[$order_id]);

    if($query_get_order_id){

        $payment_type = $query_get_order_id['invoice_h_pay_type'];

        
           if ($status_code == 2 ){
                //TODO: Update your database as payment success
                $query_get_order_id = $db->getRow('SELECT * FROM invoice_hedder WHERE invoice_h_id = ?',[$order_id]);
                $payment_type = $query_get_order_id['invoice_h_pay_type'];
                $pay_amount = $query_get_order_id['invoice_h_net_value'];
                
             $insert_payment = $db->insertRow('INSERT INTO customer_balance (invoice_h_id,amount,amountDate,invoice_h_pay_type)VALUES(?,?,?,?)', [$order_id, $pay_amount, $nowDate, $payment_type]);
                     $_SESSION['is_Online_Payment_done'] = true;   
                     $_SESSION['online_pay_amount'] = $payhere_amount;
                     $order_confirm = true;
            }
            else{

                //failed transaction
                $failTranUrl =   site_url()."transactionerror.php";
                header("Location: $failTranUrl");
                die();
            }



    }else{

        //redirect
    }

}else{

    //redirect
}

?>
  <script src="<?php echo site_url(); ?>plugins/jquery.min.js"></script>
  <script type="text/javascript">
        var is_Online_Payment_done = <?php echo json_encode($_SESSION['is_Online_Payment_done']); ?>;
        var order_confirm = <?php echo json_encode($order_confirm); ?>;
        if (is_Online_Payment_done == true) {
            $.ajax({
                url: 'order_process.php',
                type: 'POST',
                data: {
                    order_confirm: order_confirm,
                    paymentProcessTrue: is_Online_Payment_done
                },
                beforeSend: function() {
                  
                },
                success: function(result) {
                    console.log(result);
                    var jsonobj = JSON.parse(result);
                    var order_message = jsonobj.message;

                    if (jsonobj.order_status == true) {
                       //window.location.replace("<?php echo site_url(); ?>transaction.php?order_id=" + jsonobj.order_id);
                    } else {
                        //window.location.replace("<?php echo site_url(); ?>transactionerror.php");
                    }

                }

            });

        }else{
            
            //  window.location.replace("<?php echo site_url(); ?>transactionerror.php");
        }
    </script>