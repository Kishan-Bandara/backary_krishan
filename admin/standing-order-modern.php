<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');

$db = new Database();

function getCustomers() {
    try { $db = new Database(); return $db->getRows('SELECT customer_id, customer_name FROM customer ORDER BY customer_name ASC'); } catch(Exception $e){ return []; }
}
function getCategories() {
    try { $db = new Database(); return $db->getRows('SELECT category_id, category_name FROM category_master ORDER BY category_name ASC'); } catch(Exception $e){ return []; }
}
function getItemsByCategory($categoryId) {
    try {
        $db = new Database();
        $hasAllowInSalesColumn = (bool) $db->getRow("SHOW COLUMNS FROM item_master LIKE 'allow_in_sales'");
        $query = 'SELECT item_id, item_name, item_normal_selling_price FROM item_master WHERE item_category = ? AND item_active = "Y"';
        if ($hasAllowInSalesColumn) {
            $query .= ' AND (allow_in_sales = 1 OR allow_in_sales IS NULL)';
        }
        $query .= ' ORDER BY item_name ASC';
        return $db->getRows($query, [$categoryId]);
    } catch(Exception $e){
        return [];
    }
} 
$customers = getCustomers();
$categories = getCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Standing Orders (Modern)</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <link rel="stylesheet" href="assets/global/plugins/select2/select2.min.css"/>
    <link rel="stylesheet" href="assets/global/plugins/select2/select2-bootstrap.min.css"/>
    <style>
        :root{ --primary:#5b8def; --bg:#0b1220; --card:#ffffff; --muted:#6b7a90; --accent:#ffcc00; --danger:#e55353; --success:#28a745; }
        .so-hero{ background:linear-gradient(135deg, #111a2b 0%, #1c2b4a 100%); color:#fff; padding:18px 18px; border-radius:10px; margin-bottom:14px; display:flex; align-items:center; gap:16px; }
        .so-hero .chip{ background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.15); padding:6px 10px; border-radius:20px; font-size:12px; }
        .so-hero .title{ font-weight:700; letter-spacing:.3px; }
        .so-hero .actions .btn{ margin-left:8px; }
        .so-card{ background:var(--card); border:1px solid #e8ecf3; border-radius:12px; margin-bottom:16px; box-shadow:0 4px 10px rgba(18,38,63,0.06); }
        .so-card .so-card-head{ display:flex; justify-content:space-between; align-items:center; padding:12px 16px; border-bottom:1px solid #eef2f7; cursor:pointer; }
        .so-card .so-card-head h4{ margin:0; font-size:14px; font-weight:700; color:#26334c; }
        .so-card .so-card-body{ padding:10px 12px 8px; display:none; }
        .so-grid{ width:100%; }
        .so-grid thead th{ position:sticky; top:0; background:#f7f9fc; z-index:2; }
        .so-grid th,.so-grid td{ padding:8px; vertical-align:middle; }
        .so-qty{ width:64px; height:36px; padding:6px 10px; border:1px solid #d8dde6; border-radius:6px; text-align:center; }
        .so-row{ transition:background .2s; }
        .so-row:hover{ background:#fbfdff; }
        .so-remove{ color:var(--danger); cursor:pointer; }
        .so-summary{ position:sticky; bottom:0; background:#0f1830; color:#fff; padding:10px 14px; border-radius:10px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 -8px 20px rgba(0,0,0,0.2); }
        .badge-pill{ border-radius:20px; padding:6px 10px; font-weight:700; }
        .badge-accent{ background:var(--accent); color:#000; }
        .badge-success{ background:var(--success); }
        .soft{ color:var(--muted); font-size:12px; }
        .select2-container--bootstrap .select2-selection--single{ height:36px; line-height:36px; border-radius:8px; }
        .qa-inline{ display:flex; align-items:center; gap:6px; }
        .qa-inline .select2{ min-width:260px; }
        .qa-inline .btn{ height:36px; }
        @media(max-width:992px){ .qa-inline{ flex-direction:column; align-items:stretch; } .qa-inline .select2{ width:100%!important; } }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white page-sidebar-closed">
<?php include('common/manubar.php'); ?>
<div class="clearfix"></div>
<div class="page-container">
    <div class="page-sidebar-wrapper"> <?php include('common/sidebar.php'); ?> </div>
    <div class="page-content-wrapper">
        <div class="page-content">
            <div class="so-hero">
                <div class="title">Standing Orders</div>
                <span class="chip">GFP 12:30</span>
                <span class="chip">Strada 16:00</span>
                <div style="flex:1"></div>
                <div class="actions">
                    <a href="javascript:void(0)" id="save" class="btn btn-success"><i class="fa fa-check"></i> Save</a>
                    <a href="index.php" class="btn btn-default">Cancel</a>
                </div>
            </div>

            <div class="so-card">
                <div class="so-card-body">
                    <div class="qa-inline" style="margin-bottom:8px;">
                        <select id="customer" class="select2 form-control" data-placeholder="Select customer">
                            <option></option>
                            <?php foreach($customers as $c): ?>
                                <option value="<?php echo (int)$c['customer_id']; ?>"><?php echo htmlspecialchars($c['customer_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="qaItem" class="select2 form-control" data-placeholder="Search product">
                            <option></option>
                            <?php foreach ($categories as $cat): $items = getItemsByCategory($cat['category_id']); if(count($items)>0): ?>
                                <optgroup label="<?php echo htmlspecialchars($cat['category_name']); ?>">
                                    <?php foreach($items as $it): ?>
                                        <option data-price="<?php echo (float)$it['item_normal_selling_price']; ?>" value="<?php echo (int)$it['item_id']; ?>"><?php echo htmlspecialchars($it['item_name']); ?> (<?php echo number_format((float)$it['item_normal_selling_price'],2); ?>)</option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; endforeach; ?>
                        </select>
                        <?php $days=['Mon','Tue','Wed','Thu','Fri','Sat','Sun']; foreach($days as $d): ?>
                            <input type="number" min="0" class="so-qty qa" placeholder="<?php echo $d; ?>">
                        <?php endforeach; ?>
                        <a id="add" class="btn btn-warning"><i class="fa fa-plus"></i> Add</a>
                    </div>
                </div>
            </div>

            <?php foreach($categories as $cat): $items=getItemsByCategory($cat['category_id']); if(count($items)==0) continue; ?>
            <div class="so-card" data-cat="<?php echo (int)$cat['category_id']; ?>">
                <div class="so-card-head">
                    <h4><?php echo htmlspecialchars($cat['category_name']); ?></h4>
                    <div class="soft">Weekly Qty: <span class="cat-week">0</span> • Cost: <span class="cat-cost">0.00</span></div>
                </div>
                <div class="so-card-body">
                    <div class="table-responsive">
                        <table class="table so-grid">
                            <thead>
                                <tr>
                                    <th style="width:38%">Item</th>
                                    <th class="text-center">Mon</th><th class="text-center">Tue</th><th class="text-center">Wed</th>
                                    <th class="text-center">Thu</th><th class="text-center">Fri</th><th class="text-center">Sat</th><th class="text-center">Sun</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-right" style="width:110px;">Cost</th>
                                    <th class="text-center" style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($items as $it): $price=(float)$it['item_normal_selling_price']; ?>
                                <tr class="so-row" data-item-id="<?php echo (int)$it['item_id']; ?>" data-price="<?php echo $price; ?>">
                                    <td>
                                        <div><strong><?php echo htmlspecialchars($it['item_name']); ?></strong></div>
                                        <div class="soft">Unit: <?php echo number_format($price,2); ?></div>
                                    </td>
                                    <?php foreach($days as $d): ?>
                                        <td class="text-center"><input type="number" min="0" class="so-qty day" value="0"></td>
                                    <?php endforeach; ?>
                                    <td class="text-center row-total">0</td>
                                    <td class="text-right row-cost">0.00</td>
                                    <td class="text-center"><i class="fa fa-trash so-remove" title="Remove"></i></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="so-summary">
                <div>Total week qty: <span id="sumQty" class="badge badge-pill badge-success">0</span></div>
                <div>Grand cost: <span id="sumCost" class="badge badge-pill badge-accent">0.00</span></div>
            </div>
        </div>
    </div>
</div>

<script src="assets/global/plugins/jquery.min.js"></script>
<script src="assets/global/plugins/select2/select2.full.min.js"></script>
<script>
(function(){
    function fmt(n){ return Number(n).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}); }
    function recalcRow($tr){
        var price=parseFloat($tr.data('price')||0);
        var qty=0; $tr.find('input.day').each(function(){ qty += parseFloat(this.value)||0; });
        $tr.find('.row-total').text(qty);
        $tr.find('.row-cost').text(fmt(qty*price));
        recalcTotals();
    }
    function recalcCat($card){
        var week=0,cost=0; $card.find('tbody tr').each(function(){
            var $tr=$(this), price=parseFloat($tr.data('price')||0), q=0; $tr.find('input.day').each(function(){ q+=parseFloat(this.value)||0; });
            week+=q; cost+=q*price;
        });
        $card.find('.cat-week').text(week);
        $card.find('.cat-cost').text(fmt(cost));
    }
    function recalcTotals(){
        var week=0,cost=0; $('.so-row').each(function(){
            var $tr=$(this), price=parseFloat($tr.data('price')||0), q=0; $tr.find('input.day').each(function(){ q+=parseFloat(this.value)||0; });
            week+=q; cost+=q*price;
        });
        $('#sumQty').text(week); $('#sumCost').text(fmt(cost));
        $('.so-card').each(function(){ recalcCat($(this)); });
    }

    $(document).on('input','input.day',function(){ recalcRow($(this).closest('tr')); });

    $('.so-card-head').on('click', function(){
        var $body=$(this).next('.so-card-body');
        $body.slideToggle(150);
    }).each(function(){ $(this).next('.so-card-body').show(); });

    if($.fn.select2){
        var $parent=$('.page-content');
        $('#customer').select2({ theme:'bootstrap', width:'resolve', allowClear:true, placeholder:$('#customer').data('placeholder')||'Select customer', dropdownParent:$parent });
        $('#qaItem').select2({ theme:'bootstrap', width:'resolve', allowClear:true, placeholder:$('#qaItem').data('placeholder')||'Search product', dropdownParent:$parent });
    }
    
    // Load existing standing order on customer change - bind AFTER select2 init
    $('#customer').on('change select2:select', function(){
        var cid = $(this).val();
        console.log('Customer changed:', cid);
        
        // Clear all first
        $('input.day').val('0');
        recalcTotals();
        if(!cid) return;
        
        // Show loading indicator
        var $customerSelect = $('#customer');
        $customerSelect.prop('disabled', true);
        
        fetch('process/get-standing-order.php?customer_id='+encodeURIComponent(cid))
            .then(function(r){ return r.json(); })
            .then(function(j){
                console.log('Standing order response:', j);
                $customerSelect.prop('disabled', false);
                if(!j || j.status!=='success' || !j.data) {
                    console.log('No valid response');
                    return;
                }
                
                var items = j.data.items || [];
                if(items.length === 0) {
                    // No existing standing order
                    alert('No existing standing order found for this customer. You can create a new one.');
                    return;
                }
                
                // Load the items
                var loadedCount = 0;
                items.forEach(function(row){
                    var $tr = $('.so-row[data-item-id="'+row.item_id+'"]');
                    if($tr.length===0) return;
                    
                    $tr.find('input.day').each(function(ix){
                        var v = (row.qty && typeof row.qty[ix] !== 'undefined') ? row.qty[ix] : 0;
                        this.value = v;
                    });
                    recalcRow($tr);
                    
                    // Check if any qty > 0, then expand the category card
                    var hasQty = row.qty.some(function(q){ return q > 0; });
                    if(hasQty) {
                        var $card = $tr.closest('.so-card');
                        var $body = $card.find('.so-card-body');
                        if(!$body.is(':visible')) {
                            $body.slideDown(150);
                        }
                        loadedCount++;
                    }
                });
                
                recalcTotals();
                
                if(loadedCount > 0) {
                    alert('Standing order loaded! ' + loadedCount + ' product(s) with quantities. You can now amend and save.');
                }
            })
            .catch(function(e){
                console.error('Failed to load standing order', e);
                $customerSelect.prop('disabled', false);
            });
    });

    $('#add').on('click', function(){
        var $sel=$('#qaItem'); if(!$sel.val()){ alert('Select item'); return; }
        var price=parseFloat($sel.find('option:selected').data('price')||0); var name=$sel.find('option:selected').text();
        var $targetCard=$('.so-card').first(); if($sel.find('option:selected').parent('optgroup').length){
            var label=$sel.find('option:selected').parent('optgroup').attr('label');
            $('.so-card').each(function(){ if($(this).find('.so-card-head h4').text()===label){ $targetCard=$(this); return false; } });
        }
        var $tbody=$targetCard.find('tbody');
        var html='<tr class="so-row" data-item-id="'+$sel.val()+'" data-price="'+price+'">';
        html += '<td><div><strong>'+name+'</strong></div><div class="soft">Unit: '+fmt(price)+'</div></td>';
        for(var i=0;i<7;i++){ html += '<td class="text-center"><input type="number" min="0" class="so-qty day" value="0"></td>'; }
        html += '<td class="text-center row-total">0</td><td class="text-right row-cost">0.00</td><td class="text-center"><i class="fa fa-trash so-remove" title="Remove"></i></td></tr>';
        var $tr=$(html).appendTo($tbody); recalcRow($tr);
    });

    $(document).on('click','.so-remove', function(){ var $tr=$(this).closest('tr'); $tr.remove(); recalcTotals(); });

    $('#save').on('click', function(){
        var customerId=$('#customer').val(); if(!customerId){ alert('Select a customer'); return; }
        var data=[]; $('.so-row').each(function(){
            var $tr=$(this), obj={ item_id:$tr.data('item-id'), price:parseFloat($tr.data('price')||0), qty:[] };
            $tr.find('input.day').each(function(){ obj.qty.push(parseFloat(this.value)||0); }); data.push(obj);
        });
        var payload={ customer_id:customerId, items:data };
        fetch('process/save-standing-order.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) })
            .then(r=>r.text())
            .then(t=>{
                let j=null; try{ j=JSON.parse(t); }catch(e){}
                if(!j){ alert('Failed to save: '+(t ? t.substring(0,200) : 'No response')); return; }
                alert(j.message||'Saved');
            })
            .catch(e=>alert('Failed to save: '+(e && e.message ? e.message : 'Unknown error')));
    });

})();
</script>
</body>
</html>



