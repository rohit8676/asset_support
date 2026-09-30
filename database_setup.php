<?php
/**
 * Single-File Database Setup and Schema Management
 * IT Asset & Support Management System
 * Microsoft SQL Server (PDO_SQLSRV)
 */

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/includes/bootstrap.php';

class DatabaseSetup {
    private array $dbConfig;
    private ?PDO $masterPdo = null;
    private ?PDO $appPdo = null;
    private array $report = [
        'connectivity'    => false,
        'server_info'     => '',
        'database_name'   => '',
        'database_status' => '',
        'tables_created'  => [],
        'seeds_applied'   => [],
        'tables_count'    => 0,
        'tables'          => [],
        'errors'          => [],
        'success'         => false,
    ];

    public function __construct() {
        global $dbConfig;
        $this->dbConfig = $dbConfig;
        $this->report['database_name'] = $this->dbConfig['database'];
    }

    public function run(): array {
        try {
            // 1. Verify extension
            if (!extension_loaded('pdo_sqlsrv')) {
                throw new Exception("The 'pdo_sqlsrv' PHP extension is not loaded in php.ini.");
            }

            // 2. Connect to Master
            $host = $this->dbConfig['host'];
            $trustCert = $this->dbConfig['trust_cert'] ? 'true' : 'false';
            $authMode = strtolower($this->dbConfig['auth_mode'] ?? 'windows');
            $dsn = "sqlsrv:Server={$host};Database=master;TrustServerCertificate={$trustCert}";

            if ($authMode === 'sql') {
                $this->masterPdo = new PDO($dsn, $this->dbConfig['username'], $this->dbConfig['password'], $this->dbConfig['options']);
            } else {
                $this->masterPdo = new PDO($dsn, null, null, $this->dbConfig['options']);
            }

            $stmt = $this->masterPdo->query("SELECT @@VERSION AS ver, SUSER_SNAME() AS login_user");
            $res = $stmt->fetch();
            $this->report['connectivity'] = true;
            $this->report['server_info'] = "Connected as: " . ($res['login_user'] ?? 'Unknown');

            // 3. Ensure database exists
            $dbName = $this->dbConfig['database'];
            $chk = $this->masterPdo->prepare("SELECT name FROM sys.databases WHERE name = ?");
            $chk->execute([$dbName]);
            if (!$chk->fetch()) {
                $escaped = str_replace("]", "]]", $dbName);
                $this->masterPdo->exec("CREATE DATABASE [{$escaped}]");
                $this->report['database_status'] = "Newly created";
            } else {
                $this->report['database_status'] = "Existing database";
            }

            // 4. Connect to App Database
            $appDsn = "sqlsrv:Server={$host};Database={$dbName};TrustServerCertificate={$trustCert}";
            if ($authMode === 'sql') {
                $this->appPdo = new PDO($appDsn, $this->dbConfig['username'], $this->dbConfig['password'], $this->dbConfig['options']);
            } else {
                $this->appPdo = new PDO($appDsn, null, null, $this->dbConfig['options']);
            }

            // 5. Create Users Table
            $createUsersTable = "
                IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'users')
                BEGIN
                    CREATE TABLE users (
                        id INT IDENTITY(1,1) PRIMARY KEY,
                        username VARCHAR(50) NOT NULL UNIQUE,
                        email VARCHAR(100) NOT NULL UNIQUE,
                        password VARCHAR(255) NOT NULL,
                        full_name NVARCHAR(100) NOT NULL,
                        role VARCHAR(50) NOT NULL DEFAULT 'Admin',
                        status VARCHAR(20) NOT NULL DEFAULT 'active',
                        created_at DATETIME2 NOT NULL DEFAULT GETDATE()
                    );
                END
            ";
            $this->appPdo->exec($createUsersTable);
            $this->report['tables_created'][] = 'users';

            // 6. Seed Default Administrator Account (Plain-Text Password)
            $adminEmail = 'admin@company.local';
            $chkUser = $this->appPdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR username = 'admin'");
            $chkUser->execute([$adminEmail]);
            if ((int)$chkUser->fetchColumn() === 0) {
                $insUser = $this->appPdo->prepare("
                    INSERT INTO users (username, email, password, full_name, role, status, created_at)
                    VALUES ('admin', ?, 'Admin@123', 'System Administrator', 'Super Admin', 'active', GETDATE())
                ");
                $insUser->execute([$adminEmail]);
                $this->report['seeds_applied'][] = "Created default admin user (admin / Admin@123)";
            }

            // 7. Create Categories Table (Strictly 2 Columns: category_id [Prefix+Numeric] and category_name)
            $createCategoriesTable = "
                IF EXISTS (SELECT * FROM sys.tables WHERE name = 'categories')
                BEGIN
                    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('categories') AND name = 'category_id' AND (SELECT count(*) FROM sys.columns WHERE object_id = OBJECT_ID('categories')) = 2)
                    BEGIN
                        DROP TABLE categories;
                    END
                END

                IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'categories')
                BEGIN
                    CREATE TABLE categories (
                        category_id VARCHAR(50) PRIMARY KEY,
                        category_name NVARCHAR(100) NOT NULL UNIQUE
                    );
                END
            ";
            $this->appPdo->exec($createCategoriesTable);
            $this->report['tables_created'][] = 'categories';

            // 8. Seed Standard Categories with Prefix + Numeric Value Category IDs (2-digit: e.g. DTP-01)
            $defaultCategories = [
                ['DTP-01', 'Desktop Computer'],
                ['LTP-01', 'Laptop'],
                ['MON-01', 'Monitor / Display'],
                ['PRN-01', 'Printer / Scanner'],
                ['SRV-01', 'Server System'],
                ['RAM-01', 'RAM Memory'],
                ['SSD-01', 'Solid State Drive (SSD)'],
                ['HDD-01', 'Hard Disk Drive (HDD)'],
                ['GPU-01', 'Graphics Card (GPU)'],
                ['KBD-01', 'Keyboard'],
                ['MSE-01', 'Mouse'],
                ['ACC-01', 'Accessories & Cables'],
            ];

            // Normalize existing 3-digit category IDs (e.g. DTP-001 -> DTP-01) if any
            $existingCats = $this->appPdo->query("SELECT category_id, category_name FROM categories")->fetchAll();
            foreach ($existingCats as $ec) {
                if (preg_match('/^([A-Za-z0-9]+)-00(\d)$/', $ec['category_id'], $m)) {
                    $newCatId = $m[1] . '-0' . $m[2];
                    $upd = $this->appPdo->prepare("UPDATE categories SET category_id = ? WHERE category_id = ?");
                    $upd->execute([$newCatId, $ec['category_id']]);
                }
            }

            $catStmt = $this->appPdo->prepare("
                IF NOT EXISTS (SELECT 1 FROM categories WHERE category_name = ? OR category_id = ?)
                BEGIN
                    INSERT INTO categories (category_id, category_name) VALUES (?, ?);
                END
            ");

            foreach ($defaultCategories as $cat) {
                $catStmt->execute([$cat[1], $cat[0], $cat[0], $cat[1]]);
            }
            $this->report['seeds_applied'][] = "Verified standard asset categories with Prefix+Numeric Category IDs (" . count($defaultCategories) . ")";

            // 9. Create Assets Table with Required Columns:
            // [Asset Id, Asset, Description, Serial No, category-id, Source, Status, In Date]
            // Asset ID = Category ID + Serialization (e.g. DTP-01-001)
            $createAssetsTable = "
                IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'assets')
                BEGIN
                    CREATE TABLE assets (
                        asset_id VARCHAR(50) PRIMARY KEY,
                        asset_name NVARCHAR(255) NOT NULL,
                        description NVARCHAR(MAX) NULL,
                        serial_number VARCHAR(100) NULL,
                        category_id VARCHAR(50) NOT NULL,
                        source NVARCHAR(100) NULL,
                        status VARCHAR(50) NOT NULL DEFAULT 'In Stock',
                        in_date DATE NOT NULL DEFAULT CAST(GETDATE() AS DATE),
                        created_at DATETIME2 NOT NULL DEFAULT GETDATE(),
                        updated_at DATETIME2 NOT NULL DEFAULT GETDATE(),
                        CONSTRAINT FK_assets_categories FOREIGN KEY (category_id) 
                            REFERENCES categories(category_id) ON UPDATE CASCADE ON DELETE NO ACTION
                    );
                    CREATE INDEX IX_assets_category_id ON assets(category_id);
                    CREATE INDEX IX_assets_status ON assets(status);
                    CREATE INDEX IX_assets_serial_number ON assets(serial_number);
                END
            ";
            $this->appPdo->exec($createAssetsTable);
            $this->report['tables_created'][] = 'assets';

            // 10. Verify Tables
            $tStmt = $this->appPdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME");
            $this->report['tables'] = $tStmt->fetchAll(PDO::FETCH_COLUMN);
            $this->report['tables_count'] = count($this->report['tables']);
            $this->report['success'] = true;

        } catch (Throwable $e) {
            $this->report['errors'][] = $e->getMessage();
            $this->report['success'] = false;
        }

        return $this->report;
    }
}

// CLI or Web Execution
$isCli = (php_sapi_name() === 'cli' || defined('STDIN'));
$setup = new DatabaseSetup();
$report = $setup->run();

if ($isCli) {
    echo "==========================================================\n";
    echo "  IT Asset Management - Database Setup Tool\n";
    echo "==========================================================\n";
    echo "Status: " . ($report['success'] ? "SUCCESS [OK]" : "FAILED [ERROR]") . "\n";
    echo "Server Info: " . $report['server_info'] . "\n";
    echo "Target Database: " . $report['database_name'] . " (" . $report['database_status'] . ")\n";
    echo "Tables: " . implode(", ", $report['tables']) . "\n";
    if (!empty($report['seeds_applied'])) {
        echo "Seeds: " . implode(", ", $report['seeds_applied']) . "\n";
    }
    if (!empty($report['errors'])) {
        echo "Errors: " . implode("\n", $report['errors']) . "\n";
    }
    echo "==========================================================\n";
    exit($report['success'] ? 0 : 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup — IT Asset Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light py-5">
<div class="container" style="max-width: 700px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4 text-center">
            <i class="bi bi-database-gear text-primary display-4 mb-3"></i>
            <h4 class="fw-bold">Database Setup & Connection Tool</h4>
            <p class="text-muted small">Microsoft SQL Server (PDO_SQLSRV)</p>

            <div class="alert <?= $report['success'] ? 'alert-success' : 'alert-danger' ?> text-start mt-4">
                <h6><strong>Status:</strong> <?= $report['success'] ? 'Setup Completed Successfully' : 'Setup Error' ?></h6>
                <div class="small">
                    <div><strong>Server:</strong> <?= e($report['server_info']) ?></div>
                    <div><strong>Database:</strong> <?= e($report['database_name']) ?> (<?= e($report['database_status']) ?>)</div>
                    <div><strong>Tables Initialized:</strong> <?= implode(", ", array_map('htmlspecialchars', $report['tables'])) ?></div>
                </div>
            </div>

            <a href="<?= url('index.php?tab=asset_stock') ?>" class="btn btn-primary mt-2">Go to Asset & Stock Management</a>
        </div>
    </div>
</div>
</body>
</html>
