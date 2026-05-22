<?php
ob_start();
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('include/database.php');

date_default_timezone_set('Asia/Colombo');

$db = new Database();

function uatOutput($message = '')
{
    if (PHP_SAPI === 'cli') {
        echo $message . PHP_EOL;
        return;
    }

    echo htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') . "<br>\n";
}

function uatQuoteIdentifier($identifier)
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $identifier)) {
        throw new InvalidArgumentException('Invalid SQL identifier: ' . $identifier);
    }

    return '`' . $identifier . '`';
}

function uatGetPdo(Database $db)
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $reflection = new ReflectionClass($db);
    while ($reflection) {
        if ($reflection->hasProperty('datab')) {
            $property = $reflection->getProperty('datab');
            $property->setAccessible(true);
            $pdo = $property->getValue($db);
            break;
        }
        $reflection = $reflection->getParentClass();
    }

    if (!$pdo instanceof PDO) {
        throw new RuntimeException('Unable to access PDO database connection.');
    }

    return $pdo;
}

function uatFetchCurrentDatabase(PDO $pdo)
{
    $statement = $pdo->query('SELECT DATABASE() AS db_name');
    $row = $statement ? $statement->fetch(PDO::FETCH_ASSOC) : [];

    return isset($row['db_name']) ? (string) $row['db_name'] : '';
}

function uatFetchAllTables(PDO $pdo)
{
    $statement = $pdo->query('SHOW TABLES');
    $rows = $statement ? $statement->fetchAll(PDO::FETCH_NUM) : [];
    $tables = [];

    foreach ($rows as $row) {
        if (!empty($row[0])) {
            $tables[] = (string) $row[0];
        }
    }

    sort($tables, SORT_NATURAL | SORT_FLAG_CASE);

    return $tables;
}

function uatGetRowCount(Database $db, $tableName)
{
    $sql = 'SELECT COUNT(*) AS total FROM ' . uatQuoteIdentifier($tableName);
    $row = $db->getRow($sql);

    return (int) ($row['total'] ?? 0);
}

function uatGetPreservedTables()
{
    return [
        'banners',
        'banks',
        'comapny_message',
        'country',
        'coupon_codes',
        'currency',
        'custompage',
        'customer',
        'customer_shipping_address',
        'delivery_area',
        'employee',
        'front_web_settings',
        'hampers',
        'general_settings',
        'home_slider',
        'immediatepickup',
        'invoice_status',
        'item_specification',
        'item_uom',
        'item_warranty',
        'itemmapping',
        'payments_in_delivery',
        'payment_method',
        'payment_terms',
        'permissions',
        'price_type',
        'price_type_customer_mapping',
        'product_ingredients',
        'product_settlement_plan',
        'product_vat_master',
        'productimages',
        'reviews',
        'role_permissions',
        'shipping_address',
        'shipping_method',
        'site_banners',
        'smtp_settings',
        'supplier',
        'supplier_payment_options',
        'supplier_shipping_address',
        'transaction_types',
        'typemappingitem',
        'url',
        'users',
        'user_levels',
        'crm_company_person',
        'crm_sales_cycle_stage',
        'categorymappingitem',
        'groupmappingitem'
    ];
}

function uatShouldPreserveTable($tableName)
{
    static $preserveLookup = null;

    if ($preserveLookup === null) {
        $preserveLookup = array_fill_keys(uatGetPreservedTables(), true);
    }

    if (isset($preserveLookup[$tableName])) {
        return true;
    }

    if (preg_match('/(?:^|_)master$/i', $tableName)) {
        return true;
    }

    return false;
}

function uatIsConfirmed()
{
    $token = 'YES_CLEAR_UAT_NON_MASTER_DATA';
    $webToken = trim((string) ($_GET['confirm'] ?? $_POST['confirm'] ?? ''));

    if ($webToken === $token) {
        return true;
    }

    global $argv;
    if (!isset($argv) || !is_array($argv)) {
        return false;
    }

    foreach (array_slice($argv, 1) as $argument) {
        $argument = trim((string) $argument);
        if ($argument === ('confirm=' . $token) || $argument === $token) {
            return true;
        }
    }

    return false;
}

function uatResetAutoIncrement(PDO $pdo, $tableName)
{
    $pdo->exec('ALTER TABLE ' . uatQuoteIdentifier($tableName) . ' AUTO_INCREMENT = 1');
}

$pdo = uatGetPdo($db);
$databaseName = uatFetchCurrentDatabase($pdo);
$allTables = uatFetchAllTables($pdo);

$preservedTables = [];
$tablesToClear = [];
$rowCounts = [];
$rowsToDelete = 0;

foreach ($allTables as $tableName) {
    $count = uatGetRowCount($db, $tableName);
    $rowCounts[$tableName] = $count;

    if (uatShouldPreserveTable($tableName)) {
        $preservedTables[] = $tableName;
        continue;
    }

    $tablesToClear[] = $tableName;
    $rowsToDelete += $count;
}

uatOutput('=== UAT NON-MASTER DATA CLEANUP ===');
uatOutput('Date: ' . date('Y-m-d H:i:s'));
uatOutput('Database: ' . ($databaseName !== '' ? $databaseName : '[unknown]'));
uatOutput('');
uatOutput('Preserved tables: ' . count($preservedTables));
uatOutput('Tables to clear: ' . count($tablesToClear));
uatOutput('Rows scheduled for deletion: ' . number_format($rowsToDelete));
uatOutput('');

uatOutput('--- PRESERVED TABLES ---');
foreach ($preservedTables as $tableName) {
    uatOutput(sprintf('%-35s %10d row(s)', $tableName, $rowCounts[$tableName]));
}

uatOutput('');
uatOutput('--- TABLES TO CLEAR ---');
foreach ($tablesToClear as $tableName) {
    uatOutput(sprintf('%-35s %10d row(s)', $tableName, $rowCounts[$tableName]));
}

uatOutput('');
uatOutput('This script is DRY-RUN by default. No rows are deleted unless you confirm explicitly.');
uatOutput('Confirmation token: YES_CLEAR_UAT_NON_MASTER_DATA');
uatOutput('');

if (!uatIsConfirmed()) {
    uatOutput('To execute from browser:');
    uatOutput('  uat_clear_non_master_data.php?confirm=YES_CLEAR_UAT_NON_MASTER_DATA');
    uatOutput('To execute from CLI:');
    uatOutput('  php admin/uat_clear_non_master_data.php confirm=YES_CLEAR_UAT_NON_MASTER_DATA');
    uatOutput('');
    uatOutput('Review the preserved list inside this file before running on UAT.');
    exit;
}

$transactionStarted = false;

try {
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
        $transactionStarted = true;
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    uatOutput('CONFIRMED: starting cleanup...');
    uatOutput('');

    foreach ($tablesToClear as $tableName) {
        $quotedTable = uatQuoteIdentifier($tableName);
        $count = $rowCounts[$tableName] ?? 0;

        if ($count > 0) {
            $pdo->exec('DELETE FROM ' . $quotedTable);
            uatResetAutoIncrement($pdo, $tableName);
            uatOutput(sprintf('Cleared %-35s %10d row(s)', $tableName, $count));
        } else {
            uatResetAutoIncrement($pdo, $tableName);
            uatOutput(sprintf('Skipped %-35s %10d row(s)', $tableName, 0));
        }
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    if ($transactionStarted && $pdo->inTransaction()) {
        $pdo->commit();
    }

    uatOutput('');
    uatOutput('--- VERIFICATION ---');

    $remainingRows = 0;
    foreach ($tablesToClear as $tableName) {
        $count = uatGetRowCount($db, $tableName);
        $remainingRows += $count;
        uatOutput(sprintf('%-35s %10d row(s)', $tableName, $count));
    }

    uatOutput('');
    uatOutput('Cleanup completed. Remaining rows in cleared tables: ' . number_format($remainingRows));
} catch (Exception $exception) {
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    } catch (Exception $ignored) {
    }

    if ($transactionStarted && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    uatOutput('ERROR: ' . $exception->getMessage());
    exit(1);
}
?>