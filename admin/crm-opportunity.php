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

$contacts = crmFetchOpportunityContacts($db);
$salesCycles = crmFetchSalesCycles($db);
$salesPersons = crmFetchSalesPersons($db);
$segments = crmFetchSegments($db);

$contactMap = [];
foreach ($contacts as $contact) {
    $contactMap[(int) ($contact['person_id'] ?? 0)] = $contact;
}

$salesCycleMap = [];
foreach ($salesCycles as $salesCycle) {
    $salesCycleMap[(int) ($salesCycle['sales_cycle_id'] ?? 0)] = $salesCycle;
}

$salesPersonMap = [];
foreach ($salesPersons as $salesPerson) {
    $salesPersonMap[(int) ($salesPerson['sales_person_id'] ?? 0)] = $salesPerson;
}

$segmentMap = [];
foreach ($segments as $segment) {
    $segmentMap[(int) ($segment['segment_id'] ?? 0)] = $segment;
}

$editId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = false;
$existingOpportunityCode = '';
$existingOpportunity = null;

$formData = [
    'description' => '',
    'person_id' => 0,
    'contact_no' => '',
    'contact_name' => '',
    'phone_no' => '',
    'mobile_phone_no' => '',
    'email' => '',
    'contact_company_name' => '',
    'company_id' => 0,
    'sales_person_id' => 0,
    'sales_document_type' => 'Quote',
    'sales_document_no' => '',
    'sales_cycle_id' => 0,
    'current_sales_cycle_stage_id' => 0,
    'status' => 'In Progress',
    'is_closed' => 0,
    'creation_date' => date('Y-m-d'),
    'date_closed' => '',
    'segment_id' => 0,
    'estimated_sales_value' => '0.00',
    'chance_of_success_percent' => '0.00',
    'estimated_closing_date_for_stage' => '',
    'estimated_gp' => '0.00'
];

if (isset($_GET['delete_id'])) {
    $deleteId = (int) $_GET['delete_id'];
    if ($deleteId > 0) {
        try {
            $db->deleteRow('DELETE FROM crm_opportunity WHERE opportunity_id = ?', [$deleteId]);
            $message = 'Opportunity deleted successfully.';
        } catch (Exception $e) {
            $message = 'Unable to delete this opportunity right now.';
            $messageClass = 'alert-danger';
        }
    }
}

if ($editId > 0) {
    $record = crmFetchOpportunity($db, $editId);
    if ($record) {
        $existingOpportunity = $record;
        $isEdit = true;
        $existingOpportunityCode = (string) ($record['opportunity_code'] ?? '');
        $formData['description'] = (string) ($record['description'] ?? '');
        $formData['person_id'] = (int) ($record['person_id'] ?? 0);
        $formData['contact_no'] = (string) ($record['contact_no'] ?? '');
        $formData['contact_name'] = (string) ($record['contact_name'] ?? '');
        $formData['phone_no'] = (string) ($record['phone_no'] ?? '');
        $formData['mobile_phone_no'] = (string) ($record['mobile_phone_no'] ?? '');
        $formData['email'] = (string) ($record['email'] ?? '');
        $formData['contact_company_name'] = (string) ($record['contact_company_name'] ?? '');
        $formData['company_id'] = (int) ($record['company_id'] ?? 0);
        $formData['sales_person_id'] = (int) ($record['sales_person_id'] ?? 0);
        $formData['sales_document_type'] = (string) ($record['sales_document_type'] ?? 'Quote');
        $formData['sales_document_no'] = (string) ($record['sales_document_no'] ?? '');
        $formData['sales_cycle_id'] = (int) ($record['sales_cycle_id'] ?? 0);
        $formData['current_sales_cycle_stage_id'] = (int) ($record['current_sales_cycle_stage_id'] ?? 0);
        $formData['status'] = (string) ($record['status'] ?? 'In Progress');
        $formData['is_closed'] = (int) ($record['is_closed'] ?? 0);
        $formData['creation_date'] = (string) ($record['creation_date'] ?? date('Y-m-d'));
        $formData['date_closed'] = (string) ($record['date_closed'] ?? '');
        $formData['segment_id'] = (int) ($record['segment_id'] ?? 0);
        $formData['estimated_sales_value'] = number_format((float) ($record['estimated_sales_value'] ?? 0), 2, '.', '');
        $formData['chance_of_success_percent'] = number_format((float) ($record['chance_of_success_percent'] ?? 0), 2, '.', '');
        $formData['estimated_closing_date_for_stage'] = (string) ($record['estimated_closing_date_for_stage'] ?? '');
        $formData['estimated_gp'] = number_format((float) ($record['estimated_gp'] ?? 0), 2, '.', '');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editId = (int) ($_POST['edit_id'] ?? 0);
    $isEdit = $editId > 0;
    $existingOpportunityCode = trim((string) ($_POST['existing_opportunity_code'] ?? ''));
    $existingOpportunity = $isEdit ? crmFetchOpportunity($db, $editId) : null;

    $formData['description'] = trim($_POST['description'] ?? '');
    $formData['person_id'] = (int) ($_POST['person_id'] ?? 0);
    $formData['phone_no'] = trim($_POST['phone_no'] ?? '');
    $formData['sales_person_id'] = (int) ($_POST['sales_person_id'] ?? 0);
    $formData['sales_document_type'] = trim($_POST['sales_document_type'] ?? 'Quote');
    $formData['sales_document_no'] = trim($_POST['sales_document_no'] ?? '');
    $formData['sales_cycle_id'] = (int) ($_POST['sales_cycle_id'] ?? 0);
    $formData['status'] = trim($_POST['status'] ?? 'In Progress');
    $formData['is_closed'] = isset($_POST['is_closed']) ? 1 : 0;
    $formData['creation_date'] = trim($_POST['creation_date'] ?? date('Y-m-d'));
    $formData['date_closed'] = trim($_POST['date_closed'] ?? '');
    $formData['segment_id'] = (int) ($_POST['segment_id'] ?? 0);
    $formData['estimated_gp'] = trim($_POST['estimated_gp'] ?? '0.00');

    $errors = [];
    $selectedContact = $contactMap[$formData['person_id']] ?? null;

    if ($formData['description'] === '') {
        $errors[] = 'Description is required.';
    }
    if (!$selectedContact) {
        $errors[] = 'Please select a valid contact number.';
    }
    if (!isset($salesCycleMap[$formData['sales_cycle_id']])) {
        $errors[] = 'Please select a valid sales cycle.';
    }
    if (!in_array($formData['sales_document_type'], crmSalesDocumentTypes(), true)) {
        $errors[] = 'Please select a valid sales document type.';
    }
    if (!in_array($formData['status'], crmOpportunityStatuses(), true)) {
        $errors[] = 'Please select a valid opportunity status.';
    }
    if ($formData['creation_date'] === '') {
        $errors[] = 'Creation date is required.';
    }
    if ($formData['estimated_gp'] === '' || !is_numeric($formData['estimated_gp'])) {
        $errors[] = 'Estimated GP must be a valid number.';
    }
    if ($formData['sales_person_id'] > 0 && !isset($salesPersonMap[$formData['sales_person_id']])) {
        $errors[] = 'Please select a valid salesperson.';
    }
    if ($formData['segment_id'] > 0 && !isset($segmentMap[$formData['segment_id']])) {
        $errors[] = 'Please select a valid segment.';
    }

    if ($selectedContact) {
        $formData['contact_no'] = (string) ($selectedContact['person_code'] ?? '');
        $formData['contact_name'] = (string) ($selectedContact['contact_name'] ?? '');
        $formData['mobile_phone_no'] = (string) ($selectedContact['mobile_phone_no'] ?? '');
        $formData['email'] = (string) ($selectedContact['email'] ?? '');
        $formData['company_id'] = (int) ($selectedContact['company_id'] ?? 0);
        $formData['contact_company_name'] = (string) ($selectedContact['company_name'] ?? '');

        if ($formData['phone_no'] === '') {
            $formData['phone_no'] = (string) ($selectedContact['company_phone_no'] ?? '');
        }
        if ($formData['sales_person_id'] <= 0 && (int) ($selectedContact['sales_person_id'] ?? 0) > 0) {
            $formData['sales_person_id'] = (int) $selectedContact['sales_person_id'];
        }
        if ($formData['segment_id'] <= 0 && (int) ($selectedContact['segment_id'] ?? 0) > 0) {
            $formData['segment_id'] = (int) $selectedContact['segment_id'];
        }
    }

    if ($formData['is_closed']) {
        if ($formData['date_closed'] === '') {
            $formData['date_closed'] = date('Y-m-d');
        }
    } else {
        $formData['date_closed'] = '';
    }

    $previousSalesCycleId = (int) ($existingOpportunity['sales_cycle_id'] ?? 0);
    $currentStageId = (int) ($existingOpportunity['current_sales_cycle_stage_id'] ?? 0);
    $currentActivityLineIdForSave = $isEdit && $existingOpportunity && (int) ($existingOpportunity['current_activity_line_id'] ?? 0) > 0
        ? (int) $existingOpportunity['current_activity_line_id']
        : null;
    $estimatedSalesValue = (float) ($existingOpportunity['estimated_sales_value'] ?? 0);
    $chanceOfSuccessPercent = (float) ($existingOpportunity['chance_of_success_percent'] ?? 0);
    $estimatedClosingDateForStage = (string) ($existingOpportunity['estimated_closing_date_for_stage'] ?? '');
    $firstStage = null;

    if (isset($salesCycleMap[$formData['sales_cycle_id']])) {
        $firstStage = crmFetchFirstSalesCycleStage($db, $formData['sales_cycle_id']);
        if (!$firstStage) {
            $errors[] = 'Selected sales cycle has no stages.';
        }
    }

    if (empty($errors)) {
        $resetStage = !$isEdit || !$existingOpportunity || $previousSalesCycleId !== $formData['sales_cycle_id'] || $currentStageId <= 0;
        $currentStage = $resetStage ? $firstStage : crmFetchSalesCycleStage($db, $currentStageId);

        if (!$currentStage) {
            $currentStage = $firstStage;
        }

        if ($currentStage) {
            $currentStageId = (int) $currentStage['sales_cycle_stage_id'];
            if ($resetStage) {
                $currentActivityLineIdForSave = null;
                $chanceOfSuccessPercent = (float) ($currentStage['chance_of_success_percent'] ?? 0);
                $estimatedClosingDateForStage = $formData['creation_date'];
            }
        }

        $formData['current_sales_cycle_stage_id'] = $currentStageId;
        $formData['estimated_sales_value'] = number_format($estimatedSalesValue, 2, '.', '');
        $formData['chance_of_success_percent'] = number_format($chanceOfSuccessPercent, 2, '.', '');
        $formData['estimated_closing_date_for_stage'] = $estimatedClosingDateForStage;
    }

    if (empty($errors)) {
        try {
            $opportunityCode = $existingOpportunityCode !== '' ? $existingOpportunityCode : crmGenerateOpportunityCode($db);

            if ($isEdit) {
                $db->updateRow(
                    'UPDATE crm_opportunity SET description = ?, person_id = ?, company_id = ?, sales_cycle_id = ?, current_sales_cycle_stage_id = ?, current_activity_line_id = ?, segment_id = ?, sales_person_id = ?, contact_no = ?, contact_name = ?, phone_no = ?, mobile_phone_no = ?, email = ?, contact_company_name = ?, sales_document_type = ?, sales_document_no = ?, status = ?, is_closed = ?, creation_date = ?, date_closed = ?, estimated_sales_value = ?, chance_of_success_percent = ?, estimated_closing_date_for_stage = ?, estimated_gp = ? WHERE opportunity_id = ?',
                    [
                        $formData['description'],
                        $formData['person_id'],
                        $formData['company_id'] > 0 ? $formData['company_id'] : null,
                        $formData['sales_cycle_id'],
                        $formData['current_sales_cycle_stage_id'] > 0 ? $formData['current_sales_cycle_stage_id'] : null,
                        $currentActivityLineIdForSave,
                        $formData['segment_id'] > 0 ? $formData['segment_id'] : null,
                        $formData['sales_person_id'] > 0 ? $formData['sales_person_id'] : null,
                        $formData['contact_no'],
                        $formData['contact_name'],
                        $formData['phone_no'],
                        $formData['mobile_phone_no'],
                        $formData['email'],
                        $formData['contact_company_name'],
                        $formData['sales_document_type'],
                        $formData['sales_document_no'],
                        $formData['status'],
                        $formData['is_closed'],
                        $formData['creation_date'],
                        $formData['date_closed'] !== '' ? $formData['date_closed'] : null,
                        (float) $formData['estimated_sales_value'],
                        (float) $formData['chance_of_success_percent'],
                        $formData['estimated_closing_date_for_stage'] !== '' ? $formData['estimated_closing_date_for_stage'] : null,
                        (float) $formData['estimated_gp'],
                        $editId
                    ]
                );
                $message = 'Opportunity updated successfully.';
            } else {
                $db->insertRow(
                    'INSERT INTO crm_opportunity (opportunity_code, description, person_id, company_id, sales_cycle_id, current_sales_cycle_stage_id, current_activity_line_id, segment_id, sales_person_id, contact_no, contact_name, phone_no, mobile_phone_no, email, contact_company_name, sales_document_type, sales_document_no, status, is_closed, creation_date, date_closed, estimated_sales_value, chance_of_success_percent, estimated_closing_date_for_stage, estimated_gp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $opportunityCode,
                        $formData['description'],
                        $formData['person_id'],
                        $formData['company_id'] > 0 ? $formData['company_id'] : null,
                        $formData['sales_cycle_id'],
                        $formData['current_sales_cycle_stage_id'] > 0 ? $formData['current_sales_cycle_stage_id'] : null,
                        $currentActivityLineIdForSave,
                        $formData['segment_id'] > 0 ? $formData['segment_id'] : null,
                        $formData['sales_person_id'] > 0 ? $formData['sales_person_id'] : null,
                        $formData['contact_no'],
                        $formData['contact_name'],
                        $formData['phone_no'],
                        $formData['mobile_phone_no'],
                        $formData['email'],
                        $formData['contact_company_name'],
                        $formData['sales_document_type'],
                        $formData['sales_document_no'],
                        $formData['status'],
                        $formData['is_closed'],
                        $formData['creation_date'],
                        $formData['date_closed'] !== '' ? $formData['date_closed'] : null,
                        (float) $formData['estimated_sales_value'],
                        (float) $formData['chance_of_success_percent'],
                        $formData['estimated_closing_date_for_stage'] !== '' ? $formData['estimated_closing_date_for_stage'] : null,
                        (float) $formData['estimated_gp']
                    ]
                );
                $message = 'Opportunity created successfully.';
            }

            $messageClass = 'alert-success';
            $isEdit = false;
            $editId = 0;
            $existingOpportunityCode = '';
            $formData = [
                'description' => '',
                'person_id' => 0,
                'contact_no' => '',
                'contact_name' => '',
                'phone_no' => '',
                'mobile_phone_no' => '',
                'email' => '',
                'contact_company_name' => '',
                'company_id' => 0,
                'sales_person_id' => 0,
                'sales_document_type' => 'Quote',
                'sales_document_no' => '',
                'sales_cycle_id' => 0,
                'current_sales_cycle_stage_id' => 0,
                'status' => 'In Progress',
                'is_closed' => 0,
                'creation_date' => date('Y-m-d'),
                'date_closed' => '',
                'segment_id' => 0,
                'estimated_sales_value' => '0.00',
                'chance_of_success_percent' => '0.00',
                'estimated_closing_date_for_stage' => '',
                'estimated_gp' => '0.00'
            ];
        } catch (Exception $e) {
            $message = 'Unable to save opportunity right now.';
            $messageClass = 'alert-danger';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageClass = 'alert-danger';
    }
}

$opportunities = crmFetchOpportunities($db);
$displayCurrentStage = null;
if ($formData['sales_cycle_id'] > 0) {
    if ($formData['current_sales_cycle_stage_id'] > 0) {
        $displayCurrentStage = crmFetchSalesCycleStage($db, $formData['current_sales_cycle_stage_id']);
    }
    if (!$displayCurrentStage) {
        $displayCurrentStage = crmFetchFirstSalesCycleStage($db, $formData['sales_cycle_id']);
    }
}
$contactJson = [];
foreach ($contacts as $contact) {
    $contactJson[] = [
        'person_id' => (int) ($contact['person_id'] ?? 0),
        'person_code' => (string) ($contact['person_code'] ?? ''),
        'contact_name' => (string) ($contact['contact_name'] ?? ''),
        'mobile_phone_no' => (string) ($contact['mobile_phone_no'] ?? ''),
        'email' => (string) ($contact['email'] ?? ''),
        'company_id' => (int) ($contact['company_id'] ?? 0),
        'company_name' => (string) ($contact['company_name'] ?? ''),
        'company_phone_no' => (string) ($contact['company_phone_no'] ?? ''),
        'segment_id' => (int) ($contact['segment_id'] ?? 0),
        'sales_person_id' => (int) ($contact['sales_person_id'] ?? 0)
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>CRM Opportunity</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <?php include('common/head.php'); ?>
    <style>
        .crm-opportunity-readonly {
            background: #f3f6f9;
        }

        .crm-opportunity-meta {
            margin-bottom: 20px;
        }

        .crm-opportunity-meta .btn {
            margin-right: 10px;
            margin-bottom: 10px;
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
                    <li><span>Opportunity</span></li>
                </ul>
            </div>
            <h3 class="page-title">CRM Opportunity Entry</h3>

            <?php if ($message !== '') { ?>
                <div class="alert <?php echo $messageClass; ?>"><?php echo $message; ?></div>
            <?php } ?>

            <div class="crm-opportunity-meta">
                <a href="crm.php" class="btn default"><i class="fa fa-arrow-left"></i> CRM Dashboard</a>
                <a href="crm-masters.php?tab=sales_cycle" class="btn blue"><i class="fa fa-random"></i> Manage Sales Cycles</a>
                <a href="crm.php?type=person" class="btn green"><i class="fa fa-user-plus"></i> Manage Persons</a>
            </div>

            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption"><i class="fa fa-briefcase font-green"></i> <?php echo $isEdit ? 'Edit Opportunity' : 'Create Opportunity'; ?></div>
                </div>
                <div class="portlet-body form">
                    <form method="post" class="form-horizontal">
                        <input type="hidden" name="edit_id" value="<?php echo (int) $editId; ?>">
                        <input type="hidden" name="existing_opportunity_code" value="<?php echo crmEscape($existingOpportunityCode); ?>">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Description</label>
                                        <div class="col-md-8">
                                            <input type="text" name="description" class="form-control" value="<?php echo crmEscape($formData['description']); ?>" maxlength="255">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Contact No</label>
                                        <div class="col-md-8">
                                            <select name="person_id" id="person_id" class="form-control">
                                                <option value="0">Select Person</option>
                                                <?php foreach ($contacts as $contact) { ?>
                                                    <option value="<?php echo (int) $contact['person_id']; ?>" <?php echo crmSelected($formData['person_id'], $contact['person_id']); ?>>
                                                        <?php echo crmEscape($contact['person_code'] . ' - ' . $contact['contact_name']); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                            <span class="help-block">Contact No maps to the CRM Person record.</span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Contact Name</label>
                                        <div class="col-md-8">
                                            <input type="text" id="contact_name" class="form-control crm-opportunity-readonly" value="<?php echo crmEscape($formData['contact_name']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Phone No</label>
                                        <div class="col-md-8">
                                            <input type="text" name="phone_no" id="phone_no" class="form-control crm-opportunity-readonly" value="<?php echo crmEscape($formData['phone_no']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Mobile Phone No</label>
                                        <div class="col-md-8">
                                            <input type="text" id="mobile_phone_no" class="form-control crm-opportunity-readonly" value="<?php echo crmEscape($formData['mobile_phone_no']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Email</label>
                                        <div class="col-md-8">
                                            <input type="text" id="email" class="form-control crm-opportunity-readonly" value="<?php echo crmEscape($formData['email']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Contact Company Name</label>
                                        <div class="col-md-8">
                                            <input type="text" id="contact_company_name" class="form-control crm-opportunity-readonly" value="<?php echo crmEscape($formData['contact_company_name']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Salesperson Code</label>
                                        <div class="col-md-8">
                                            <select name="sales_person_id" id="sales_person_id" class="form-control">
                                                <option value="0">Select Salesperson</option>
                                                <?php foreach ($salesPersons as $salesPerson) { ?>
                                                    <option value="<?php echo (int) $salesPerson['sales_person_id']; ?>" <?php echo crmSelected($formData['sales_person_id'], $salesPerson['sales_person_id']); ?>><?php echo crmEscape($salesPerson['sales_person_name']); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Sales Document Type</label>
                                        <div class="col-md-8">
                                            <select name="sales_document_type" class="form-control">
                                                <?php foreach (crmSalesDocumentTypes() as $salesDocumentType) { ?>
                                                    <option value="<?php echo crmEscape($salesDocumentType); ?>" <?php echo crmSelected($formData['sales_document_type'], $salesDocumentType); ?>><?php echo crmEscape($salesDocumentType); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Sales Document No</label>
                                        <div class="col-md-8">
                                            <input type="text" name="sales_document_no" class="form-control" value="<?php echo crmEscape($formData['sales_document_no']); ?>" maxlength="100">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Sales Cycle Code</label>
                                        <div class="col-md-8">
                                            <select name="sales_cycle_id" class="form-control">
                                                <option value="0">Select Sales Cycle</option>
                                                <?php foreach ($salesCycles as $salesCycle) { ?>
                                                    <option value="<?php echo (int) $salesCycle['sales_cycle_id']; ?>" <?php echo crmSelected($formData['sales_cycle_id'], $salesCycle['sales_cycle_id']); ?>><?php echo crmEscape($salesCycle['cycle_code']); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Current Stage</label>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control crm-opportunity-readonly" value="<?php echo $displayCurrentStage ? crmEscape($displayCurrentStage['stage_no'] . ' - ' . $displayCurrentStage['stage_description']) : ''; ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Status</label>
                                        <div class="col-md-8">
                                            <select name="status" class="form-control">
                                                <?php foreach (crmOpportunityStatuses() as $statusOption) { ?>
                                                    <option value="<?php echo crmEscape($statusOption); ?>" <?php echo crmSelected($formData['status'], $statusOption); ?>><?php echo crmEscape($statusOption); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Closed</label>
                                        <div class="col-md-8">
                                            <div class="mt-checkbox-inline" style="padding-top:7px;">
                                                <label class="mt-checkbox mt-checkbox-outline">
                                                    <input type="checkbox" name="is_closed" id="is_closed" value="1" <?php echo crmChecked((int) $formData['is_closed'] === 1); ?>>
                                                    <span></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Creation Date</label>
                                        <div class="col-md-8">
                                            <input type="date" name="creation_date" class="form-control" value="<?php echo crmEscape($formData['creation_date']); ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Date Closed</label>
                                        <div class="col-md-8">
                                            <input type="date" name="date_closed" id="date_closed" class="form-control" value="<?php echo crmEscape($formData['date_closed']); ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Segment No</label>
                                        <div class="col-md-8">
                                            <select name="segment_id" id="segment_id" class="form-control">
                                                <option value="0">Select Segment</option>
                                                <?php foreach ($segments as $segment) { ?>
                                                    <option value="<?php echo (int) $segment['segment_id']; ?>" <?php echo crmSelected($formData['segment_id'], $segment['segment_id']); ?>><?php echo crmEscape($segment['segment_name']); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Estimated GP</label>
                                        <div class="col-md-8">
                                            <input type="number" min="0" step="0.01" name="estimated_gp" class="form-control" value="<?php echo crmEscape($formData['estimated_gp']); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions right">
                            <a href="crm-opportunity.php" class="btn default">Clear</a>
                            <?php if ($isEdit) { ?>
                                <a href="crm-opportunity-update.php?id=<?php echo (int) $editId; ?>" class="btn blue"><i class="fa fa-random"></i> Update Opportunity</a>
                            <?php } ?>
                            <button type="submit" class="btn green"><i class="fa fa-check"></i> <?php echo $isEdit ? 'Update' : 'Save'; ?> Opportunity</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption"><i class="fa fa-list font-blue"></i> Opportunity List</div>
                </div>
                <div class="portlet-body table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Opportunity No</th>
                                <th>Description</th>
                                <th>Contact No</th>
                                <th>Contact Name</th>
                                <th>Company</th>
                                <th>Sales Cycle</th>
                                <th>Stage</th>
                                <th>Status</th>
                                <th>Creation Date</th>
                                <th style="width:160px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($opportunities as $opportunity) { ?>
                                <tr>
                                    <td><?php echo crmEscape($opportunity['opportunity_code']); ?></td>
                                    <td><?php echo crmEscape($opportunity['description']); ?></td>
                                    <td><?php echo crmEscape($opportunity['contact_no']); ?></td>
                                    <td><?php echo crmEscape($opportunity['contact_name']); ?></td>
                                    <td><?php echo crmEscape($opportunity['contact_company_name']); ?></td>
                                    <td><?php echo crmEscape($opportunity['cycle_code']); ?></td>
                                    <td><?php echo crmEscape(($opportunity['current_stage_no'] !== null ? $opportunity['current_stage_no'] . ' - ' : '') . ($opportunity['current_stage_description'] ?? '')); ?></td>
                                    <td><?php echo crmEscape($opportunity['status']); ?></td>
                                    <td><?php echo crmEscape($opportunity['creation_date']); ?></td>
                                    <td>
                                        <a href="crm-opportunity.php?id=<?php echo (int) $opportunity['opportunity_id']; ?>" class="btn btn-xs btn-primary" title="Edit"><i class="fa fa-pencil"></i></a>
                                        <a href="crm-opportunity-update.php?id=<?php echo (int) $opportunity['opportunity_id']; ?>" class="btn btn-xs btn-info" title="Update"><i class="fa fa-random"></i></a>
                                        <a href="crm-opportunity-activity.php?id=<?php echo (int) $opportunity['opportunity_id']; ?>" class="btn btn-xs btn-default" title="Activity Tasks"><i class="fa fa-tasks"></i></a>
                                        <a href="crm-opportunity.php?delete_id=<?php echo (int) $opportunity['opportunity_id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete this opportunity?');" title="Delete"><i class="fa fa-trash"></i></a>
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
<?php include('common/footer.php'); ?>
<script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
<script>
    (function ($) {
        var contactLookup = {};
        var contacts = <?php echo json_encode($contactJson); ?>;

        $.each(contacts, function (_, contact) {
            contactLookup[String(contact.person_id)] = contact;
        });

        function todayIso() {
            var date = new Date();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            return date.getFullYear() + '-' + month + '-' + day;
        }

        function syncContactDetails(forceDerivedSelections) {
            var personId = $('#person_id').val();
            var contact = contactLookup[personId] || null;

            if (!contact) {
                $('#contact_name').val('');
                $('#phone_no').val('');
                $('#mobile_phone_no').val('');
                $('#email').val('');
                $('#contact_company_name').val('');
                if (forceDerivedSelections) {
                    $('#sales_person_id').val('0');
                    $('#segment_id').val('0');
                }
                return;
            }

            $('#contact_name').val(contact.contact_name || '');
            $('#phone_no').val(contact.company_phone_no || '');
            $('#mobile_phone_no').val(contact.mobile_phone_no || '');
            $('#email').val(contact.email || '');
            $('#contact_company_name').val(contact.company_name || '');

            if (forceDerivedSelections || !$('#sales_person_id').val() || $('#sales_person_id').val() === '0') {
                $('#sales_person_id').val(String(contact.sales_person_id || 0));
            }
            if (forceDerivedSelections || !$('#segment_id').val() || $('#segment_id').val() === '0') {
                $('#segment_id').val(String(contact.segment_id || 0));
            }
        }

        function syncClosedDate() {
            var isClosed = $('#is_closed').is(':checked');
            if (!isClosed) {
                $('#date_closed').val('');
                return;
            }

            if (!$('#date_closed').val()) {
                $('#date_closed').val(todayIso());
            }
        }

        $(document).on('change', '#person_id', function () {
            syncContactDetails(true);
        });

        $(document).on('change', '#is_closed', syncClosedDate);

        syncContactDetails(false);
        syncClosedDate();
    })(jQuery);
</script>
</body>
</html>