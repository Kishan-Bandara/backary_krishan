<?php

function migrationRunnerEnsureLogTable(Database $db)
{
    $db->insertRow("CREATE TABLE IF NOT EXISTS system_migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration_key VARCHAR(191) NOT NULL,
        migration_name VARCHAR(255) NOT NULL,
        migration_type VARCHAR(20) NOT NULL,
        migration_path VARCHAR(255) NOT NULL,
        checksum VARCHAR(64) DEFAULT NULL,
        status VARCHAR(30) NOT NULL,
        notes TEXT DEFAULT NULL,
        executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_system_migrations_key (migration_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function migrationRunnerDiscoverMigrations()
{
    $definitions = [];
    $baseDir = realpath(__DIR__ . '/..');
    $sqlDir = realpath(__DIR__ . '/../DB Migration');
    $processDir = realpath(__DIR__ . '/../process');

    if ($sqlDir !== false) {
        $sqlFiles = glob($sqlDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
        natcasesort($sqlFiles);

        foreach ($sqlFiles as $sqlFile) {
            $definitions[] = [
                'key' => 'sql:' . migrationRunnerRelativePath($sqlFile, $baseDir),
                'name' => basename($sqlFile),
                'type' => 'sql',
                'path' => $sqlFile,
                'relative_path' => migrationRunnerRelativePath($sqlFile, $baseDir),
            ];
        }
    }

    $phpFiles = [];
    if ($processDir !== false) {
        $patterns = [
            $processDir . DIRECTORY_SEPARATOR . '*migration*.php',
            $processDir . DIRECTORY_SEPARATOR . 'migrate_*.php',
        ];

        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: [] as $phpFile) {
                if (basename($phpFile) === 'check-batch-tracking.php') {
                    continue;
                }

                $phpFiles[$phpFile] = true;
            }
        }
    }

    $extraPhpFiles = [
        realpath(__DIR__ . '/../DB Migration/run_migration.php'),
    ];

    foreach ($extraPhpFiles as $extraPhpFile) {
        if ($extraPhpFile !== false) {
            $phpFiles[$extraPhpFile] = true;
        }
    }

    $phpFileList = array_keys($phpFiles);
    natcasesort($phpFileList);

    foreach ($phpFileList as $phpFile) {
        $definitions[] = [
            'key' => 'php:' . migrationRunnerRelativePath($phpFile, $baseDir),
            'name' => basename($phpFile),
            'type' => 'php',
            'path' => $phpFile,
            'relative_path' => migrationRunnerRelativePath($phpFile, $baseDir),
        ];
    }

    return array_values($definitions);
}

function migrationRunnerRunAll(Database $db)
{
    @set_time_limit(0);
    @ignore_user_abort(true);

    migrationRunnerEnsureLogTable($db);

    $definitions = migrationRunnerDiscoverMigrations();
    $logMap = migrationRunnerGetLogMap($db);
    $results = [];
    $summary = [
        'total' => count($definitions),
        'executed' => 0,
        'already_applied' => 0,
        'logged' => 0,
        'failed' => 0,
        'remaining' => 0,
        'halted' => false,
    ];

    foreach ($definitions as $definition) {
        $existingLog = isset($logMap[$definition['key']]) ? $logMap[$definition['key']] : null;
        $checksum = is_file($definition['path']) ? sha1_file($definition['path']) : null;

        if ($existingLog && in_array($existingLog['status'], ['applied', 'already_applied'], true)) {
            $results[] = [
                'name' => $definition['name'],
                'type' => $definition['type'],
                'relative_path' => $definition['relative_path'],
                'status' => 'logged',
                'message' => 'Already recorded on ' . $existingLog['executed_at'] . '.',
            ];
            $summary['logged']++;
            continue;
        }

        if (migrationRunnerLooksApplied($db, $definition)) {
            $message = 'Schema already matches this migration.';
            migrationRunnerWriteLog($db, $definition, $checksum, 'already_applied', $message);
            $results[] = [
                'name' => $definition['name'],
                'type' => $definition['type'],
                'relative_path' => $definition['relative_path'],
                'status' => 'already_applied',
                'message' => $message,
            ];
            $summary['already_applied']++;
            continue;
        }

        try {
            if ($definition['type'] === 'sql') {
                $execution = migrationRunnerExecuteSqlFile($db, $definition['path']);
            } else {
                $execution = migrationRunnerExecutePhpFile($definition['path']);
            }

            $message = $execution['message'];
            migrationRunnerWriteLog($db, $definition, $checksum, 'applied', $message);
            $results[] = [
                'name' => $definition['name'],
                'type' => $definition['type'],
                'relative_path' => $definition['relative_path'],
                'status' => 'applied',
                'message' => $message,
            ];
            $summary['executed']++;
        } catch (Throwable $e) {
            $message = $e->getMessage();
            migrationRunnerWriteLog($db, $definition, $checksum, 'failed', $message);
            $results[] = [
                'name' => $definition['name'],
                'type' => $definition['type'],
                'relative_path' => $definition['relative_path'],
                'status' => 'failed',
                'message' => $message,
            ];
            $summary['failed']++;
            $summary['halted'] = true;
            break;
        }
    }

    $summary['remaining'] = max(0, $summary['total'] - count($results));

    return [
        'summary' => $summary,
        'results' => $results,
        'ran_at' => date('Y-m-d H:i:s'),
    ];
}

function migrationRunnerGetLogMap(Database $db)
{
    $rows = $db->getRows('SELECT migration_key, status, executed_at FROM system_migrations');
    $map = [];

    foreach ($rows as $row) {
        $map[$row['migration_key']] = $row;
    }

    return $map;
}

function migrationRunnerWriteLog(Database $db, array $definition, $checksum, $status, $notes)
{
    $db->insertRow(
        'INSERT INTO system_migrations (migration_key, migration_name, migration_type, migration_path, checksum, status, notes, executed_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
             migration_name = VALUES(migration_name),
             migration_type = VALUES(migration_type),
             migration_path = VALUES(migration_path),
             checksum = VALUES(checksum),
             status = VALUES(status),
             notes = VALUES(notes),
             updated_at = NOW()',
        [
            $definition['key'],
            $definition['name'],
            $definition['type'],
            $definition['relative_path'],
            $checksum,
            $status,
            $notes,
        ]
    );
}

function migrationRunnerLooksApplied(Database $db, array $definition)
{
    if (!is_file($definition['path'])) {
        return false;
    }

    if ($definition['type'] === 'php') {
        return migrationRunnerPhpLooksApplied($db, basename($definition['path']));
    }

    return migrationRunnerSqlLooksApplied($db, $definition['path']);
}

function migrationRunnerPhpLooksApplied(Database $db, $fileName)
{
    switch ($fileName) {
        case 'batch-tracking-migration.php':
            return migrationRunnerColumnExists($db, 'item_master', 'batch_tracking')
                && migrationRunnerTableExists($db, 'batch_master')
                && migrationRunnerColumnExists($db, 'fifo', 'batch_id')
                && migrationRunnerColumnExists($db, 'grn_details', 'batch_id')
                && migrationRunnerColumnExists($db, 'stock_transfer_items', 'batch_id')
                && migrationRunnerColumnExists($db, 'stock_issue_items', 'batch_id');

        case 'migrate_add_location_to_product_price_mapping.php':
            return migrationRunnerColumnExists($db, 'product_price_mapping', 'location_id')
                && migrationRunnerIndexExists($db, 'product_price_mapping', 'uq_product_price_type_location')
                && migrationRunnerIndexExists($db, 'product_price_mapping', 'idx_location');

        case 'migrate_purchase_return_tables.php':
            return migrationRunnerTableExists($db, 'purchase_return_header')
                && migrationRunnerTableExists($db, 'purchase_return_details');

        case 'run_migration.php':
            $column = migrationRunnerGetColumnMeta($db, 'standing_order', 'shipping_address_id');
            if (!$column) {
                return false;
            }

            $columnType = strtolower((string) $column['COLUMN_TYPE']);
            return strpos($columnType, 'int') !== false;
    }

    return false;
}

function migrationRunnerSqlLooksApplied(Database $db, $filePath)
{
    $sql = file_get_contents($filePath);
    if ($sql === false) {
        return false;
    }

    $statements = migrationRunnerSplitSqlStatements($sql);
    if (empty($statements)) {
        return true;
    }

    $detectableStatements = 0;

    foreach ($statements as $statement) {
        $statementIsDetectable = migrationRunnerStatementIsDetectable($statement);
        if (!$statementIsDetectable) {
            continue;
        }

        $detectableStatements++;
        if (!migrationRunnerStatementLooksApplied($db, $statement)) {
            return false;
        }
    }

    return $detectableStatements > 0;
}

function migrationRunnerExecuteSqlFile(Database $db, $filePath)
{
    $sql = file_get_contents($filePath);
    if ($sql === false) {
        throw new Exception('Unable to read SQL file: ' . basename($filePath));
    }

    $statements = migrationRunnerSplitSqlStatements($sql);
    $pdo = $db->getConnection();
    $executed = 0;
    $ignored = 0;

    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (Throwable $e) {
            if (migrationRunnerShouldIgnoreStatementError($db, $statement, $e->getMessage())) {
                $ignored++;
                continue;
            }

            throw new Exception(basename($filePath) . ': ' . $e->getMessage());
        }
    }

    if ($executed === 0 && $ignored === 0) {
        return ['message' => 'No executable SQL statements found.'];
    }

    $message = 'Executed ' . $executed . ' SQL statement(s)';
    if ($ignored > 0) {
        $message .= ' and skipped ' . $ignored . ' already-applied statement(s)';
    }

    return ['message' => $message . '.'];
}

function migrationRunnerExecutePhpFile($filePath)
{
    ob_start();

    try {
        (function ($__migrationFile) {
            include $__migrationFile;
        })($filePath);
        $output = trim(ob_get_clean());
    } catch (Throwable $e) {
        ob_end_clean();
        throw new Exception(basename($filePath) . ': ' . $e->getMessage());
    }

    if ($output !== '' && preg_match('/migration failed|migration error|fatal error/i', $output)) {
        throw new Exception(basename($filePath) . ': ' . trim(strip_tags($output)));
    }

    if ($output === '') {
        $output = 'PHP migration executed successfully.';
    }

    $output = preg_replace('/\s+/', ' ', strip_tags($output));
    return ['message' => trim($output)];
}

function migrationRunnerShouldIgnoreStatementError(Database $db, $statement, $message)
{
    if (migrationRunnerStatementIsDetectable($statement) && migrationRunnerStatementLooksApplied($db, $statement)) {
        return true;
    }

    $normalizedMessage = strtolower($message);
    $ignorablePatterns = [
        'already exists',
        'duplicate column name',
        'duplicate key name',
        'duplicate entry',
        'can\'t drop',
        'cannot drop',
        'unknown column',
        'doesn\'t exist',
    ];

    foreach ($ignorablePatterns as $pattern) {
        if (strpos($normalizedMessage, $pattern) !== false) {
            return true;
        }
    }

    return false;
}

function migrationRunnerStatementIsDetectable($statement)
{
    $trimmed = trim($statement);

    return preg_match('/^(CREATE\s+TABLE|ALTER\s+TABLE|CREATE\s+(UNIQUE\s+)?INDEX|DROP\s+INDEX)/i', $trimmed) === 1;
}

function migrationRunnerStatementLooksApplied(Database $db, $statement)
{
    $statement = trim($statement);

    if (preg_match('/^CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?\s+`?([a-zA-Z0-9_]+)`?/i', $statement, $matches)) {
        return migrationRunnerTableExists($db, $matches[1]);
    }

    if (preg_match('/^CREATE\s+(?:UNIQUE\s+)?INDEX\s+`?([a-zA-Z0-9_]+)`?\s+ON\s+`?([a-zA-Z0-9_]+)`?/i', $statement, $matches)) {
        return migrationRunnerIndexExists($db, $matches[2], $matches[1]);
    }

    if (preg_match('/^DROP\s+INDEX\s+`?([a-zA-Z0-9_]+)`?\s+ON\s+`?([a-zA-Z0-9_]+)`?/i', $statement, $matches)) {
        return !migrationRunnerIndexExists($db, $matches[2], $matches[1]);
    }

    if (preg_match('/^ALTER\s+TABLE\s+`?([a-zA-Z0-9_]+)`?\s+(.*)$/is', $statement, $matches)) {
        $tableName = $matches[1];
        $operations = migrationRunnerSplitAlterOperations($matches[2]);
        if (empty($operations)) {
            return false;
        }

        foreach ($operations as $operation) {
            if (!migrationRunnerAlterOperationLooksApplied($db, $tableName, $operation)) {
                return false;
            }
        }

        return true;
    }

    return false;
}

function migrationRunnerAlterOperationLooksApplied(Database $db, $tableName, $operation)
{
    $operation = trim($operation);

    if (preg_match('/^ADD(?:\s+COLUMN)?\s+`?([a-zA-Z0-9_]+)`?/i', $operation, $matches)) {
        return migrationRunnerColumnExists($db, $tableName, $matches[1]);
    }

    if (preg_match('/^CHANGE(?:\s+COLUMN)?\s+`?[a-zA-Z0-9_]+`?\s+`?([a-zA-Z0-9_]+)`?/i', $operation, $matches)) {
        return migrationRunnerColumnExists($db, $tableName, $matches[1]);
    }

    if (preg_match('/^MODIFY(?:\s+COLUMN)?\s+`?([a-zA-Z0-9_]+)`?/i', $operation, $matches)) {
        return migrationRunnerColumnExists($db, $tableName, $matches[1]);
    }

    if (preg_match('/^DROP\s+COLUMN\s+`?([a-zA-Z0-9_]+)`?/i', $operation, $matches)) {
        return !migrationRunnerColumnExists($db, $tableName, $matches[1]);
    }

    if (preg_match('/^ADD\s+UNIQUE\s+KEY\s+`?([a-zA-Z0-9_]+)`?/i', $operation, $matches)) {
        return migrationRunnerIndexExists($db, $tableName, $matches[1]);
    }

    if (preg_match('/^ADD\s+KEY\s+`?([a-zA-Z0-9_]+)`?/i', $operation, $matches)) {
        return migrationRunnerIndexExists($db, $tableName, $matches[1]);
    }

    if (preg_match('/^ADD\s+INDEX\s+`?([a-zA-Z0-9_]+)`?/i', $operation, $matches)) {
        return migrationRunnerIndexExists($db, $tableName, $matches[1]);
    }

    if (preg_match('/^DROP\s+(?:INDEX|KEY)\s+`?([a-zA-Z0-9_]+)`?/i', $operation, $matches)) {
        return !migrationRunnerIndexExists($db, $tableName, $matches[1]);
    }

    if (preg_match('/^ADD\s+PRIMARY\s+KEY/i', $operation)) {
        return migrationRunnerPrimaryKeyExists($db, $tableName);
    }

    return false;
}

function migrationRunnerSplitSqlStatements($sql)
{
    $sql = str_replace(["\r\n", "\r"], "\n", (string) $sql);
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $inSingleQuote = false;
    $inDoubleQuote = false;
    $inBacktick = false;

    for ($index = 0; $index < $length; $index++) {
        $char = $sql[$index];
        $next = ($index + 1 < $length) ? $sql[$index + 1] : '';
        $previous = ($index > 0) ? $sql[$index - 1] : '';

        if (!$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
            if ($char === '-' && $next === '-' && ($previous === '' || ctype_space($previous))) {
                while ($index < $length && $sql[$index] !== "\n") {
                    $index++;
                }
                continue;
            }

            if ($char === '#') {
                while ($index < $length && $sql[$index] !== "\n") {
                    $index++;
                }
                continue;
            }

            if ($char === '/' && $next === '*') {
                $index += 2;
                while ($index < $length - 1 && !($sql[$index] === '*' && $sql[$index + 1] === '/')) {
                    $index++;
                }
                $index++;
                continue;
            }
        }

        if ($char === "'" && !$inDoubleQuote && !$inBacktick && !migrationRunnerIsEscaped($sql, $index)) {
            $inSingleQuote = !$inSingleQuote;
        } elseif ($char === '"' && !$inSingleQuote && !$inBacktick && !migrationRunnerIsEscaped($sql, $index)) {
            $inDoubleQuote = !$inDoubleQuote;
        } elseif ($char === '`' && !$inSingleQuote && !$inDoubleQuote) {
            $inBacktick = !$inBacktick;
        }

        if ($char === ';' && !$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $statement = trim($buffer);
    if ($statement !== '') {
        $statements[] = $statement;
    }

    return $statements;
}

function migrationRunnerSplitAlterOperations($operationsSql)
{
    $operations = [];
    $buffer = '';
    $length = strlen($operationsSql);
    $depth = 0;
    $inSingleQuote = false;
    $inDoubleQuote = false;
    $inBacktick = false;

    for ($index = 0; $index < $length; $index++) {
        $char = $operationsSql[$index];

        if ($char === "'" && !$inDoubleQuote && !$inBacktick && !migrationRunnerIsEscaped($operationsSql, $index)) {
            $inSingleQuote = !$inSingleQuote;
        } elseif ($char === '"' && !$inSingleQuote && !$inBacktick && !migrationRunnerIsEscaped($operationsSql, $index)) {
            $inDoubleQuote = !$inDoubleQuote;
        } elseif ($char === '`' && !$inSingleQuote && !$inDoubleQuote) {
            $inBacktick = !$inBacktick;
        } elseif (!$inSingleQuote && !$inDoubleQuote && !$inBacktick) {
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')' && $depth > 0) {
                $depth--;
            } elseif ($char === ',' && $depth === 0) {
                $operation = trim($buffer);
                if ($operation !== '') {
                    $operations[] = $operation;
                }
                $buffer = '';
                continue;
            }
        }

        $buffer .= $char;
    }

    $operation = trim($buffer);
    if ($operation !== '') {
        $operations[] = $operation;
    }

    return $operations;
}

function migrationRunnerIsEscaped($sql, $index)
{
    $slashCount = 0;
    for ($cursor = $index - 1; $cursor >= 0 && $sql[$cursor] === '\\'; $cursor--) {
        $slashCount++;
    }

    return ($slashCount % 2) === 1;
}

function migrationRunnerTableExists(Database $db, $tableName)
{
    $row = $db->getRow(
        'SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
        [$tableName]
    );

    return !empty($row) && (int) $row['total'] > 0;
}

function migrationRunnerColumnExists(Database $db, $tableName, $columnName)
{
    $row = $db->getRow(
        'SELECT COUNT(*) AS total FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
        [$tableName, $columnName]
    );

    return !empty($row) && (int) $row['total'] > 0;
}

function migrationRunnerIndexExists(Database $db, $tableName, $indexName)
{
    $row = $db->getRow(
        'SELECT COUNT(*) AS total FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
        [$tableName, $indexName]
    );

    return !empty($row) && (int) $row['total'] > 0;
}

function migrationRunnerPrimaryKeyExists(Database $db, $tableName)
{
    $row = $db->getRow(
        "SELECT COUNT(*) AS total FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = ? AND constraint_type = 'PRIMARY KEY'",
        [$tableName]
    );

    return !empty($row) && (int) $row['total'] > 0;
}

function migrationRunnerGetColumnMeta(Database $db, $tableName, $columnName)
{
    return $db->getRow(
        'SELECT COLUMN_NAME, COLUMN_TYPE, DATA_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
        [$tableName, $columnName]
    );
}

function migrationRunnerRelativePath($absolutePath, $baseDir)
{
    $normalizedBase = str_replace('\\', '/', (string) $baseDir);
    $normalizedPath = str_replace('\\', '/', (string) $absolutePath);

    if ($normalizedBase !== '' && strpos($normalizedPath, $normalizedBase . '/') === 0) {
        return substr($normalizedPath, strlen($normalizedBase) + 1);
    }

    return basename($absolutePath);
}