<?php
header('Content-Type: application/json');
require 'access.php';
requirePost();
require 'db.php';

$body = json_decode(file_get_contents('php://input'), true);
$token = fieldText($body['token'] ?? null);
$name = participantName($body['name'] ?? null);
if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
    failRequest(404, 'This invitation has expired or has already been used.');
}

startCharterSession();
$hash = hash('sha256', $token);
$lookup = $pdo->prepare(
    'SELECT t.id, t.thread_uuid FROM thread_participants p JOIN threads t ON t.id = p.thread_id
     WHERE p.invite_hash = ? AND p.invite_expires_at > UTC_TIMESTAMP()'
);
$lookup->execute([$hash]);
$invite = $lookup->fetch();
if (!$invite) failRequest(404, 'This invitation has expired or has already been used.');
if (isset($_SESSION['memberships'][$invite['thread_uuid']])) {
    failRequest(409, 'Open the invitation in a different browser or private window.');
}

$pdo->beginTransaction();
$thread = $pdo->prepare('SELECT status FROM threads WHERE id = ? FOR UPDATE');
$thread->execute([$invite['id']]);
$state = $thread->fetchColumn();
if (!$state || $state === 'archived') {
    $pdo->rollBack();
    failRequest(404, 'This negotiation is unavailable.');
}
$seatQuery = $pdo->prepare(
    'SELECT seat FROM thread_participants WHERE thread_id = ? AND invite_hash = ?
     AND invite_expires_at > UTC_TIMESTAMP() AND display_name IS NULL'
);
$seatQuery->execute([$invite['id'], $hash]);
$seat = $seatQuery->fetchColumn();
if (!$seat) {
    $pdo->rollBack();
    failRequest(404, 'This invitation has expired or has already been used.');
}
$duplicate = $pdo->prepare('SELECT 1 FROM thread_participants WHERE thread_id = ? AND display_name = ?');
$duplicate->execute([$invite['id'], $name]);
if ($duplicate->fetchColumn()) {
    $pdo->rollBack();
    failRequest(409, 'Choose a name different from the other participant.');
}
$claim = $pdo->prepare(
    'UPDATE thread_participants SET display_name = ?, invite_hash = NULL, invite_expires_at = NULL WHERE thread_id = ? AND seat = ?'
);
$claim->execute([$name, $invite['id'], $seat]);
$pdo->commit();

rememberMembership($invite['thread_uuid'], $seat);
echo json_encode(['status' => 'success', 'thread_uuid' => $invite['thread_uuid']]);
