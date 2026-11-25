<?php
header('Content-Type: application/json');
require 'access.php';
requirePost();
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$offerId = filter_var($input['offer_id'] ?? null, FILTER_VALIDATE_INT);
$uuid = fieldText($input['thread_uuid'] ?? null);
$participant = requireParticipant($pdo, $uuid);
$party = $participant['display_name'];
if (!$offerId) failRequest(400, 'An offer is required.');

$pdo->beginTransaction();
$thread = $pdo->prepare('SELECT id, status FROM threads WHERE thread_uuid = ? FOR UPDATE');
$thread->execute([$uuid]);
$threadRow = $thread->fetch();

$latest = $pdo->prepare('SELECT id, party, accepted_by FROM offers WHERE thread_uuid = ? ORDER BY version DESC, id DESC LIMIT 1');
$latest->execute([$uuid]);
$offer = $latest->fetch();

if (!$threadRow || $threadRow['status'] === 'archived' || !$offer || (int)$offer['id'] !== $offerId ||
    strcasecmp($party, $offer['party'] ?? '') === 0 ||
    ($offer['accepted_by'] !== null && $offer['accepted_by'] !== $party)) {
    $pdo->rollBack();
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => 'Only the counterparty can accept the current offer. Refresh the negotiation.']);
    exit;
}

if ($offer['accepted_by'] === null) {
    $update = $pdo->prepare('UPDATE offers SET accepted_by = ?, accepted_at = NOW() WHERE id = ?');
    $update->execute([$party, $offerId]);
    $status = $pdo->prepare("UPDATE threads SET status = 'agreed' WHERE id = ?");
    $status->execute([$threadRow['id']]);
}
$pdo->commit();

echo json_encode([
    'status' => 'success',
    'message' => 'Offer accepted.',
    'recap_url' => 'generate_recap.php?uuid=' . rawurlencode($uuid),
]);
