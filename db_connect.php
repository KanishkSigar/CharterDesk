<?php
// db_connect.php
// Attempts to connect to MySQL using TCP (127.0.0.1:3306) first, then falls back to common socket paths for localhost.
// Update $dbname, $user and $pass as needed for your environment (phpMyAdmin / XAMPP).

error_reporting(E_ALL);
ini_set('display_errors', '1');

$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = ''; // keep empty for XAMPP default
$dbname = 'ime_negotiation'; // change to the exact database name you created in phpMyAdmin

// Try TCP connection first (use 127.0.0.1 to force TCP instead of UNIX socket)
$conn = @new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_errno) {
    // If TCP failed, try common socket locations with 'localhost'
    $socket_paths = [
        '/tmp/mysql.sock',
        '/var/run/mysqld/mysqld.sock',
        '/var/mysql/mysql.sock',
    ];

    $connected = false;
    foreach ($socket_paths as $sock) {
        $tmp = @new mysqli('localhost', $user, $pass, $dbname, null, $sock);
        if (!$tmp->connect_errno) {
            $conn = $tmp;
            $connected = true;
            break;
        }
    }

    if (!$connected) {
        // Provide a helpful diagnostic message
        $msg = "MySQL connection failed. Last error: ({$conn->connect_errno}) {$conn->connect_error}\n";
        $msg .= "Tried TCP: {$host}:{$port} and socket paths: " . implode(', ', $socket_paths) . "\n";
        $msg .= "Common causes: MySQL server not running, wrong DB name, or PHP using a different socket path.\n";
        $msg .= "Verify MySQL is running (e.g., XAMPP control panel, 'brew services list', or 'mysql.server start') and that the DB '{$dbname}' exists in phpMyAdmin.\n";
        $msg .= "If your MySQL uses a custom socket or port, update db_connect.php accordingly.\n";
        die($msg);
    }
}

// Set character encoding
$conn->set_charset('utf8mb4');

// Connection is available as $conn (mysqli instance)
// Uncomment the following line temporarily for debugging only:
// echo "Connected to MySQL successfully. Using DB: " . ($conn->query("SELECT DATABASE() as db")->fetch_assoc()['db']) . "\n";
?>
