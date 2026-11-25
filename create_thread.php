<?php
header('Content-Type: application/json');
require 'access.php';
requirePost();
require 'db.php';

$name = participantName($_POST['created_by'] ?? null);
$title = fieldText($_POST['title'] ?? null) ?: 'Negotiation';
if (mb_strlen($title) > 160 || !mb_check_encoding($title, 'UTF-8')) {
    failRequest(400, 'Use a title up to 160 characters.');
}
$uuid = bin2hex(random_bytes(16));
startCharterSession();
$pdo->beginTransaction();
$thread = $pdo->prepare("INSERT INTO threads (thread_uuid, created_by, title, status) VALUES (?, ?, ?, 'open')");
$thread->execute([$uuid, $name, $title]);
$threadId = (int)$pdo->lastInsertId();
$participant = $pdo->prepare("INSERT INTO thread_participants (thread_id, seat, display_name) VALUES (?, 'host', ?)");
$participant->execute([$threadId, $name]);
$pdo->commit();
rememberMembership($uuid, 'host');
echo json_encode(['status' => 'success', 'thread_id' => $threadId, 'thread_uuid' => $uuid]);
