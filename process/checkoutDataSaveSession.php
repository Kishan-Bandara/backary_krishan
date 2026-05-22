<?php
session_start();
if(isset($_POST['key']) && isset($_POST['value'])){

    $name =  $_POST['key'];
    $value =  $_POST['value'];

    $_SESSION[$name]= $value;
}

?>