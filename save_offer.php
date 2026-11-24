<?php
header('Content-Type: application/json');
require 'db.php';

$in = json_decode(file_get_contents('php://input'), true);
$uuid  = $in['thread_uuid'] ?? '';
$party = $in['party']       ?? 'User';
$role  = $in['role']        ?? 'Unknown';
$data  = $in['data']        ?? [];

if (!$uuid) { echo json_encode(["status"=>"error","message"=>"Missing uuid"]); exit; }
if (!is_array($data)) $data = [];

$pdo->beginTransaction();
$sth = $pdo->prepare("SELECT id, status, locked_fields FROM threads WHERE thread_uuid=? FOR UPDATE");
$sth->execute([$uuid]);
$thread = $sth->fetch();
$check = $pdo->prepare("SELECT id FROM offers WHERE thread_uuid=? AND accepted_at IS NOT NULL LIMIT 1");
$check->execute([$uuid]);
if (!$thread || $thread['status'] === 'agreed' || $check->fetchColumn()) {
    $pdo->rollBack();
    http_response_code(409);
    echo json_encode(["status"=>"error","message"=>"This negotiation is unavailable or already agreed."]);
    exit;
}
$locked = json_decode($thread['locked_fields'] ?: '[]', true);
if (!is_array($locked)) $locked = [];

// last version
$qv = $pdo->prepare("SELECT IFNULL(MAX(version),0) FROM offers WHERE thread_uuid=?");
$qv->execute([$uuid]);
$lastVer = (int)$qv->fetchColumn();
$nextVer = $lastVer + 1;

// preserve locked fields from last version
if ($lastVer > 0 && !empty($locked)) {
  $qld = $pdo->prepare("SELECT data FROM offers WHERE thread_uuid=? AND version=?");
  $qld->execute([$uuid, $lastVer]);
  $lastData = json_decode(($qld->fetchColumn() ?: '{}'), true);
  if (!is_array($lastData)) $lastData = [];
  foreach ($locked as $f) {
    if (array_key_exists($f, $lastData)) {
      $data[$f] = $lastData[$f];
    }
  }
}

$thread_id = (int)$thread['id'];

$ins = $pdo->prepare("INSERT INTO offers(thread_id, thread_uuid, version, party, role, data) VALUES(?,?,?,?,?,?)");
$ok  = $ins->execute([$thread_id, $uuid, $nextVer, $party, $role, json_encode($data, JSON_UNESCAPED_UNICODE)]);

if ($ok) $pdo->commit();
else $pdo->rollBack();

echo json_encode($ok ? ["status"=>"success","version"=>$nextVer]
                     : ["status"=>"error","message"=>"DB insert failed"]);
