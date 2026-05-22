<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('include/database.php');
include('include/check_login.php');
include('include/delivery_rules.php');

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$db = new Database();
ensureDeliveryRulesSchema($db);

$message = '';
$messageClass = 'alert-success';

// Single rule delete
if (isset($_GET['deleteID'])) {
    $deleteId = (int) $_GET['deleteID'];
    if ($deleteId > 0) {
        if (deleteDeliveryRule($deleteId)) {
            header('Location: manage-delivery-rules.php?success=deleted');
            exit();
        } else {
            $message = 'Unable to delete rule.';
            $messageClass = 'alert-danger';
        }
    }
}

// Save (handles global settings + every rule + new rules + deletions in one shot)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_all'])) {
    $errors = [];

    // 1) global settings
    saveDeliveryRuleSettings([
        'apply_to' => $_POST['apply_to'] ?? 'gross',
        'weekly_avg_free_delivery' => trim($_POST['weekly_avg_free_delivery'] ?? ''),
        'standing_order_daily_avg_min' => trim($_POST['standing_order_daily_avg_min'] ?? ''),
        'min_cart_order' => trim($_POST['min_cart_order'] ?? ''),
    ]);

    // 2) deletes (rule ids submitted in delete_rule_ids[])
    $deleteIds = $_POST['delete_rule_ids'] ?? [];
    if (is_array($deleteIds)) {
        foreach ($deleteIds as $delId) {
            $delId = (int) $delId;
            if ($delId > 0) {
                deleteDeliveryRule($delId);
            }
        }
    }

    // 3) save existing + new rules. Each rule entry is keyed by an index.
    //    rule_id[idx], rule_name[idx], tier_threshold[idx][], tier_price[idx][]
    $ruleIds = $_POST['rule_id'] ?? [];
    $ruleNames = $_POST['rule_name'] ?? [];
    $tierThresholds = $_POST['tier_threshold'] ?? [];
    $tierPrices = $_POST['tier_price'] ?? [];
    $deletedSet = [];
    foreach ($deleteIds as $d) { $deletedSet[(int)$d] = true; }

    $sortOrder = 0;
    foreach ($ruleNames as $idx => $name) {
        $name = trim((string) $name);
        $existingId = (int) ($ruleIds[$idx] ?? 0);
        if ($existingId > 0 && isset($deletedSet[$existingId])) {
            // already deleted above
            continue;
        }
        if ($name === '') {
            // skip blank rows entirely (allows users to clear a brand-new unsaved rule)
            if ($existingId === 0) {
                continue;
            }
            $errors[] = 'Rule name is required.';
            continue;
        }

        $thresholds = $tierThresholds[$idx] ?? [];
        $prices = $tierPrices[$idx] ?? [];
        $tiers = [];
        $count = max(count((array)$thresholds), count((array)$prices));
        for ($i = 0; $i < $count; $i++) {
            $tH = trim((string) ($thresholds[$i] ?? ''));
            $tP = trim((string) ($prices[$i] ?? ''));
            if ($tH === '' && $tP === '') {
                continue; // blank row
            }
            if (!is_numeric($tH) || !is_numeric($tP)) {
                $errors[] = 'Rule "' . $name . '" has invalid tier values.';
                continue;
            }
            $tiers[] = [
                'invoice_larger_than' => (float) $tH,
                'price' => (float) $tP,
            ];
        }

        if (empty($tiers)) {
            $errors[] = 'Rule "' . $name . '" must have at least one tier.';
            continue;
        }

        $newId = saveDeliveryRule($existingId, $name, $tiers, 1, $sortOrder);
        if ($newId === 0) {
            $errors[] = 'Unable to save rule "' . $name . '".';
        }
        $sortOrder++;
    }

    if (empty($errors)) {
        header('Location: manage-delivery-rules.php?success=saved');
        exit();
    }
    $message = implode('<br>', array_map('h', $errors));
    $messageClass = 'alert-danger';
}

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'saved') {
        $message = 'Delivery rules saved successfully.';
    } elseif ($_GET['success'] === 'deleted') {
        $message = 'Delivery rule deleted successfully.';
    }
}

$settings = getDeliveryRuleSettings();
$rules = getDeliveryRulesWithTiers(false);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Delivery Rules | STOCK MANAGEMENT SYSTEM</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        .delivery-rules-wrap { max-width: 760px; margin: 0 auto; }
        .delivery-rule-panel {
            background: #fff;
            border: 1px solid #e7eaec;
            border-radius: 4px;
            margin-bottom: 12px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .delivery-rule-header {
            padding: 14px 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #1f8fcf;
            font-weight: 600;
            font-size: 15px;
        }
        .delivery-rule-header.system { color: #2ec4a5; }
        .delivery-rule-header .caret-icon { margin-right: 10px; transition: transform .15s; }
        .delivery-rule-header .caret-icon.open { transform: rotate(90deg); }
        .delivery-rule-body { padding: 0 22px 20px 22px; display: none; border-top: 1px solid #f1f3f4; }
        .delivery-rule-body.open { display: block; }
        .tier-row { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
        .tier-row .tier-label { display: flex; flex-direction: column; flex: 1; }
        .tier-row .tier-arrow { font-weight: 700; color: #999; padding: 0 6px; }
        .tier-row .input-group-addon { background: #eee; }
        .tier-row .btn-remove-tier { color: #1f8fcf; border: none; background: transparent; }
        .tier-row .btn-remove-tier:hover { color: #c0392b; }
        .rule-delete-btn { color: #1f8fcf; background: transparent; border: none; font-size: 16px; }
        .rule-delete-btn:hover { color: #c0392b; }
        .tier-headings { display: flex; gap: 12px; margin-bottom: 6px; color: #555; font-weight: 600; font-size: 12px; text-transform: uppercase; padding: 0 4px; }
        .tier-headings span { flex: 1; text-align: left; }
        .tier-headings .arrow-spacer { width: 22px; flex: 0 0 22px; }
        .rules-box { background: #fafbfc; padding: 16px 18px; border: 1px solid #f1f3f4; border-radius: 4px; }
        .system-row { display: flex; align-items: center; gap: 16px; margin-bottom: 12px; flex-wrap: wrap; }
        .system-row label.col-form { flex: 0 0 220px; font-weight: 600; color: #444; margin-bottom: 0; }
        .system-row .control-wrap { flex: 1; min-width: 220px; }
        .input-money { max-width: 220px; }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
<?php include('common/manubar.php'); ?>
<div class="clearfix"></div>
<div class="page-container">
    <div class="page-sidebar-wrapper">
        <?php include('common/sidebar.php'); ?>
    </div>
    <div class="page-content-wrapper">
        <div class="page-content">
            <div class="page-bar">
                <ul class="page-breadcrumb">
                    <li><a href="index.php">Home</a><i class="fa fa-circle"></i></li>
                    <li><span>Delivery Rules</span></li>
                </ul>
            </div>

            <form id="deliveryRulesForm" action="manage-delivery-rules.php" method="POST" novalidate>
                <input type="hidden" name="save_all" value="1">
                <input type="hidden" name="delete_rule_ids" id="delete_rule_ids_holder" value="">
                <div id="deletedRuleIds"></div>

                <div class="page-bar" style="background: transparent; border: 0; padding: 0; margin-bottom: 12px;">
                    <div style="display:flex; align-items:center; justify-content:space-between;">
                        <h3 class="page-title" style="margin:0;">Delivery Rules</h3>
                        <div>
                            <button type="button" class="btn blue" id="btnCreateNewRule"><i class="fa fa-plus"></i> Create New Rule</button>
                            <button type="submit" class="btn green"><i class="fa fa-check"></i> Save</button>
                        </div>
                    </div>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert <?php echo h($messageClass); ?> alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                        <?php echo $message; /* errors already escaped */ ?>
                    </div>
                <?php endif; ?>

                <div class="delivery-rules-wrap">

                    <!-- System panel: Delivery Rules (apply-to + weekly average) -->
                    <div class="delivery-rule-panel">
                        <div class="delivery-rule-header system" data-toggle-body>
                            <span><i class="fa fa-angle-right caret-icon"></i> Delivery Rules</span>
                        </div>
                        <div class="delivery-rule-body">
                            <div class="rules-box">
                                <div class="system-row">
                                    <label class="col-form">Apply Delivery Rules to</label>
                                    <div class="control-wrap">
                                        <label class="radio-inline">
                                            <input type="radio" name="apply_to" value="net" <?php echo ($settings['apply_to'] === 'net') ? 'checked' : ''; ?>> Net Order Total
                                        </label>
                                        <label class="radio-inline">
                                            <input type="radio" name="apply_to" value="gross" <?php echo ($settings['apply_to'] !== 'net') ? 'checked' : ''; ?>> Gross Order Total
                                        </label>
                                    </div>
                                </div>
                                <div class="system-row">
                                    <label class="col-form">Weekly average for free delivery</label>
                                    <div class="control-wrap">
                                        <div class="input-group input-money">
                                            <span class="input-group-addon">$</span>
                                            <input type="number" step="0.01" min="0" class="form-control" name="weekly_avg_free_delivery" value="<?php echo h($settings['weekly_avg_free_delivery']); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System panel: Minimum spend for delivery -->
                    <div class="delivery-rule-panel">
                        <div class="delivery-rule-header system" data-toggle-body>
                            <span><i class="fa fa-angle-right caret-icon"></i> Minimum spend for delivery (or daily average)</span>
                        </div>
                        <div class="delivery-rule-body">
                            <div class="rules-box">
                                <div class="system-row">
                                    <label class="col-form">Standing Order Daily Average</label>
                                    <div class="control-wrap">
                                        <div class="input-group input-money">
                                            <span class="input-group-addon">$</span>
                                            <input type="number" step="0.01" min="0" class="form-control" name="standing_order_daily_avg_min" value="<?php echo h($settings['standing_order_daily_avg_min']); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="system-row">
                                    <label class="col-form">Minimum Cart Order</label>
                                    <div class="control-wrap">
                                        <div class="input-group input-money">
                                            <span class="input-group-addon">$</span>
                                            <input type="number" step="0.01" min="0" class="form-control" name="min_cart_order" value="<?php echo h($settings['min_cart_order']); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User-created rules -->
                    <div id="userRulesContainer">
                        <?php foreach ($rules as $idx => $rule): ?>
                            <?php
                            $tiers = $rule['tiers'];
                            if (empty($tiers)) { $tiers = [['invoice_larger_than' => 0, 'price' => 0]]; }
                            ?>
                            <div class="delivery-rule-panel rule-card" data-rule-index="<?php echo (int)$idx; ?>">
                                <div class="delivery-rule-header" data-toggle-body>
                                    <span class="rule-title"><i class="fa fa-angle-right caret-icon"></i> <span class="rule-title-text"><?php echo h($rule['name']); ?></span></span>
                                    <button type="button" class="rule-delete-btn btn-delete-rule" title="Delete rule"><i class="fa fa-trash-o"></i></button>
                                </div>
                                <div class="delivery-rule-body">
                                    <input type="hidden" name="rule_id[<?php echo (int)$idx; ?>]" value="<?php echo (int)$rule['id']; ?>">
                                    <div class="form-group" style="margin-top: 14px;">
                                        <label style="font-weight:600;color:#444;">Rule name</label>
                                        <input type="text" class="form-control rule-name-input" name="rule_name[<?php echo (int)$idx; ?>]" value="<?php echo h($rule['name']); ?>" placeholder="Name" required style="max-width: 320px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="font-weight:600;color:#444;">Rules</label>
                                        <div class="rules-box">
                                            <div class="tier-headings">
                                                <span>Invoice larger than</span>
                                                <span class="arrow-spacer"></span>
                                                <span>Price</span>
                                                <span style="flex:0 0 28px;"></span>
                                            </div>
                                            <div class="tier-rows">
                                                <?php foreach ($tiers as $t): ?>
                                                <div class="tier-row">
                                                    <div class="tier-label">
                                                        <div class="input-group">
                                                            <span class="input-group-addon">$</span>
                                                            <input type="number" step="0.01" min="0" class="form-control" name="tier_threshold[<?php echo (int)$idx; ?>][]" value="<?php echo h($t['invoice_larger_than']); ?>" required>
                                                        </div>
                                                    </div>
                                                    <span class="tier-arrow">=&gt;</span>
                                                    <div class="tier-label">
                                                        <div class="input-group">
                                                            <span class="input-group-addon">$</span>
                                                            <input type="number" step="0.01" min="0" class="form-control" name="tier_price[<?php echo (int)$idx; ?>][]" value="<?php echo h($t['price']); ?>" required>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn-remove-tier" title="Remove level"><i class="fa fa-trash-o"></i></button>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div style="text-align:right; margin-top: 8px;">
                                                <button type="button" class="btn green btn-sm btn-add-tier"><i class="fa fa-plus"></i> Add level</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            </form>

        </div>
    </div>
</div>

<!-- Hidden template for a brand new rule -->
<template id="newRuleTemplate">
    <div class="delivery-rule-panel rule-card" data-rule-index="__INDEX__" data-new-rule="1">
        <div class="delivery-rule-header" data-toggle-body>
            <span class="rule-title"><i class="fa fa-angle-down caret-icon open"></i> <span class="rule-title-text">New Rule Name</span> <span class="text-warning" style="font-weight:400;">(new)</span></span>
            <button type="button" class="rule-delete-btn btn-delete-rule" title="Delete rule"><i class="fa fa-trash-o"></i></button>
        </div>
        <div class="delivery-rule-body open">
            <input type="hidden" name="rule_id[__INDEX__]" value="0">
            <div class="form-group" style="margin-top: 14px;">
                <label style="font-weight:600;color:#444;">Rule name</label>
                <input type="text" class="form-control rule-name-input" name="rule_name[__INDEX__]" value="" placeholder="Name" required style="max-width: 320px;">
            </div>
            <div class="form-group">
                <label style="font-weight:600;color:#444;">Rules</label>
                <div class="rules-box">
                    <div class="tier-headings">
                        <span>Invoice larger than</span>
                        <span class="arrow-spacer"></span>
                        <span>Price</span>
                        <span style="flex:0 0 28px;"></span>
                    </div>
                    <div class="tier-rows">
                        <div class="tier-row">
                            <div class="tier-label">
                                <div class="input-group"><span class="input-group-addon">$</span><input type="number" step="0.01" min="0" class="form-control" name="tier_threshold[__INDEX__][]" value="0" required></div>
                            </div>
                            <span class="tier-arrow">=&gt;</span>
                            <div class="tier-label">
                                <div class="input-group"><span class="input-group-addon">$</span><input type="number" step="0.01" min="0" class="form-control" name="tier_price[__INDEX__][]" value="1.00" required></div>
                            </div>
                            <button type="button" class="btn-remove-tier" title="Remove level"><i class="fa fa-trash-o"></i></button>
                        </div>
                    </div>
                    <div style="text-align:right; margin-top: 8px;">
                        <button type="button" class="btn green btn-sm btn-add-tier"><i class="fa fa-plus"></i> Add level</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Hidden tier-row template -->
<template id="newTierRowTemplate">
    <div class="tier-row">
        <div class="tier-label">
            <div class="input-group"><span class="input-group-addon">$</span><input type="number" step="0.01" min="0" class="form-control" name="__THRESHOLD_NAME__" value="" required></div>
        </div>
        <span class="tier-arrow">=&gt;</span>
        <div class="tier-label">
            <div class="input-group"><span class="input-group-addon">$</span><input type="number" step="0.01" min="0" class="form-control" name="__PRICE_NAME__" value="" required></div>
        </div>
        <button type="button" class="btn-remove-tier" title="Remove level"><i class="fa fa-trash-o"></i></button>
    </div>
</template>

<?php include('common/footer.php'); ?>
<script>
$(function () {
    var nextRuleIndex = <?php echo count($rules); ?>;
    var deletedIds = [];

    function refreshDeletedHidden() {
        $('#delete_rule_ids_holder').remove();
        var $form = $('#deliveryRulesForm');
        deletedIds.forEach(function (id) {
            $form.append('<input type="hidden" name="delete_rule_ids[]" value="' + id + '">');
        });
    }

    // Toggle accordion
    $(document).on('click', '[data-toggle-body]', function (e) {
        if ($(e.target).closest('.rule-delete-btn').length) return;
        var $panel = $(this).closest('.delivery-rule-panel');
        var $body = $panel.find('.delivery-rule-body').first();
        var $caret = $(this).find('.caret-icon').first();
        $body.toggleClass('open');
        if ($body.hasClass('open')) {
            $body.show();
            $caret.addClass('open');
        } else {
            $body.hide();
            $caret.removeClass('open');
        }
    });

    // Add new rule
    $('#btnCreateNewRule').on('click', function () {
        var tplHtml = $('#newRuleTemplate').html().replace(/__INDEX__/g, nextRuleIndex);
        var $node = $(tplHtml);
        $('#userRulesContainer').append($node);
        $node.find('.delivery-rule-body').show();
        nextRuleIndex++;
        $node.find('.rule-name-input').first().focus();
    });

    // Delete rule
    $(document).on('click', '.btn-delete-rule', function (e) {
        e.stopPropagation();
        var $card = $(this).closest('.rule-card');
        if (!confirm('Delete this rule?')) return;
        var ruleId = parseInt($card.find('input[name^="rule_id"]').val(), 10) || 0;
        if (ruleId > 0) {
            deletedIds.push(ruleId);
            refreshDeletedHidden();
        }
        $card.remove();
    });

    // Add tier level
    $(document).on('click', '.btn-add-tier', function () {
        var $card = $(this).closest('.rule-card');
        var ruleIndex = $card.data('rule-index');
        var tplHtml = $('#newTierRowTemplate').html()
            .replace(/__THRESHOLD_NAME__/g, 'tier_threshold[' + ruleIndex + '][]')
            .replace(/__PRICE_NAME__/g, 'tier_price[' + ruleIndex + '][]');
        $card.find('.tier-rows').append(tplHtml);
    });

    // Remove tier level (keep at least one)
    $(document).on('click', '.btn-remove-tier', function () {
        var $row = $(this).closest('.tier-row');
        var $rows = $row.parent();
        if ($rows.find('.tier-row').length <= 1) {
            // clear inputs instead
            $row.find('input[type="number"]').val('');
            return;
        }
        $row.remove();
    });

    // Live update title text
    $(document).on('input', '.rule-name-input', function () {
        var name = $(this).val() || 'New Rule Name';
        $(this).closest('.rule-card').find('.rule-title-text').text(name);
    });
});
</script>
</body>
</html>
