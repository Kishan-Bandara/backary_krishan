<?php
session_start();

if($_SESSION['cart'])
{

	$_SESSION['cart'] = array();
}

array_push($_SESSION['cart'],$_GET['id']);


echo "done!";
?>



