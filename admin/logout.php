<?php
    session_start();

    $getid = $_GET['logout'];

    if($getid == 777)
    {

	session_destroy();
    header('Location:login.php');


    }

    
?>



