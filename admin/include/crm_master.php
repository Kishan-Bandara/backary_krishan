<?php
if (!function_exists('crmEnsureSchema')) {
    function crmEnsureSchema(Database $db)
    {
        static $ready = false;

        if ($ready) {
            return;
        }

        $db->insertRow(
            'CREATE TABLE IF NOT EXISTS crm_person_master (
                person_id INT NOT NULL AUTO_INCREMENT,
                person_code VARCHAR(30) NOT NULL,
                title VARCHAR(10) NOT NULL DEFAULT "Mr",
                contact_name VARCHAR(150) NOT NULL,
                contact_no VARCHAR(50) NOT NULL,
                address TEXT NULL,
                designation VARCHAR(150) NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (person_id),
                UNIQUE KEY uq_crm_person_code (person_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        $db->insertRow(
            'CREATE TABLE IF NOT EXISTS crm_company_master (
                company_id INT NOT NULL AUTO_INCREMENT,
                company_code VARCHAR(30) NOT NULL,
                company_name VARCHAR(180) NOT NULL,
                company_type VARCHAR(100) NOT NULL,
                contact_details VARCHAR(255) NULL,
                address TEXT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (company_id),
                UNIQUE KEY uq_crm_company_code (company_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        $db->insertRow(
            'CREATE TABLE IF NOT EXISTS crm_designation_master (
                designation_id INT NOT NULL AUTO_INCREMENT,
                designation_name VARCHAR(150) NOT NULL,
                description TEXT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (designation_id),
                UNIQUE KEY uq_crm_designation_name (designation_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        $db->insertRow(
            'CREATE TABLE IF NOT EXISTS crm_segment_master (
                segment_id INT NOT NULL AUTO_INCREMENT,
                segment_name VARCHAR(150) NOT NULL,
                description TEXT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (segment_id),
                UNIQUE KEY uq_crm_segment_name (segment_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        $db->insertRow(
            'CREATE TABLE IF NOT EXISTS crm_category_master (
                category_id INT NOT NULL AUTO_INCREMENT,
                segment_id INT NOT NULL,
                category_name VARCHAR(150) NOT NULL,
                description TEXT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (category_id),
                UNIQUE KEY uq_crm_segment_category (segment_id, category_name),
                KEY idx_crm_category_segment (segment_id),
                CONSTRAINT fk_crm_category_segment FOREIGN KEY (segment_id) REFERENCES crm_segment_master(segment_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        $db->insertRow(
            'CREATE TABLE IF NOT EXISTS crm_sales_person_master (
                sales_person_id INT NOT NULL AUTO_INCREMENT,
                sales_person_name VARCHAR(150) NOT NULL,
                contact_no VARCHAR(50) NULL,
                email VARCHAR(150) NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (sales_person_id),
                UNIQUE KEY uq_crm_sales_person_name (sales_person_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        $db->insertRow(
            'CREATE TABLE IF NOT EXISTS crm_sales_cycle_master (
                sales_cycle_id INT NOT NULL AUTO_INCREMENT,
                cycle_code VARCHAR(50) NOT NULL,
                cycle_description VARCHAR(255) NOT NULL,
                probability_calculation VARCHAR(100) NOT NULL DEFAULT "Chances of Success %",
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (sales_cycle_id),
                UNIQUE KEY uq_crm_sales_cycle_code (cycle_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        $db->insertRow(
            'CREATE TABLE IF NOT EXISTS crm_sales_cycle_stage (
                sales_cycle_stage_id INT NOT NULL AUTO_INCREMENT,
                sales_cycle_id INT NOT NULL,
                stage_no INT NOT NULL,
                stage_description VARCHAR(255) NOT NULL,
                completed_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
                chance_of_success_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
                activity_code VARCHAR(100) NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (sales_cycle_stage_id),
                UNIQUE KEY uq_crm_sales_cycle_stage (sales_cycle_id, stage_no),
                KEY idx_crm_sales_cycle_stage_cycle (sales_cycle_id),
                CONSTRAINT fk_crm_sales_cycle_stage_cycle FOREIGN KEY (sales_cycle_id) REFERENCES crm_sales_cycle_master(sales_cycle_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        $db->insertRow(
            'CREATE TABLE IF NOT EXISTS crm_activity_master (
                activity_id INT NOT NULL AUTO_INCREMENT,
                activity_code VARCHAR(50) NOT NULL,
                description VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (activity_id),
                UNIQUE KEY uq_crm_activity_code (activity_code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        $db->insertRow(
            'CREATE TABLE IF NOT EXISTS crm_activity_line (
                activity_line_id INT NOT NULL AUTO_INCREMENT,
                activity_id INT NOT NULL,
                line_type VARCHAR(100) NULL,
                description TEXT NOT NULL,
                activity_percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
                priority VARCHAR(20) NOT NULL DEFAULT "Low",
                date_formula VARCHAR(30) NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (activity_line_id),
                KEY idx_crm_activity_line_activity (activity_id),
                CONSTRAINT fk_crm_activity_line_activity FOREIGN KEY (activity_id) REFERENCES crm_activity_master(activity_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        crmEnsureColumn($db, 'crm_person_master', 'designation_id', 'ALTER TABLE crm_person_master ADD COLUMN designation_id INT NULL AFTER designation');
        crmEnsureColumn($db, 'crm_person_master', 'email', 'ALTER TABLE crm_person_master ADD COLUMN email VARCHAR(150) NULL AFTER contact_no');
        crmEnsureColumn($db, 'crm_company_master', 'segment_id', 'ALTER TABLE crm_company_master ADD COLUMN segment_id INT NULL AFTER company_type');
        crmEnsureColumn($db, 'crm_company_master', 'category_id', 'ALTER TABLE crm_company_master ADD COLUMN category_id INT NULL AFTER segment_id');
        crmEnsureColumn($db, 'crm_company_master', 'sales_person_id', 'ALTER TABLE crm_company_master ADD COLUMN sales_person_id INT NULL AFTER category_id');

        crmSyncLegacyDesignations($db);

        $db->insertRow(
            'CREATE TABLE IF NOT EXISTS crm_company_person (
                company_person_id INT NOT NULL AUTO_INCREMENT,
                company_id INT NOT NULL,
                person_id INT NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (company_person_id),
                UNIQUE KEY uq_crm_company_person (company_id, person_id),
                KEY idx_crm_company_person_person (person_id),
                CONSTRAINT fk_crm_company_person_company FOREIGN KEY (company_id) REFERENCES crm_company_master(company_id) ON DELETE CASCADE,
                CONSTRAINT fk_crm_company_person_person FOREIGN KEY (person_id) REFERENCES crm_person_master(person_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        $db->insertRow(
            'CREATE TABLE IF NOT EXISTS crm_opportunity (
                opportunity_id INT NOT NULL AUTO_INCREMENT,
                opportunity_code VARCHAR(30) NOT NULL,
                description VARCHAR(255) NOT NULL,
                person_id INT NOT NULL,
                company_id INT NULL,
                sales_cycle_id INT NULL,
                current_sales_cycle_stage_id INT NULL,
                    current_activity_line_id INT NULL,
                segment_id INT NULL,
                sales_person_id INT NULL,
                contact_no VARCHAR(30) NOT NULL,
                contact_name VARCHAR(180) NOT NULL,
                phone_no VARCHAR(50) NULL,
                mobile_phone_no VARCHAR(50) NULL,
                email VARCHAR(150) NULL,
                contact_company_name VARCHAR(180) NULL,
                sales_document_type VARCHAR(100) NULL,
                sales_document_no VARCHAR(100) NULL,
                status VARCHAR(50) NOT NULL DEFAULT "In Progress",
                is_closed TINYINT(1) NOT NULL DEFAULT 0,
                creation_date DATE NULL,
                date_closed DATE NULL,
                estimated_sales_value DECIMAL(14,2) NOT NULL DEFAULT 0,
                chance_of_success_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
                estimated_closing_date_for_stage DATE NULL,
                estimated_gp DECIMAL(14,2) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (opportunity_id),
                UNIQUE KEY uq_crm_opportunity_code (opportunity_code),
                KEY idx_crm_opportunity_person (person_id),
                KEY idx_crm_opportunity_company (company_id),
                KEY idx_crm_opportunity_cycle (sales_cycle_id),
                KEY idx_crm_opportunity_stage (current_sales_cycle_stage_id),
                    KEY idx_crm_opportunity_current_activity_line (current_activity_line_id),
                KEY idx_crm_opportunity_segment (segment_id),
                KEY idx_crm_opportunity_sales_person (sales_person_id),
                CONSTRAINT fk_crm_opportunity_person FOREIGN KEY (person_id) REFERENCES crm_person_master(person_id) ON DELETE RESTRICT,
                CONSTRAINT fk_crm_opportunity_company FOREIGN KEY (company_id) REFERENCES crm_company_master(company_id) ON DELETE SET NULL,
                CONSTRAINT fk_crm_opportunity_sales_cycle FOREIGN KEY (sales_cycle_id) REFERENCES crm_sales_cycle_master(sales_cycle_id) ON DELETE SET NULL,
                CONSTRAINT fk_crm_opportunity_stage FOREIGN KEY (current_sales_cycle_stage_id) REFERENCES crm_sales_cycle_stage(sales_cycle_stage_id) ON DELETE SET NULL,
                    CONSTRAINT fk_crm_opportunity_current_activity_line FOREIGN KEY (current_activity_line_id) REFERENCES crm_activity_line(activity_line_id) ON DELETE SET NULL,
                CONSTRAINT fk_crm_opportunity_segment FOREIGN KEY (segment_id) REFERENCES crm_segment_master(segment_id) ON DELETE SET NULL,
                CONSTRAINT fk_crm_opportunity_sales_person FOREIGN KEY (sales_person_id) REFERENCES crm_sales_person_master(sales_person_id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        crmEnsureColumn($db, 'crm_opportunity', 'current_sales_cycle_stage_id', 'ALTER TABLE crm_opportunity ADD COLUMN current_sales_cycle_stage_id INT NULL AFTER sales_cycle_id');
            crmEnsureColumn($db, 'crm_opportunity', 'current_activity_line_id', 'ALTER TABLE crm_opportunity ADD COLUMN current_activity_line_id INT NULL AFTER current_sales_cycle_stage_id');
        crmEnsureColumn($db, 'crm_opportunity', 'estimated_sales_value', 'ALTER TABLE crm_opportunity ADD COLUMN estimated_sales_value DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER date_closed');
        crmEnsureColumn($db, 'crm_opportunity', 'chance_of_success_percent', 'ALTER TABLE crm_opportunity ADD COLUMN chance_of_success_percent DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER estimated_sales_value');
        crmEnsureColumn($db, 'crm_opportunity', 'estimated_closing_date_for_stage', 'ALTER TABLE crm_opportunity ADD COLUMN estimated_closing_date_for_stage DATE NULL AFTER chance_of_success_percent');

        $db->insertRow(
            'CREATE TABLE IF NOT EXISTS crm_opportunity_update (
                opportunity_update_id INT NOT NULL AUTO_INCREMENT,
                opportunity_id INT NOT NULL,
                action_type VARCHAR(30) NOT NULL DEFAULT "Current",
                sales_cycle_stage_id INT NOT NULL,
                date_of_change DATE NOT NULL,
                estimated_sales_value DECIMAL(14,2) NOT NULL DEFAULT 0,
                chance_of_success_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
                estimated_closing_date_for_stage DATE NULL,
                opportunity_closing_date DATE NULL,
                cancel_existing_open_tasks TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (opportunity_update_id),
                KEY idx_crm_opportunity_update_opportunity (opportunity_id),
                KEY idx_crm_opportunity_update_stage (sales_cycle_stage_id),
                CONSTRAINT fk_crm_opportunity_update_opportunity FOREIGN KEY (opportunity_id) REFERENCES crm_opportunity(opportunity_id) ON DELETE CASCADE,
                CONSTRAINT fk_crm_opportunity_update_stage FOREIGN KEY (sales_cycle_stage_id) REFERENCES crm_sales_cycle_stage(sales_cycle_stage_id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        $db->insertRow(
            'CREATE TABLE IF NOT EXISTS crm_opportunity_activity_task (
                opportunity_activity_task_id INT NOT NULL AUTO_INCREMENT,
                opportunity_id INT NOT NULL,
                activity_line_id INT NOT NULL,
                finish_date DATE NOT NULL,
                remark TEXT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (opportunity_activity_task_id),
                UNIQUE KEY uq_crm_opportunity_activity_task (opportunity_id, activity_line_id),
                KEY idx_crm_opportunity_activity_task_opportunity (opportunity_id),
                KEY idx_crm_opportunity_activity_task_line (activity_line_id),
                CONSTRAINT fk_crm_opportunity_activity_task_opportunity FOREIGN KEY (opportunity_id) REFERENCES crm_opportunity(opportunity_id) ON DELETE CASCADE,
                CONSTRAINT fk_crm_opportunity_activity_task_line FOREIGN KEY (activity_line_id) REFERENCES crm_activity_line(activity_line_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );

        $ready = true;
    }
}

if (!function_exists('crmCompanyTypes')) {
    function crmCompanyTypes()
    {
        return [
            'Agriculture',
            'IT',
            'Manufacturing',
            'Retail',
            'Wholesale',
            'Logistics',
            'Finance',
            'Healthcare',
            'Education',
            'Other'
        ];
    }
}

if (!function_exists('crmProbabilityCalculationOptions')) {
    function crmProbabilityCalculationOptions()
    {
        return [
            'Chances of Success %',
            'Completed %'
        ];
    }
}

if (!function_exists('crmOpportunityStatuses')) {
    function crmOpportunityStatuses()
    {
        return [
            'In Progress',
            'Open',
            'Won',
            'Lost',
            'On Hold',
            'Closed'
        ];
    }
}

if (!function_exists('crmSalesDocumentTypes')) {
    function crmSalesDocumentTypes()
    {
        return [
            'Quote',
            'Order',
            'Invoice',
            'Blanket Order',
            'Other'
        ];
    }
}

if (!function_exists('crmOpportunityUpdateActions')) {
    function crmOpportunityUpdateActions()
    {
        return [
            'Current',
            'Next',
            'Previous'
        ];
    }
}

if (!function_exists('crmEnsureColumn')) {
    function crmEnsureColumn(Database $db, $tableName, $columnName, $alterSql)
    {
        $column = $db->getRow("SHOW COLUMNS FROM {$tableName} LIKE ?", [$columnName]);
        if (!$column) {
            $db->insertRow($alterSql);
        }
    }
}

if (!function_exists('crmEnsureDesignation')) {
    function crmEnsureDesignation(Database $db, $designationName)
    {
        $designationName = trim((string) $designationName);
        if ($designationName === '') {
            return 0;
        }

        $existing = $db->getRow(
            'SELECT designation_id FROM crm_designation_master WHERE designation_name = ? LIMIT 1',
            [$designationName]
        );
        if ($existing) {
            return (int) $existing['designation_id'];
        }

        $db->insertRow(
            'INSERT INTO crm_designation_master (designation_name) VALUES (?)',
            [$designationName]
        );

        $saved = $db->getRow(
            'SELECT designation_id FROM crm_designation_master WHERE designation_name = ? LIMIT 1',
            [$designationName]
        );

        return (int) ($saved['designation_id'] ?? 0);
    }
}

if (!function_exists('crmSyncLegacyDesignations')) {
    function crmSyncLegacyDesignations(Database $db)
    {
        $rows = $db->getRows(
            'SELECT person_id, designation FROM crm_person_master WHERE COALESCE(designation, "") <> "" AND (designation_id IS NULL OR designation_id = 0)'
        );

        foreach ($rows as $row) {
            $designationName = trim((string) ($row['designation'] ?? ''));
            if ($designationName === '') {
                continue;
            }

            $designationId = crmEnsureDesignation($db, $designationName);
            $db->updateRow(
                'UPDATE crm_person_master SET designation_id = ? WHERE person_id = ?',
                [$designationId, (int) $row['person_id']]
            );
        }
    }
}

if (!function_exists('crmGenerateCode')) {
    function crmGenerateCode(Database $db, $tableName, $columnName, $prefix)
    {
        for ($i = 0; $i < 10; $i++) {
            $candidate = $prefix . '-' . date('Ymd') . '-' . sprintf('%04d', mt_rand(0, 9999));
            $row = $db->getRow("SELECT {$columnName} FROM {$tableName} WHERE {$columnName} = ? LIMIT 1", [$candidate]);
            if (!$row) {
                return $candidate;
            }
        }

        $row = $db->getRow("SELECT COUNT(*) AS total FROM {$tableName}");
        $next = (int) ($row['total'] ?? 0) + 1;
        return $prefix . '-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('crmCodeExists')) {
    function crmCodeExists(Database $db, $code, $excludeType = '', $excludeId = 0)
    {
        crmEnsureSchema($db);

        $person = $db->getRow(
            'SELECT person_id FROM crm_person_master WHERE person_code = ?' . ($excludeType === 'person' ? ' AND person_id <> ?' : '') . ' LIMIT 1',
            $excludeType === 'person' ? [$code, (int) $excludeId] : [$code]
        );
        if ($person) {
            return true;
        }

        $company = $db->getRow(
            'SELECT company_id FROM crm_company_master WHERE company_code = ?' . ($excludeType === 'company' ? ' AND company_id <> ?' : '') . ' LIMIT 1',
            $excludeType === 'company' ? [$code, (int) $excludeId] : [$code]
        );

        return (bool) $company;
    }
}

if (!function_exists('crmGenerateContactCode')) {
    function crmGenerateContactCode(Database $db)
    {
        crmEnsureSchema($db);

        for ($i = 0; $i < 10; $i++) {
            $candidate = 'CT-' . date('Ymd') . '-' . sprintf('%04d', mt_rand(0, 9999));
            if (!crmCodeExists($db, $candidate)) {
                return $candidate;
            }
        }

        $personRow = $db->getRow('SELECT COUNT(*) AS total FROM crm_person_master');
        $companyRow = $db->getRow('SELECT COUNT(*) AS total FROM crm_company_master');
        $next = (int) ($personRow['total'] ?? 0) + (int) ($companyRow['total'] ?? 0) + 1;
        return 'CT-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('crmGenerateOpportunityCode')) {
    function crmGenerateOpportunityCode(Database $db)
    {
        crmEnsureSchema($db);
        return crmGenerateCode($db, 'crm_opportunity', 'opportunity_code', 'OPP');
    }
}

if (!function_exists('crmFetchPersons')) {
    function crmFetchPersons(Database $db)
    {
        crmEnsureSchema($db);
        return $db->getRows(
            'SELECT p.*, d.designation_name
             FROM crm_person_master p
             LEFT JOIN crm_designation_master d ON d.designation_id = p.designation_id
             ORDER BY p.contact_name ASC, p.person_id DESC'
        );
    }
}

if (!function_exists('crmFetchCompanies')) {
    function crmFetchCompanies(Database $db)
    {
        crmEnsureSchema($db);
        return $db->getRows(
            'SELECT c.*, s.segment_name, cat.category_name, sp.sales_person_name,
                    COUNT(cp.person_id) AS person_count,
                    MAX(cp.person_id) AS linked_person_id,
                    MAX(p.contact_name) AS linked_person_name
             FROM crm_company_master c
             LEFT JOIN crm_segment_master s ON s.segment_id = c.segment_id
             LEFT JOIN crm_category_master cat ON cat.category_id = c.category_id
             LEFT JOIN crm_sales_person_master sp ON sp.sales_person_id = c.sales_person_id
             LEFT JOIN crm_company_person cp ON cp.company_id = c.company_id
             LEFT JOIN crm_person_master p ON p.person_id = cp.person_id
             GROUP BY c.company_id
             ORDER BY c.company_name ASC, c.company_id DESC'
        );
    }
}

if (!function_exists('crmFetchUnifiedRecords')) {
    function crmFetchUnifiedRecords(Database $db)
    {
        crmEnsureSchema($db);

        return $db->getRows(
            'SELECT
                "person" AS crm_type,
                p.person_id AS record_id,
                p.person_code AS crm_code,
                p.contact_name AS display_name,
                p.title AS company_type,
                p.contact_no AS contact_info,
                TRIM(CONCAT(COALESCE(d.designation_name, ""), CASE WHEN d.designation_name IS NOT NULL AND c.company_name IS NOT NULL AND c.company_name <> "" THEN " | " ELSE "" END, COALESCE(c.company_name, ""))) AS extra_info,
                p.address AS address,
                COUNT(cp.company_id) AS relation_count,
                p.created_at AS created_at
            FROM crm_person_master p
            LEFT JOIN crm_designation_master d ON d.designation_id = p.designation_id
            LEFT JOIN crm_company_person cp ON cp.person_id = p.person_id
            LEFT JOIN crm_company_master c ON c.company_id = cp.company_id
            GROUP BY p.person_id

            UNION ALL

            SELECT
                "company" AS crm_type,
                c.company_id AS record_id,
                c.company_code AS crm_code,
                c.company_name AS display_name,
                c.company_type AS company_type,
                c.contact_details AS contact_info,
                TRIM(CONCAT(
                    COALESCE(GROUP_CONCAT(p.contact_name ORDER BY p.contact_name SEPARATOR ", "), ""),
                    CASE WHEN MAX(s.segment_name) IS NOT NULL AND MAX(s.segment_name) <> "" THEN CONCAT(" | ", MAX(s.segment_name)) ELSE "" END,
                    CASE WHEN MAX(cat.category_name) IS NOT NULL AND MAX(cat.category_name) <> "" THEN CONCAT(" / ", MAX(cat.category_name)) ELSE "" END,
                    CASE WHEN MAX(sp.sales_person_name) IS NOT NULL AND MAX(sp.sales_person_name) <> "" THEN CONCAT(" | ", MAX(sp.sales_person_name)) ELSE "" END
                )) AS extra_info,
                c.address AS address,
                COUNT(cp.person_id) AS relation_count,
                c.created_at AS created_at
            FROM crm_company_master c
            LEFT JOIN crm_segment_master s ON s.segment_id = c.segment_id
            LEFT JOIN crm_category_master cat ON cat.category_id = c.category_id
            LEFT JOIN crm_sales_person_master sp ON sp.sales_person_id = c.sales_person_id
            LEFT JOIN crm_company_person cp ON cp.company_id = c.company_id
            LEFT JOIN crm_person_master p ON p.person_id = cp.person_id
            GROUP BY c.company_id

            ORDER BY created_at DESC, crm_type ASC, record_id DESC'
        );
    }
}

if (!function_exists('crmFetchCompanyPersonIds')) {
    function crmFetchCompanyPersonIds(Database $db, $companyId)
    {
        crmEnsureSchema($db);
        $rows = $db->getRows('SELECT person_id FROM crm_company_person WHERE company_id = ?', [(int) $companyId]);
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row['person_id'];
        }
        return $ids;
    }
}

if (!function_exists('crmFetchPersonCompanyId')) {
    function crmFetchPersonCompanyId(Database $db, $personId)
    {
        crmEnsureSchema($db);

        $row = $db->getRow(
            'SELECT company_id FROM crm_company_person WHERE person_id = ? ORDER BY company_person_id ASC LIMIT 1',
            [(int) $personId]
        );

        return (int) ($row['company_id'] ?? 0);
    }
}

if (!function_exists('crmCompanyAssignedToAnotherPerson')) {
    function crmCompanyAssignedToAnotherPerson(Database $db, $companyId, $personId = 0)
    {
        crmEnsureSchema($db);

        $row = $db->getRow(
            'SELECT person_id FROM crm_company_person WHERE company_id = ? ORDER BY company_person_id ASC LIMIT 1',
            [(int) $companyId]
        );

        $linkedPersonId = (int) ($row['person_id'] ?? 0);
        if ($linkedPersonId > 0 && $linkedPersonId !== (int) $personId) {
            return $linkedPersonId;
        }

        return 0;
    }
}

if (!function_exists('crmAssignPersonCompany')) {
    function crmAssignPersonCompany(Database $db, $personId, $companyId)
    {
        crmEnsureSchema($db);

        $personId = (int) $personId;
        $companyId = (int) $companyId;

        $db->deleteRow('DELETE FROM crm_company_person WHERE person_id = ?', [$personId]);

        if ($companyId <= 0) {
            return;
        }

        $db->deleteRow('DELETE FROM crm_company_person WHERE company_id = ?', [$companyId]);
        $db->insertRow(
            'INSERT INTO crm_company_person (company_id, person_id) VALUES (?, ?)',
            [$companyId, $personId]
        );
    }
}

if (!function_exists('crmFetchCompanyLinkedPerson')) {
    function crmFetchCompanyLinkedPerson(Database $db, $companyId)
    {
        crmEnsureSchema($db);

        return $db->getRow(
            'SELECT p.person_id, p.person_code, p.contact_name
             FROM crm_company_person cp
             INNER JOIN crm_person_master p ON p.person_id = cp.person_id
             WHERE cp.company_id = ?
             ORDER BY cp.company_person_id ASC
             LIMIT 1',
            [(int) $companyId]
        );
    }
}

if (!function_exists('crmSyncCompanyPersons')) {
    function crmSyncCompanyPersons(Database $db, $companyId, array $personIds)
    {
        crmEnsureSchema($db);

        $uniqueIds = [];
        foreach ($personIds as $personId) {
            $personId = (int) $personId;
            if ($personId > 0) {
                $uniqueIds[$personId] = true;
            }
        }

        $db->deleteRow('DELETE FROM crm_company_person WHERE company_id = ?', [(int) $companyId]);

        foreach (array_keys($uniqueIds) as $personId) {
            $db->insertRow(
                'INSERT INTO crm_company_person (company_id, person_id) VALUES (?, ?)',
                [(int) $companyId, $personId]
            );
        }
    }
}

if (!function_exists('crmSelected')) {
    function crmSelected($left, $right)
    {
        return (string) $left === (string) $right ? 'selected' : '';
    }
}

if (!function_exists('crmChecked')) {
    function crmChecked($condition)
    {
        return $condition ? 'checked' : '';
    }
}

if (!function_exists('crmEscape')) {
    function crmEscape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('crmFetchDesignations')) {
    function crmFetchDesignations(Database $db)
    {
        crmEnsureSchema($db);
        return $db->getRows('SELECT * FROM crm_designation_master ORDER BY designation_name ASC, designation_id DESC');
    }
}

if (!function_exists('crmFetchDesignationName')) {
    function crmFetchDesignationName(Database $db, $designationId)
    {
        crmEnsureSchema($db);
        if ((int) $designationId <= 0) {
            return '';
        }

        $row = $db->getRow('SELECT designation_name FROM crm_designation_master WHERE designation_id = ? LIMIT 1', [(int) $designationId]);
        return (string) ($row['designation_name'] ?? '');
    }
}

if (!function_exists('crmFetchSegments')) {
    function crmFetchSegments(Database $db)
    {
        crmEnsureSchema($db);
        return $db->getRows('SELECT * FROM crm_segment_master ORDER BY segment_name ASC, segment_id DESC');
    }
}

if (!function_exists('crmFetchCategories')) {
    function crmFetchCategories(Database $db, $segmentId = 0)
    {
        crmEnsureSchema($db);

        if ((int) $segmentId > 0) {
            return $db->getRows(
                'SELECT cat.*, seg.segment_name
                 FROM crm_category_master cat
                 INNER JOIN crm_segment_master seg ON seg.segment_id = cat.segment_id
                 WHERE cat.segment_id = ?
                 ORDER BY cat.category_name ASC, cat.category_id DESC',
                [(int) $segmentId]
            );
        }

        return $db->getRows(
            'SELECT cat.*, seg.segment_name
             FROM crm_category_master cat
             INNER JOIN crm_segment_master seg ON seg.segment_id = cat.segment_id
             ORDER BY seg.segment_name ASC, cat.category_name ASC, cat.category_id DESC'
        );
    }
}

if (!function_exists('crmCategoryBelongsToSegment')) {
    function crmCategoryBelongsToSegment(Database $db, $categoryId, $segmentId)
    {
        crmEnsureSchema($db);
        if ((int) $categoryId <= 0 || (int) $segmentId <= 0) {
            return false;
        }

        $row = $db->getRow(
            'SELECT category_id FROM crm_category_master WHERE category_id = ? AND segment_id = ? LIMIT 1',
            [(int) $categoryId, (int) $segmentId]
        );

        return (bool) $row;
    }
}

if (!function_exists('crmFetchSalesPersons')) {
    function crmFetchSalesPersons(Database $db)
    {
        crmEnsureSchema($db);
        return $db->getRows('SELECT * FROM crm_sales_person_master ORDER BY sales_person_name ASC, sales_person_id DESC');
    }
}

if (!function_exists('crmFetchSalesCycles')) {
    function crmFetchSalesCycles(Database $db)
    {
        crmEnsureSchema($db);
        return $db->getRows(
            'SELECT sc.*, COUNT(st.sales_cycle_stage_id) AS stage_count
             FROM crm_sales_cycle_master sc
             LEFT JOIN crm_sales_cycle_stage st ON st.sales_cycle_id = sc.sales_cycle_id
             GROUP BY sc.sales_cycle_id
             ORDER BY sc.cycle_code ASC, sc.sales_cycle_id DESC'
        );
    }
}

if (!function_exists('crmFetchSalesCycle')) {
    function crmFetchSalesCycle(Database $db, $salesCycleId)
    {
        crmEnsureSchema($db);
        return $db->getRow('SELECT * FROM crm_sales_cycle_master WHERE sales_cycle_id = ? LIMIT 1', [(int) $salesCycleId]);
    }
}

if (!function_exists('crmFetchSalesCycleStages')) {
    function crmFetchSalesCycleStages(Database $db, $salesCycleId)
    {
        crmEnsureSchema($db);
        return $db->getRows(
            'SELECT * FROM crm_sales_cycle_stage WHERE sales_cycle_id = ? ORDER BY stage_no ASC, sales_cycle_stage_id ASC',
            [(int) $salesCycleId]
        );
    }
}

if (!function_exists('crmFetchSalesCycleStage')) {
    function crmFetchSalesCycleStage(Database $db, $salesCycleStageId)
    {
        crmEnsureSchema($db);
        return $db->getRow(
            'SELECT * FROM crm_sales_cycle_stage WHERE sales_cycle_stage_id = ? LIMIT 1',
            [(int) $salesCycleStageId]
        );
    }
}

if (!function_exists('crmFetchOpportunityContacts')) {
    function crmFetchOpportunityContacts(Database $db)
    {
        crmEnsureSchema($db);

        return $db->getRows(
            'SELECT
                p.person_id,
                p.person_code,
                p.contact_name,
                p.contact_no AS mobile_phone_no,
                COALESCE(p.email, "") AS email,
                p.address AS person_address,
                cp.company_id,
                c.company_code,
                c.company_name,
                COALESCE(c.contact_details, "") AS company_phone_no,
                c.address AS company_address,
                c.segment_id,
                COALESCE(seg.segment_name, "") AS segment_name,
                c.sales_person_id,
                COALESCE(sp.sales_person_name, "") AS sales_person_name
             FROM crm_person_master p
                 LEFT JOIN (
                     SELECT person_id, MIN(company_person_id) AS company_person_id
                     FROM crm_company_person
                     GROUP BY person_id
                 ) cp_map ON cp_map.person_id = p.person_id
                 LEFT JOIN crm_company_person cp ON cp.company_person_id = cp_map.company_person_id
             LEFT JOIN crm_company_master c ON c.company_id = cp.company_id
             LEFT JOIN crm_segment_master seg ON seg.segment_id = c.segment_id
             LEFT JOIN crm_sales_person_master sp ON sp.sales_person_id = c.sales_person_id
             ORDER BY p.person_code ASC, p.contact_name ASC, p.person_id DESC'
        );
    }
}

if (!function_exists('crmFetchOpportunityContactByPersonId')) {
    function crmFetchOpportunityContactByPersonId(Database $db, $personId)
    {
        crmEnsureSchema($db);
        $rows = crmFetchOpportunityContacts($db);
        foreach ($rows as $row) {
            if ((int) ($row['person_id'] ?? 0) === (int) $personId) {
                return $row;
            }
        }
        return null;
    }
}

if (!function_exists('crmFetchOpportunities')) {
    function crmFetchOpportunities(Database $db)
    {
        crmEnsureSchema($db);

        return $db->getRows(
            'SELECT
                o.*,
                sc.cycle_code,
                st.stage_no AS current_stage_no,
                st.stage_description AS current_stage_description,
                seg.segment_name,
                sp.sales_person_name,
                p.person_code,
                p.contact_name AS person_master_name,
                c.company_name AS company_master_name
             FROM crm_opportunity o
             LEFT JOIN crm_sales_cycle_master sc ON sc.sales_cycle_id = o.sales_cycle_id
             LEFT JOIN crm_sales_cycle_stage st ON st.sales_cycle_stage_id = o.current_sales_cycle_stage_id
             LEFT JOIN crm_segment_master seg ON seg.segment_id = o.segment_id
             LEFT JOIN crm_sales_person_master sp ON sp.sales_person_id = o.sales_person_id
             LEFT JOIN crm_person_master p ON p.person_id = o.person_id
             LEFT JOIN crm_company_master c ON c.company_id = o.company_id
             ORDER BY o.creation_date DESC, o.opportunity_id DESC'
        );
    }
}

if (!function_exists('crmFetchOpportunity')) {
    function crmFetchOpportunity(Database $db, $opportunityId)
    {
        crmEnsureSchema($db);
        return $db->getRow('SELECT * FROM crm_opportunity WHERE opportunity_id = ? LIMIT 1', [(int) $opportunityId]);
    }
}

if (!function_exists('crmFetchOpportunityUpdateHistory')) {
    function crmFetchOpportunityUpdateHistory(Database $db, $opportunityId)
    {
        crmEnsureSchema($db);

        return $db->getRows(
            'SELECT
                ou.*,
                st.stage_no,
                st.stage_description,
                sc.cycle_code
             FROM crm_opportunity_update ou
             INNER JOIN crm_sales_cycle_stage st ON st.sales_cycle_stage_id = ou.sales_cycle_stage_id
             INNER JOIN crm_sales_cycle_master sc ON sc.sales_cycle_id = st.sales_cycle_id
             WHERE ou.opportunity_id = ?
             ORDER BY ou.date_of_change DESC, ou.opportunity_update_id DESC',
            [(int) $opportunityId]
        );
    }
}

if (!function_exists('crmFetchFirstSalesCycleStage')) {
    function crmFetchFirstSalesCycleStage(Database $db, $salesCycleId)
    {
        crmEnsureSchema($db);
        return $db->getRow(
            'SELECT * FROM crm_sales_cycle_stage WHERE sales_cycle_id = ? ORDER BY stage_no ASC, sales_cycle_stage_id ASC LIMIT 1',
            [(int) $salesCycleId]
        );
    }
}

if (!function_exists('crmResolveOpportunityCurrentStage')) {
    function crmResolveOpportunityCurrentStage(Database $db, array $opportunity)
    {
        crmEnsureSchema($db);

        $currentStageId = (int) ($opportunity['current_sales_cycle_stage_id'] ?? 0);
        if ($currentStageId > 0) {
            $stage = crmFetchSalesCycleStage($db, $currentStageId);
            if ($stage) {
                return $stage;
            }
        }

        $salesCycleId = (int) ($opportunity['sales_cycle_id'] ?? 0);
        if ($salesCycleId <= 0) {
            return null;
        }

        return crmFetchFirstSalesCycleStage($db, $salesCycleId);
    }
}

if (!function_exists('crmResolveOpportunityTargetStage')) {
    function crmResolveOpportunityTargetStage(Database $db, $salesCycleId, $currentStageId, $actionType)
    {
        crmEnsureSchema($db);

        $stages = crmFetchSalesCycleStages($db, $salesCycleId);
        if (empty($stages)) {
            return null;
        }

        $actionType = in_array($actionType, crmOpportunityUpdateActions(), true) ? $actionType : 'Current';
        $currentIndex = 0;

        foreach ($stages as $index => $stage) {
            if ((int) ($stage['sales_cycle_stage_id'] ?? 0) === (int) $currentStageId) {
                $currentIndex = $index;
                break;
            }
        }

        if ($actionType === 'Next') {
            $targetIndex = min($currentIndex + 1, count($stages) - 1);
        } elseif ($actionType === 'Previous') {
            $targetIndex = max($currentIndex - 1, 0);
        } else {
            $targetIndex = $currentIndex;
        }

        return $stages[$targetIndex] ?? $stages[0];
    }
}

if (!function_exists('crmMasterRecordExists')) {
    function crmMasterRecordExists(Database $db, $tableName, $nameColumn, $nameValue, $idColumn, $excludeId = 0, $parentColumn = '', $parentId = 0)
    {
        crmEnsureSchema($db);

        $sql = "SELECT {$idColumn} FROM {$tableName} WHERE {$nameColumn} = ?";
        $params = [trim((string) $nameValue)];

        if ($parentColumn !== '' && (int) $parentId > 0) {
            $sql .= " AND {$parentColumn} = ?";
            $params[] = (int) $parentId;
        }
        if ((int) $excludeId > 0) {
            $sql .= " AND {$idColumn} <> ?";
            $params[] = (int) $excludeId;
        }

        $sql .= ' LIMIT 1';
        return (bool) $db->getRow($sql, $params);
    }
}

if (!function_exists('crmMasterUsageCount')) {
    function crmMasterUsageCount(Database $db, $tableName, $columnName, $idValue)
    {
        crmEnsureSchema($db);
        $row = $db->getRow("SELECT COUNT(*) AS total FROM {$tableName} WHERE {$columnName} = ?", [(int) $idValue]);
        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('crmActivityPriorities')) {
    function crmActivityPriorities()
    {
        return ['Low', 'Normal', 'High'];
    }
}

if (!function_exists('crmFetchActivities')) {
    function crmFetchActivities(Database $db)
    {
        crmEnsureSchema($db);
        return $db->getRows(
            'SELECT a.*, COUNT(al.activity_line_id) AS line_count
             FROM crm_activity_master a
             LEFT JOIN crm_activity_line al ON al.activity_id = a.activity_id
             GROUP BY a.activity_id
             ORDER BY a.activity_code ASC, a.activity_id DESC'
        );
    }
}

if (!function_exists('crmFetchActivity')) {
    function crmFetchActivity(Database $db, $activityId)
    {
        crmEnsureSchema($db);
        return $db->getRow('SELECT * FROM crm_activity_master WHERE activity_id = ? LIMIT 1', [(int) $activityId]);
    }
}

if (!function_exists('crmFetchActivityByCode')) {
    function crmFetchActivityByCode(Database $db, $activityCode)
    {
        crmEnsureSchema($db);
        return $db->getRow('SELECT * FROM crm_activity_master WHERE activity_code = ? LIMIT 1', [trim((string) $activityCode)]);
    }
}

if (!function_exists('crmFetchActivityLines')) {
    function crmFetchActivityLines(Database $db, $activityId)
    {
        crmEnsureSchema($db);
        return $db->getRows(
            'SELECT * FROM crm_activity_line WHERE activity_id = ? ORDER BY activity_line_id ASC',
            [(int) $activityId]
        );
    }
}

if (!function_exists('crmFetchActivityLine')) {
    function crmFetchActivityLine(Database $db, $activityLineId)
    {
        crmEnsureSchema($db);
        return $db->getRow(
            'SELECT * FROM crm_activity_line WHERE activity_line_id = ? LIMIT 1',
            [(int) $activityLineId]
        );
    }
}

if (!function_exists('crmFetchOpportunityActivityTaskRecords')) {
    function crmFetchOpportunityActivityTaskRecords(Database $db, $opportunityId, array $activityLineIds = [])
    {
        crmEnsureSchema($db);

        $opportunityId = (int) $opportunityId;
        if ($opportunityId <= 0) {
            return [];
        }

        $cleanLineIds = [];
        foreach ($activityLineIds as $activityLineId) {
            $activityLineId = (int) $activityLineId;
            if ($activityLineId > 0) {
                $cleanLineIds[] = $activityLineId;
            }
        }

        $params = [$opportunityId];
        $sql = 'SELECT * FROM crm_opportunity_activity_task WHERE opportunity_id = ?';

        if (!empty($cleanLineIds)) {
            $placeholders = implode(',', array_fill(0, count($cleanLineIds), '?'));
            $sql .= ' AND activity_line_id IN (' . $placeholders . ')';
            foreach ($cleanLineIds as $cleanLineId) {
                $params[] = $cleanLineId;
            }
        }

        $sql .= ' ORDER BY finish_date DESC, opportunity_activity_task_id DESC';

        return $db->getRows($sql, $params);
    }
}

if (!function_exists('crmFetchOpportunityActivityTaskRecord')) {
    function crmFetchOpportunityActivityTaskRecord(Database $db, $opportunityId, $activityLineId)
    {
        crmEnsureSchema($db);
        return $db->getRow(
            'SELECT * FROM crm_opportunity_activity_task WHERE opportunity_id = ? AND activity_line_id = ? LIMIT 1',
            [(int) $opportunityId, (int) $activityLineId]
        );
    }
}

if (!function_exists('crmCountSampleOpportunities')) {
    function crmCountSampleOpportunities(Database $db)
    {
        crmEnsureSchema($db);
        $row = $db->getRow(
            'SELECT COUNT(*) AS total FROM crm_opportunity WHERE opportunity_code LIKE ?',
            ['OPP-SMP-%']
        );

        return (int) ($row['total'] ?? 0);
    }
}

if (!function_exists('crmSeedSampleData')) {
    function crmSeedSampleData(Database $db)
    {
        crmEnsureSchema($db);

        $summary = [
            'designations' => 0,
            'segments' => 0,
            'categories' => 0,
            'sales_people' => 0,
            'sales_cycles' => 0,
            'stages' => 0,
            'activities' => 0,
            'activity_lines' => 0,
            'companies' => 0,
            'persons' => 0,
            'opportunities' => 0,
            'updates' => 0,
        ];

        $findId = function ($tableName, $idColumn, $whereSql, array $params) use ($db) {
            $row = $db->getRow(
                'SELECT ' . $idColumn . ' AS record_id FROM ' . $tableName . ' WHERE ' . $whereSql . ' LIMIT 1',
                $params
            );

            return (int) ($row['record_id'] ?? 0);
        };

        $ensureRow = function ($tableName, $idColumn, $whereSql, array $whereParams, $insertSql, array $insertParams, $summaryKey) use ($db, $findId, &$summary) {
            $recordId = $findId($tableName, $idColumn, $whereSql, $whereParams);
            if ($recordId > 0) {
                return $recordId;
            }

            $db->insertRow($insertSql, $insertParams);
            $summary[$summaryKey]++;

            return $findId($tableName, $idColumn, $whereSql, $whereParams);
        };

        $designations = [
            'Purchasing Manager',
            'Branch Operations Lead',
            'Procurement Executive',
            'Finance Manager',
        ];

        $designationIds = [];
        foreach ($designations as $designationName) {
            $designationIds[$designationName] = $ensureRow(
                'crm_designation_master',
                'designation_id',
                'designation_name = ?',
                [$designationName],
                'INSERT INTO crm_designation_master (designation_name, description) VALUES (?, ?)',
                [$designationName, 'CRM sample data'],
                'designations'
            );
        }

        $segments = [
            [
                'name' => 'Retail Chains',
                'description' => 'Supermarkets, mini marts, and chain grocers.'
            ],
            [
                'name' => 'Hospitality',
                'description' => 'Hotels, cafes, and resort food service operations.'
            ],
            [
                'name' => 'Corporate',
                'description' => 'Office pantry, employee meal, and event supply opportunities.'
            ],
        ];

        $segmentIds = [];
        foreach ($segments as $segment) {
            $segmentIds[$segment['name']] = $ensureRow(
                'crm_segment_master',
                'segment_id',
                'segment_name = ?',
                [$segment['name']],
                'INSERT INTO crm_segment_master (segment_name, description) VALUES (?, ?)',
                [$segment['name'], $segment['description']],
                'segments'
            );
        }

        $categories = [
            ['segment' => 'Retail Chains', 'name' => 'Supermarket', 'description' => 'Large supermarket buying teams.'],
            ['segment' => 'Retail Chains', 'name' => 'Mini Mart', 'description' => 'Neighbourhood and convenience retail outlets.'],
            ['segment' => 'Hospitality', 'name' => 'Hotels', 'description' => 'Breakfast buffet and room service supply.'],
            ['segment' => 'Hospitality', 'name' => 'Cafe Groups', 'description' => 'Multi-branch cafe and coffee chains.'],
            ['segment' => 'Corporate', 'name' => 'Office Pantry', 'description' => 'Recurring pantry and meeting catering demand.'],
        ];

        $categoryIds = [];
        foreach ($categories as $category) {
            $segmentId = (int) ($segmentIds[$category['segment']] ?? 0);
            $categoryIds[$category['name']] = $ensureRow(
                'crm_category_master',
                'category_id',
                'segment_id = ? AND category_name = ?',
                [$segmentId, $category['name']],
                'INSERT INTO crm_category_master (segment_id, category_name, description) VALUES (?, ?, ?)',
                [$segmentId, $category['name'], $category['description']],
                'categories'
            );
        }

        $salesPeople = [
            ['name' => 'Ayesha Perera', 'contact_no' => '0773000101', 'email' => 'ayesha.perera@example.com'],
            ['name' => 'Dilan Fernando', 'contact_no' => '0773000102', 'email' => 'dilan.fernando@example.com'],
            ['name' => 'Nadeesha Silva', 'contact_no' => '0773000103', 'email' => 'nadeesha.silva@example.com'],
        ];

        $salesPersonIds = [];
        foreach ($salesPeople as $salesPerson) {
            $salesPersonIds[$salesPerson['name']] = $ensureRow(
                'crm_sales_person_master',
                'sales_person_id',
                'sales_person_name = ?',
                [$salesPerson['name']],
                'INSERT INTO crm_sales_person_master (sales_person_name, contact_no, email) VALUES (?, ?, ?)',
                [$salesPerson['name'], $salesPerson['contact_no'], $salesPerson['email']],
                'sales_people'
            );
        }

        $salesCycleId = $ensureRow(
            'crm_sales_cycle_master',
            'sales_cycle_id',
            'cycle_code = ?',
            ['CRM-SMP'],
            'INSERT INTO crm_sales_cycle_master (cycle_code, cycle_description, probability_calculation) VALUES (?, ?, ?)',
            ['CRM-SMP', 'Bakery Distribution Sample Funnel', 'Chances of Success %'],
            'sales_cycles'
        );

        $activities = [
            'CRM-SMP-LEAD' => [
                'description' => 'Sample lead qualification activity',
                'lines' => [
                    ['line_type' => 'Call', 'description' => 'Call the prospect and confirm interest level', 'activity_percentage' => 30, 'priority' => 'High'],
                    ['line_type' => 'Research', 'description' => 'Capture product mix and branch count requirements', 'activity_percentage' => 35, 'priority' => 'Normal'],
                    ['line_type' => 'Meeting', 'description' => 'Schedule a discovery meeting with the buyer', 'activity_percentage' => 35, 'priority' => 'High'],
                ],
            ],
            'CRM-SMP-QUAL' => [
                'description' => 'Sample qualification activity',
                'lines' => [
                    ['line_type' => 'Survey', 'description' => 'Confirm weekly volume and delivery windows', 'activity_percentage' => 50, 'priority' => 'High'],
                    ['line_type' => 'Review', 'description' => 'Map locations and operational constraints', 'activity_percentage' => 50, 'priority' => 'Normal'],
                ],
            ],
            'CRM-SMP-PROP' => [
                'description' => 'Sample proposal activity',
                'lines' => [
                    ['line_type' => 'Pricing', 'description' => 'Prepare pricing and service proposal', 'activity_percentage' => 60, 'priority' => 'High'],
                    ['line_type' => 'Sampling', 'description' => 'Arrange tasting samples for key SKUs', 'activity_percentage' => 40, 'priority' => 'Normal'],
                ],
            ],
            'CRM-SMP-NEG' => [
                'description' => 'Sample negotiation activity',
                'lines' => [
                    ['line_type' => 'Meeting', 'description' => 'Review payment, delivery, and exclusivity terms', 'activity_percentage' => 60, 'priority' => 'High'],
                    ['line_type' => 'Trial', 'description' => 'Confirm the trial order and onboarding checklist', 'activity_percentage' => 40, 'priority' => 'High'],
                ],
            ],
            'CRM-SMP-CLOSE' => [
                'description' => 'Sample close and onboarding activity',
                'lines' => [
                    ['line_type' => 'Launch', 'description' => 'Finalize onboarding and first delivery schedule', 'activity_percentage' => 50, 'priority' => 'High'],
                    ['line_type' => 'Follow-up', 'description' => 'Capture the first order feedback and expansion plan', 'activity_percentage' => 50, 'priority' => 'Normal'],
                ],
            ],
        ];

        $activityIds = [];
        $activityLineIds = [];
        foreach ($activities as $activityCode => $activity) {
            $activityId = $ensureRow(
                'crm_activity_master',
                'activity_id',
                'activity_code = ?',
                [$activityCode],
                'INSERT INTO crm_activity_master (activity_code, description) VALUES (?, ?)',
                [$activityCode, $activity['description']],
                'activities'
            );
            $activityIds[$activityCode] = $activityId;

            foreach ($activity['lines'] as $lineIndex => $line) {
                $lineId = $ensureRow(
                    'crm_activity_line',
                    'activity_line_id',
                    'activity_id = ? AND description = ?',
                    [$activityId, $line['description']],
                    'INSERT INTO crm_activity_line (activity_id, line_type, description, activity_percentage, priority, date_formula) VALUES (?, ?, ?, ?, ?, ?)',
                    [$activityId, $line['line_type'], $line['description'], $line['activity_percentage'], $line['priority'], ''],
                    'activity_lines'
                );

                if ($lineIndex === 0) {
                    $activityLineIds[$activityCode] = $lineId;
                }
            }
        }

        $stageDefinitions = [
            ['stage_no' => 1, 'stage_description' => 'Lead In', 'completed_percent' => 10, 'chance' => 15, 'activity_code' => 'CRM-SMP-LEAD'],
            ['stage_no' => 2, 'stage_description' => 'Qualified', 'completed_percent' => 30, 'chance' => 35, 'activity_code' => 'CRM-SMP-QUAL'],
            ['stage_no' => 3, 'stage_description' => 'Proposal Sent', 'completed_percent' => 55, 'chance' => 55, 'activity_code' => 'CRM-SMP-PROP'],
            ['stage_no' => 4, 'stage_description' => 'Negotiation', 'completed_percent' => 75, 'chance' => 75, 'activity_code' => 'CRM-SMP-NEG'],
            ['stage_no' => 5, 'stage_description' => 'Closed Won', 'completed_percent' => 100, 'chance' => 100, 'activity_code' => 'CRM-SMP-CLOSE'],
        ];

        $stageMap = [];
        foreach ($stageDefinitions as $stageDefinition) {
            $stageId = $ensureRow(
                'crm_sales_cycle_stage',
                'sales_cycle_stage_id',
                'sales_cycle_id = ? AND stage_no = ?',
                [$salesCycleId, $stageDefinition['stage_no']],
                'INSERT INTO crm_sales_cycle_stage (sales_cycle_id, stage_no, stage_description, completed_percent, chance_of_success_percent, activity_code) VALUES (?, ?, ?, ?, ?, ?)',
                [$salesCycleId, $stageDefinition['stage_no'], $stageDefinition['stage_description'], $stageDefinition['completed_percent'], $stageDefinition['chance'], $stageDefinition['activity_code']],
                'stages'
            );

            $stageMap[$stageDefinition['stage_description']] = [
                'id' => $stageId,
                'activity_code' => $stageDefinition['activity_code'],
                'chance' => $stageDefinition['chance'],
                'stage_no' => $stageDefinition['stage_no'],
            ];
        }

        $companies = [
            ['code' => 'CT-SMP-C01', 'name' => 'FreshMart Colombo', 'type' => 'Retail', 'segment' => 'Retail Chains', 'category' => 'Supermarket', 'sales_person' => 'Ayesha Perera', 'contact_details' => '0112500001 / procurement@freshmart.example', 'address' => '14 Union Place, Colombo 02'],
            ['code' => 'CT-SMP-C02', 'name' => 'CityGrocer Central', 'type' => 'Retail', 'segment' => 'Retail Chains', 'category' => 'Supermarket', 'sales_person' => 'Ayesha Perera', 'contact_details' => '0112500002 / central@citygrocer.example', 'address' => '82 Galle Road, Colombo 03'],
            ['code' => 'CT-SMP-C03', 'name' => 'Sunrise Mini Mart', 'type' => 'Retail', 'segment' => 'Retail Chains', 'category' => 'Mini Mart', 'sales_person' => 'Dilan Fernando', 'contact_details' => '0312201101 / owner@sunrisemini.example', 'address' => '45 Negombo Road, Wattala'],
            ['code' => 'CT-SMP-C04', 'name' => 'LakeView Hotel', 'type' => 'Wholesale', 'segment' => 'Hospitality', 'category' => 'Hotels', 'sales_person' => 'Dilan Fernando', 'contact_details' => '0112650004 / culinary@lakeview.example', 'address' => '29 Independence Avenue, Colombo 07'],
            ['code' => 'CT-SMP-C05', 'name' => 'Urban Bean Cafes', 'type' => 'Retail', 'segment' => 'Hospitality', 'category' => 'Cafe Groups', 'sales_person' => 'Nadeesha Silva', 'contact_details' => '0112650005 / supply@urbanbean.example', 'address' => '118 Gregory Road, Colombo 07'],
            ['code' => 'CT-SMP-C06', 'name' => 'Coastal Resort Group', 'type' => 'Wholesale', 'segment' => 'Hospitality', 'category' => 'Hotels', 'sales_person' => 'Nadeesha Silva', 'contact_details' => '0912750006 / ops@coastalresort.example', 'address' => '7 Marine Drive, Galle'],
            ['code' => 'CT-SMP-C07', 'name' => 'NorthGate Offices', 'type' => 'Other', 'segment' => 'Corporate', 'category' => 'Office Pantry', 'sales_person' => 'Ayesha Perera', 'contact_details' => '0112800007 / admin@northgate.example', 'address' => '55 Park Street, Colombo 02'],
            ['code' => 'CT-SMP-C08', 'name' => 'Metro Pantry Services', 'type' => 'Logistics', 'segment' => 'Corporate', 'category' => 'Office Pantry', 'sales_person' => 'Dilan Fernando', 'contact_details' => '0112800008 / pantry@metro.example', 'address' => '10 Kynsey Road, Colombo 08'],
        ];

        $companyRows = [];
        foreach ($companies as $company) {
            $segmentId = (int) ($segmentIds[$company['segment']] ?? 0);
            $categoryId = (int) ($categoryIds[$company['category']] ?? 0);
            $salesPersonId = (int) ($salesPersonIds[$company['sales_person']] ?? 0);

            $companyId = $ensureRow(
                'crm_company_master',
                'company_id',
                'company_code = ?',
                [$company['code']],
                'INSERT INTO crm_company_master (company_code, company_name, company_type, segment_id, category_id, sales_person_id, contact_details, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$company['code'], $company['name'], $company['type'], $segmentId, $categoryId, $salesPersonId, $company['contact_details'], $company['address']],
                'companies'
            );

            $companyRows[$company['code']] = [
                'company_id' => $companyId,
                'company_name' => $company['name'],
                'segment_id' => $segmentId,
                'sales_person_id' => $salesPersonId,
                'contact_details' => $company['contact_details'],
            ];
        }

        $people = [
            ['code' => 'CT-SMP-P01', 'title' => 'Miss', 'name' => 'Nimali Perera', 'contact_no' => '0719000001', 'email' => 'nimali.perera@example.com', 'address' => 'Colombo 02', 'designation' => 'Purchasing Manager', 'company_code' => 'CT-SMP-C01'],
            ['code' => 'CT-SMP-P02', 'title' => 'Mr', 'name' => 'Ashan Fernando', 'contact_no' => '0719000002', 'email' => 'ashan.fernando@example.com', 'address' => 'Colombo 03', 'designation' => 'Procurement Executive', 'company_code' => 'CT-SMP-C02'],
            ['code' => 'CT-SMP-P03', 'title' => 'Mr', 'name' => 'Chamara Dissanayake', 'contact_no' => '0719000003', 'email' => 'chamara.dissanayake@example.com', 'address' => 'Wattala', 'designation' => 'Branch Operations Lead', 'company_code' => 'CT-SMP-C03'],
            ['code' => 'CT-SMP-P04', 'title' => 'Miss', 'name' => 'Ruwini Silva', 'contact_no' => '0719000004', 'email' => 'ruwini.silva@example.com', 'address' => 'Colombo 07', 'designation' => 'Purchasing Manager', 'company_code' => 'CT-SMP-C04'],
            ['code' => 'CT-SMP-P05', 'title' => 'Miss', 'name' => 'Tharushi Jayasinghe', 'contact_no' => '0719000005', 'email' => 'tharushi.jayasinghe@example.com', 'address' => 'Colombo 07', 'designation' => 'Procurement Executive', 'company_code' => 'CT-SMP-C05'],
            ['code' => 'CT-SMP-P06', 'title' => 'Mr', 'name' => 'Prabath Fernando', 'contact_no' => '0719000006', 'email' => 'prabath.fernando@example.com', 'address' => 'Galle', 'designation' => 'Finance Manager', 'company_code' => 'CT-SMP-C06'],
            ['code' => 'CT-SMP-P07', 'title' => 'Miss', 'name' => 'Ishadi Wijeratne', 'contact_no' => '0719000007', 'email' => 'ishadi.wijeratne@example.com', 'address' => 'Colombo 02', 'designation' => 'Branch Operations Lead', 'company_code' => 'CT-SMP-C07'],
            ['code' => 'CT-SMP-P08', 'title' => 'Mr', 'name' => 'Kavindu Senanayake', 'contact_no' => '0719000008', 'email' => 'kavindu.senanayake@example.com', 'address' => 'Colombo 08', 'designation' => 'Purchasing Manager', 'company_code' => 'CT-SMP-C08'],
        ];

        $personRows = [];
        foreach ($people as $person) {
            $designationId = (int) ($designationIds[$person['designation']] ?? 0);
            $companyId = (int) (($companyRows[$person['company_code']] ?? [])['company_id'] ?? 0);

            $personId = $ensureRow(
                'crm_person_master',
                'person_id',
                'person_code = ?',
                [$person['code']],
                'INSERT INTO crm_person_master (person_code, title, contact_name, contact_no, email, address, designation, designation_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$person['code'], $person['title'], $person['name'], $person['contact_no'], $person['email'], $person['address'], $person['designation'], $designationId],
                'persons'
            );

            if ($personId > 0 && $companyId > 0 && crmFetchPersonCompanyId($db, $personId) !== $companyId) {
                crmAssignPersonCompany($db, $personId, $companyId);
            }

            $personRows[$person['code']] = [
                'person_id' => $personId,
                'contact_name' => $person['name'],
                'contact_no' => $person['contact_no'],
                'email' => $person['email'],
                'company_code' => $person['company_code'],
            ];
        }

        $opportunityThemes = [
            'weekly bread supply program',
            'breakfast pastry contract',
            'staff pantry snack bundle',
            'seasonal dessert counter rollout',
            'hotel bakery replenishment deal',
            'multi-branch sandwich bread listing',
        ];
        $opportunityCounts = [
            'Lead In' => 10,
            'Qualified' => 8,
            'Proposal Sent' => 6,
            'Negotiation' => 4,
            'Closed Won' => 2,
        ];
        $samplePersonCodes = array_keys($personRows);
        $currentYear = (int) date('Y');
        $opportunityNumber = 1;

        foreach ($opportunityCounts as $stageDescription => $count) {
            $stageInfo = $stageMap[$stageDescription] ?? null;
            if (!$stageInfo) {
                continue;
            }

            for ($index = 0; $index < $count; $index++, $opportunityNumber++) {
                $personCode = $samplePersonCodes[($opportunityNumber - 1) % count($samplePersonCodes)];
                $personRow = $personRows[$personCode];
                $companyRow = $companyRows[$personRow['company_code']];
                $activityLineId = (int) ($activityLineIds[$stageInfo['activity_code']] ?? 0);
                $month = (($opportunityNumber - 1) % 12) + 1;
                $day = (($opportunityNumber * 2) % 27) + 1;
                $creationDate = sprintf('%04d-%02d-%02d', $currentYear, $month, $day);
                $closingDate = null;
                if ((int) $stageInfo['chance'] >= 100) {
                    $closingDate = date('Y-m-d', strtotime($creationDate . ' +14 days'));
                }

                $estimatedValue = 45000 + (($opportunityNumber % 7) * 12500) + ((int) $stageInfo['stage_no'] * 6000);
                $estimatedGp = round($estimatedValue * 0.24, 2);
                $description = $companyRow['company_name'] . ' - ' . $opportunityThemes[($opportunityNumber - 1) % count($opportunityThemes)];
                $opportunityCode = 'OPP-SMP-' . str_pad((string) $opportunityNumber, 3, '0', STR_PAD_LEFT);

                $opportunityId = $findId('crm_opportunity', 'opportunity_id', 'opportunity_code = ?', [$opportunityCode]);
                if ($opportunityId <= 0) {
                    $db->insertRow(
                        'INSERT INTO crm_opportunity (opportunity_code, description, person_id, company_id, sales_cycle_id, current_sales_cycle_stage_id, current_activity_line_id, segment_id, sales_person_id, contact_no, contact_name, phone_no, mobile_phone_no, email, contact_company_name, sales_document_type, sales_document_no, status, is_closed, creation_date, date_closed, estimated_sales_value, chance_of_success_percent, estimated_closing_date_for_stage, estimated_gp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        [
                            $opportunityCode,
                            $description,
                            (int) $personRow['person_id'],
                            (int) $companyRow['company_id'],
                            $salesCycleId,
                            (int) $stageInfo['id'],
                            $activityLineId > 0 ? $activityLineId : null,
                            (int) $companyRow['segment_id'],
                            (int) $companyRow['sales_person_id'],
                            $personRow['contact_no'],
                            $personRow['contact_name'],
                            $companyRow['contact_details'],
                            $personRow['contact_no'],
                            $personRow['email'],
                            $companyRow['company_name'],
                            'Quote',
                            'Q-SMP-' . str_pad((string) $opportunityNumber, 3, '0', STR_PAD_LEFT),
                            ((int) $stageInfo['chance'] >= 100 ? 'Won' : 'In Progress'),
                            ((int) $stageInfo['chance'] >= 100 ? 1 : 0),
                            $creationDate,
                            $closingDate,
                            $estimatedValue,
                            (float) $stageInfo['chance'],
                            date('Y-m-d', strtotime($creationDate . ' +' . max(7, 45 - ((int) $stageInfo['stage_no'] * 6)) . ' days')),
                            $estimatedGp,
                        ]
                    );
                    $summary['opportunities']++;
                    $opportunityId = $findId('crm_opportunity', 'opportunity_id', 'opportunity_code = ?', [$opportunityCode]);
                }

                if ($opportunityId > 0) {
                    $existingUpdate = $db->getRow(
                        'SELECT opportunity_update_id FROM crm_opportunity_update WHERE opportunity_id = ? AND action_type = ? AND sales_cycle_stage_id = ? LIMIT 1',
                        [$opportunityId, 'Current', (int) $stageInfo['id']]
                    );

                    if (!$existingUpdate) {
                        $db->insertRow(
                            'INSERT INTO crm_opportunity_update (opportunity_id, action_type, sales_cycle_stage_id, date_of_change, estimated_sales_value, chance_of_success_percent, estimated_closing_date_for_stage, opportunity_closing_date, cancel_existing_open_tasks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                            [
                                $opportunityId,
                                'Current',
                                (int) $stageInfo['id'],
                                $creationDate,
                                $estimatedValue,
                                (float) $stageInfo['chance'],
                                date('Y-m-d', strtotime($creationDate . ' +' . max(7, 45 - ((int) $stageInfo['stage_no'] * 6)) . ' days')),
                                $closingDate,
                                0,
                            ]
                        );
                        $summary['updates']++;
                    }
                }
            }
        }

        return $summary;
    }
}
