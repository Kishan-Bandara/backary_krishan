<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');

$db = new Database();
$activationStatusClass = 'alert alert-danger';
$activationTitle = 'Activation failed';
$activationMessage = 'The activation link is invalid or has expired.';
$loginUrl = site_url() . 'login.php';

$customerId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$verificationCode = isset($_GET['verification_code']) ? trim((string) $_GET['verification_code']) : '';

if ($customerId > 0 && $verificationCode !== '') {
    $customer = $db->getRow('SELECT customer_id, customer_name, customer_avtive_code, is_active FROM customer WHERE customer_id = ?', [$customerId]);

    if ($customer && hash_equals((string) ($customer['customer_avtive_code'] ?? ''), $verificationCode)) {
        if ((int) ($customer['is_active'] ?? 0) === 1) {
            $activationStatusClass = 'alert alert-info';
            $activationTitle = 'Already activated';
            $activationMessage = 'Your account is already active. You can log in now.';
        } else {
            $db->updateRow('UPDATE customer SET is_active = 1 WHERE customer_id = ? AND customer_avtive_code = ?', [$customerId, $verificationCode]);
            $activationStatusClass = 'alert alert-success';
            $activationTitle = 'Account activated';
            $activationMessage = 'Your account has been activated successfully. You can log in now.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('common/styles.php'); ?>
</head>
<body>
    <div class="ps-page">
        <?php include('common/header.php'); ?>
        <div class="ps-account">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-12 col-md-8 col-lg-6">
                        <div class="ps-form--review">
                            <h2 class="ps-form__title"><?php echo htmlspecialchars($activationTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
                            <div class="<?php echo $activationStatusClass; ?>">
                                <?php echo htmlspecialchars($activationMessage, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="ps-form__submit">
                                <a class="ps-btn ps-btn--warning" href="<?php echo $loginUrl; ?>">Go to Login</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include('common/footer.php'); ?>
    </div>

    <script src="<?php echo site_url(); ?>plugins/jquery.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/popper.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/bootstrap4/js/bootstrap.min.js"></script>
    <script src="<?php echo site_url(); ?>js/main.js"></script>
</body>
</html>