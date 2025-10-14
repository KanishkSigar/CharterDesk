<?php
header('Content-Type: application/json');
require 'db_connect.php';

// (optional but recommended) make sure connection is utf8mb4
if (method_exists($conn, 'set_charset')) {
  $conn->set_charset('utf8mb4');
}

$uuid = $_GET['uuid'] ?? '';
if ($uuid === '') {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'missing uuid']);
  exit;
}

// use a prepared statement for the thread lookup
$stmt = $conn->prepare("SELECT id FROM threads WHERE thread_uuid = ? LIMIT 1");
$stmt->bind_param('s', $uuid);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
  http_response_code(404);
  echo json_encode(['status'=>'error','message'=>'thread not found']);
  exit;
}
$row = $res->fetch_assoc();
$thread_id = (int)$row['id'];
$stmt->close();

// fetch offers for this thread (thread_id is trusted numeric now)
$sql = "SELECT id, `version`, party, role, `data`, riders, created_at
        FROM offers
        WHERE thread_id = $thread_id
        ORDER BY `version` ASC";
$r2 = $conn->query($sql);

if ($r2 === false) {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>'db query failed']);
  exit;
}

$offers = [];
while ($o = $r2->fetch_assoc()) {
  // normalize types so the frontend can match reliably
  $o['id']      = (int)$o['id'];
  $o['version'] = (int)$o['version'];
  // data column is JSON text; decode defensively
  $o['data']    = json_decode($o['data'] ?? '{}', true) ?: [];
  $offers[] = $o;
}

echo json_encode(
  ['status'=>'success','offers'=>$offers],
  JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
);
