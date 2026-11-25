<?php
header('Content-Type: application/json');
require 'access.php';
require 'db.php';

$uuid = fieldText($_GET['uuid'] ?? null);
$participant = requireParticipant($pdo, $uuid);

// read thread meta (locked fields + title)
$st = $pdo->prepare("SELECT locked_fields, title, status FROM threads WHERE thread_uuid=?");
$st->execute([$uuid]);
$row = $st->fetch();
if (!$row) { echo json_encode(["status"=>"error","message"=>"Thread not found"]); exit; }

$locked = json_decode($row['locked_fields'] ?: '[]', true);
if (!is_array($locked)) $locked = [];
$title = $row['title'] ?: 'Negotiation';

// read offers
$off = $pdo->prepare("SELECT id, version, party, role, data, created_at, accepted_by, accepted_at
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
    "created_at" => $r['created_at'],
    "accepted_by"=> $r['accepted_by'],
    "accepted_at"=> $r['accepted_at'],
  ];
}

$guest = $pdo->prepare("SELECT 1 FROM thread_participants WHERE thread_id = ? AND seat = 'guest' AND display_name IS NOT NULL");
$guest->execute([$participant['thread_id']]);

echo json_encode([
  "status"        => "success",
  "title"         => $title,
  "guest_joined"  => (bool)$guest->fetchColumn(),
  "thread_status" => $row["status"],
  "participant"   => ["name" => $participant["display_name"], "host" => $participant["seat"] === "host"],
  "locked_fields" => $locked,
  "offers"        => $out
]);
