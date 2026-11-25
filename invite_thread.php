<?php
header('Content-Type: application/json');
require 'access.php';
requirePost();
require 'db.php';

$body = json_decode(file_get_contents('php://input'), true);
$uuid = fieldText($body['thread_uuid'] ?? null);
$host = requireParticipant($pdo, $uuid, true);
$token = bin2hex(random_bytes(32));

$pdo->beginTransaction();
$thread = $pdo->prepare('SELECT status FROM threads WHERE id = ? FOR UPDATE');
$thread->execute([$host['thread_id']]);
if (!in_array($thread->fetchColumn(), ['open', 'negotiating'], true)) {
    $pdo->rollBack();
    failRequest(409, 'This negotiation is closed.');
}
$guest = $pdo->prepare("SELECT display_name FROM thread_participants WHERE thread_id = ? AND seat = 'guest'");
$guest->execute([$host['thread_id']]);
if (is_string($guest->fetchColumn())) {
    $pdo->rollBack();
    failRequest(409, 'A counterparty has already joined.');
}
$invite = $pdo->prepare(
    "INSERT INTO thread_participants (thread_id, seat, invite_hash, invite_expires_at)
     VALUES (?, 'guest', ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 24 HOUR))
     ON DUPLICATE KEY UPDATE invite_hash = VALUES(invite_hash), invite_expires_at = VALUES(invite_expires_at)"
);
$invite->execute([$host['thread_id'], hash('sha256', $token)]);
$pdo->commit();
echo json_encode(['status' => 'success', 'token' => $token]);
