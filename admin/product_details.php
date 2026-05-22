<?php
ob_start();
error_reporting (E_ALL ^ E_NOTICE);

session_start();
include('include/database.php');
function filter($var)
{

  return preg_replace('[0-9]',' ' , $var);
}

$db = new Database();
$item_id = filter($_GET['id']);


if($item_id > 0){


	$query_check_item = $db->getRow('SELECT * FROM item_master WHERE item_id = ?',[$item_id]);

	$real_item_id = $query_check_item['item_id'];
		 
		 if($real_item_id > 0){

		 		$item_image_1 = $query_check_item['item_image'];
		 		$item_name = $query_check_item['item_name'];
		 		$item_price = $query_check_item['item_normal_selling_price'];
		 		$item_discount = $query_check_item['item_discount'];
		 		$item_promotion_status = $query_check_item['item_promotion_status'];
		 		$item_description = $query_check_item['item_discription'];



		 		$query_check_fifo = $db->getRow('SELECT SUM(ft_blanace) as qty FROM fifo WHERE ft_item = ? AND ft_location = ? ',[$item_id,1]);
	             $master_item_qty = $query_check_fifo['qty'];

	            $promotion_price = $item_price -(($item_price * $item_discount)/100);

		 }else{


		 	//redirect code
		 }



}else{

	//redirect code

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
   <style type="text/css">

   		 .tiles {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  }

  .tile {
    position: relative;
    float: left;
    width: 99%;
    height: 100%;
    overflow: hidden;
  }

  .photo {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-repeat: no-repeat;
    background-position: center;
    background-size: cover;
    transition: transform .5s ease-out;
  }

  .txt {
    position: absolute;
    z-index: 2;
    right: 0;
    bottom: 10%;
    left: 0;
    font-family: 'Roboto Slab', serif;
    font-size: 9px;
    line-height: 12px;
    text-align: center;
    cursor: default;
  }

  .x {
    font-size: 32px;
    line-height: 32px;
  }


.count-input {
  position: relative;
  width: 100%;
  max-width: 165px;
  margin: 10px 0;
}
.count-input input {
  width: 100%;
  height: 36.92307692px;
  border: 1px solid #000;
  border-radius: 2px;
  background: none;
  text-align: center;
}
.count-input input:focus {
  outline: none;
}
.count-input .incr-btn {
  display: block;
  position: absolute;
  width: 30px;
  height: 30px;
  font-size: 26px;
  font-weight: 300;
  text-align: center;
  line-height: 30px;
  top: 66%;
  right: 0;
  margin-top: -15px;
  text-decoration:none;
}
.count-input .incr-btn:first-child {
  right: auto;
  left: 0;
  top: 66%;
}
.count-input.count-input-sm {
  max-width: 125px;
}
.count-input.count-input-sm input {
  height: 36px;
}
.count-input.count-input-lg {
  max-width: 200px;
}
.count-input.count-input-lg input {
  height: 70px;
  border-radius: 3px;
}
   </style>

</head>

<body class="common-home res layout-home10 pattern-28 loaded">
    <div id="wrapper" class="wrapper-boxed banners-effect-7">
		<!-- Header Container  -->
			<?php include('common/header.php'); ?>
		<!-- //Header Container  -->
		<!-- Main Container  --><br>
			<div class="main-container container">
			
			
			<div class="row">
				<!--Middle Part Start-->
				<div id="content" class="col-md-12 col-sm-12">
					<div class="product-view row">
						<div class="left-content-product col-lg-10 col-xs-12">
							<div class="row">
								<div class="content-product-left class-honizol col-sm-6 col-xs-12 " style="height:388px;">
									
									 <div class="tiles">
   
													    <div class="tile" data-scale="1.6" data-image="<?php echo $item_image_1; ?>"></div>
													   
													  </div>
								 

									
								</div>

								<div class="content-product-right col-sm-6 col-xs-12" style="padding-left: 30px;">
									<div class="title-product">
										<h1><?php echo $item_name; ?></h1>
									</div>
									<!-- Review ---->
						

									<div class="product-label form-group">
										<?php if ($master_item_qty > 0){?>
											<div class="stock"><span>Availability:</span> <span class="status-stock">In Stock</span></div>
										<?php } else { ?>
											<div class="stock"><span>Availability:</span> <span class="">Out Stock</span></div>

										<?php } ?>
										
										<div class="product_page_price price" itemprop="offerDetails" itemscope="" itemtype="http://data-vocabulary.org/Offer">
											<span class="price-new" itemprop="price"><?php echo $item_price; ?></span>
											
											<?php if($item_price > $promotion_price){?>
											<span class="price-old">LKR <?php echo number_format($promotion_price,2); ?></span>
											<?php } ?>
										</div>
										
									</div>

							<!-- 		<div class="product-box-desc">
										<div class="inner-box-desc">
											<div class="price-tax"><span>Ex Tax:</span> $60.00</div>
											<div class="reward"><span>Price in reward points:</span> 400</div>
											<div class="brand"><span>Brand:</span><a href="#">Apple</a>		</div>
											<div class="model"><span>Product Code:</span> Product 15</div>
											<div class="reward"><span>Reward Points:</span> 100</div>
										</div>
									</div> -->


									<div id="product">
								
										<div class="box-checkbox form-group required">
										
										</div>

										<div class="form-group box-info-product">

											<form method="POST" id="frn-item">
											<div class="option quantity">
												<div class="input-group quantity-control" unselectable="on" style="-webkit-user-select: none;">
													<label>Qty</label>
													<div class="count-input space-bottom">

                                <a class="incr-btn" data-action="decrease" href="#">–</a>
                                <input class="quantity" type="text" name="quantity" value="1"/>
                                <a class="incr-btn" data-action="increase" href="#">&plus;</a>
                            </div>
												</div>
												<input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
											</div>
											<div class="cart" style="    margin-top: 7%;">
												<input type="submit" data-toggle="tooltip" data-item-id="<?php echo $item_id; ?>" value="Add Cart"  id="button-cart" class="btn btn-mega btn-lg main-page-product-list-add-btn btnAddItemFromList" style="color: #fff;
    height: 35px;
    line-height: 33px;
    padding: 0 3px 0 3px;
    font-weight: normal;
    font-size: 21px;
    border-radius: 0;
    background: #57963a url() no-repeat;
    background-position: 8px center;
    text-transform: uppercase;" onclick="cart.add('42', '1');" data-original-title="Add to Cart">
											</div>
										
												</form>

										</div>
													<div class="tabsslider  ">
							<ul class="nav nav-tabs">
								<li class="active" ><a data-toggle="tab" href="#tab-1" style="background-color:#228a6d; color:white; font-weight:bold;">Product Description</a></li>
							
							</ul>
							<div class="tab-content col-xs-12">
								<div id="tab-1" class="tab-pane fade active in">
									<?php echo $item_description; ?>
									
								</div>
							
							</div>
						</div>
									</div>
									<!-- end box info product -->

								</div>
							</div>
						</div>
						
						<section class="col-lg-2 hidden-sm hidden-md hidden-xs slider-products">
							<div class="module col-sm-12 four-block" style="margin-top:196px;">
								<div class="modcontent clearfix">
									<div class="policy-detail">
										<div class="banner-policy" style="background-color:white; border:1px solid #ded6d6;">
											
											<div class="policy policy2">
											 <!--<span class="ico-policy">&nbsp;&nbsp;&nbsp;</span>	-->	<b>	lowest price guaranteed for Hampers</b>
											</div>
											
										</div>
									</div>
								</div>
							</div>
						</section>
					</div>
					
					<!-- Product Tabs -->

				
				
			</div>
			<!--Middle Part End-->
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
	<script type="text/javascript" src="js/notifi8/jquery.notific8.js"></script>
	<script type="text/javascript" src="js/social_kit/social-share-kit.js"></script>


	<!-- Theme files
	============================================ -->
	<script type="text/javascript" src="js/themejs/application.js"></script>
	<!-- <script type="text/javascript" src="js/themejs/toppanel.js"></script> -->
	<script type="text/javascript" src="js/themejs/so_megamenu.js"></script>
	<!-- <script type="text/javascript" src="js/themejs/addtocart.js"></script>  -->
	
	<script type="text/javascript" src="js/themejs/accordion.js"></script>	
	<script type="text/javascript" src="js/myFunctions.js"></script>

</body>

</html>



  
<script>
$('document').ready(function()
{ 

  $(document).on('submit','#frn-item', function()
    {

       var data = $(this).serialize();
   
   $.ajax({
    
   type : 'POST',
   url  : 'process/add-item-session.php',
   data : data,
   
   success :  function(response)
      {      
 
   	alert(response);
    var params = {
                life: 1000,
                theme: "teal",
                sticky: "",
                horizontalEdge: "top",
                verticalEdge: "right"
            },
            text = response,
            $heading = $('#notific8Heading');
            $icon = $('#notific8Icon');

        if ($.trim($heading.val()) !== '') {
            params.heading = $heading.val();
        }
        if ($.trim($icon.val()) !== '') {
            params.icon = $icon.val();
        }

        // show notification
        $.notific8(text, params);
   
         location.reload();
   		
     }
     }
   });
    return false;

    });


});


</script>
	<script type="text/javascript">
  $('.tile')
    // tile mouse actions
    .on('mouseover', function(){
      $(this).children('.photo').css({'transform': 'scale('+ $(this).attr('data-scale') +')'});
    })
    .on('mouseout', function(){
      $(this).children('.photo').css({'transform': 'scale(1)'});
    })
    .on('mousemove', function(e){
      $(this).children('.photo').css({'transform-origin': ((e.pageX - $(this).offset().left) / $(this).width()) * 100 + '% ' + ((e.pageY - $(this).offset().top) / $(this).height()) * 100 +'%'});
    })
    // tiles set up
    .each(function(){
      $(this)
        // add a photo container
        .append('<div class="photo"></div>')
        // some text just to show zoom level on current item in this example
       /* .append('<div class="txt"><div class="x">'+ $(this).attr('data-scale') +'x</div>ZOOM ON<br>HOVER</div>')*/
        // set up a background image for each tile based on data-image attribute
        .children('.photo').css({'background-image': 'url('+ $(this).attr('data-image') +')'});
    })
</script>
						
					
		<script type="text/javascript">

			    $(".incr-btn").on("click", function (e) {
        var $button = $(this);
        var oldValue = $button.parent().find('.quantity').val();
        $button.parent().find('.incr-btn[data-action="decrease"]').removeClass('inactive');
        if ($button.data('action') == "increase") {
            var newVal = parseFloat(oldValue) + 1;
        } else {
            // Don't allow decrementing below 1
            if (oldValue > 1) {
                var newVal = parseFloat(oldValue) - 1;
            } else {
                newVal = 1;
                $button.addClass('inactive');
            }
        }
        $button.parent().find('.quantity').val(newVal);
        e.preventDefault();
    });

		</script>						



