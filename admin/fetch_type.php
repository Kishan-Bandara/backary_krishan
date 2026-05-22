<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('include/database.php');

$groupId = $_POST['groupId'] ?? ($_POST['group_id'] ?? '');
$groupId = trim((string) $groupId);

$output = '<option value="">Select Type</option>';

if ($groupId === '') {
    echo $output;
    exit();
}

try {
    $db = new Database();
    $rows = $db->getRows('SELECT type_id, type_name FROM type_master WHERE group_id = ? ORDER BY type_name ASC', [$groupId]);
    foreach ($rows as $row) {
        $typeId = htmlspecialchars((string) ($row['type_id'] ?? ''), ENT_QUOTES, 'UTF-8');
        $typeName = htmlspecialchars((string) ($row['type_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $output .= '<option value="' . $typeId . '">' . $typeName . '</option>';
    }
} catch (Exception $e) {
    // keep default option only
}

echo $output;
?>



