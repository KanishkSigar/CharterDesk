<?php
// Lightweight test harness to check DB connection using db_connect.php
// Run: php test_db_connect.php

// Make sure errors are visible
error_reporting(E_ALL);
ini_set('display_errors', '1');

// If db_connect.php uses mysqli and dies on failure, we wrap include in a try-catch-like flow
$dbPath = __DIR__ . '/db_connect.php';
if (!file_exists($dbPath)) {
    echo "db_connect.php not found at $dbPath\n";
    exit(1);
}

// Include but suppress the default die; we will check $conn afterwards
require $dbPath;

if (!isset($conn)) {
    echo "\nNo \$conn variable created by db_connect.php.\n";
    echo "Check that db_connect.php creates a mysqli or PDO connection and assigns it to \$conn.\n";
    exit(2);
}

// If mysqli, check connect_errno
if ($conn instanceof mysqli) {
    if ($conn->connect_errno) {
        echo "MySQLi connection error ({$conn->connect_errno}): {$conn->connect_error}\n";
        exit(3);
    }
    echo "MySQLi connected successfully to database. Server info: " . $conn->server_info . "\n";
    // show selected DB
    $res = $conn->query("SELECT DATABASE() as db");
    if ($res) {
        $row = $res->fetch_assoc();
        echo "Using database: " . ($row['db'] ?? 'NULL') . "\n";
    }
    $conn->close();
    exit(0);
}

// If PDO
if ($conn instanceof PDO) {
    try {
        $db = $conn->query('select database() as db')->fetch();
        echo "PDO connected successfully. Using database: " . ($db['db'] ?? 'unknown') . "\n";
    } catch (Exception $e) {
        echo "PDO connection test failed: " . $e->getMessage() . "\n";
        exit(4);
    }
    exit(0);
}

echo "Connection object \$conn is of unexpected type: " . gettype($conn) . "\n";
exit(5);

?>
