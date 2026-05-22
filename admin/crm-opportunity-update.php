<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('include/database.php');
include('include/check_login.php');
include('include/crm_master.php');

$db = new Database();
crmEnsureSchema($db);

$message = '';
$messageClass = 'alert-success';

$opportunityId = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['opportunity_id'] ?? 0);
$opportunity = $opportunityId > 0 ? crmFetchOpportunity($db, $opportunityId) : null;

if (!$opportunity) {
    $message = 'Opportunity record was not found.';
    $messageClass = 'alert-danger';
}

$salesCycle = $opportunity ? crmFetchSalesCycle($db, (int) ($opportunity['sales_cycle_id'] ?? 0)) : null;
$stages = $salesCycle ? crmFetchSalesCycleStages($db, (int) $salesCycle['sales_cycle_id']) : [];
$currentStage = $opportunity ? crmResolveOpportunityCurrentStage($db, $opportunity) : null;

if (!$currentStage && !empty($stages)) {
    $currentStage = $stages[0];
}

$defaultActionType = 'Current';
if ($currentStage && count($stages) > 1) {
    $lastStage = end($stages);
    reset($stages);
    if ((int) ($currentStage['sales_cycle_stage_id'] ?? 0) !== (int) ($lastStage['sales_cycle_stage_id'] ?? 0)) {
        $defaultActionType = 'Next';
    }
}

$targetStage = ($salesCycle && $currentStage)
    ? crmResolveOpportunityTargetStage($db, (int) $salesCycle['sales_cycle_id'], (int) $currentStage['sales_cycle_stage_id'], $defaultActionType)
    : null;

$formData = [
    'action_type' => $defaultActionType,
    'sales_cycle_stage_id' => (int) ($targetStage['sales_cycle_stage_id'] ?? 0),
    'sales_cycle_stage_no' => (string) ($targetStage['stage_no'] ?? ''),
    'sales_cycle_stage_description' => (string) ($targetStage['stage_description'] ?? ''),
    'date_of_change' => date('Y-m-d'),
    'estimated_sales_value' => number_format((float) ($opportunity['estimated_sales_value'] ?? 0), 2, '.', ''),
    'chance_of_success_percent' => number_format((float) ($targetStage['chance_of_success_percent'] ?? 0), 2, '.', ''),
    'estimated_closing_date_for_stage' => (string) (($opportunity['estimated_closing_date_for_stage'] ?? '') !== '' ? $opportunity['estimated_closing_date_for_stage'] : date('Y-m-d')),
    'opportunity_closing_date' => (string) ($opportunity['date_closed'] ?? ''),
    'cancel_existing_open_tasks' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $opportunity) {
    $formData['action_type'] = trim($_POST['action_type'] ?? 'Current');
    $formData['date_of_change'] = trim($_POST['date_of_change'] ?? date('Y-m-d'));
    $formData['estimated_sales_value'] = trim($_POST['estimated_sales_value'] ?? '0.00');
    $formData['estimated_closing_date_for_stage'] = trim($_POST['estimated_closing_date_for_stage'] ?? '');
    $formData['opportunity_closing_date'] = trim($_POST['opportunity_closing_date'] ?? '');
    $formData['cancel_existing_open_tasks'] = isset($_POST['cancel_existing_open_tasks']) ? 1 : 0;

    $errors = [];

    if (!in_array($formData['action_type'], crmOpportunityUpdateActions(), true)) {
        $errors[] = 'Please select a valid action type.';
    }
    if ($formData['date_of_change'] === '') {
        $errors[] = 'Date of change is required.';
    }
    if ($formData['estimated_sales_value'] === '' || !is_numeric($formData['estimated_sales_value'])) {
        $errors[] = 'Estimated sales value must be a valid number.';
    }
    if (!$salesCycle) {
        $errors[] = 'This opportunity does not have a valid sales cycle.';
    }
    if (empty($stages)) {
        $errors[] = 'This sales cycle does not have any stages.';
    }

    if (empty($errors)) {
        $currentStage = crmResolveOpportunityCurrentStage($db, $opportunity);
        if (!$currentStage) {
            $currentStage = crmFetchFirstSalesCycleStage($db, (int) $salesCycle['sales_cycle_id']);
        }

        $targetStage = crmResolveOpportunityTargetStage(
            $db,
            (int) $salesCycle['sales_cycle_id'],
            (int) ($currentStage['sales_cycle_stage_id'] ?? 0),
            $formData['action_type']
        );

        if (!$targetStage) {
            $errors[] = 'Unable to resolve the target sales cycle stage.';
        } else {
            $formData['sales_cycle_stage_id'] = (int) $targetStage['sales_cycle_stage_id'];
            $formData['sales_cycle_stage_no'] = (string) $targetStage['stage_no'];
            $formData['sales_cycle_stage_description'] = (string) $targetStage['stage_description'];
            $formData['chance_of_success_percent'] = number_format((float) ($targetStage['chance_of_success_percent'] ?? 0), 2, '.', '');
        }
    }

    if (empty($errors)) {
        try {
            $chanceOfSuccessPercent = (float) $formData['chance_of_success_percent'];
            $isClosed = $formData['opportunity_closing_date'] !== '' || $chanceOfSuccessPercent >= 100;
            $opportunityClosingDate = $isClosed
                ? ($formData['opportunity_closing_date'] !== '' ? $formData['opportunity_closing_date'] : $formData['date_of_change'])
                : null;
            $status = $isClosed ? 'Closed' : 'In Progress';
            $currentActivityLineIdForSave = (int) ($opportunity['current_activity_line_id'] ?? 0) > 0
                ? (int) $opportunity['current_activity_line_id']
                : null;

            if ((int) ($opportunity['current_sales_cycle_stage_id'] ?? 0) !== (int) $formData['sales_cycle_stage_id']) {
                $currentActivityLineIdForSave = null;
            }

            $db->insertRow(
                'INSERT INTO crm_opportunity_update (opportunity_id, action_type, sales_cycle_stage_id, date_of_change, estimated_sales_value, chance_of_success_percent, estimated_closing_date_for_stage, opportunity_closing_date, cancel_existing_open_tasks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $opportunityId,
                    $formData['action_type'],
                    $formData['sales_cycle_stage_id'],
                    $formData['date_of_change'],
                    (float) $formData['estimated_sales_value'],
                    $chanceOfSuccessPercent,
                    $formData['estimated_closing_date_for_stage'] !== '' ? $formData['estimated_closing_date_for_stage'] : null,
                    $opportunityClosingDate,
                    $formData['cancel_existing_open_tasks']
                ]
            );

            $db->updateRow(
                'UPDATE crm_opportunity SET current_sales_cycle_stage_id = ?, current_activity_line_id = ?, estimated_sales_value = ?, chance_of_success_percent = ?, estimated_closing_date_for_stage = ?, status = ?, is_closed = ?, date_closed = ? WHERE opportunity_id = ?',
                [
                    $formData['sales_cycle_stage_id'],
                    $currentActivityLineIdForSave,
                    (float) $formData['estimated_sales_value'],
                    $chanceOfSuccessPercent,
                    $formData['estimated_closing_date_for_stage'] !== '' ? $formData['estimated_closing_date_for_stage'] : null,
                    $status,
                    $isClosed ? 1 : 0,
                    $opportunityClosingDate,
                    $opportunityId
                ]
            );

            $opportunity = crmFetchOpportunity($db, $opportunityId);
            $currentStage = crmResolveOpportunityCurrentStage($db, $opportunity);
            $message = 'Opportunity stage updated successfully.';
            $messageClass = 'alert-success';
        } catch (Exception $e) {
            $message = 'Unable to update this opportunity right now.';
            $messageClass = 'alert-danger';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageClass = 'alert-danger';
    }
}

$updateHistory = $opportunity ? crmFetchOpportunityUpdateHistory($db, $opportunityId) : [];

$stageJson = [];
foreach ($stages as $stage) {
    $stageJson[] = [
        'sales_cycle_stage_id' => (int) ($stage['sales_cycle_stage_id'] ?? 0),
        'stage_no' => (int) ($stage['stage_no'] ?? 0),
        'stage_description' => (string) ($stage['stage_description'] ?? ''),
        'chance_of_success_percent' => (float) ($stage['chance_of_success_percent'] ?? 0)
    ];
}

$pageTitle = 'Update Opportunity';
if ($opportunity) {
    $pageTitle .= ' - ' . trim(
        (string) ($opportunity['contact_no'] ?? '') . ' ' .
        (string) ($opportunity['contact_company_name'] ?? '') . ' ' .
        (string) ($opportunity['description'] ?? '')
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?php echo crmEscape($pageTitle); ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        .crm-update-readonly {
            background: #f3f6f9;
        }
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
                    <li><a href="crm.php">CRM</a><i class="fa fa-circle"></i></li>
                    <li><a href="crm-opportunity.php">Opportunity</a><i class="fa fa-circle"></i></li>
                    <li><span>Update Opportunity</span></li>
                </ul>
            </div>
            <h3 class="page-title"><?php echo crmEscape($pageTitle); ?></h3>

            <?php if ($message !== '') { ?>
                <div class="alert <?php echo $messageClass; ?>"><?php echo $message; ?></div>
            <?php } ?>

            <?php if ($opportunity) { ?>
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption"><i class="fa fa-random font-green"></i> Opportunity Update</div>
                        <div class="actions">
                            <a href="crm-opportunity.php?id=<?php echo (int) $opportunityId; ?>" class="btn btn-sm default"><i class="fa fa-pencil"></i> Opportunity Entry</a>
                        </div>
                    </div>
                    <div class="portlet-body form">
                        <form method="post" class="form-horizontal">
                            <input type="hidden" name="opportunity_id" value="<?php echo (int) $opportunityId; ?>">
                            <div class="form-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Action Type</label>
                                            <div class="col-md-8">
                                                <select name="action_type" id="action_type" class="form-control">
                                                    <?php foreach (crmOpportunityUpdateActions() as $actionType) { ?>
                                                        <option value="<?php echo crmEscape($actionType); ?>" <?php echo crmSelected($formData['action_type'], $actionType); ?>><?php echo crmEscape($actionType); ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Sales Cycle Stage</label>
                                            <div class="col-md-8">
                                                <input type="text" id="sales_cycle_stage_no" class="form-control crm-update-readonly" value="<?php echo crmEscape($formData['sales_cycle_stage_no']); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Sales Cycle Stage Description</label>
                                            <div class="col-md-8">
                                                <input type="text" id="sales_cycle_stage_description" class="form-control crm-update-readonly" value="<?php echo crmEscape($formData['sales_cycle_stage_description']); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Date of Change</label>
                                            <div class="col-md-8">
                                                <input type="date" name="date_of_change" class="form-control" value="<?php echo crmEscape($formData['date_of_change']); ?>">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Estimated sales value (LCY)</label>
                                            <div class="col-md-8">
                                                <input type="number" min="0" step="0.01" name="estimated_sales_value" class="form-control" value="<?php echo crmEscape($formData['estimated_sales_value']); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Chances of Success (%)</label>
                                            <div class="col-md-8">
                                                <input type="text" id="chance_of_success_percent" class="form-control crm-update-readonly" value="<?php echo crmEscape(rtrim(rtrim($formData['chance_of_success_percent'], '0'), '.')); ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Estimated Closing Date For Stage</label>
                                            <div class="col-md-8">
                                                <input type="date" name="estimated_closing_date_for_stage" class="form-control" value="<?php echo crmEscape($formData['estimated_closing_date_for_stage']); ?>">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Opportunity Closing Date</label>
                                            <div class="col-md-8">
                                                <input type="date" name="opportunity_closing_date" id="opportunity_closing_date" class="form-control" value="<?php echo crmEscape($formData['opportunity_closing_date']); ?>">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Cancel Existing Open Tasks</label>
                                            <div class="col-md-8">
                                                <div class="mt-checkbox-inline" style="padding-top:7px;">
                                                    <label class="mt-checkbox mt-checkbox-outline">
                                                        <input type="checkbox" name="cancel_existing_open_tasks" value="1" <?php echo crmChecked((int) $formData['cancel_existing_open_tasks'] === 1); ?>>
                                                        <span></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-md-4 control-label">Current Cycle</label>
                                            <div class="col-md-8">
                                                <input type="text" class="form-control crm-update-readonly" value="<?php echo crmEscape(($salesCycle['cycle_code'] ?? '') . ' - ' . ($salesCycle['cycle_description'] ?? '')); ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions right">
                                <a href="crm-opportunity.php?id=<?php echo (int) $opportunityId; ?>" class="btn default">Back</a>
                                <button type="submit" class="btn green"><i class="fa fa-check"></i> Save Opportunity Update</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption"><i class="fa fa-history font-blue"></i> Update History</div>
                    </div>
                    <div class="portlet-body table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Action</th>
                                    <th>Stage</th>
                                    <th>Stage Description</th>
                                    <th>Sales Value</th>
                                    <th>Success %</th>
                                    <th>Stage Closing Date</th>
                                    <th>Opportunity Closing Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($updateHistory as $history) { ?>
                                    <tr>
                                        <td><?php echo crmEscape($history['date_of_change']); ?></td>
                                        <td><?php echo crmEscape($history['action_type']); ?></td>
                                        <td><?php echo (int) ($history['stage_no'] ?? 0); ?></td>
                                        <td><?php echo crmEscape($history['stage_description']); ?></td>
                                        <td><?php echo crmEscape(number_format((float) ($history['estimated_sales_value'] ?? 0), 2, '.', '')); ?></td>
                                        <td><?php echo crmEscape(rtrim(rtrim(number_format((float) ($history['chance_of_success_percent'] ?? 0), 2, '.', ''), '0'), '.')); ?></td>
                                        <td><?php echo crmEscape($history['estimated_closing_date_for_stage']); ?></td>
                                        <td><?php echo crmEscape($history['opportunity_closing_date']); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<?php include('common/footer.php'); ?>
<script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
<script>
    (function ($) {
        var stages = <?php echo json_encode($stageJson); ?>;
        var currentStageId = <?php echo (int) ($currentStage['sales_cycle_stage_id'] ?? 0); ?>;

        function formatChance(value) {
            var text = Number(value || 0).toFixed(2);
            return text.replace(/\.00$/, '').replace(/(\.\d*[1-9])0$/, '$1');
        }

        function resolveTargetStage(actionType) {
            if (!stages.length) {
                return null;
            }

            var currentIndex = 0;
            $.each(stages, function (index, stage) {
                if (Number(stage.sales_cycle_stage_id) === Number(currentStageId)) {
                    currentIndex = index;
                    return false;
                }
            });

            var targetIndex = currentIndex;
            if (actionType === 'Next') {
                targetIndex = Math.min(currentIndex + 1, stages.length - 1);
            } else if (actionType === 'Previous') {
                targetIndex = Math.max(currentIndex - 1, 0);
            }

            return stages[targetIndex] || stages[0];
        }

        function syncStagePreview() {
            var actionType = $('#action_type').val();
            var targetStage = resolveTargetStage(actionType);

            if (!targetStage) {
                $('#sales_cycle_stage_no').val('');
                $('#sales_cycle_stage_description').val('');
                $('#chance_of_success_percent').val('');
                return;
            }

            $('#sales_cycle_stage_no').val(targetStage.stage_no);
            $('#sales_cycle_stage_description').val(targetStage.stage_description);
            $('#chance_of_success_percent').val(formatChance(targetStage.chance_of_success_percent));
        }

        $(document).on('change', '#action_type', syncStagePreview);
        syncStagePreview();
    })(jQuery);
</script>
</body>
</html>