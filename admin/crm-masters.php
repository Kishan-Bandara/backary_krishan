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

$tabs = ['designation', 'segment', 'category', 'sales_person', 'sales_cycle', 'activity'];
$activeTab = isset($_REQUEST['tab']) ? strtolower(trim($_REQUEST['tab'])) : 'designation';
if (!in_array($activeTab, $tabs, true)) {
    $activeTab = 'designation';
}

$message = '';
$messageClass = 'alert-success';
$selectedSalesCycleId = isset($_REQUEST['cycle_id']) ? (int) $_REQUEST['cycle_id'] : 0;
$selectedActivityId = isset($_REQUEST['activity_id']) ? (int) $_REQUEST['activity_id'] : 0;

$editRows = [
    'designation' => null,
    'segment' => null,
    'category' => null,
    'sales_person' => null,
    'sales_cycle' => null,
    'sales_cycle_stage' => null,
    'activity' => null,
    'activity_line' => null
];

if (isset($_GET['delete_type'], $_GET['delete_id'])) {
    $deleteType = strtolower(trim($_GET['delete_type']));
    $deleteId = (int) $_GET['delete_id'];
    if ($deleteId > 0) {
        try {
            if ($deleteType === 'designation') {
                $usageCount = crmMasterUsageCount($db, 'crm_person_master', 'designation_id', $deleteId);
                if ($usageCount > 0) {
                    throw new Exception('This designation is already used in person records.');
                }
                $db->deleteRow('DELETE FROM crm_designation_master WHERE designation_id = ?', [$deleteId]);
                $message = 'Designation deleted successfully.';
            } elseif ($deleteType === 'segment') {
                $categoryUsage = crmMasterUsageCount($db, 'crm_category_master', 'segment_id', $deleteId);
                $companyUsage = crmMasterUsageCount($db, 'crm_company_master', 'segment_id', $deleteId);
                if ($categoryUsage > 0 || $companyUsage > 0) {
                    throw new Exception('This segment is mapped to categories or companies.');
                }
                $db->deleteRow('DELETE FROM crm_segment_master WHERE segment_id = ?', [$deleteId]);
                $message = 'Segment deleted successfully.';
            } elseif ($deleteType === 'category') {
                $usageCount = crmMasterUsageCount($db, 'crm_company_master', 'category_id', $deleteId);
                if ($usageCount > 0) {
                    throw new Exception('This category is already used in company records.');
                }
                $db->deleteRow('DELETE FROM crm_category_master WHERE category_id = ?', [$deleteId]);
                $message = 'Category deleted successfully.';
            } elseif ($deleteType === 'sales_person') {
                $usageCount = crmMasterUsageCount($db, 'crm_company_master', 'sales_person_id', $deleteId);
                if ($usageCount > 0) {
                    throw new Exception('This sales person is already assigned to company records.');
                }
                $db->deleteRow('DELETE FROM crm_sales_person_master WHERE sales_person_id = ?', [$deleteId]);
                $message = 'Sales person deleted successfully.';
            } elseif ($deleteType === 'sales_cycle') {
                $db->deleteRow('DELETE FROM crm_sales_cycle_master WHERE sales_cycle_id = ?', [$deleteId]);
                if ($selectedSalesCycleId === $deleteId) {
                    $selectedSalesCycleId = 0;
                }
                $message = 'Sales cycle deleted successfully.';
            } elseif ($deleteType === 'sales_cycle_stage') {
                $stageRow = crmFetchSalesCycleStage($db, $deleteId);
                if (!$stageRow) {
                    throw new Exception('Selected sales cycle stage was not found.');
                }
                $selectedSalesCycleId = (int) ($stageRow['sales_cycle_id'] ?? $selectedSalesCycleId);
                $db->deleteRow('DELETE FROM crm_sales_cycle_stage WHERE sales_cycle_stage_id = ?', [$deleteId]);
                $message = 'Sales cycle stage deleted successfully.';
            } elseif ($deleteType === 'activity') {
                $db->deleteRow('DELETE FROM crm_activity_master WHERE activity_id = ?', [$deleteId]);
                if ($selectedActivityId === $deleteId) {
                    $selectedActivityId = 0;
                }
                $message = 'Activity deleted successfully.';
            } elseif ($deleteType === 'activity_line') {
                $lineRow = crmFetchActivityLine($db, $deleteId);
                if (!$lineRow) {
                    throw new Exception('Selected activity line was not found.');
                }
                $selectedActivityId = (int) ($lineRow['activity_id'] ?? $selectedActivityId);
                $db->deleteRow('DELETE FROM crm_activity_line WHERE activity_line_id = ?', [$deleteId]);
                $message = 'Activity line deleted successfully.';
            } else {
                throw new Exception('Unsupported CRM master delete request.');
            }
        } catch (Exception $e) {
            $message = $e->getMessage() ?: 'Unable to delete the selected CRM master.';
            $messageClass = 'alert-danger';
        }
        $activeTab = in_array($deleteType, ['sales_cycle_stage'], true) ? 'sales_cycle' : ($deleteType === 'activity_line' ? 'activity' : $deleteType);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $masterType = strtolower(trim($_POST['master_type'] ?? $activeTab));
    if (!in_array($masterType, array_merge($tabs, ['sales_cycle_stage', 'activity_line']), true)) {
        $masterType = 'designation';
    }
    $activeTab = $masterType === 'sales_cycle_stage' ? 'sales_cycle' : ($masterType === 'activity_line' ? 'activity' : $masterType);
    $editId = (int) ($_POST['edit_id'] ?? 0);

    try {
        if ($masterType === 'designation') {
            $name = trim($_POST['designation_name'] ?? '');
            $description = trim($_POST['designation_description'] ?? '');

            if ($name === '') {
                throw new Exception('Designation name is required.');
            }
            if (crmMasterRecordExists($db, 'crm_designation_master', 'designation_name', $name, 'designation_id', $editId)) {
                throw new Exception('This designation already exists.');
            }

            if ($editId > 0) {
                $db->updateRow(
                    'UPDATE crm_designation_master SET designation_name = ?, description = ? WHERE designation_id = ?',
                    [$name, $description, $editId]
                );
                $db->updateRow(
                    'UPDATE crm_person_master SET designation = ? WHERE designation_id = ?',
                    [$name, $editId]
                );
                $message = 'Designation updated successfully.';
            } else {
                $db->insertRow(
                    'INSERT INTO crm_designation_master (designation_name, description) VALUES (?, ?)',
                    [$name, $description]
                );
                $message = 'Designation created successfully.';
            }
        } elseif ($masterType === 'segment') {
            $name = trim($_POST['segment_name'] ?? '');
            $description = trim($_POST['segment_description'] ?? '');

            if ($name === '') {
                throw new Exception('Segment name is required.');
            }
            if (crmMasterRecordExists($db, 'crm_segment_master', 'segment_name', $name, 'segment_id', $editId)) {
                throw new Exception('This segment already exists.');
            }

            if ($editId > 0) {
                $db->updateRow(
                    'UPDATE crm_segment_master SET segment_name = ?, description = ? WHERE segment_id = ?',
                    [$name, $description, $editId]
                );
                $message = 'Segment updated successfully.';
            } else {
                $db->insertRow(
                    'INSERT INTO crm_segment_master (segment_name, description) VALUES (?, ?)',
                    [$name, $description]
                );
                $message = 'Segment created successfully.';
            }
        } elseif ($masterType === 'category') {
            $segmentId = (int) ($_POST['category_segment_id'] ?? 0);
            $name = trim($_POST['category_name'] ?? '');
            $description = trim($_POST['category_description'] ?? '');

            if ($segmentId <= 0) {
                throw new Exception('Segment is required for category mapping.');
            }
            if ($name === '') {
                throw new Exception('Category name is required.');
            }
            if (crmMasterRecordExists($db, 'crm_category_master', 'category_name', $name, 'category_id', $editId, 'segment_id', $segmentId)) {
                throw new Exception('This category already exists in the selected segment.');
            }

            if ($editId > 0) {
                $db->updateRow(
                    'UPDATE crm_category_master SET segment_id = ?, category_name = ?, description = ? WHERE category_id = ?',
                    [$segmentId, $name, $description, $editId]
                );
                $message = 'Category updated successfully.';
            } else {
                $db->insertRow(
                    'INSERT INTO crm_category_master (segment_id, category_name, description) VALUES (?, ?, ?)',
                    [$segmentId, $name, $description]
                );
                $message = 'Category created successfully.';
            }
        } elseif ($masterType === 'sales_person') {
            $name = trim($_POST['sales_person_name'] ?? '');
            $contactNo = trim($_POST['sales_person_contact_no'] ?? '');
            $email = trim($_POST['sales_person_email'] ?? '');

            if ($name === '') {
                throw new Exception('Sales person name is required.');
            }
            if (crmMasterRecordExists($db, 'crm_sales_person_master', 'sales_person_name', $name, 'sales_person_id', $editId)) {
                throw new Exception('This sales person already exists.');
            }

            if ($editId > 0) {
                $db->updateRow(
                    'UPDATE crm_sales_person_master SET sales_person_name = ?, contact_no = ?, email = ? WHERE sales_person_id = ?',
                    [$name, $contactNo, $email, $editId]
                );
                $message = 'Sales person updated successfully.';
            } else {
                $db->insertRow(
                    'INSERT INTO crm_sales_person_master (sales_person_name, contact_no, email) VALUES (?, ?, ?)',
                    [$name, $contactNo, $email]
                );
                $message = 'Sales person created successfully.';
            }
        } elseif ($masterType === 'sales_cycle') {
            $cycleCode = strtoupper(trim($_POST['cycle_code'] ?? ''));
            $cycleDescription = trim($_POST['cycle_description'] ?? '');
            $probabilityCalculation = trim($_POST['probability_calculation'] ?? 'Chances of Success %');

            if ($cycleCode === '') {
                throw new Exception('Sales cycle code is required.');
            }
            if ($cycleDescription === '') {
                throw new Exception('Sales cycle description is required.');
            }
            if (!in_array($probabilityCalculation, crmProbabilityCalculationOptions(), true)) {
                throw new Exception('Please select a valid probability calculation.');
            }
            if (crmMasterRecordExists($db, 'crm_sales_cycle_master', 'cycle_code', $cycleCode, 'sales_cycle_id', $editId)) {
                throw new Exception('This sales cycle code already exists.');
            }

            if ($editId > 0) {
                $db->updateRow(
                    'UPDATE crm_sales_cycle_master SET cycle_code = ?, cycle_description = ?, probability_calculation = ? WHERE sales_cycle_id = ?',
                    [$cycleCode, $cycleDescription, $probabilityCalculation, $editId]
                );
                $selectedSalesCycleId = $editId;
                $message = 'Sales cycle updated successfully.';
            } else {
                $db->insertRow(
                    'INSERT INTO crm_sales_cycle_master (cycle_code, cycle_description, probability_calculation) VALUES (?, ?, ?)',
                    [$cycleCode, $cycleDescription, $probabilityCalculation]
                );
                $savedCycle = $db->getRow('SELECT sales_cycle_id FROM crm_sales_cycle_master WHERE cycle_code = ? LIMIT 1', [$cycleCode]);
                $selectedSalesCycleId = (int) ($savedCycle['sales_cycle_id'] ?? 0);
                $message = 'Sales cycle created successfully.';
            }
        } elseif ($masterType === 'sales_cycle_stage') {
            $selectedSalesCycleId = (int) ($_POST['sales_cycle_id'] ?? 0);
            $stageNo = (int) ($_POST['stage_no'] ?? 0);
            $stageDescription = trim($_POST['stage_description'] ?? '');
            $completedPercent = (float) ($_POST['completed_percent'] ?? 0);
            $chanceOfSuccessPercent = (float) ($_POST['chance_of_success_percent'] ?? 0);
            $activityCode = trim($_POST['activity_code'] ?? '');

            if ($selectedSalesCycleId <= 0 || !crmFetchSalesCycle($db, $selectedSalesCycleId)) {
                throw new Exception('Please select a valid sales cycle before managing stages.');
            }
            if ($stageNo <= 0) {
                throw new Exception('Stage number must be greater than zero.');
            }
            if ($stageDescription === '') {
                throw new Exception('Stage description is required.');
            }
            if ($completedPercent < 0 || $completedPercent > 100) {
                throw new Exception('Completed % must be between 0 and 100.');
            }
            if ($chanceOfSuccessPercent < 0 || $chanceOfSuccessPercent > 100) {
                throw new Exception('Chances of Success % must be between 0 and 100.');
            }
            if (crmMasterRecordExists($db, 'crm_sales_cycle_stage', 'stage_no', $stageNo, 'sales_cycle_stage_id', $editId, 'sales_cycle_id', $selectedSalesCycleId)) {
                throw new Exception('This stage number already exists for the selected sales cycle.');
            }

            if ($editId > 0) {
                $db->updateRow(
                    'UPDATE crm_sales_cycle_stage SET sales_cycle_id = ?, stage_no = ?, stage_description = ?, completed_percent = ?, chance_of_success_percent = ?, activity_code = ? WHERE sales_cycle_stage_id = ?',
                    [$selectedSalesCycleId, $stageNo, $stageDescription, $completedPercent, $chanceOfSuccessPercent, $activityCode, $editId]
                );
                $message = 'Sales cycle stage updated successfully.';
            } else {
                $db->insertRow(
                    'INSERT INTO crm_sales_cycle_stage (sales_cycle_id, stage_no, stage_description, completed_percent, chance_of_success_percent, activity_code) VALUES (?, ?, ?, ?, ?, ?)',
                    [$selectedSalesCycleId, $stageNo, $stageDescription, $completedPercent, $chanceOfSuccessPercent, $activityCode]
                );
                $message = 'Sales cycle stage created successfully.';
            }
        } elseif ($masterType === 'activity') {
            $activityCode = strtoupper(trim($_POST['activity_code'] ?? ''));
            $activityDescription = trim($_POST['activity_description'] ?? '');

            if ($activityCode === '') {
                throw new Exception('Activity code is required.');
            }
            if ($activityDescription === '') {
                throw new Exception('Activity description is required.');
            }
            if (crmMasterRecordExists($db, 'crm_activity_master', 'activity_code', $activityCode, 'activity_id', $editId)) {
                throw new Exception('This activity code already exists.');
            }

            if ($editId > 0) {
                $db->updateRow(
                    'UPDATE crm_activity_master SET activity_code = ?, description = ? WHERE activity_id = ?',
                    [$activityCode, $activityDescription, $editId]
                );
                $selectedActivityId = $editId;
                $message = 'Activity updated successfully.';
            } else {
                $db->insertRow(
                    'INSERT INTO crm_activity_master (activity_code, description) VALUES (?, ?)',
                    [$activityCode, $activityDescription]
                );
                $savedActivity = $db->getRow('SELECT activity_id FROM crm_activity_master WHERE activity_code = ? LIMIT 1', [$activityCode]);
                $selectedActivityId = (int) ($savedActivity['activity_id'] ?? 0);
                $message = 'Activity created successfully.';
            }
        } elseif ($masterType === 'activity_line') {
            $selectedActivityId = (int) ($_POST['activity_id'] ?? 0);
            $lineType = trim($_POST['line_type'] ?? '');
            $lineDescription = trim($_POST['line_description'] ?? '');
            $activityPercentage = (float) ($_POST['activity_percentage'] ?? 0);
            $priority = trim($_POST['priority'] ?? 'Low');
            $dateFormula = trim($_POST['date_formula'] ?? '');

            if ($selectedActivityId <= 0 || !crmFetchActivity($db, $selectedActivityId)) {
                throw new Exception('Please select a valid activity before managing lines.');
            }
            if ($lineDescription === '') {
                throw new Exception('Line description is required.');
            }
            if ($activityPercentage < 0 || $activityPercentage > 100) {
                throw new Exception('Activity percentage must be between 0 and 100.');
            }
            if (!in_array($priority, crmActivityPriorities(), true)) {
                throw new Exception('Please select a valid priority.');
            }

            if ($editId > 0) {
                $db->updateRow(
                    'UPDATE crm_activity_line SET activity_id = ?, line_type = ?, description = ?, activity_percentage = ?, priority = ?, date_formula = ? WHERE activity_line_id = ?',
                    [$selectedActivityId, $lineType, $lineDescription, $activityPercentage, $priority, $dateFormula, $editId]
                );
                $message = 'Activity line updated successfully.';
            } else {
                $db->insertRow(
                    'INSERT INTO crm_activity_line (activity_id, line_type, description, activity_percentage, priority, date_formula) VALUES (?, ?, ?, ?, ?, ?)',
                    [$selectedActivityId, $lineType, $lineDescription, $activityPercentage, $priority, $dateFormula]
                );
                $message = 'Activity line created successfully.';
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage() ?: 'Unable to save the selected CRM master.';
        $messageClass = 'alert-danger';
    }
}

if (isset($_GET['edit_id'])) {
    $editId = (int) $_GET['edit_id'];
    if ($editId > 0) {
        if ($activeTab === 'designation') {
            $editRows['designation'] = $db->getRow('SELECT * FROM crm_designation_master WHERE designation_id = ? LIMIT 1', [$editId]);
        } elseif ($activeTab === 'segment') {
            $editRows['segment'] = $db->getRow('SELECT * FROM crm_segment_master WHERE segment_id = ? LIMIT 1', [$editId]);
        } elseif ($activeTab === 'category') {
            $editRows['category'] = $db->getRow('SELECT * FROM crm_category_master WHERE category_id = ? LIMIT 1', [$editId]);
        } elseif ($activeTab === 'sales_cycle') {
            $editRows['sales_cycle'] = crmFetchSalesCycle($db, $editId);
            $selectedSalesCycleId = $editRows['sales_cycle'] ? (int) $editRows['sales_cycle']['sales_cycle_id'] : $selectedSalesCycleId;
        } elseif ($activeTab === 'activity') {
            $editRows['activity'] = crmFetchActivity($db, $editId);
            $selectedActivityId = $editRows['activity'] ? (int) $editRows['activity']['activity_id'] : $selectedActivityId;
        } else {
            $editRows['sales_person'] = $db->getRow('SELECT * FROM crm_sales_person_master WHERE sales_person_id = ? LIMIT 1', [$editId]);
        }
    }
}

if ($activeTab === 'sales_cycle' && isset($_GET['stage_edit_id'])) {
    $stageEditId = (int) $_GET['stage_edit_id'];
    if ($stageEditId > 0) {
        $editRows['sales_cycle_stage'] = crmFetchSalesCycleStage($db, $stageEditId);
        if ($editRows['sales_cycle_stage']) {
            $selectedSalesCycleId = (int) $editRows['sales_cycle_stage']['sales_cycle_id'];
        }
    }
}

if ($activeTab === 'activity' && isset($_GET['line_edit_id'])) {
    $lineEditId = (int) $_GET['line_edit_id'];
    if ($lineEditId > 0) {
        $editRows['activity_line'] = crmFetchActivityLine($db, $lineEditId);
        if ($editRows['activity_line']) {
            $selectedActivityId = (int) $editRows['activity_line']['activity_id'];
        }
    }
}

$designations = crmFetchDesignations($db);
$segments = crmFetchSegments($db);
$categories = crmFetchCategories($db);
$salesPersons = crmFetchSalesPersons($db);
$salesCycles = crmFetchSalesCycles($db);

if ($activeTab === 'sales_cycle' && $selectedSalesCycleId <= 0 && !empty($salesCycles)) {
    $selectedSalesCycleId = (int) $salesCycles[0]['sales_cycle_id'];
}

$selectedSalesCycle = $selectedSalesCycleId > 0 ? crmFetchSalesCycle($db, $selectedSalesCycleId) : null;
$salesCycleStages = $selectedSalesCycleId > 0 ? crmFetchSalesCycleStages($db, $selectedSalesCycleId) : [];

if ($activeTab === 'sales_cycle' && $selectedSalesCycleId > 0 && !$selectedSalesCycle && !empty($salesCycles)) {
    $selectedSalesCycleId = (int) $salesCycles[0]['sales_cycle_id'];
    $selectedSalesCycle = crmFetchSalesCycle($db, $selectedSalesCycleId);
    $salesCycleStages = crmFetchSalesCycleStages($db, $selectedSalesCycleId);
}

$activities = crmFetchActivities($db);

if ($activeTab === 'activity' && $selectedActivityId <= 0 && !empty($activities)) {
    $selectedActivityId = (int) $activities[0]['activity_id'];
}

$selectedActivity = $selectedActivityId > 0 ? crmFetchActivity($db, $selectedActivityId) : null;
$activityLines = $selectedActivityId > 0 ? crmFetchActivityLines($db, $selectedActivityId) : [];

if ($activeTab === 'activity' && $selectedActivityId > 0 && !$selectedActivity && !empty($activities)) {
    $selectedActivityId = (int) $activities[0]['activity_id'];
    $selectedActivity = crmFetchActivity($db, $selectedActivityId);
    $activityLines = crmFetchActivityLines($db, $selectedActivityId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>CRM Masters</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        .crm-master-nav {
            margin-bottom: 20px;
        }

        .crm-master-nav .nav-tabs > li > a {
            font-weight: 600;
        }

        .crm-master-table td,
        .crm-master-table th {
            vertical-align: middle !important;
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
                    <li><span>CRM Masters</span></li>
                </ul>
            </div>
            <h3 class="page-title">CRM Masters</h3>

            <?php if ($message !== '') { ?>
                <div class="alert <?php echo $messageClass; ?>"><?php echo crmEscape($message); ?></div>
            <?php } ?>

            <div class="crm-master-nav">
                <ul class="nav nav-tabs">
                    <li class="<?php echo $activeTab === 'designation' ? 'active' : ''; ?>"><a href="crm-masters.php?tab=designation">Designations</a></li>
                    <li class="<?php echo $activeTab === 'segment' ? 'active' : ''; ?>"><a href="crm-masters.php?tab=segment">Segments</a></li>
                    <li class="<?php echo $activeTab === 'category' ? 'active' : ''; ?>"><a href="crm-masters.php?tab=category">Categories</a></li>
                    <li class="<?php echo $activeTab === 'sales_person' ? 'active' : ''; ?>"><a href="crm-masters.php?tab=sales_person">Sales Persons</a></li>
                    <li class="<?php echo $activeTab === 'sales_cycle' ? 'active' : ''; ?>"><a href="crm-masters.php?tab=sales_cycle">Sales Cycles</a></li>
                    <li class="<?php echo $activeTab === 'activity' ? 'active' : ''; ?>"><a href="crm-masters.php?tab=activity">Activities</a></li>
                </ul>
            </div>

            <div class="tab-content">
                <div class="tab-pane fade<?php echo $activeTab === 'designation' ? ' active in' : ''; ?>" id="designation">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-id-badge font-green"></i> Designation Form</div>
                                </div>
                                <div class="portlet-body form">
                                    <form method="post" class="form-horizontal">
                                        <input type="hidden" name="tab" value="designation">
                                        <input type="hidden" name="master_type" value="designation">
                                        <input type="hidden" name="edit_id" value="<?php echo (int) ($editRows['designation']['designation_id'] ?? 0); ?>">
                                        <div class="form-body">
                                            <div class="form-group">
                                                <label class="col-md-4 control-label">Name</label>
                                                <div class="col-md-8">
                                                    <input type="text" name="designation_name" class="form-control" value="<?php echo crmEscape($editRows['designation']['designation_name'] ?? ''); ?>" maxlength="150">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-md-4 control-label">Description</label>
                                                <div class="col-md-8">
                                                    <textarea name="designation_description" class="form-control" rows="3"><?php echo crmEscape($editRows['designation']['description'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-actions right">
                                            <a href="crm-masters.php?tab=designation" class="btn default">Clear</a>
                                            <button type="submit" class="btn green"><?php echo !empty($editRows['designation']) ? 'Update' : 'Save'; ?> Designation</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-list font-green"></i> Designation List</div>
                                </div>
                                <div class="portlet-body table-responsive">
                                    <table class="table table-striped table-bordered crm-master-table">
                                        <thead>
                                            <tr><th>Name</th><th>Description</th><th style="width:120px;">Action</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($designations as $designation) { ?>
                                                <tr>
                                                    <td><?php echo crmEscape($designation['designation_name']); ?></td>
                                                    <td><?php echo crmEscape($designation['description']); ?></td>
                                                    <td>
                                                        <a href="crm-masters.php?tab=designation&edit_id=<?php echo (int) $designation['designation_id']; ?>" class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i></a>
                                                        <a href="crm-masters.php?tab=designation&delete_type=designation&delete_id=<?php echo (int) $designation['designation_id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete this designation?');"><i class="fa fa-trash"></i></a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade<?php echo $activeTab === 'segment' ? ' active in' : ''; ?>" id="segment">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-sitemap font-blue"></i> Segment Form</div>
                                </div>
                                <div class="portlet-body form">
                                    <form method="post" class="form-horizontal">
                                        <input type="hidden" name="tab" value="segment">
                                        <input type="hidden" name="master_type" value="segment">
                                        <input type="hidden" name="edit_id" value="<?php echo (int) ($editRows['segment']['segment_id'] ?? 0); ?>">
                                        <div class="form-body">
                                            <div class="form-group">
                                                <label class="col-md-4 control-label">Name</label>
                                                <div class="col-md-8">
                                                    <input type="text" name="segment_name" class="form-control" value="<?php echo crmEscape($editRows['segment']['segment_name'] ?? ''); ?>" maxlength="150">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-md-4 control-label">Description</label>
                                                <div class="col-md-8">
                                                    <textarea name="segment_description" class="form-control" rows="3"><?php echo crmEscape($editRows['segment']['description'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-actions right">
                                            <a href="crm-masters.php?tab=segment" class="btn default">Clear</a>
                                            <button type="submit" class="btn green"><?php echo !empty($editRows['segment']) ? 'Update' : 'Save'; ?> Segment</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-list font-blue"></i> Segment List</div>
                                </div>
                                <div class="portlet-body table-responsive">
                                    <table class="table table-striped table-bordered crm-master-table">
                                        <thead>
                                            <tr><th>Name</th><th>Description</th><th style="width:120px;">Action</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($segments as $segment) { ?>
                                                <tr>
                                                    <td><?php echo crmEscape($segment['segment_name']); ?></td>
                                                    <td><?php echo crmEscape($segment['description']); ?></td>
                                                    <td>
                                                        <a href="crm-masters.php?tab=segment&edit_id=<?php echo (int) $segment['segment_id']; ?>" class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i></a>
                                                        <a href="crm-masters.php?tab=segment&delete_type=segment&delete_id=<?php echo (int) $segment['segment_id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete this segment?');"><i class="fa fa-trash"></i></a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade<?php echo $activeTab === 'category' ? ' active in' : ''; ?>" id="category">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-folder-open font-purple"></i> Category Form</div>
                                </div>
                                <div class="portlet-body form">
                                    <form method="post" class="form-horizontal">
                                        <input type="hidden" name="tab" value="category">
                                        <input type="hidden" name="master_type" value="category">
                                        <input type="hidden" name="edit_id" value="<?php echo (int) ($editRows['category']['category_id'] ?? 0); ?>">
                                        <div class="form-body">
                                            <div class="form-group">
                                                <label class="col-md-4 control-label">Segment</label>
                                                <div class="col-md-8">
                                                    <select name="category_segment_id" class="form-control">
                                                        <option value="0">Select Segment</option>
                                                        <?php foreach ($segments as $segment) { ?>
                                                            <option value="<?php echo (int) $segment['segment_id']; ?>" <?php echo crmSelected($editRows['category']['segment_id'] ?? 0, $segment['segment_id']); ?>><?php echo crmEscape($segment['segment_name']); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-md-4 control-label">Name</label>
                                                <div class="col-md-8">
                                                    <input type="text" name="category_name" class="form-control" value="<?php echo crmEscape($editRows['category']['category_name'] ?? ''); ?>" maxlength="150">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-md-4 control-label">Description</label>
                                                <div class="col-md-8">
                                                    <textarea name="category_description" class="form-control" rows="3"><?php echo crmEscape($editRows['category']['description'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-actions right">
                                            <a href="crm-masters.php?tab=category" class="btn default">Clear</a>
                                            <button type="submit" class="btn green"><?php echo !empty($editRows['category']) ? 'Update' : 'Save'; ?> Category</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-list font-purple"></i> Category List</div>
                                </div>
                                <div class="portlet-body table-responsive">
                                    <table class="table table-striped table-bordered crm-master-table">
                                        <thead>
                                            <tr><th>Segment</th><th>Name</th><th>Description</th><th style="width:120px;">Action</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($categories as $category) { ?>
                                                <tr>
                                                    <td><?php echo crmEscape($category['segment_name']); ?></td>
                                                    <td><?php echo crmEscape($category['category_name']); ?></td>
                                                    <td><?php echo crmEscape($category['description']); ?></td>
                                                    <td>
                                                        <a href="crm-masters.php?tab=category&edit_id=<?php echo (int) $category['category_id']; ?>" class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i></a>
                                                        <a href="crm-masters.php?tab=category&delete_type=category&delete_id=<?php echo (int) $category['category_id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete this category?');"><i class="fa fa-trash"></i></a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade<?php echo $activeTab === 'sales_person' ? ' active in' : ''; ?>" id="sales_person">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-users font-red"></i> Sales Person Form</div>
                                </div>
                                <div class="portlet-body form">
                                    <form method="post" class="form-horizontal">
                                        <input type="hidden" name="tab" value="sales_person">
                                        <input type="hidden" name="master_type" value="sales_person">
                                        <input type="hidden" name="edit_id" value="<?php echo (int) ($editRows['sales_person']['sales_person_id'] ?? 0); ?>">
                                        <div class="form-body">
                                            <div class="form-group">
                                                <label class="col-md-4 control-label">Name</label>
                                                <div class="col-md-8">
                                                    <input type="text" name="sales_person_name" class="form-control" value="<?php echo crmEscape($editRows['sales_person']['sales_person_name'] ?? ''); ?>" maxlength="150">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-md-4 control-label">Contact No</label>
                                                <div class="col-md-8">
                                                    <input type="text" name="sales_person_contact_no" class="form-control" value="<?php echo crmEscape($editRows['sales_person']['contact_no'] ?? ''); ?>" maxlength="50">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-md-4 control-label">Email</label>
                                                <div class="col-md-8">
                                                    <input type="text" name="sales_person_email" class="form-control" value="<?php echo crmEscape($editRows['sales_person']['email'] ?? ''); ?>" maxlength="150">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-actions right">
                                            <a href="crm-masters.php?tab=sales_person" class="btn default">Clear</a>
                                            <button type="submit" class="btn green"><?php echo !empty($editRows['sales_person']) ? 'Update' : 'Save'; ?> Sales Person</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-list font-red"></i> Sales Person List</div>
                                </div>
                                <div class="portlet-body table-responsive">
                                    <table class="table table-striped table-bordered crm-master-table">
                                        <thead>
                                            <tr><th>Name</th><th>Contact No</th><th>Email</th><th style="width:120px;">Action</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($salesPersons as $salesPerson) { ?>
                                                <tr>
                                                    <td><?php echo crmEscape($salesPerson['sales_person_name']); ?></td>
                                                    <td><?php echo crmEscape($salesPerson['contact_no']); ?></td>
                                                    <td><?php echo crmEscape($salesPerson['email']); ?></td>
                                                    <td>
                                                        <a href="crm-masters.php?tab=sales_person&edit_id=<?php echo (int) $salesPerson['sales_person_id']; ?>" class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i></a>
                                                        <a href="crm-masters.php?tab=sales_person&delete_type=sales_person&delete_id=<?php echo (int) $salesPerson['sales_person_id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete this sales person?');"><i class="fa fa-trash"></i></a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade<?php echo $activeTab === 'sales_cycle' ? ' active in' : ''; ?>" id="sales_cycle">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-random font-green"></i> Sales Cycle Form</div>
                                </div>
                                <div class="portlet-body form">
                                    <form method="post" class="form-horizontal">
                                        <input type="hidden" name="tab" value="sales_cycle">
                                        <input type="hidden" name="master_type" value="sales_cycle">
                                        <input type="hidden" name="edit_id" value="<?php echo (int) ($editRows['sales_cycle']['sales_cycle_id'] ?? 0); ?>">
                                        <div class="form-body">
                                            <div class="form-group">
                                                <label class="col-md-4 control-label">Code</label>
                                                <div class="col-md-8">
                                                    <input type="text" name="cycle_code" class="form-control" value="<?php echo crmEscape($editRows['sales_cycle']['cycle_code'] ?? ''); ?>" maxlength="50">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-md-4 control-label">Description</label>
                                                <div class="col-md-8">
                                                    <textarea name="cycle_description" class="form-control" rows="3"><?php echo crmEscape($editRows['sales_cycle']['cycle_description'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-md-4 control-label">Probability</label>
                                                <div class="col-md-8">
                                                    <select name="probability_calculation" class="form-control">
                                                        <?php foreach (crmProbabilityCalculationOptions() as $probabilityOption) { ?>
                                                            <option value="<?php echo crmEscape($probabilityOption); ?>" <?php echo crmSelected($editRows['sales_cycle']['probability_calculation'] ?? 'Chances of Success %', $probabilityOption); ?>><?php echo crmEscape($probabilityOption); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-actions right">
                                            <a href="crm-masters.php?tab=sales_cycle<?php echo $selectedSalesCycleId > 0 ? '&cycle_id=' . (int) $selectedSalesCycleId : ''; ?>" class="btn default">Clear</a>
                                            <button type="submit" class="btn green"><?php echo !empty($editRows['sales_cycle']) ? 'Update' : 'Save'; ?> Sales Cycle</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-list font-green"></i> Sales Cycle List</div>
                                </div>
                                <div class="portlet-body table-responsive">
                                    <table class="table table-striped table-bordered crm-master-table">
                                        <thead>
                                            <tr><th>Code</th><th>Description</th><th>Probability Calculation</th><th>Stages</th><th style="width:160px;">Action</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($salesCycles as $salesCycle) { ?>
                                                <tr<?php echo $selectedSalesCycleId === (int) $salesCycle['sales_cycle_id'] ? ' class="info"' : ''; ?>>
                                                    <td><?php echo crmEscape($salesCycle['cycle_code']); ?></td>
                                                    <td><?php echo crmEscape($salesCycle['cycle_description']); ?></td>
                                                    <td><?php echo crmEscape($salesCycle['probability_calculation']); ?></td>
                                                    <td><?php echo (int) ($salesCycle['stage_count'] ?? 0); ?></td>
                                                    <td>
                                                        <a href="crm-masters.php?tab=sales_cycle&cycle_id=<?php echo (int) $salesCycle['sales_cycle_id']; ?>" class="btn btn-xs btn-default" title="Stages"><i class="fa fa-list-ul"></i></a>
                                                        <a href="crm-masters.php?tab=sales_cycle&edit_id=<?php echo (int) $salesCycle['sales_cycle_id']; ?>&cycle_id=<?php echo (int) $salesCycle['sales_cycle_id']; ?>" class="btn btn-xs btn-primary" title="Edit"><i class="fa fa-pencil"></i></a>
                                                        <a href="crm-masters.php?tab=sales_cycle&delete_type=sales_cycle&delete_id=<?php echo (int) $salesCycle['sales_cycle_id']; ?>" class="btn btn-xs btn-danger" title="Delete" onclick="return confirm('Delete this sales cycle and all its stages?');"><i class="fa fa-trash"></i></a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-tasks font-purple"></i> Cycle Stage Form</div>
                                </div>
                                <div class="portlet-body form">
                                    <?php if ($selectedSalesCycle) { ?>
                                        <form method="post" class="form-horizontal">
                                            <input type="hidden" name="tab" value="sales_cycle">
                                            <input type="hidden" name="master_type" value="sales_cycle_stage">
                                            <input type="hidden" name="sales_cycle_id" value="<?php echo (int) $selectedSalesCycle['sales_cycle_id']; ?>">
                                            <input type="hidden" name="edit_id" value="<?php echo (int) ($editRows['sales_cycle_stage']['sales_cycle_stage_id'] ?? 0); ?>">
                                            <div class="form-body">
                                                <div class="form-group">
                                                    <label class="col-md-4 control-label">Sales Cycle</label>
                                                    <div class="col-md-8">
                                                        <p class="form-control-static"><?php echo crmEscape($selectedSalesCycle['cycle_code'] . ' - ' . $selectedSalesCycle['cycle_description']); ?></p>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-md-4 control-label">Stage</label>
                                                    <div class="col-md-8">
                                                        <input type="number" min="1" name="stage_no" class="form-control" value="<?php echo crmEscape($editRows['sales_cycle_stage']['stage_no'] ?? ''); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-md-4 control-label">Description</label>
                                                    <div class="col-md-8">
                                                        <textarea name="stage_description" class="form-control" rows="3"><?php echo crmEscape($editRows['sales_cycle_stage']['stage_description'] ?? ''); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-md-4 control-label">Completed %</label>
                                                    <div class="col-md-8">
                                                        <input type="number" min="0" max="100" step="0.01" name="completed_percent" class="form-control" value="<?php echo crmEscape($editRows['sales_cycle_stage']['completed_percent'] ?? '0'); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-md-4 control-label">Success %</label>
                                                    <div class="col-md-8">
                                                        <input type="number" min="0" max="100" step="0.01" name="chance_of_success_percent" class="form-control" value="<?php echo crmEscape($editRows['sales_cycle_stage']['chance_of_success_percent'] ?? '0'); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-md-4 control-label">Activity Code</label>
                                                    <div class="col-md-8">
                                                        <select name="activity_code" class="form-control">
                                                            <option value="">-- None --</option>
                                                            <?php foreach ($activities as $actOpt) { ?>
                                                                <option value="<?php echo crmEscape($actOpt['activity_code']); ?>" <?php echo crmSelected($editRows['sales_cycle_stage']['activity_code'] ?? '', $actOpt['activity_code']); ?>><?php echo crmEscape($actOpt['activity_code'] . ' - ' . $actOpt['description']); ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-actions right">
                                                <a href="crm-masters.php?tab=sales_cycle&cycle_id=<?php echo (int) $selectedSalesCycle['sales_cycle_id']; ?>" class="btn default">Clear</a>
                                                <button type="submit" class="btn green"><?php echo !empty($editRows['sales_cycle_stage']) ? 'Update' : 'Save'; ?> Cycle Stage</button>
                                            </div>
                                        </form>
                                    <?php } else { ?>
                                        <div class="alert alert-info" style="margin-bottom:0;">Create or select a sales cycle first to manage its stages.</div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-table font-purple"></i> Cycle Stage List</div>
                                    <?php if ($selectedSalesCycle) { ?>
                                        <div class="actions">
                                            <span class="btn btn-sm default disabled"><?php echo crmEscape($selectedSalesCycle['cycle_code']); ?></span>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="portlet-body table-responsive">
                                    <?php if ($selectedSalesCycle) { ?>
                                        <table class="table table-striped table-bordered crm-master-table">
                                            <thead>
                                                <tr><th>Stage</th><th>Description</th><th>Completed %</th><th>Chances of Success %</th><th>Activity Code</th><th style="width:120px;">Action</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($salesCycleStages as $stage) { ?>
                                                    <tr>
                                                        <td><?php echo (int) $stage['stage_no']; ?></td>
                                                        <td><?php echo crmEscape($stage['stage_description']); ?></td>
                                                        <td><?php echo crmEscape(rtrim(rtrim(number_format((float) $stage['completed_percent'], 2, '.', ''), '0'), '.')); ?></td>
                                                        <td><?php echo crmEscape(rtrim(rtrim(number_format((float) $stage['chance_of_success_percent'], 2, '.', ''), '0'), '.')); ?></td>
                                                        <td><?php echo crmEscape($stage['activity_code']); ?></td>
                                                        <td>
                                                            <a href="crm-masters.php?tab=sales_cycle&cycle_id=<?php echo (int) $selectedSalesCycle['sales_cycle_id']; ?>&stage_edit_id=<?php echo (int) $stage['sales_cycle_stage_id']; ?>" class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i></a>
                                                            <a href="crm-masters.php?tab=sales_cycle&cycle_id=<?php echo (int) $selectedSalesCycle['sales_cycle_id']; ?>&delete_type=sales_cycle_stage&delete_id=<?php echo (int) $stage['sales_cycle_stage_id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete this cycle stage?');"><i class="fa fa-trash"></i></a>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    <?php } else { ?>
                                        <div class="alert alert-info" style="margin-bottom:0;">No sales cycle selected.</div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade<?php echo $activeTab === 'activity' ? ' active in' : ''; ?>" id="activity">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-flag font-green"></i> Activity Form</div>
                                </div>
                                <div class="portlet-body form">
                                    <form method="post" class="form-horizontal">
                                        <input type="hidden" name="tab" value="activity">
                                        <input type="hidden" name="master_type" value="activity">
                                        <input type="hidden" name="edit_id" value="<?php echo (int) ($editRows['activity']['activity_id'] ?? 0); ?>">
                                        <div class="form-body">
                                            <div class="form-group">
                                                <label class="col-md-4 control-label">Code</label>
                                                <div class="col-md-8">
                                                    <input type="text" name="activity_code" class="form-control" value="<?php echo crmEscape($editRows['activity']['activity_code'] ?? ''); ?>" maxlength="50" style="text-transform:uppercase;">
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-md-4 control-label">Description</label>
                                                <div class="col-md-8">
                                                    <textarea name="activity_description" class="form-control" rows="3"><?php echo crmEscape($editRows['activity']['description'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-actions right">
                                            <a href="crm-masters.php?tab=activity<?php echo $selectedActivityId > 0 ? '&activity_id=' . (int) $selectedActivityId : ''; ?>" class="btn default">Clear</a>
                                            <button type="submit" class="btn green"><?php echo !empty($editRows['activity']) ? 'Update' : 'Save'; ?> Activity</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-list font-green"></i> Activity List</div>
                                </div>
                                <div class="portlet-body table-responsive">
                                    <table class="table table-striped table-bordered crm-master-table">
                                        <thead>
                                            <tr><th>Code</th><th>Description</th><th>Lines</th><th style="width:160px;">Action</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($activities as $activity) { ?>
                                                <tr<?php echo $selectedActivityId === (int) $activity['activity_id'] ? ' class="info"' : ''; ?>>
                                                    <td><?php echo crmEscape($activity['activity_code']); ?></td>
                                                    <td><?php echo crmEscape($activity['description']); ?></td>
                                                    <td><?php echo (int) ($activity['line_count'] ?? 0); ?></td>
                                                    <td>
                                                        <a href="crm-masters.php?tab=activity&activity_id=<?php echo (int) $activity['activity_id']; ?>" class="btn btn-xs btn-default" title="Lines"><i class="fa fa-list-ul"></i></a>
                                                        <a href="crm-masters.php?tab=activity&edit_id=<?php echo (int) $activity['activity_id']; ?>&activity_id=<?php echo (int) $activity['activity_id']; ?>" class="btn btn-xs btn-primary" title="Edit"><i class="fa fa-pencil"></i></a>
                                                        <a href="crm-masters.php?tab=activity&delete_type=activity&delete_id=<?php echo (int) $activity['activity_id']; ?>" class="btn btn-xs btn-danger" title="Delete" onclick="return confirm('Delete this activity and all its lines?');"><i class="fa fa-trash"></i></a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-tasks font-purple"></i> Activity Line Form</div>
                                </div>
                                <div class="portlet-body form">
                                    <?php if ($selectedActivity) { ?>
                                        <form method="post" class="form-horizontal">
                                            <input type="hidden" name="tab" value="activity">
                                            <input type="hidden" name="master_type" value="activity_line">
                                            <input type="hidden" name="activity_id" value="<?php echo (int) $selectedActivity['activity_id']; ?>">
                                            <input type="hidden" name="edit_id" value="<?php echo (int) ($editRows['activity_line']['activity_line_id'] ?? 0); ?>">
                                            <div class="form-body">
                                                <div class="form-group">
                                                    <label class="col-md-4 control-label">Activity</label>
                                                    <div class="col-md-8">
                                                        <p class="form-control-static"><?php echo crmEscape($selectedActivity['activity_code'] . ' - ' . $selectedActivity['description']); ?></p>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-md-4 control-label">Type</label>
                                                    <div class="col-md-8">
                                                        <input type="text" name="line_type" class="form-control" value="<?php echo crmEscape($editRows['activity_line']['line_type'] ?? ''); ?>" maxlength="100">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-md-4 control-label">Description</label>
                                                    <div class="col-md-8">
                                                        <textarea name="line_description" class="form-control" rows="3"><?php echo crmEscape($editRows['activity_line']['description'] ?? ''); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-md-4 control-label">Activity %</label>
                                                    <div class="col-md-8">
                                                        <input type="number" min="0" max="100" step="0.01" name="activity_percentage" class="form-control" value="<?php echo crmEscape($editRows['activity_line']['activity_percentage'] ?? '0'); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-md-4 control-label">Priority</label>
                                                    <div class="col-md-8">
                                                        <select name="priority" class="form-control">
                                                            <?php foreach (crmActivityPriorities() as $priorityOption) { ?>
                                                                <option value="<?php echo crmEscape($priorityOption); ?>" <?php echo crmSelected($editRows['activity_line']['priority'] ?? 'Low', $priorityOption); ?>><?php echo crmEscape($priorityOption); ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-md-4 control-label">Date Formula</label>
                                                    <div class="col-md-8">
                                                        <input type="text" name="date_formula" class="form-control" value="<?php echo crmEscape($editRows['activity_line']['date_formula'] ?? ''); ?>" maxlength="30" placeholder="e.g. 2D, 1W, 1M">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-actions right">
                                                <a href="crm-masters.php?tab=activity&activity_id=<?php echo (int) $selectedActivity['activity_id']; ?>" class="btn default">Clear</a>
                                                <button type="submit" class="btn green"><?php echo !empty($editRows['activity_line']) ? 'Update' : 'Save'; ?> Activity Line</button>
                                            </div>
                                        </form>
                                    <?php } else { ?>
                                        <div class="alert alert-info" style="margin-bottom:0;">Create or select an activity first to manage its lines.</div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="portlet light bordered">
                                <div class="portlet-title">
                                    <div class="caption"><i class="fa fa-table font-purple"></i> Activity Lines</div>
                                    <?php if ($selectedActivity) { ?>
                                        <div class="actions">
                                            <span class="btn btn-sm default disabled"><?php echo crmEscape($selectedActivity['activity_code']); ?></span>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="portlet-body table-responsive">
                                    <?php if ($selectedActivity) { ?>
                                        <table class="table table-striped table-bordered crm-master-table">
                                            <thead>
                                                <tr><th>Type</th><th>Description</th><th>Activity %</th><th>Priority</th><th>Date Formula</th><th style="width:120px;">Action</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($activityLines as $line) { ?>
                                                    <tr>
                                                        <td><?php echo crmEscape($line['line_type']); ?></td>
                                                        <td><?php echo crmEscape($line['description']); ?></td>
                                                        <td><?php echo crmEscape(rtrim(rtrim(number_format((float) $line['activity_percentage'], 2, '.', ''), '0'), '.')); ?></td>
                                                        <td><?php echo crmEscape($line['priority']); ?></td>
                                                        <td><?php echo crmEscape($line['date_formula']); ?></td>
                                                        <td>
                                                            <a href="crm-masters.php?tab=activity&activity_id=<?php echo (int) $selectedActivity['activity_id']; ?>&line_edit_id=<?php echo (int) $line['activity_line_id']; ?>" class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i></a>
                                                            <a href="crm-masters.php?tab=activity&activity_id=<?php echo (int) $selectedActivity['activity_id']; ?>&delete_type=activity_line&delete_id=<?php echo (int) $line['activity_line_id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete this activity line?');"><i class="fa fa-trash"></i></a>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    <?php } else { ?>
                                        <div class="alert alert-info" style="margin-bottom:0;">No activity selected.</div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<?php include('common/footer.php'); ?>
<script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
</body>
</html>