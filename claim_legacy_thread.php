<?php
// Local administrator utility for inviting the host of a pre-existing negotiation.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require 'db.php';
$uuid = $argv[1] ?? '';
if ($uuid === '') {
    fwrite(STDERR, "Usage: php claim_legacy_thread.php THREAD_UUID\n");
    exit(1);
}
$pdo->beginTransaction();
$thread = $pdo->prepare('SELECT id FROM threads WHERE thread_uuid = ? FOR UPDATE');
$thread->execute([$uuid]);
$id = $thread->fetchColumn();
if (!$id) {
    fwrite(STDERR, "Negotiation not found.\n");
    exit(1);
}
$existing = $pdo->prepare('SELECT 1 FROM thread_participants WHERE thread_id = ? AND display_name IS NOT NULL LIMIT 1');
$existing->execute([$id]);
if ($existing->fetchColumn()) {
    fwrite(STDERR, "This negotiation already has an authenticated participant.\n");
    exit(1);
}
$token = bin2hex(random_bytes(32));
$invite = $pdo->prepare(
    "INSERT INTO thread_participants (thread_id, seat, invite_hash, invite_expires_at)
     VALUES (?, 'host', ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 24 HOUR))
     ON DUPLICATE KEY UPDATE invite_hash = VALUES(invite_hash), invite_expires_at = VALUES(invite_expires_at)"
);
$invite->execute([$id, hash('sha256', $token)]);
$pdo->commit();
echo 'join.html#' . $token . PHP_EOL;
