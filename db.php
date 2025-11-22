<?php
// db.php
// Compatibility wrapper that ensures a PDO $pdo is available for code that expects it.
// It will try to include db_connect.php to reuse host/user/pass/dbname. If not present,
// fall back to sane defaults (localhost, root, empty pass).

error_reporting(E_ALL);
ini_set('display_errors', '1');

$dbConnectPath = __DIR__ . '/db_connect.php';
if (file_exists($dbConnectPath)) {
    // include to populate $host, $user, $pass, $dbname if db_connect.php defines them
    include_once $dbConnectPath;
}

// If variables aren't defined by db_connect.php, provide defaults
if (!isset($host)) $host = '127.0.0.1';
if (!isset($port)) $port = 3306;
if (!isset($user)) $user = 'root';
if (!isset($pass)) $pass = '';
if (!isset($dbname)) $dbname = 'ime_negotiation';

// Create PDO instance named $pdo if not already created
if (!isset($pdo) || !($pdo instanceof PDO)) {
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        // Provide a helpful error with context
        $msg = "PDO connection failed: " . $e->getMessage() . "\n";
        $msg .= "Attempted DSN: {$dsn}\n";
        $msg .= "Please verify MySQL server is running and credentials are correct.\n";
        die($msg);
    }
}

// Export $pdo for requiring files
// End of db.php

?>
