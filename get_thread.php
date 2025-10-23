<?php
header('Content-Type: application/json');
require 'db.php';

$uuid = $_GET['uuid'] ?? '';
if (!$uuid) { echo json_encode(["status"=>"error","message"=>"Missing uuid"]); exit; }

// read locked fields
$st = $pdo->prepare("SELECT locked_fields FROM threads WHERE thread_uuid=?");
$st->execute([$uuid]);
$row = $st->fetch();
if (!$row) { echo json_encode(["status"=>"error","message"=>"Thread not found"]); exit; }

$locked = json_decode($row['locked_fields'] ?: '[]', true);
if (!is_array($locked)) $locked = [];

// read offers
$off = $pdo->prepare("SELECT id, version, party, role, data, accepted_by, accepted_at
                      FROM offers WHERE thread_uuid=? ORDER BY version ASC");
$off->execute([$uuid]);

$out = [];
while ($r = $off->fetch()) {
  $data = json_decode($r['data'] ?: '{}', true);
  if (!is_array($data)) $data = [];
  $out[] = [
    "id"         => (int)$r['id'],
    "version"    => (int)$r['version'],
    "party"      => $r['party'] ?: 'User',
    "role"       => $r['role']  ?: 'Unknown',
    "data"       => $data,
    "accepted_by"=> $r['accepted_by'],
    "accepted_at"=> $r['accepted_at'],
  ];
}

echo json_encode([
  "status"        => "success",
  "locked_fields" => $locked,
  "offers"        => $out
]);
