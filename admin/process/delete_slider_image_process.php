<?php
ob_start();
error_reporting(E_ALL ^ E_NOTICE);

session_start();
include('../include/database.php');
include('../include/check_login.php');

header('Content-Type: application/json');

$nowDate = date("Y-m-d");
$nowTime = date("h:i:s");
$nowDateTime = date("Y-m-d h:i:s");
$nowDateTime_2 = date("Y-m-d H:i:s");
$thisYear = date("Y");
$thisMonth = date("m");

$whiteSpace = '\s';
$pattern = '/[^a-zA-Z0-9'  . $whiteSpace . ']/u';

$message = "";
$status = false;
$productId = 0;
$imageTypeStatus = false;
$db = new database();

if (isset($_POST['imageId'])) {
    $imageId = (int) $_POST['imageId'];

    $queryCheck = $db->getRow('SELECT image, path FROM home_slider WHERE id = ?', [$imageId]);

    if ($queryCheck) {
        $imageName = trim((string) ($queryCheck['image'] ?? ''));
        $imagepath = trim((string) ($queryCheck['path'] ?? ''));

        if ($imageName !== '' && $imagepath !== '') {
            $fullPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, rtrim($imagepath, '/')) . DIRECTORY_SEPARATOR . $imageName;
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
    }
    
    try{

        $deleteRecode = $db->deleteRow('DELETE FROM home_slider WHERE id = ?',[$imageId]);
        $status = true;
        $message = 'Slider image deleted successfully';

    }catch (Exception $e) {
        $message = $e->getMessage();

    }
}
$output =  array(
    'status' => $status,
    'message' => $message

);
echo json_encode($output, JSON_FORCE_OBJECT);



