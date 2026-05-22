<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');
include('../include/check_login.php');


?>
<?php
if(isset($_POST['receipt_id'])){

   $receipt_id =$_POST['receipt_id'];

    $db = new Database();
    $get_real_receipt_id = $db->getRow('SELECT * FROM invoice_hedder WHERE invoice_h_id = ?',[$receipt_id]);
    $real_receipt_id = $get_real_receipt_id['invoice_h_id'];

    //check user id Fake or not
    if($receipt_id == $real_receipt_id){

    	$receipt_h_ref_no = $get_real_receipt_id['invoice_h_code'];
      $receipt_customer_id = $get_real_receipt_id['invoice_h_customer_id'];
      $receipt_date = $get_real_receipt_id['invoice_h_date'];

      $receipt_payment_status = $get_real_receipt_id['invoice_h_status'];
      $receipt_deliver_mode = $get_real_receipt_id['invoice_h_delivery_mode'];
      $receipt_deliver_contact_name = $get_real_receipt_id['invoice_h_delivery_name'];
      $receipt_deliver_city = $get_real_receipt_id['invoice_h_delivery_city'];
      $receipt_deliver_address = $get_real_receipt_id['invoice_h_delivery_address'];
      $receipt_deliver_date = $get_real_receipt_id['invoice_h_delivery_date']." ".$get_real_receipt_id['invoice_h_delivery_time'];
      $receipt_deliver_contact_number = $get_real_receipt_id['invoice_h_delivery_contact_no'];

      $receipt_deliver_cost = $get_real_receipt_id['invoice_h_delivery_cost'];

      $receipt_payment_type_id = $get_real_receipt_id['invoice_h_pay_type'];

      $receipt_coupon_discount = $get_real_receipt_id['invoice_h_coupon_value'];

      $receipt_net_value = $get_real_receipt_id['invoice_h_net_value'];

      $receipt_gross_value = $get_real_receipt_id['invoice_h_gross_value'];

                   #payment mode


        

          if( $receipt_payment_status == 0){

             $receipt_payment_status = "Pending";

          }else if( $receipt_payment_status == 1){

             $receipt_payment_status = "Paid";
          }else{

             $receipt_payment_status = "Canseled";
          }
   
           
                 #deliver mode

        if($receipt_deliver_city){
          try {

            $query_get_deliver_city = $db->getRow('SELECT * FROM city_master WHERE id = ?',[$receipt_deliver_city]);

            $receipt_deliver_city = $query_get_deliver_city['city'];
            
          } catch (Exception $e) {
            
            echo 'Message: ' .$e->getMessage();
          }


        }


             #deliver mode

        if($receipt_deliver_mode){
          try {

            $query_get_deliver_mode = $db->getRow('SELECT * FROM delivery_master WHERE id = ?',[$receipt_deliver_mode]);

            $receipt_deliver_mode = $query_get_deliver_mode['method'];
            
          } catch (Exception $e) {
            
            echo 'Message: ' .$e->getMessage();
          }


        }

        #customer Name

        if($receipt_customer_id){
          try {

            $query_get_customer = $db->getRow('SELECT * FROM customer WHERE customer_id = ?',[$receipt_customer_id]);

            $receipt_customer_name = $query_get_customer['customer_name'];
            
          } catch (Exception $e) {
            
            echo 'Message: ' .$e->getMessage();
          }


        }
      	

      	#payment type 

      	if($receipt_payment_type_id){
      		try {

      			$query_get_payment_type = $db->getRow('SELECT * FROM payment_method WHERE id = ?',[$receipt_payment_type_id]);

      			$receipt_payment_type_name = $query_get_payment_type['type'];
      			
      		} catch (Exception $e) {
      			
      			echo 'Message: ' .$e->getMessage();
      		}


      	}



       


      	#receipt details

        $output =  array('receipt_no' => $receipt_h_ref_no,
                          'customer_name' => $receipt_customer_name,
                          'receipt_date' => $receipt_date,
                          'deliver_mode' => $receipt_deliver_mode,
                          'deliver_name' => $receipt_deliver_contact_name,
                          'deliver_city' => $receipt_deliver_city,
                          'deliver_address' => $receipt_deliver_address,
                          'deliver_date' => $receipt_deliver_date,
                          'deliver_contact_no' => $receipt_deliver_contact_number,
                          'deliver_cost' => $receipt_deliver_cost,
                          'payment_type' => $receipt_payment_type_name,
                          'discount' => $receipt_coupon_discount,
                          'net_value' => $receipt_net_value,
                          'gross_value' => $receipt_gross_value,
                          'invoice_status' => $receipt_payment_status );

        echo json_encode($output,JSON_FORCE_OBJECT);

    }
    else
    {

      
        echo "this user id not found on System.";
      
    }

}


?>