<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
requirePermission('settings.permissions');
function getImages()
{
  
    $db = new database();
    $query = $db->getRows('SELECT * FROM home_slider ORDER BY Id DESC',);

    return $query;
}


?>

<!DOCTYPE html>

<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<!-- BEGIN HEAD -->


<head>
    <meta charset="utf-8" />
    <title>Configure- Slider</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>
</head>
<!-- END HEAD -->

<body class="page-sidebar-closed-hide-logo page-content-white">
    <?php include('common/manubar.php'); ?>
    <!-- BEGIN HEADER & CONTENT DIVIDER -->
    <div class="clearfix"> </div>
    <!-- END HEADER & CONTENT DIVIDER -->
    <!-- BEGIN CONTAINER -->
    <div class="page-container">
        <div class="page-sidebar-wrapper">
            <?php include('common/sidebar.php'); ?>

        </div>
        <!-- END SIDEBAR -->
        <!-- BEGIN CONTENT -->
        <div class="page-content-wrapper">
            <!-- BEGIN CONTENT BODY -->
            <div class="page-content">
                <!-- BEGIN PAGE HEADER-->

                <!-- BEGIN PAGE BAR -->
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li>
                            <a href="index-2.html">Home</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <a href="#">Tables</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>Datatables</span>
                        </li>
                    </ul>

                </div>
                <!-- END PAGE BAR -->
                <!-- BEGIN PAGE TITLE-->
                <div class="alert <?php echo $MessageClass; ?> alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                    <?php echo $CompanyMessage; ?>
                </div>
                <!-- END PAGE TITLE-->
                <!-- END PAGE HEADER-->

                <div class="row">
                    <div class="col-md-12">
                        <div class="container">
                            <form method="POST" class="horizontal-form" id="frmaddSlider" name="frmaddSlider" enctype="multipart/form-data">
                                <div class="form-body">

                                    <div class="row">

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label>image</label>
                                                <div class="fileinput fileinput-new" data-provides="fileinput">
                                                    <span class="btn btn-default btn-file">
                                                        <span class="fileinput-new">Choose</span>
                                                        <span class="fileinput-exists">Change</span>
                                                        <input type="file" name="uploadMainImage">
                                                    </span>
                                                    <span class="fileinput-filename"></span>
                                                    <a href="#" class="close fileinput-exists" data-dismiss="fileinput" style="float: none">×</a>
                                                </div>

                                            </div>

                                            <div class="form-group">
                                                <label>URL</label>
                                                <input type="text" class="form-control " name="sliderUrl" id="sliderUrl">
                                            </div>
                                            <div class="form-group">
                                                <label>Contain</label>
                                                <textarea class="form-control" rows="3" name="sliderContain" id="sliderContain" spellcheck="true"></textarea>
                                            </div>

                                        </div>
                                        <!--/row-->


                                        <!--/row-->
                                    </div>

                                    <div class="form-actions left">

                                        <button type="submit" name="uploadSlider" class="btn blue">
                                            <i class="fa fa-check"></i> Update photo</button>
                                    </div>
                                </div>
                            </form>
                            <div class="row" style="margin-top:20px;">





                            <div class="table-responsive">
                                                    <table id="images" class="table table-striped table-bordered table-hover">
                                                        <thead>
                                                            <tr>
                                                                <td class="text-left"> Images</td>
                                                                <td class="text-right">Sort Order</td>
                                                                <td></td>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $data = getImages();
                                                            $i = 0;

                                                            foreach ($data as $query) {

                                                            ?>


                                                                <tr id="image-row0">
                                                                    <td class="text-left"><a href="javascript:void(0)" id="thumb-image0" data-toggle="image" class="img-thumbnail"><img src="../<?php echo rtrim($query['path'], '/'); ?>/<?php echo $query['image']; ?>" style="width:100px;"></a>
                                                                        <input type="hidden" name="#" value="catalog/demo/canon_logo.jpg" id="input-image0"></td>
                                                                    <td class="text-right"><input type="text" name="product_image[0][sort_order]" value="0" placeholder="Sort Order" class="form-control"></td>
                                                                    <td class="text-left"><button type="button" class="btn btn-danger" data-original-title="Remove" data-id="<?php echo $query['id']; ?>" id="imageDelete"><i class="fa fa-minus-circle"></i></button></td>
                                                                </tr>
                                                            <?php

                                                            } ?>
                                                        </tbody>

                                                    </table>
                                                </div>



                            </div>
                        </div>
                    </div>

                </div>

            </div>
            <!-- END CONTENT BODY -->
        </div>
        <!-- END CONTENT -->

    </div>
    <!-- END CONTAINER -->
    <?php include('common/footer.php'); ?>
    <!--[if lt IE 9]>
<script src="assets/global/plugins/respond.min.js"></script>
<script src="assets/global/plugins/excanvas.min.js"></script> 
<![endif]-->
    <!-- BEGIN CORE PLUGINS -->
    <script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
    <!-- END CORE PLUGINS -->
    <!-- BEGIN PAGE LEVEL PLUGINS -->
    <script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
    <script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
    <!-- END PAGE LEVEL PLUGINS -->
    <!-- BEGIN THEME GLOBAL SCRIPTS -->
    <script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
    <!-- END THEME GLOBAL SCRIPTS -->
    <!-- BEGIN PAGE LEVEL SCRIPTS -->
    <script src="assets/pages/scripts/table-datatables-responsive.min.js" type="text/javascript"></script>
    <!-- END PAGE LEVEL SCRIPTS -->
    <!-- BEGIN THEME LAYOUT SCRIPTS -->
    <script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
    <script src="assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>
    <script src="assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>
    <!-- END THEME LAYOUT SCRIPTS -->

</body>

</html>
<script>
    $(document).ready(function() {
        $(document).on('submit', '#frmaddSlider', function() {
            var data = $(this).serialize();
            $.ajax({

                type: 'POST',
                url: 'process/add_slider_process.php',
                mimeType: "multipart/form-data",
                data: new FormData(this),
                contentType: false,
                cache: false,
                processData: false,
                success: function(data) {
                    var jsonobj = JSON.parse(data);
                    if (jsonobj.status == true) {
                        location.reload();
                    }
                }
            });
            return false;
        });

    });

    $(document).ready(function() {
        $(document).on('click', '#imageDelete', function() {

           
            var imageId = $(this).data("id");
            $.ajax({

                type: 'POST',
                url: 'process/delete_slider_image_process.php',
                data: {
                    imageId: imageId
                },
                success: function(data) {
                    var jsonobj = JSON.parse(data);
                    
                    if (jsonobj.status == true) {

                        location.reload();
                    }

                }
            });
            return false;
        });

    });
</script>



