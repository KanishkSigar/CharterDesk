<?php
// poll.php - tiny change-signature for a thread so the client only does a
// full reload when something actually changed (cheaper than refetching offers).
header('Content-Type: application/json');
require 'db.php';

$uuid = $_GET['uuid'] ?? '';
if (!$uuid) { echo json_encode(['status'=>'error','message'=>'Missing uuid']); exit; }

$sql = "SELECT t.status, t.locked_fields,
               COUNT(o.id) AS c,
               COALESCE(MAX(o.version),0) AS v,
               COALESCE(SUM(CASE WHEN o.accepted_at IS NOT NULL THEN 1 ELSE 0 END),0) AS a,
               COALESCE(MAX(UNIX_TIMESTAMP(IFNULL(o.accepted_at, o.created_at))),0) AS ts
        FROM threads t
        LEFT JOIN offers o ON o.thread_uuid = t.thread_uuid
        WHERE t.thread_uuid = ?
        GROUP BY t.id, t.status, t.locked_fields";

$st = $pdo->prepare($sql);
$st->execute([$uuid]);
$r = $st->fetch();
if (!$r) { echo json_encode(['status'=>'error','message'=>'Thread not found']); exit; }

$lockedLen = strlen($r['locked_fields'] ?: '');
$sig = implode('-', [$r['c'], $r['v'], $r['a'], $r['ts'], $lockedLen, $r['status']]);

echo json_encode(['status'=>'success','sig'=>$sig]);
