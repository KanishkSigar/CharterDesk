<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // keep empty for XAMPP default
$dbname = 'ime_negotiation'; // your database name from phpMyAdmin

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set character encoding
$conn->set_charset("utf8mb4");

// For debugging (you can remove this line after testing)
# echo "Connected successfully";
?>
