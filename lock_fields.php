<?php
header('Content-Type: application/json');
require 'db.php';

$in = json_decode(file_get_contents('php://input'), true);
$uuid   = $in['thread_uuid'] ?? '';
$fields = $in['fields']      ?? [];

if (!$uuid) { echo json_encode(["status"=>"error","message"=>"Missing uuid"]); exit; }
if (!is_array($fields)) $fields = [];

// read existing
$sel = $pdo->prepare("SELECT locked_fields FROM threads WHERE thread_uuid=?");
$sel->execute([$uuid]);
$existing = json_decode(($sel->fetchColumn() ?: '[]'), true);
if (!is_array($existing)) $existing = [];

// merge unique
$merged = array_values(array_unique(array_merge($existing, $fields)));

$up = $pdo->prepare("UPDATE threads SET locked_fields=? WHERE thread_uuid=?");
$ok = $up->execute([json_encode($merged, JSON_UNESCAPED_UNICODE), $uuid]);

echo json_encode($ok ? ["status"=>"success","locked_fields"=>$merged]
                     : ["status"=>"error","message"=>"Update failed"]);
