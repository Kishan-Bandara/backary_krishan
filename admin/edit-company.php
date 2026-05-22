<?php
$companyId = isset($_GET['companyID']) ? (int) $_GET['companyID'] : 0;
header('Location: crm.php?edit_type=company&id=' . $companyId);
exit;