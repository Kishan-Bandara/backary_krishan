<?php 
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('../include/database.php');
include('../include/check_login.php');
function filter($var)
{

    return preg_replace('/ [^a-za-z0-9\s@.]/',' ' , $var);
}
?>

<?php
//parana id eka search karala aluth id ekak hadagannawa.
$db = new Database();

?>
<?php

  
  if(isset($_POST['pid']))
  {

  $product_id = $_POST['pid'];


    #album image 1 

        if($_FILES['img1'] > 0){

            if(!empty($_FILES['img1'])){
          $img_name_1  = $_FILES["img1"] ["name"];
          $img_type_1  = $_FILES["img1"] ["type"];
          $img_size_1  = $_FILES["img1"] ["size"];
          $img_temp_1 = $_FILES["img1"] ["tmp_name"];
          $img_error_1 = $_FILES["img1"] ["error"];

     

               list($img_width_1, $img_height_1) = getimagesize($img_temp_1);
         
         

          $image_1_random_name = md5(rand(0,1000).rand(0,1000).rand(0,1000))."_grocery_supermarket_in_SriLanka";

          if($img_type_1 == "image/jpeg"){

            $img_path_1 =  '../../image/product_img/'.$image_1_random_name.'.jpg';
            $img_path_display_1 =  'image/product_img/'.$image_1_random_name.'.jpg';

          }elseif($img_type_1 == "image/png"){
            
            $img_path_1 =  '../../image/product_img/'.$image_1_random_name.'.png';
            $img_path_display_1 =  'image/product_img/'.$image_1_random_name.'.png';

          }else{

            $img_path_1 = '../../image/product_img/'.$image_1_random_name.'.jpg';
            $img_path_display_1 =  'img/upload/promo/'.$image_1_random_name.'.png';
          }
          

            if($img_error_1 > 0)
            {

            
              $img_message_1 = "Error uploading main image! Code".$error;

            }else{

              if($img_type_1 == "image/png" || $img_type_1 == "image/jpeg" ){


                if(1==1){

                  try {
                    

          
                      if(move_uploaded_file($img_temp_1, $img_path_1)){
                  
                  
                       $image_parth_1 = $img_path_display_1;
                  
                       $image_sucess = "sucess";
                               
                          }
                          else{
                              $img_message_1 = " STILL DID NOT MOVE";
                          }



                 try {
                          
                             $insertproduct = $db->insertRow('UPDATE item_master SET  item_image = ? WHERE item_id = ?',[$image_parth_1,$product_id]);

                            $message = "Photo has been updated";
                         } catch (Exception $e) {
                            $img_message_1 = " STILL DID NOT UPLOAD";

                         }



                  } catch (Exception $e) {
                    

                    $img_message_1 = "upload error";
                    $error_style = "red";
                    $error_font = "#FFF";
                  }


                }else{

                  $img_message_1 = "Please set the image size 1920 x 920";
                  $error_style = "red";
                  $error_font = "#FFF";
                }


              }else{

                $img_message_1 = "Sorry! can not upload this file";
                $error_style = "red";
                $error_font = "#FFF";
              }

            }
            


           

  }

        }else{

          $message = "please select the image";
        }


  }


echo $message;

?>



