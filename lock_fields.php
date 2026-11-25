<?php
header('Content-Type: application/json');
require 'access.php';
requirePost();
require 'db.php';

$in = json_decode(file_get_contents('php://input'), true);
$uuid = fieldText($in['thread_uuid'] ?? null);
requireParticipant($pdo, $uuid);
$fields = $in['fields'] ?? [];
$fields = is_array($fields) ? array_filter($fields, 'is_string') : [];
$pdo->beginTransaction();
$sel = $pdo->prepare("SELECT status, locked_fields FROM threads WHERE thread_uuid=? FOR UPDATE");
$sel->execute([$uuid]);
$thread = $sel->fetch();
if (!$thread || in_array($thread['status'], ['agreed', 'archived'], true)) {
    $pdo->rollBack();
    failRequest(409, 'This negotiation is closed.');
}
$existing = json_decode($thread['locked_fields'] ?: '[]', true) ?: [];

// merge unique
$merged = array_values(array_unique(array_merge($existing, $fields)));

$up = $pdo->prepare("UPDATE threads SET locked_fields=? WHERE thread_uuid=?");
$ok = $up->execute([json_encode($merged, JSON_UNESCAPED_UNICODE), $uuid]);

if ($ok) $pdo->commit();
else $pdo->rollBack();

echo json_encode($ok ? ["status"=>"success","locked_fields"=>$merged]
                     : ["status"=>"error","message"=>"Update failed"]);
