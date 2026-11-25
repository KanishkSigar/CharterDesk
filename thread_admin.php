<?php
// thread_admin.php - archive / reopen / delete a negotiation.
header('Content-Type: application/json');
require 'access.php';
requirePost();
require 'db.php';

$in     = json_decode(file_get_contents('php://input'), true);
$uuid   = fieldText($in['thread_uuid'] ?? null);
$action = $in['action'] ?? '';

requireParticipant($pdo, $uuid, true);

try {
    $pdo->beginTransaction();
    $thread = $pdo->prepare('SELECT id FROM threads WHERE thread_uuid=? FOR UPDATE');
    $thread->execute([$uuid]);
    if (!$thread->fetchColumn()) {
        $pdo->rollBack();
        failRequest(404, 'This negotiation is unavailable.');
    }
    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM offers WHERE thread_uuid=?')->execute([$uuid]);
        $pdo->prepare('DELETE FROM threads WHERE thread_uuid=?')->execute([$uuid]);
        $pdo->commit();
        echo json_encode(['status'=>'success','message'=>'Negotiation deleted']);
    } elseif ($action === 'archive' || $action === 'reopen') {
        $status = $action === 'archive' ? 'archived' : 'open';
        $pdo->prepare('UPDATE threads SET status=? WHERE thread_uuid=?')->execute([$status, $uuid]);
        $pdo->commit();
        echo json_encode(['status'=>'success','message'=>"Negotiation {$action}d"]);
    } else {
        $pdo->rollBack();
        failRequest(400, 'Unknown action.');
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    failRequest(500, 'Could not update this negotiation.');
}
