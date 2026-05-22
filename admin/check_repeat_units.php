<?php
include('include/database.php');
$db = new Database();
$units = $db->getRows('SELECT id, name, display_name FROM repeat_units ORDER BY id ASC');
$output = "Repeat Units:\n";
foreach ($units as $unit) {
    $output .= "ID: {$unit['id']}, Name: {$unit['name']}, Display: {$unit['display_name']}\n";
}
file_put_contents('repeat_units_output.txt', $output);
echo "Output written to repeat_units_output.txt\n";
?>



