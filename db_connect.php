<?php
// db_connect.php — PDO connection (works with all backend files)
$host = '127.0.0.1';
$db   = 'ime_chat';   // <- your DB name
$user = 'root';
$pass = '';
$dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

$pdo = new PDO($dsn, $user, $pass, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);
