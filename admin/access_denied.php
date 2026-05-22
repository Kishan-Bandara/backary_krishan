<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('include/database.php');
include('include/check_login.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Access Denied</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
    <?php include('common/manubar.php'); ?>
    <div class="page-container">
        <div class="page-content-wrapper">
            <div class="page-content">
                <div class="note note-danger">
                    <h4 class="block">Access denied</h4>
                    <p>You do not have permission to access this page.</p>
                </div>
            </div>
        </div>
    </div>
    <?php include('common/foot.php'); ?>
</body>
</html>
