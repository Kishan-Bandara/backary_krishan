<html>
<body>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>

<label>Department Name</label></br>

<div class="input-group">
                                                          <span class="input-group-btn">
                                                            <button type="button" class="btn btn-info">Barcode</button>
                                                          </span>
                                                        <input type="text" name="barcode" id="department_name" class="form-control barcode" placeholder="Scan Product Barcode">
                                                     <textarea class="crazytext"></textarea>
                                                    </div>
                                                    <div id="result"></div>
<script type="text/javascript">
$(function() {
    var availableTags = <?php include('search.php'); ?>;
    $("#department_name").autocomplete({
        source: availableTags,
        autoFocus:true
    });
});
</script>

<script>
$(document).ready(function () {
    $('.barcode').keydown(function (event) {
        if (event.keyCode == 13) {
        	event.preventDefault();
            $.ajax({
                url: 'search.php',
                type: 'POST',
                data: {
                    html: $(this).val()
                },
                success: function(result) {
                    $("#result").text(result);
                }
            });
        }
    });
});
</script>

</body>
</html>



