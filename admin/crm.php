<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
session_start();
include('include/database.php');
include('include/check_login.php');
include('include/crm_master.php');

$db = new Database();
crmEnsureSchema($db);

$message = '';
$messageClass = '';
$recordType = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : 'person';
if ($recordType !== 'company') {
    $recordType = 'person';
}

$editType = isset($_GET['edit_type']) ? strtolower(trim($_GET['edit_type'])) : '';
$editId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = false;

$formData = [
    'code' => crmGenerateContactCode($db),
    'person_title' => 'Mr',
    'person_name' => '',
    'person_contact_no' => '',
    'person_email' => '',
    'person_address' => '',
    'person_designation_id' => 0,
    'person_designation' => '',
    'person_company_id' => 0,
    'company_name' => '',
    'company_type' => 'Agriculture',
    'company_segment_id' => 0,
    'company_category_id' => 0,
    'company_sales_person_id' => 0,
    'company_contact_details' => '',
    'company_address' => ''
];

$companyLinkedPerson = null;

if ($editId > 0 && in_array($editType, ['person', 'company'], true)) {
    if ($editType === 'person') {
        requirePermission('crm.person.view');
        $record = $db->getRow('SELECT * FROM crm_person_master WHERE person_id = ? LIMIT 1', [$editId]);
        if ($record) {
            $recordType = 'person';
            $isEdit = true;
            $formData['code'] = $record['person_code'];
            $formData['person_title'] = $record['title'];
            $formData['person_name'] = $record['contact_name'];
            $formData['person_contact_no'] = $record['contact_no'];
            $formData['person_email'] = $record['email'] ?? '';
            $formData['person_address'] = $record['address'];
            $formData['person_designation_id'] = (int) ($record['designation_id'] ?? 0);
            $formData['person_designation'] = $record['designation'];
            $formData['person_company_id'] = crmFetchPersonCompanyId($db, $editId);
        }
    } else {
        requirePermission('crm.company.view');
        $record = $db->getRow('SELECT * FROM crm_company_master WHERE company_id = ? LIMIT 1', [$editId]);
        if ($record) {
            $recordType = 'company';
            $isEdit = true;
            $formData['code'] = $record['company_code'];
            $formData['company_name'] = $record['company_name'];
            $formData['company_type'] = $record['company_type'];
            $formData['company_segment_id'] = (int) ($record['segment_id'] ?? 0);
            $formData['company_category_id'] = (int) ($record['category_id'] ?? 0);
            $formData['company_sales_person_id'] = (int) ($record['sales_person_id'] ?? 0);
            $formData['company_contact_details'] = $record['contact_details'];
            $formData['company_address'] = $record['address'];
            $companyLinkedPerson = crmFetchCompanyLinkedPerson($db, $editId);
        }
    }
}

if (isset($_GET['delete_type'], $_GET['delete_id'])) {
    $deleteType = strtolower(trim($_GET['delete_type']));
    $deleteId = (int) $_GET['delete_id'];

    if ($deleteId > 0 && in_array($deleteType, ['person', 'company'], true)) {
        try {
            if ($deleteType === 'person') {
                requirePermission('crm.person.view');
                $db->deleteRow('DELETE FROM crm_person_master WHERE person_id = ?', [$deleteId]);
                $message = 'Person record deleted successfully.';
            } else {
                requirePermission('crm.company.view');
                $db->deleteRow('DELETE FROM crm_company_master WHERE company_id = ?', [$deleteId]);
                $message = 'Company record deleted successfully.';
            }
            $messageClass = 'alert-success';
        } catch (Exception $e) {
            $message = 'Unable to delete this CRM record right now.';
            $messageClass = 'alert-danger';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recordType = strtolower(trim($_POST['record_type'] ?? 'person'));
    if (!in_array($recordType, ['person', 'company'], true)) {
        $recordType = 'person';
    }

    $editType = strtolower(trim($_POST['edit_type'] ?? ''));
    $editId = (int) ($_POST['edit_id'] ?? 0);
    $isEdit = $editId > 0 && $editType === $recordType;

    $formData['code'] = trim($_POST['code'] ?? $formData['code']);
    $formData['person_title'] = trim($_POST['person_title'] ?? 'Mr');
    $formData['person_name'] = trim($_POST['person_name'] ?? '');
    $formData['person_contact_no'] = trim($_POST['person_contact_no'] ?? '');
    $formData['person_email'] = trim($_POST['person_email'] ?? '');
    $formData['person_address'] = trim($_POST['person_address'] ?? '');
    $formData['person_designation_id'] = (int) ($_POST['person_designation_id'] ?? 0);
    $formData['person_designation'] = trim($_POST['person_designation'] ?? '');
    $formData['person_company_id'] = (int) ($_POST['person_company_id'] ?? 0);
    $formData['company_name'] = trim($_POST['company_name'] ?? '');
    $formData['company_type'] = trim($_POST['company_type'] ?? 'Agriculture');
    $formData['company_segment_id'] = (int) ($_POST['company_segment_id'] ?? 0);
    $formData['company_category_id'] = (int) ($_POST['company_category_id'] ?? 0);
    $formData['company_sales_person_id'] = (int) ($_POST['company_sales_person_id'] ?? 0);
    $formData['company_contact_details'] = trim($_POST['company_contact_details'] ?? '');
    $formData['company_address'] = trim($_POST['company_address'] ?? '');

    $errors = [];

    if ($formData['code'] === '') {
        $errors[] = 'Code is required.';
    } elseif (stripos($formData['code'], 'CT-') !== 0) {
        $errors[] = 'Code must start with CT-.';
    } elseif (crmCodeExists($db, $formData['code'], $isEdit ? $recordType : '', $isEdit ? $editId : 0)) {
        $errors[] = 'This CRM code already exists.';
    }

    if ($recordType === 'person') {
        if ($isEdit) {
            requirePermission('crm.person.view');
        } else {
            requirePermission('crm.person.create');
        }

        if ($formData['person_name'] === '') {
            $errors[] = 'Contact name is required.';
        }
        if ($formData['person_contact_no'] === '') {
            $errors[] = 'Contact number is required.';
        }
        if ($formData['person_email'] !== '' && !filter_var($formData['person_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (!in_array($formData['person_title'], ['Mr', 'Miss'], true)) {
            $errors[] = 'Title must be Mr or Miss.';
        }
        if ($formData['person_designation_id'] > 0) {
            $formData['person_designation'] = crmFetchDesignationName($db, $formData['person_designation_id']);
            if ($formData['person_designation'] === '') {
                $errors[] = 'Please select a valid designation.';
            }
        } else {
            $formData['person_designation'] = '';
        }
        if ($formData['person_company_id'] <= 0) {
            $errors[] = 'Please select a company for this person.';
        } elseif (crmCompanyAssignedToAnotherPerson($db, $formData['person_company_id'], $isEdit ? $editId : 0) > 0) {
            $errors[] = 'This company is already linked to another person.';
        }

        if (empty($errors)) {
            try {
                if ($isEdit) {
                    $db->updateRow(
                        'UPDATE crm_person_master SET person_code = ?, title = ?, contact_name = ?, contact_no = ?, email = ?, address = ?, designation = ?, designation_id = ? WHERE person_id = ?',
                        [
                            $formData['code'],
                            $formData['person_title'],
                            $formData['person_name'],
                            $formData['person_contact_no'],
                            $formData['person_email'],
                            $formData['person_address'],
                            $formData['person_designation'],
                            $formData['person_designation_id'] > 0 ? $formData['person_designation_id'] : null,
                            $editId
                        ]
                    );
                    crmAssignPersonCompany($db, $editId, $formData['person_company_id']);
                    $message = 'Person record updated successfully.';
                } else {
                    $db->insertRow(
                        'INSERT INTO crm_person_master (person_code, title, contact_name, contact_no, email, address, designation, designation_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $formData['code'],
                            $formData['person_title'],
                            $formData['person_name'],
                            $formData['person_contact_no'],
                            $formData['person_email'],
                            $formData['person_address'],
                            $formData['person_designation'],
                            $formData['person_designation_id'] > 0 ? $formData['person_designation_id'] : null
                        ]
                    );
                    $newPerson = $db->getRow('SELECT person_id FROM crm_person_master WHERE person_code = ? LIMIT 1', [$formData['code']]);
                    if (!empty($newPerson['person_id'])) {
                        crmAssignPersonCompany($db, (int) $newPerson['person_id'], $formData['person_company_id']);
                    }
                    $message = 'Person record created successfully.';
                }
                $messageClass = 'alert-success';
                $isEdit = false;
                $editType = '';
                $editId = 0;
                $formData = [
                    'code' => crmGenerateContactCode($db),
                    'person_title' => 'Mr',
                    'person_name' => '',
                    'person_contact_no' => '',
                    'person_email' => '',
                    'person_address' => '',
                    'person_designation_id' => 0,
                    'person_designation' => '',
                    'person_company_id' => 0,
                    'company_name' => '',
                    'company_type' => 'Agriculture',
                    'company_segment_id' => 0,
                    'company_category_id' => 0,
                    'company_sales_person_id' => 0,
                    'company_contact_details' => '',
                    'company_address' => ''
                ];
            } catch (Exception $e) {
                $message = 'Unable to save person record right now.';
                $messageClass = 'alert-danger';
            }
        }
    } else {
        if ($isEdit) {
            requirePermission('crm.company.view');
        } else {
            requirePermission('crm.company.create');
        }

        if ($formData['company_name'] === '') {
            $errors[] = 'Company name is required.';
        }
        if (!in_array($formData['company_type'], crmCompanyTypes(), true)) {
            $errors[] = 'Please select a valid company type.';
        }
        if ($formData['company_segment_id'] > 0 && $formData['company_category_id'] > 0 && !crmCategoryBelongsToSegment($db, $formData['company_category_id'], $formData['company_segment_id'])) {
            $errors[] = 'Selected category does not belong to the selected segment.';
        }
        if ($formData['company_segment_id'] <= 0 && $formData['company_category_id'] > 0) {
            $errors[] = 'Please select a segment before selecting a category.';
        }

        if (empty($errors)) {
            try {
                if ($isEdit) {
                    $db->updateRow(
                        'UPDATE crm_company_master SET company_code = ?, company_name = ?, company_type = ?, segment_id = ?, category_id = ?, sales_person_id = ?, contact_details = ?, address = ? WHERE company_id = ?',
                        [
                            $formData['code'],
                            $formData['company_name'],
                            $formData['company_type'],
                            $formData['company_segment_id'] > 0 ? $formData['company_segment_id'] : null,
                            $formData['company_category_id'] > 0 ? $formData['company_category_id'] : null,
                            $formData['company_sales_person_id'] > 0 ? $formData['company_sales_person_id'] : null,
                            $formData['company_contact_details'],
                            $formData['company_address'],
                            $editId
                        ]
                    );
                    $message = 'Company record updated successfully.';
                } else {
                    $db->insertRow(
                        'INSERT INTO crm_company_master (company_code, company_name, company_type, segment_id, category_id, sales_person_id, contact_details, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $formData['code'],
                            $formData['company_name'],
                            $formData['company_type'],
                            $formData['company_segment_id'] > 0 ? $formData['company_segment_id'] : null,
                            $formData['company_category_id'] > 0 ? $formData['company_category_id'] : null,
                            $formData['company_sales_person_id'] > 0 ? $formData['company_sales_person_id'] : null,
                            $formData['company_contact_details'],
                            $formData['company_address']
                        ]
                    );
                    $message = 'Company record created successfully.';
                }
                $messageClass = 'alert-success';
                $isEdit = false;
                $editType = '';
                $editId = 0;
                $formData = [
                    'code' => crmGenerateContactCode($db),
                    'person_title' => 'Mr',
                    'person_name' => '',
                    'person_contact_no' => '',
                    'person_email' => '',
                    'person_address' => '',
                    'person_designation_id' => 0,
                    'person_designation' => '',
                    'person_company_id' => 0,
                    'company_name' => '',
                    'company_type' => 'Agriculture',
                    'company_segment_id' => 0,
                    'company_category_id' => 0,
                    'company_sales_person_id' => 0,
                    'company_contact_details' => '',
                    'company_address' => ''
                ];
                $recordType = 'company';
            } catch (Exception $e) {
                $message = 'Unable to save company record right now.';
                $messageClass = 'alert-danger';
            }
        }
    }

    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $messageClass = 'alert-danger';
    }
}

$personCount = 0;
$companyCount = 0;
$relationshipCount = 0;
try {
    $personRow = $db->getRow('SELECT COUNT(*) AS total FROM crm_person_master');
    $companyRow = $db->getRow('SELECT COUNT(*) AS total FROM crm_company_master');
    $relationshipRow = $db->getRow('SELECT COUNT(*) AS total FROM crm_company_person');
    $personCount = (int) ($personRow['total'] ?? 0);
    $companyCount = (int) ($companyRow['total'] ?? 0);
    $relationshipCount = (int) ($relationshipRow['total'] ?? 0);
} catch (Exception $e) {
    $personCount = 0;
    $companyCount = 0;
    $relationshipCount = 0;
}

$designations = crmFetchDesignations($db);
$segments = crmFetchSegments($db);
$categories = crmFetchCategories($db);
$salesPersons = crmFetchSalesPersons($db);
$salesCycles = crmFetchSalesCycles($db);
$companies = crmFetchCompanies($db);
$crmMasterCount = count($designations) + count($segments) + count($categories) + count($salesPersons) + count($salesCycles);
$currentPersonId = ($recordType === 'person' && $isEdit) ? $editId : 0;
if ($recordType === 'company' && $isEdit) {
    $companyLinkedPerson = crmFetchCompanyLinkedPerson($db, $editId);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>CRM</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>
    <style>
        .crm-card {
            background: #fff;
            border: 1px solid #e7ecf1;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 20px;
            min-height: 180px;
        }

        .crm-card h4 {
            margin-top: 0;
            margin-bottom: 10px;
        }

        .crm-stat {
            font-size: 34px;
            font-weight: 700;
            color: #32c5d2;
            line-height: 1;
            margin-bottom: 10px;
        }

        .crm-actions .btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .crm-type-panel {
            margin-bottom: 25px;
        }

        .crm-type-fields {
            display: none;
        }

        .crm-type-fields.is-active {
            display: block;
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
                        <li>
                            <a href="index.php">Home</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>CRM</span>
                        </li>
                    </ul>
                </div>
                <h3 class="page-title">CRM Dashboard</h3>
                <?php if ($message !== '') { ?>
                    <div class="alert <?php echo $messageClass; ?>"><?php echo $message; ?></div>
                <?php } ?>
                <div class="row">
                    <div class="col-md-4">
                        <div class="crm-card">
                            <h4>Person Master</h4>
                            <div class="crm-stat"><?php echo $personCount; ?></div>
                            <p>Store individual contact records with code, title, designation, company link, phone number and address.</p>
                            <div class="crm-actions">
                                <a href="crm.php?type=person" class="btn green"><i class="fa fa-user-plus"></i> Person Entry</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="crm-card">
                            <h4>Company Master</h4>
                            <div class="crm-stat"><?php echo $companyCount; ?></div>
                            <p>Create companies with company type, segment, category, sales person, contact details and address.</p>
                            <div class="crm-actions">
                                <a href="crm.php?type=company" class="btn blue"><i class="fa fa-building-o"></i> Company Entry</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="crm-card">
                            <h4>CRM Masters</h4>
                            <div class="crm-stat"><?php echo (int) $crmMasterCount; ?></div>
                            <p>Manage designations, segments, categories mapped to segments, sales persons, and sales cycles from one place.</p>
                            <div class="crm-actions">
                                <a href="crm-opportunity.php" class="btn green-jungle"><i class="fa fa-briefcase"></i> Opportunity Entry</a>
                                <a href="crm-masters.php" class="btn purple"><i class="fa fa-sitemap"></i> Manage Masters</a>
                                <a href="crm-masters.php?tab=sales_cycle" class="btn default"><i class="fa fa-random"></i> Sales Cycles</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="portlet light bordered crm-type-panel">
                    <div class="portlet-title">
                        <div class="caption"><i class="fa fa-edit font-green"></i> CRM Master Entry</div>
                        <div class="actions">
                            <a href="crm-masters.php" class="btn btn-sm default"><i class="fa fa-cogs"></i> Open CRM Masters</a>
                            <a href="crm-opportunity.php" class="btn btn-sm green-jungle"><i class="fa fa-briefcase"></i> Opportunities</a>
                        </div>
                    </div>
                    <div class="portlet-body form">
                        <form method="post" class="form-horizontal">
                            <input type="hidden" name="edit_type" value="<?php echo crmEscape($isEdit ? $recordType : ''); ?>">
                            <input type="hidden" name="edit_id" value="<?php echo (int) ($isEdit ? $editId : 0); ?>">
                            <div class="form-body">
                                <div class="form-group">
                                    <label class="col-md-3 control-label">Type</label>
                                    <div class="col-md-6">
                                        <select name="record_type" id="record_type" class="form-control">
                                            <option value="person" <?php echo crmSelected($recordType, 'person'); ?>>Person</option>
                                            <option value="company" <?php echo crmSelected($recordType, 'company'); ?>>Company</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-3 control-label">Code</label>
                                    <div class="col-md-6">
                                        <input type="text" name="code" class="form-control" value="<?php echo crmEscape($formData['code']); ?>" maxlength="30" required>
                                        <span class="help-block">Both Person and Company codes must start with CT-.</span>
                                    </div>
                                </div>

                                <div class="crm-type-fields<?php echo $recordType === 'person' ? ' is-active' : ''; ?>" data-type-panel="person">
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">Title</label>
                                        <div class="col-md-6">
                                            <select name="person_title" class="form-control">
                                                <option value="Mr" <?php echo crmSelected($formData['person_title'], 'Mr'); ?>>Mr</option>
                                                <option value="Miss" <?php echo crmSelected($formData['person_title'], 'Miss'); ?>>Miss</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">Contact Name</label>
                                        <div class="col-md-6">
                                            <input type="text" name="person_name" class="form-control" value="<?php echo crmEscape($formData['person_name']); ?>" maxlength="150">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">Contact No</label>
                                        <div class="col-md-6">
                                            <input type="text" name="person_contact_no" class="form-control" value="<?php echo crmEscape($formData['person_contact_no']); ?>" maxlength="50">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">Email</label>
                                        <div class="col-md-6">
                                            <input type="email" name="person_email" class="form-control" value="<?php echo crmEscape($formData['person_email']); ?>" maxlength="150">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">Designation</label>
                                        <div class="col-md-6">
                                            <select name="person_designation_id" class="form-control">
                                                <option value="0">Select Designation</option>
                                                <?php foreach ($designations as $designation) { ?>
                                                    <option value="<?php echo (int) $designation['designation_id']; ?>" <?php echo crmSelected($formData['person_designation_id'], $designation['designation_id']); ?>><?php echo crmEscape($designation['designation_name']); ?></option>
                                                <?php } ?>
                                            </select>
                                            <input type="hidden" name="person_designation" value="<?php echo crmEscape($formData['person_designation']); ?>">
                                            <span class="help-block"><a href="crm-masters.php?tab=designation">Manage designations</a></span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">Company</label>
                                        <div class="col-md-6">
                                            <select name="person_company_id" class="form-control">
                                                <option value="0">Select Company</option>
                                                <?php foreach ($companies as $company) {
                                                    $linkedPersonId = (int) ($company['linked_person_id'] ?? 0);
                                                    $isAssignedElsewhere = $linkedPersonId > 0 && $linkedPersonId !== $currentPersonId;
                                                ?>
                                                    <option value="<?php echo (int) $company['company_id']; ?>" <?php echo crmSelected($formData['person_company_id'], $company['company_id']); ?> <?php echo $isAssignedElsewhere ? 'disabled' : ''; ?>>
                                                        <?php echo crmEscape($company['company_code'] . ' - ' . $company['company_name'] . ($isAssignedElsewhere ? ' (Linked)' : '')); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                            <span class="help-block">Each person must be linked with one company. Companies already linked to another person cannot be selected.</span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">Address</label>
                                        <div class="col-md-6">
                                            <textarea name="person_address" class="form-control" rows="4"><?php echo crmEscape($formData['person_address']); ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="crm-type-fields<?php echo $recordType === 'company' ? ' is-active' : ''; ?>" data-type-panel="company">
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">Company Name</label>
                                        <div class="col-md-6">
                                            <input type="text" name="company_name" class="form-control" value="<?php echo crmEscape($formData['company_name']); ?>" maxlength="180">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">Company Type</label>
                                        <div class="col-md-6">
                                            <select name="company_type" class="form-control">
                                                <?php foreach (crmCompanyTypes() as $type) { ?>
                                                    <option value="<?php echo crmEscape($type); ?>" <?php echo crmSelected($formData['company_type'], $type); ?>><?php echo crmEscape($type); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">Segment</label>
                                        <div class="col-md-6">
                                            <select name="company_segment_id" id="company_segment_id" class="form-control">
                                                <option value="0">Select Segment</option>
                                                <?php foreach ($segments as $segment) { ?>
                                                    <option value="<?php echo (int) $segment['segment_id']; ?>" <?php echo crmSelected($formData['company_segment_id'], $segment['segment_id']); ?>><?php echo crmEscape($segment['segment_name']); ?></option>
                                                <?php } ?>
                                            </select>
                                            <span class="help-block"><a href="crm-masters.php?tab=segment">Manage segments</a></span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">Category</label>
                                        <div class="col-md-6">
                                            <select name="company_category_id" id="company_category_id" class="form-control">
                                                <option value="0">Select Category</option>
                                                <?php foreach ($categories as $category) { ?>
                                                    <option value="<?php echo (int) $category['category_id']; ?>" data-segment-id="<?php echo (int) $category['segment_id']; ?>" <?php echo crmSelected($formData['company_category_id'], $category['category_id']); ?>><?php echo crmEscape($category['segment_name'] . ' - ' . $category['category_name']); ?></option>
                                                <?php } ?>
                                            </select>
                                            <span class="help-block"><a href="crm-masters.php?tab=category">Manage categories mapped to segments</a></span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">Sales Person</label>
                                        <div class="col-md-6">
                                            <select name="company_sales_person_id" class="form-control">
                                                <option value="0">Select Sales Person</option>
                                                <?php foreach ($salesPersons as $salesPerson) { ?>
                                                    <option value="<?php echo (int) $salesPerson['sales_person_id']; ?>" <?php echo crmSelected($formData['company_sales_person_id'], $salesPerson['sales_person_id']); ?>><?php echo crmEscape($salesPerson['sales_person_name']); ?></option>
                                                <?php } ?>
                                            </select>
                                            <span class="help-block"><a href="crm-masters.php?tab=sales_person">Manage sales persons</a></span>
                                        </div>
                                    </div>
                                    <?php if ($companyLinkedPerson) { ?>
                                        <div class="form-group">
                                            <label class="col-md-3 control-label">Linked Person</label>
                                            <div class="col-md-6">
                                                <p class="form-control-static"><?php echo crmEscape($companyLinkedPerson['person_code'] . ' - ' . $companyLinkedPerson['contact_name']); ?></p>
                                                <span class="help-block">This relationship is managed from the Person entry screen.</span>
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <div class="form-group">
                                            <label class="col-md-3 control-label">Linked Person</label>
                                            <div class="col-md-6">
                                                <p class="form-control-static">No person linked yet.</p>
                                                <span class="help-block">Create or edit a person record to link this company.</span>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">Contact Details</label>
                                        <div class="col-md-6">
                                            <textarea name="company_contact_details" class="form-control" rows="3"><?php echo crmEscape($formData['company_contact_details']); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-3 control-label">Address</label>
                                        <div class="col-md-6">
                                            <textarea name="company_address" class="form-control" rows="4"><?php echo crmEscape($formData['company_address']); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions right">
                                <a href="crm.php?type=<?php echo crmEscape($recordType); ?>" class="btn default">Clear</a>
                                <a href="crm-masters.php" class="btn default">CRM Masters</a>
                                <a href="crm-list.php<?php echo $recordType !== '' ? '?filter=' . crmEscape($recordType) : ''; ?>" class="btn blue">Open Contact List</a>
                                <button type="submit" class="btn green"><i class="fa fa-check"></i> <?php echo $isEdit ? 'Update' : 'Save'; ?> CRM Record</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('common/footer.php'); ?>
    <script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
    <script>
        (function ($) {
            var companyCategories = <?php echo json_encode(array_map(function ($category) {
                return [
                    'id' => (int) $category['category_id'],
                    'segment_id' => (int) $category['segment_id'],
                    'label' => $category['segment_name'] . ' - ' . $category['category_name']
                ];
            }, $categories)); ?>;

            function syncCrmPanels() {
                var selectedType = $('#record_type').val();
                $('[data-type-panel]').removeClass('is-active');
                $('[data-type-panel="' + selectedType + '"]').addClass('is-active');
            }

            function syncCategoryOptions() {
                var segmentId = parseInt($('#company_segment_id').val() || '0', 10);
                var selectedCategoryId = parseInt($('#company_category_id').val() || '0', 10);
                var hasSelectedCategory = false;
                var options = ['<option value="0">Select Category</option>'];

                $.each(companyCategories, function (_, category) {
                    if (segmentId > 0 && category.segment_id !== segmentId) {
                        return;
                    }

                    var isSelected = category.id === selectedCategoryId;
                    if (isSelected) {
                        hasSelectedCategory = true;
                    }

                    options.push('<option value="' + category.id + '"' + (isSelected ? ' selected' : '') + '>' + category.label + '</option>');
                });

                $('#company_category_id').html(options.join(''));
                if (!hasSelectedCategory) {
                    $('#company_category_id').val('0');
                }
            }

            $(document).on('change', '#record_type', syncCrmPanels);
            $(document).on('change', '#company_segment_id', syncCategoryOptions);
            syncCrmPanels();
            syncCategoryOptions();
        })(jQuery);
    </script>
</body>
</html>
