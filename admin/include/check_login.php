<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', TRUE);

require_once __DIR__ . '/permissions.php';

if (!function_exists('isSuperAdmin')) {
    function isSuperAdmin() {
        return isset($_SESSION['userlevel']) && (int) $_SESSION['userlevel'] === 1;
    }
}

//store session into variable
if(isset($_SESSION['Status']))
{

$session_user_name =  $_SESSION['username'];
$session_user_f_name =  $_SESSION['first_name'];
$session_user_password = $_SESSION['password'];
$session_user_level = $_SESSION['userlevel'];
$session_user_activeed = $_SESSION['activated'];
$session_user_locked = $_SESSION['locked'];
$session_user_status = $_SESSION['Status'];
$session_user_id = $_SESSION['userid'];

//check in db account lock or not

$db = new Database();
    $session_user_check_status= $db->getRow('SELECT * FROM users WHERE userid = ? ',[$session_user_id]);
    $session_user_real_activeted = $session_user_check_status['activated'] ?? 'N';
    $session_user_real_locked = $session_user_check_status['locked'] ?? 'Y';
    $session_user_real_level = (int) ($session_user_check_status['user_level'] ?? 0);
    $session_user_real_location = (int) ($session_user_check_status['location_status'] ?? 0);
    $_SESSION['userlevel'] = $session_user_real_level;
    $session_user_level = $session_user_real_level;

    if ($session_user_real_level !== 1 && $session_user_real_location > 0) {
        $_SESSION['location'] = $session_user_real_location;
    }

if($session_user_status !== "login_success" || $session_user_real_locked == "Y" || $session_user_real_activeted == "N")
{

// Check if this is an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login again.']);
    exit;
} else {
    session_destroy();
    echo "<script type='text/javascript'>window.location.href = 'login.php';</script>";
    $redirect_mess = 'done';
}


}
else
{

$redirect_mess = "error";

    initUserPermissions($session_user_id, $session_user_level);
    requirePagePermission();

}
}
else
{

    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login again.']);
        exit;
    } else {
        echo "<script type='text/javascript'>window.location.href = 'login.php';</script>";
    }
}


?>



