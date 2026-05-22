<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
if (isset($_SESSION['LoginStatus'])) {
    $LoginStatus = $_SESSION['LoginStatus'];
} else {
    $LoginStatus = "";
}
if ($LoginStatus == "login_success") {
    Redirect('index.php', false);
}
function filter($var)
{

    return preg_replace('[0-9]', ' ', $var);
}

?>
<!DOCTYPE html>
<html lang="en">


<head>
    <?php include('common/styles.php'); ?>
    <style>
        .ps-account {
            padding: 80px 0;
            background-color: #fafafa;
        }
        .auth-card {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 50px 40px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }
        .auth-card__title {
            font-family: 'Playfair Display', 'Georgia', serif;
            font-size: 32px;
            font-weight: 400;
            text-align: center;
            letter-spacing: 0.02em;
            color: #111;
            margin-bottom: 30px;
            text-transform: uppercase;
        }
        .auth-card__legend {
            font-size: 20px;
            font-family: 'Playfair Display', 'Georgia', serif;
            color: #111;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 30px 0 20px;
            border-bottom: 2px solid #e5e5e5;
            padding-bottom: 10px;
        }
        .auth-card .ps-form__group {
            margin-bottom: 20px;
        }
        .auth-card .ps-form__label {
            font-size: 13px;
            font-weight: 600;
            color: #111;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
            display: block;
        }
        .auth-card .ps-form__input {
            height: 48px;
            border: 1px solid #d1d1d1;
            border-radius: 0;
            font-size: 15px;
            color: #111;
            padding: 0 15px;
            background: #fafafa;
            transition: all 0.2s ease;
        }
        .auth-card .ps-form__input:focus {
            border-color: #111;
            background: #fff;
            box-shadow: none;
        }
        .auth-card .ps-btn--warning {
            width: 100%;
            height: 50px;
            background: #111;
            color: #fff;
            border: none;
            border-radius: 0;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        .auth-card .ps-btn--warning:hover {
            background: #333;
            color: #fff;
            cursor: pointer;
        }
        .auth-card__divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }
        .auth-card__divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            border-top: 1px solid #e5e5e5;
            z-index: 1;
        }
        .auth-card__divider span {
            position: relative;
            z-index: 2;
            background: #fff;
            padding: 0 15px;
            color: #6b6b6b;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .auth-card__login-btn {
            display: block;
            width: 100%;
            height: 50px;
            line-height: 48px;
            text-align: center;
            border: 1px solid #111;
            color: #111;
            background: transparent;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .auth-card__login-btn:hover {
            background: #111;
            color: #fff;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="ps-page">
        <?php include('common/header.php'); ?>
        <div class="ps-account">
            <div class="container">
                <div class="auth-card">
                    <form action="#" method="post" id="frn-reg" enctype="multipart/form-data">
                        <h2 class="auth-card__title">Register Account</h2>
                        
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="ps-form__group">
                                    <label class="ps-form__label">First Name</label>
                                    <input type="text" name="firstname" value="" placeholder="First Name" id="input-firstname" class="form-control ps-form__input" required>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="ps-form__group">
                                    <label class="ps-form__label">Last Name</label>
                                    <input type="text" name="lastname" value="" placeholder="Last Name" id="input-lastname" class="form-control ps-form__input" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="ps-form__group" style="display:none;">
                            <label class="ps-form__label">NIC</label>
                            <input type="text" name="nic" value="" placeholder="Nic" class="form-control ps-form__input">
                        </div>
                        
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="ps-form__group">
                                    <label class="ps-form__label">Landline</label>
                                    <input type="text" name="landline" value="" placeholder="Landline" class="form-control ps-form__input">
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="ps-form__group">
                                    <label class="ps-form__label">Mobile *</label>
                                    <input type="text" name="telephone" value="" placeholder="Telephone" id="input-telephone" class="form-control ps-form__input" required>
                                </div>
                            </div>
                        </div>

                        <h3 class="auth-card__legend">Login Details</h3>
                        
                        <div class="ps-form__group">
                            <label class="ps-form__label">Email *</label>
                            <input type="email" name="email" value="" placeholder="Email Address" id="input-email" class="form-control ps-form__input" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="ps-form__group">
                                    <label class="ps-form__label">Password *</label>
                                    <input type="password" name="password" value="" placeholder="Password" id="password" data-minlength="6" class="form-control ps-form__input" required>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="ps-form__group">
                                    <label class="ps-form__label">Confirm Password *</label>
                                    <input type="password" name="confirm" value="" placeholder="Confirm Password" id="inputPasswordConfirm" data-match="#password" required data-match-error="Whoops, these don't match" class="form-control ps-form__input">
                                </div>
                            </div>
                        </div>
                        
                        <div class="ps-form__submit">
                            <button type="submit" class="ps-btn ps-btn--warning">Register</button>
                        </div>
                    </form>

                    <div class="auth-card__divider">
                        <span>Already have an account?</span>
                    </div>
                    
                    <a href="login.php" class="auth-card__login-btn">Log In Now</a>
                </div>
            </div>
        </div>
        <?php include('common/footer.php'); ?>
    </div>


    <script data-cfasync="false" src="../../cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/jquery.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/popper.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/bootstrap4/js/bootstrap.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/select2/dist/js/select2.full.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/owl-carousel/owl.carousel.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/jquery-bar-rating/dist/jquery.barrating.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/lightGallery/dist/js/lightgallery-all.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/slick/slick/slick.min.js"></script>
    <script src="<?php echo site_url(); ?>plugins/noUiSlider/nouislider.min.js"></script>
    <!-- custom code-->
    <script src="<?php echo site_url(); ?>js/main.js"></script>
</body>


</html>
<script type="text/javascript">
    $(document).ready(function() {
        $(document).on('submit', '#frn-reg', function() {

            var data = $(this).serialize();


            $.ajax({

                type: 'POST',
                url: 'process/customer-register.php',
                data: data,
                success: function(data) {

                    var jsonobj;
                    try {
                        jsonobj = JSON.parse(data);
                    } catch (e) {
                        alert('Unexpected response from server.');
                        return;
                    }

                    var output = jsonobj.email_message || jsonobj.message || 'Registration response received.';
                    var status = jsonobj.status === true || jsonobj.status === 1;

                    if (status) {
                        $("#email_message").html(output);
                        $("#email_title").html(jsonobj.email_title || 'Registration');
                        $('#email_confirom_model').modal('show');
                        setTimeout(function() { window.location.href = 'index.php'; }, 4000);
                    } else {
                        alert(output);
                        $("#email_message").html(output);
                        if (jsonobj.email_title) {
                            $("#email_title").html(jsonobj.email_title);
                        }
                    }

                }
            });
            return false;
        });

    });
</script>

<script>
    var password = document.getElementById("password"),
        confirm_password = document.getElementById("inputPasswordConfirm");

    function validatePassword() {
        if (password.value != confirm_password.value) {
            confirm_password.setCustomValidity("Passwords Don't Match");
        } else {
            confirm_password.setCustomValidity('');
        }
    }

    password.onchange = validatePassword;
    confirm_password.onkeyup = validatePassword;
</script>