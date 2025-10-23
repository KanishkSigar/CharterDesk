<?php
$host = 'sqlXXX.epizy.com';       // ← from InfinityFree (NOT localhost)
$db   = 'epiz_12345678_imechat';  // ← your full DB name
$user = 'epiz_12345678';          // ← your MySQL username
$pass = 'YOUR_DB_PASSWORD';       // ← your MySQL password
$dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

$pdo = new PDO($dsn, $user, $pass, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);
