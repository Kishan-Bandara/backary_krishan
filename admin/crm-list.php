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

$filter = isset($_GET['filter']) ? strtolower(trim($_GET['filter'])) : 'all';
if (!in_array($filter, ['all', 'person', 'company'], true)) {
    $filter = 'all';
}

$message = '';
$messageClass = 'alert-success';

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
        } catch (Exception $e) {
            $message = 'Unable to delete this CRM record right now.';
            $messageClass = 'alert-danger';
        }
    }
}

$records = [];
try {
    $records = crmFetchUnifiedRecords($db);
    if ($filter !== 'all') {
        $records = array_values(array_filter($records, function ($record) use ($filter) {
            return ($record['crm_type'] ?? '') === $filter;
        }));
    }
} catch (Exception $e) {
    $records = [];
    $message = 'Unable to load CRM records.';
    $messageClass = 'alert-danger';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>CRM Contact List</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>
    <link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
<?php include('common/manubar.php'); ?>
<div class="page-container">
    <div class="page-content-wrapper">
        <div class="page-content">
            <div class="page-bar">
                <ul class="page-breadcrumb">
                    <li><a href="index.php">Home</a><i class="fa fa-circle"></i></li>
                    <li><a href="crm.php">CRM</a><i class="fa fa-circle"></i></li>
                    <li><span>Contact List</span></li>
                </ul>
            </div>
            <h3 class="page-title">CRM Contact List</h3>

            <?php if ($message !== '') { ?>
                <div class="alert <?php echo $messageClass; ?>"><?php echo $message; ?></div>
            <?php } ?>

            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption"><i class="fa fa-list font-green"></i> Contact Records</div>
                    <div class="actions">
                        <a href="crm.php?type=person" class="btn btn-sm <?php echo $filter === 'person' ? 'green' : 'default'; ?>"><i class="fa fa-user-plus"></i> Add Person</a>
                        <a href="crm.php?type=company" class="btn btn-sm <?php echo $filter === 'company' ? 'blue' : 'default'; ?>"><i class="fa fa-building-o"></i> Add Company</a>
                        <a href="crm-opportunity.php" class="btn btn-sm green-jungle"><i class="fa fa-briefcase"></i> Opportunity Entry</a>
                        <a href="crm-masters.php" class="btn btn-sm default"><i class="fa fa-cogs"></i> CRM Masters</a>
                    </div>
                </div>
                <div class="portlet-body">
                    <div class="btn-group" style="margin-bottom:15px;">
                        <a href="crm-list.php?filter=all" class="btn <?php echo $filter === 'all' ? 'green' : 'default'; ?>">All</a>
                        <a href="crm-list.php?filter=person" class="btn <?php echo $filter === 'person' ? 'green' : 'default'; ?>">Persons</a>
                        <a href="crm-list.php?filter=company" class="btn <?php echo $filter === 'company' ? 'green' : 'default'; ?>">Companies</a>
                    </div>
                    <table class="table table-striped table-bordered table-hover" id="sample_2">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Type / Title</th>
                                <th>Contact</th>
                                <th>Relations</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record) { ?>
                                <tr>
                                    <td><?php echo ucfirst(crmEscape($record['crm_type'])); ?></td>
                                    <td><?php echo crmEscape($record['crm_code']); ?></td>
                                    <td><?php echo crmEscape($record['display_name']); ?></td>
                                    <td>
                                        <?php if ($record['crm_type'] === 'company') { ?>
                                            <?php echo crmEscape($record['company_type']); ?>
                                        <?php } else { ?>
                                            <?php echo crmEscape($record['company_type']); ?>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php echo crmEscape($record['contact_info']); ?>
                                        <?php if ($record['crm_type'] === 'person' && trim((string) $record['extra_info']) !== '') { ?>
                                            <div class="text-muted"><?php echo crmEscape($record['extra_info']); ?></div>
                                        <?php } ?>
                                        <?php if ($record['crm_type'] === 'company' && trim((string) $record['extra_info']) !== '') { ?>
                                            <div class="text-muted"><?php echo crmEscape($record['extra_info']); ?></div>
                                        <?php } ?>
                                    </td>
                                    <td><?php echo (int) ($record['relation_count'] ?? 0); ?></td>
                                    <td>
                                        <a href="crm.php?edit_type=<?php echo crmEscape($record['crm_type']); ?>&id=<?php echo (int) $record['record_id']; ?>" class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i></a>
                                        <a href="crm-list.php?filter=<?php echo crmEscape($filter); ?>&delete_type=<?php echo crmEscape($record['crm_type']); ?>&delete_id=<?php echo (int) $record['record_id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete this CRM record?');"><i class="fa fa-trash"></i></a>
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
<script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
<script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
<script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
<script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
<script src="assets/pages/scripts/table-datatables-responsive.min.js" type="text/javascript"></script>
</body>
</html>