<?php
header('Content-Type: application/json');
require 'db.php';

// Inputs
$created_by = $_POST['created_by'] ?? 'User';
$title      = $_POST['title']      ?? 'Negotiation';

// UUID like th_a1b2c3d4e5f6
$uuid = 'th_'.bin2hex(random_bytes(6));

$ins = $pdo->prepare("INSERT INTO threads(thread_uuid, title, created_by, locked_fields) VALUES(?,?,?, '[]')");
$ok  = $ins->execute([$uuid, $title, $created_by]);

echo json_encode($ok ? ["status"=>"success","thread_uuid"=>$uuid]
                     : ["status"=>"error","message"=>"Failed to create thread"]);
