<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
require_once 'fbConfig.php';
if($_SESSION['LoginStatus'] != "login_success"){

  
echo "<script type='text/javascript'>window.location.href = 'index.php';</script>";
}



?>
<!DOCTYPE html>
<html lang="en">

<head>
    
    <!-- Basic page needs
	============================================ -->
<title>instagrocery.lk | Latest online grocery store for fresh Vegetables|fruits|household needs|office Stationary|baby Products |Free Delivery.</title>
	<meta charset="utf-8">
    <meta name="keywords" content="instagrocery.lk,Online grocery Sri Lanka, Online shopping Sri Lanka, Online Market, Buy groceries online Sri Lanka, Online groceries, Online grocery shop,
Online grocery store, Online shopping in Sri Lanka,Sri Lanka,Online supermarket,Online supermarkets,
Fresh vegetables,Online shopping,Free delivery,Cheap delivery,Cheap vegetable,Cheap grocery,Farm fresh,Grocery shipping and delivery,
Health food,Healthy food,Groceries,Delivery,Vegetables,Grocery,Grocery store,Vegetable,Supermarket,Super market,Colombo market,Colombo Delivery,Organic vegetables,
Cheap Organic vegetables,Organic vegetable,Cheap Organic fruits,High quality vegetable,clean vegetables,Discount on bills,Ontime delivery,instant Grocery,
Grocery Deals, Colomob online offers,Cheapest supermarket in Sri Lanka,Cheapest supermarket in Colombo,Cheapest vegetables in Sri Lanka,Cheapest vegetables in Colombo,
Cheapest delivery in Sri Lanka,Cheapest delivery in Colombo,Fresh Vegetables, Fruits, Delivery, Door delivery, Sri Lanka online buy, Colombo,Order online, 
Buy online, Free delivery,Online Shopping, Home Delivery, Office Delivery colombo, Sri Lankan, Grocery, Groceries, Online Supermarket, food, food city, supermarket,
house hold, chocolates,Sweets,Cakes,Roses,foods, baby items, goods, meat, fish, gifts,Pet foods,Breakfast and cereals,baby gifts, toys, toys in colombo, baby pampers, baby pampers in colombo,  Retail in Sri Lanka, online stores, online Ecommerce shopping, shop online,
super,supr,super market,products,items,promotions,deals,savings,offers,discounts,quality,fresh,vegetable,Beverages, Personal care, produce,meat,fresh meat,
office delivery, free office delivery,shop for me,Grocery list, Call for Grocery,Call online supermarket. Delivery to Friends, Delivery to parents, Deliver to Sri lanka 
relatives." />
    <meta name="author" content="">
    <meta name="robots" content="index, follow" />
   
	<?php include('common/style.html'); ?>


</head>

<body class="common-home res layout-home10 pattern-28">
    <div id="wrapper" class="wrapper-boxed banners-effect-7">
		<!-- Header Container  -->
			<?php include('common/header.php'); ?>
		<!-- //Header Container  -->
		<!-- Main Container  -->
	<div class="main-container container">
			<ul class="breadcrumb">
				<li><a href="#"><i class="fa fa-home"></i></a></li>
				<li><a href="#">Refer Friend</a></li>
				
			</ul>
			
			<div class="row">
				<div id="content" class="col-sm-12">
					<div class="page-login">
					
						<div class="account-border">
							<div class="row">
							
							
								<form action="#" method="post" enctype="multipart/form-data" id="frm">
									<div class="col-sm-6">
													<img src="image/refer_friend.jpg">
												</div>
									<div class="col-sm-6 customer-login ">
											
										<div id="output"> </div>
										
										<div class="well" style="    line-height: 21px;">
											
											
											<div class="row">
												<div class="col-md-9 col-md-offset-2">
													<p style="text-align:left; font-size:18px;">REFER A FRIEND: </p>
													<p style="text-align:center ;font-size:26px; color:red;"> <strong>Get LKR 200/- OFF from your total bill</strong></p>
													<p style="text-align:center; font-size:22px;"> On your total bill when your friend make an order for the first time!</p><br>
													<div class="row">
														<div class="col-md-6">
															<div class="form-group">
											
												<input type="text" name="first_name" value="" id="first_name" class="form-control" style="height: 45px;" placeholder="Enter First name">
											</div>
														</div>
														<div class="col-md-6">
															<div class="form-group">
											
												<input type="text" name="last_name" value="" id="last_name" class="form-control" style="height: 45px;" placeholder="Enter Last name">
											</div>
														</div>
													</div>
											<div class="form-group">
											
												<input type="email" name="email" value="" id="email" class="form-control" style="height: 45px;" placeholder="Enter Email Address">
											</div>
											<p style="text-align:center; font-size:20px;padding-top: 5px;"><input type="submit" value="SHARE WITH A FRIEND" class="btn btn-default pull-center refer_btn" name="sub" ></p>
												
												</div>

											</div>
										</div>
										
									</div>
								</form>
							</div>
						</div>
						
					</div>
				</div>
			</div>
		</div>
		<!-- //Main Container -->
		
		<script type="text/javascript"><!--
			var $typeheader = 'header-home10';
			//-->
		</script>
		<!-- Footer Container -->
		<?php include('common/footer.php'); ?>
		<!-- //end Footer Container -->

    </div>
	<!-- Social widgets -->
	<!-- <section class="social-widgets visible-lg">
		<ul class="items">
			<li class="item item-01 facebook"> <a href="php/facebook8859.html?account=envato" class="tab-icon"><span class="fa fa-facebook"></span></a>
				<div class="tab-content">
					<div class="title">
						<h5>FACEBOOK</h5>
					</div>
					<div class="loading">
						<img src="image/theme/lazy-loader.gif" class="ajaxloader" alt="loader">
					</div>
				</div>
			</li>
			<li class="item item-02 twitter"> <a href="php/twitterfdaa.html?account_twitter=envato" class="tab-icon"><span class="fa fa-twitter"></span></a>
				<div class="tab-content">
					<div class="title">
						<h5>TWITTER FEEDS</h5> 
					</div>
					<div class="loading">
						<img src="image/theme/lazy-loader.gif" class="ajaxloader" alt="loader">
					</div>
				</div>
			</li>
			<li class="item item-03 youtube"> <a href="php/youtubevideo2de8.html?account_video=PY2RLgTmiZY" class="tab-icon"><span class="fa fa-youtube"></span></a>
				<div class="tab-content">
					<div class="title">
						<h5>YouTube</h5>
					</div>
					<div class="loading"> <img src="image/theme/lazy-loader.gif" class="ajaxloader" alt="loader"></div>
				</div>
			</li>
		</ul>
	</section>	 --><!-- End Social widgets -->

	
	<!-- Preloading Screen -->
	
	<!-- End Preloading Screen -->
	
	<!-- Include Libs & Plugins
	============================================ -->
	<!-- Placed at the end of the document so the pages load faster -->
	<script type="text/javascript" src="js/jquery-2.2.4.min.js"></script>
	<script type="text/javascript" src="js/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/owl-carousel/owl.carousel.js"></script>
	<script type="text/javascript" src="js/themejs/libs.js"></script>
	<script type="text/javascript" src="js/unveil/jquery.unveil.js"></script>
	<script type="text/javascript" src="js/countdown/jquery.countdown.min.js"></script>
	<script type="text/javascript" src="js/dcjqaccordion/jquery.dcjqaccordion.2.8.min.js"></script>
	<script type="text/javascript" src="js/datetimepicker/moment.js"></script>
	<script type="text/javascript" src="js/datetimepicker/bootstrap-datetimepicker.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui/jquery-ui.min.js"></script>
	<script type="text/javascript" src="js/social_kit/social-share-kit.js"></script>

	<!-- Theme files
	============================================ -->
	<script type="text/javascript" src="js/themejs/application.js"></script>
	<!-- <script type="text/javascript" src="js/themejs/toppanel.js"></script> -->
	<script type="text/javascript" src="js/themejs/so_megamenu.js"></script>
	<script type="text/javascript" src="js/themejs/addtocart.js"></script>
	
	<script type="text/javascript" src="js/themejs/accordion.js"></script>	
	<script type="text/javascript" src="js/myFunctions.js"></script>

</body>

</html>



  
<script type="text/javascript">
$(document).ready(function()
{
 $(document).on('submit', '#frm', function()
 {
  
  var data = $(this).serialize();
  
  
  $.ajax({
  
 type : 'POST',
 url  : 'process/refere_friends_process.php',
  data : data,
  success :  function(response)
       {
   			
   		   	var jsonobj = JSON.parse(response);
   		   	$("#output").fadeIn(1000, function(){  
      		$("#output").html('<div class="">'+jsonobj.message+'</div>');    
      		
        	 });
      	
      	if(jsonobj.status == 1){
      		

      		setTimeout(' window.location.href = "index.php"; ',1000);

      	}



       }
  });
  return false;
 });

});

    </script>
	
						
					
								



