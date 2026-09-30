<?php
/**
 * Database Connection Service
 * Microsoft SQL Server (PDO_SQLSRV) Singleton
 */

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }
        return self::$instance;
    }

    public static function resetConnection(): void {
        self::$instance = null;
    }

    public static function createConnection(?string $overrideDatabase = null): PDO {
        global $dbConfig;
        if (!$dbConfig) {
            $dbConfig = require BASE_PATH . '/config/database.php';
        }

        $host = $dbConfig['host'];
        $database = $overrideDatabase ?? $dbConfig['database'];
        $trustCert = $dbConfig['trust_cert'] ? 'true' : 'false';
        $authMode = strtolower($dbConfig['auth_mode'] ?? 'windows');

        $dsn = "sqlsrv:Server={$host};Database={$database};TrustServerCertificate={$trustCert}";

        $options = $dbConfig['options'] ?? [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        if ($authMode === 'sql') {
            return new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
        } else {
            return new PDO($dsn, null, null, $options);
        }
    }

    public static function testConnection(?string $targetDb = null): array {
        try {
            $pdo = self::createConnection($targetDb);
            $stmt = $pdo->query("SELECT @@VERSION as version, DB_NAME() as current_db, SUSER_SNAME() as current_user_login");
            $info = $stmt->fetch();
            return [
                'success' => true,
                'message' => 'Successfully connected to Microsoft SQL Server.',
                'server_version' => $info['version'] ?? 'Unknown',
                'current_database' => $info['current_db'] ?? $targetDb,
                'user_login' => $info['current_user_login'] ?? 'Unknown',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
