<?php
$personId = isset($_GET['personID']) ? (int) $_GET['personID'] : 0;
header('Location: crm.php?edit_type=person&id=' . $personId);
exit;