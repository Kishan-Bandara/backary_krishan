<?php
// Migration runner for item_master columns
include 'include/database.php';

try {
    $db = new Database();
    echo "Connected to database successfully.\n";

    // Read the migration file
    $migrationFile = 'DB Migration/2025-12-12_add_comprehensive_item_master_fields.sql';
    if (!file_exists($migrationFile)) {
        die("Migration file not found: $migrationFile\n");
    }

    $sql = file_get_contents($migrationFile);
    echo "Read migration file.\n";

    // Split into statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach($statements as $stmt) {
        $stmt = trim($stmt);
        if (!empty($stmt) && !preg_match('/^--/', $stmt)) {
            echo "Executing: " . substr($stmt, 0, 60) . "...\n";
            try {
                $db->datab->exec($stmt);
                echo "✓ Success\n";
            } catch(Exception $e) {
                echo "✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "Migration completed!\n";

    // Verify columns were added
    $result = $db->datab->query("DESCRIBE item_master");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);

    echo "\nCurrent item_master columns:\n";
    foreach($columns as $col) {
        echo "- {$col['Field']}: {$col['Type']}\n";
    }

} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
?>



