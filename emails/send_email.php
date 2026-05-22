<?php
define("DEMO", false);
$template_file = "../emails/templates.php";
$email_to = "malith.sachinthana@gmail.com";
$subject = "simple emails with php";

$swap_var = array(
    "{SITE_ADDR}" => "https://Regoora.com",
    "{EMAIL_LOGO}" => "https://Regoora.com/logo.png",
    "{EMAIL_TITLE}" => "Send custom email with template",
    "{TO_NAME}" => "Malith",
    "{TO_EMAIL}" => "malith.sachinthana@gmail.com"
);

$headers = "From: Regoora <info@regoora.com>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

if (file_exists($template_file))
    $message = file_get_contents($template_file);
else
    die("unable to locate the template file");

    foreach(array_keys($swap_var) as $key){
        if(strlen($key)>2 && trim($key) != "")
        $message = str_replace($key,$swap_var[$key],$message);
    }


echo $message;
if (DEMO) die("no email was sent on purpose");

if (mail($email_to, $subject, $message, $headers))
    echo '<hr />success';
else
    echo '<hr >not sent';
