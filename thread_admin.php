<?php
// thread_admin.php - archive / reopen / delete a negotiation.
header('Content-Type: application/json');
require 'db.php';

$in     = json_decode(file_get_contents('php://input'), true);
$uuid   = $in['thread_uuid'] ?? '';
$action = $in['action'] ?? '';

if (!$uuid) { echo json_encode(['status'=>'error','message'=>'Missing thread_uuid']); exit; }

try {
    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM offers WHERE thread_uuid=?')->execute([$uuid]);
        $pdo->prepare('DELETE FROM threads WHERE thread_uuid=?')->execute([$uuid]);
        echo json_encode(['status'=>'success','message'=>'Negotiation deleted']);
    } elseif ($action === 'archive' || $action === 'reopen') {
        $status = $action === 'archive' ? 'archived' : 'open';
        $pdo->prepare('UPDATE threads SET status=? WHERE thread_uuid=?')->execute([$status, $uuid]);
        echo json_encode(['status'=>'success','message'=>"Negotiation {$action}d"]);
    } else {
        echo json_encode(['status'=>'error','message'=>'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
