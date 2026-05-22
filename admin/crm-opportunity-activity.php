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
$finishFormData = [
    'finish_date' => date('Y-m-d'),
    'remark' => ''
];

$opportunityId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$opportunity = $opportunityId > 0 ? crmFetchOpportunity($db, $opportunityId) : null;

if (!$opportunity) {
    $message = 'Opportunity not found.';
    $messageClass = 'alert-danger';
}

$loadActivityState = function ($opportunityRecord) use ($db) {
    $salesCycle = null;
    $currentStage = null;
    $activity = null;
    $activityLines = [];
    $currentActivityLine = null;
    $completedActivityTaskMap = [];

    if ($opportunityRecord) {
        if ((int) ($opportunityRecord['sales_cycle_id'] ?? 0) > 0) {
            $salesCycle = crmFetchSalesCycle($db, (int) $opportunityRecord['sales_cycle_id']);
        }

        $currentStage = crmResolveOpportunityCurrentStage($db, $opportunityRecord);
        if (!$currentStage && (int) ($opportunityRecord['sales_cycle_id'] ?? 0) > 0) {
            $currentStage = crmFetchFirstSalesCycleStage($db, (int) $opportunityRecord['sales_cycle_id']);
        }

        if ($currentStage && trim((string) ($currentStage['activity_code'] ?? '')) !== '') {
            $activity = crmFetchActivityByCode($db, $currentStage['activity_code']);
            if ($activity) {
                $activityLines = crmFetchActivityLines($db, (int) $activity['activity_id']);
                $activityLineIds = [];
                foreach ($activityLines as $activityLine) {
                    $activityLineIds[] = (int) ($activityLine['activity_line_id'] ?? 0);
                }

                $completedActivityTasks = crmFetchOpportunityActivityTaskRecords(
                    $db,
                    (int) ($opportunityRecord['opportunity_id'] ?? 0),
                    $activityLineIds
                );

                foreach ($completedActivityTasks as $completedActivityTask) {
                    $completedActivityTaskMap[(int) ($completedActivityTask['activity_line_id'] ?? 0)] = $completedActivityTask;
                }
            }
        }

        $currentActivityLineId = (int) ($opportunityRecord['current_activity_line_id'] ?? 0);
        if (!isset($completedActivityTaskMap[$currentActivityLineId])) {
            foreach ($activityLines as $activityLine) {
                if ((int) ($activityLine['activity_line_id'] ?? 0) === $currentActivityLineId) {
                    $currentActivityLine = $activityLine;
                    break;
                }
            }
        }
    }

    return [
        'salesCycle' => $salesCycle,
        'currentStage' => $currentStage,
        'activity' => $activity,
        'activityLines' => $activityLines,
        'currentActivityLine' => $currentActivityLine,
        'completedActivityTaskMap' => $completedActivityTaskMap
    ];
};

$activityState = $loadActivityState($opportunity);
$salesCycle = $activityState['salesCycle'];
$currentStage = $activityState['currentStage'];
$activity = $activityState['activity'];
$activityLines = $activityState['activityLines'];
$currentActivityLine = $activityState['currentActivityLine'];
$completedActivityTaskMap = $activityState['completedActivityTaskMap'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $opportunity && isset($_POST['set_current_activity_line'])) {
    $selectedActivityLineId = (int) ($_POST['current_activity_line_id'] ?? 0);
    $activityLineIdForSave = null;

    if (!$currentStage || !$activity) {
        $message = 'The current stage does not have an activity with tasks to update.';
        $messageClass = 'alert-danger';
    } else {
        if ($selectedActivityLineId > 0) {
            $selectedActivityLine = crmFetchActivityLine($db, $selectedActivityLineId);
            if (!$selectedActivityLine || (int) ($selectedActivityLine['activity_id'] ?? 0) !== (int) ($activity['activity_id'] ?? 0)) {
                $message = 'Please select a valid task from the current activity.';
                $messageClass = 'alert-danger';
            } elseif (crmFetchOpportunityActivityTaskRecord($db, $opportunityId, $selectedActivityLineId)) {
                $message = 'This activity task is already finished.';
                $messageClass = 'alert-danger';
            } else {
                $activityLineIdForSave = $selectedActivityLineId;
            }
        }

        if ($messageClass !== 'alert-danger') {
            try {
                $db->updateRow(
                    'UPDATE crm_opportunity SET current_activity_line_id = ? WHERE opportunity_id = ?',
                    [$activityLineIdForSave, $opportunityId]
                );

                $opportunity = crmFetchOpportunity($db, $opportunityId);
                $activityState = $loadActivityState($opportunity);
                $salesCycle = $activityState['salesCycle'];
                $currentStage = $activityState['currentStage'];
                $activity = $activityState['activity'];
                $activityLines = $activityState['activityLines'];
                $currentActivityLine = $activityState['currentActivityLine'];
                $completedActivityTaskMap = $activityState['completedActivityTaskMap'];
                $message = $activityLineIdForSave ? 'Current activity task updated successfully.' : 'Current activity task cleared successfully.';
                $messageClass = 'alert-success';
            } catch (Exception $e) {
                $message = 'Unable to update the current activity task right now.';
                $messageClass = 'alert-danger';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $opportunity && isset($_POST['finish_activity_task'])) {
    $selectedActivityLineId = (int) ($_POST['current_activity_line_id'] ?? 0);
    $finishFormData['finish_date'] = trim((string) ($_POST['finish_date'] ?? date('Y-m-d')));
    $finishFormData['remark'] = trim((string) ($_POST['remark'] ?? ''));

    if (!$currentStage || !$activity) {
        $message = 'The current stage does not have an activity with tasks to finish.';
        $messageClass = 'alert-danger';
    } else {
        $selectedActivityLine = crmFetchActivityLine($db, $selectedActivityLineId);

        if (!$selectedActivityLine || (int) ($selectedActivityLine['activity_id'] ?? 0) !== (int) ($activity['activity_id'] ?? 0)) {
            $message = 'Please select a valid task from the current activity.';
            $messageClass = 'alert-danger';
        } elseif ($finishFormData['finish_date'] === '') {
            $message = 'Finish date is required.';
            $messageClass = 'alert-danger';
        } elseif (crmFetchOpportunityActivityTaskRecord($db, $opportunityId, $selectedActivityLineId)) {
            $message = 'This activity task is already finished.';
            $messageClass = 'alert-danger';
        } else {
            try {
                $db->insertRow(
                    'INSERT INTO crm_opportunity_activity_task (opportunity_id, activity_line_id, finish_date, remark) VALUES (?, ?, ?, ?)',
                    [
                        $opportunityId,
                        $selectedActivityLineId,
                        $finishFormData['finish_date'],
                        $finishFormData['remark'] !== '' ? $finishFormData['remark'] : null
                    ]
                );

                if ((int) ($opportunity['current_activity_line_id'] ?? 0) === $selectedActivityLineId) {
                    $db->updateRow(
                        'UPDATE crm_opportunity SET current_activity_line_id = NULL WHERE opportunity_id = ?',
                        [$opportunityId]
                    );
                }

                $opportunity = crmFetchOpportunity($db, $opportunityId);
                $activityState = $loadActivityState($opportunity);
                $salesCycle = $activityState['salesCycle'];
                $currentStage = $activityState['currentStage'];
                $activity = $activityState['activity'];
                $activityLines = $activityState['activityLines'];
                $currentActivityLine = $activityState['currentActivityLine'];
                $completedActivityTaskMap = $activityState['completedActivityTaskMap'];
                $finishFormData = [
                    'finish_date' => date('Y-m-d'),
                    'remark' => ''
                ];
                $message = 'Activity task finished successfully.';
                $messageClass = 'alert-success';
            } catch (Exception $e) {
                $message = 'Unable to finish the selected activity task right now.';
                $messageClass = 'alert-danger';
            }
        }
    }
}

$currentActivityTaskLabel = 'No current task selected';
if ($currentActivityLine) {
    $currentActivityTaskLabel = trim(
        ((string) ($currentActivityLine['line_type'] ?? '') !== '' ? (string) $currentActivityLine['line_type'] . ' - ' : '') .
        (string) ($currentActivityLine['description'] ?? '')
    );
}

$pageTitle = 'Opportunity Activity Tasks';
if ($opportunity) {
    $pageTitle .= ' - ' . trim((string) ($opportunity['opportunity_code'] ?? ''));
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
        .crm-task-readonly {
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
                    <li><span>Activity Tasks</span></li>
                </ul>
            </div>
            <h3 class="page-title"><?php echo crmEscape($pageTitle); ?></h3>

            <?php if ($message !== '') { ?>
                <div class="alert <?php echo $messageClass; ?>"><?php echo $message; ?></div>
            <?php } ?>

            <?php if ($opportunity) { ?>
                <div class="portlet light bordered">
                    <div class="portlet-title">
                        <div class="caption"><i class="fa fa-tasks font-blue"></i> Opportunity Activity Task Details</div>
                        <div class="actions">
                            <a href="crm-opportunity.php" class="btn btn-sm default"><i class="fa fa-list"></i> Opportunity List</a>
                            <a href="crm-opportunity-update.php?id=<?php echo (int) $opportunityId; ?>" class="btn btn-sm blue"><i class="fa fa-random"></i> Update Opportunity Stage</a>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered table-condensed">
                                    <tr><th>Opportunity No</th><td><?php echo crmEscape($opportunity['opportunity_code']); ?></td></tr>
                                    <tr><th>Description</th><td><?php echo crmEscape($opportunity['description']); ?></td></tr>
                                    <tr><th>Contact</th><td><?php echo crmEscape($opportunity['contact_name']); ?></td></tr>
                                    <tr><th>Sales Cycle</th><td><?php echo crmEscape($salesCycle ? ($salesCycle['cycle_code'] ?? '') : ''); ?></td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered table-condensed">
                                    <tr><th>Current Stage</th><td><?php echo crmEscape($currentStage ? ($currentStage['stage_no'] . ' - ' . $currentStage['stage_description']) : 'Not available'); ?></td></tr>
                                    <tr><th>Activity Code</th><td><?php echo crmEscape($currentStage['activity_code'] ?? 'None'); ?></td></tr>
                                    <tr><th>Activity Name</th><td><?php echo crmEscape($activity ? ($activity['activity_code'] . ' - ' . $activity['description']) : 'No activity defined'); ?></td></tr>
                                    <tr><th>Current Activity Task</th><td><?php echo crmEscape($currentActivityTaskLabel); ?></td></tr>
                                </table>
                            </div>
                        </div>

                        <div class="alert <?php echo $currentActivityLine ? 'alert-success' : 'alert-info'; ?>">
                            <strong>Current activity:</strong>
                            <?php echo crmEscape($activity ? ($activity['activity_code'] . ' - ' . $activity['description']) : ($currentStage && trim((string) ($currentStage['activity_code'] ?? '')) !== '' ? $currentStage['activity_code'] : 'None')); ?>
                            <br>
                            <strong>Current activity task:</strong>
                            <?php echo crmEscape($currentActivityTaskLabel); ?>
                            <br>
                            <?php if ($activity && !empty($activityLines)) { ?>
                                Click <strong>Set Current</strong> against a task below to mark what you are working on now. When the task is finished, enter a <strong>Finish Date</strong> and <strong>Remark</strong> in the finish form below. Use <strong>Update Opportunity Stage</strong> above if you need to move to another activity.
                            <?php } else { ?>
                                Use <strong>Update Opportunity Stage</strong> above if you need to move this opportunity to a stage that has task definitions.
                            <?php } ?>
                        </div>

                        <?php if ($activity && $currentActivityLine) { ?>
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-check-square-o font-green"></i> Finish Current Task</div>
                                </div>
                                <div class="portlet-body form">
                                    <form method="post" class="form-horizontal">
                                        <input type="hidden" name="current_activity_line_id" value="<?php echo (int) $currentActivityLine['activity_line_id']; ?>">
                                        <div class="form-body">
                                            <div class="form-group">
                                                <label class="col-md-3 control-label">Current Task</label>
                                                <div class="col-md-9">
                                                    <input type="text" class="form-control crm-task-readonly" value="<?php echo crmEscape($currentActivityTaskLabel); ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-md-3 control-label">Finish Date</label>
                                                <div class="col-md-4">
                                                    <input type="date" name="finish_date" class="form-control" value="<?php echo crmEscape($finishFormData['finish_date']); ?>">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-md-3 control-label">Remark</label>
                                                <div class="col-md-9">
                                                    <textarea name="remark" class="form-control" rows="3" placeholder="Optional completion remark"><?php echo crmEscape($finishFormData['remark']); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-actions right">
                                            <button type="submit" name="finish_activity_task" value="1" class="btn green"><i class="fa fa-check"></i> Finish Task</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php } elseif ($activity && !empty($activityLines)) { ?>
                            <div class="alert alert-warning">Select a current task first to enter its finish date and remark.</div>
                        <?php } ?>

                        <?php if ($currentStage && trim((string) ($currentStage['activity_code'] ?? '')) !== '') { ?>
                            <?php if ($activity) { ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Task Type</th>
                                                <th>Description</th>
                                                <th>Status</th>
                                                <th>Percentage</th>
                                                <th>Priority</th>
                                                <th>Date Formula</th>
                                                <th>Finish Date</th>
                                                <th>Remark</th>
                                                <th style="width:120px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($activityLines)) { ?>
                                                <tr>
                                                    <td colspan="10">No activity tasks have been defined for this activity.</td>
                                                </tr>
                                            <?php } else { ?>
                                                <?php foreach ($activityLines as $index => $line) { ?>
                                                    <?php $completedTask = $completedActivityTaskMap[(int) ($line['activity_line_id'] ?? 0)] ?? null; ?>
                                                    <?php $isCurrentTask = $currentActivityLine && (int) ($currentActivityLine['activity_line_id'] ?? 0) === (int) ($line['activity_line_id'] ?? 0); ?>
                                                    <tr<?php echo $completedTask ? ' class="active"' : ($isCurrentTask ? ' class="success"' : ''); ?>>
                                                        <td><?php echo $index + 1; ?></td>
                                                        <td><?php echo crmEscape($line['line_type']); ?></td>
                                                        <td><?php echo crmEscape($line['description']); ?></td>
                                                        <td>
                                                            <?php if ($completedTask) { ?>
                                                                <span class="label label-primary">Completed</span>
                                                            <?php } elseif ($isCurrentTask) { ?>
                                                                <span class="label label-success">Current Task</span>
                                                            <?php } else { ?>
                                                                <span class="label label-default">Available</span>
                                                            <?php } ?>
                                                        </td>
                                                        <td><?php echo crmEscape(number_format((float) ($line['activity_percentage'] ?? 0), 2, '.', '')); ?></td>
                                                        <td><?php echo crmEscape($line['priority']); ?></td>
                                                        <td><?php echo crmEscape($line['date_formula']); ?></td>
                                                        <td><?php echo crmEscape($completedTask['finish_date'] ?? ''); ?></td>
                                                        <td><?php echo crmEscape($completedTask['remark'] ?? ''); ?></td>
                                                        <td>
                                                            <?php if ($completedTask) { ?>
                                                                <button type="button" class="btn btn-xs btn-primary" disabled>Finished</button>
                                                            <?php } elseif ($isCurrentTask) { ?>
                                                                <button type="button" class="btn btn-xs btn-success" disabled>Current</button>
                                                            <?php } else { ?>
                                                                <form method="post" style="margin:0;">
                                                                    <input type="hidden" name="current_activity_line_id" value="<?php echo (int) $line['activity_line_id']; ?>">
                                                                    <button type="submit" name="set_current_activity_line" value="1" class="btn btn-xs btn-default">Set Current</button>
                                                                </form>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } else { ?>
                                <div class="alert alert-warning">The current sales stage has an activity code, but no matching activity master record was found.</div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="alert alert-info">This opportunity's current stage has no activity code assigned, so there are no activity tasks to display.</div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<?php include('common/footer.php'); ?>
<script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
</body>
</html>
