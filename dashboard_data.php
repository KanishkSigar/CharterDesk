<?php
// dashboard_data.php — aggregated overview of all negotiations for the dashboard.
header('Content-Type: application/json');
require 'access.php';
require 'db.php';

$memberships = memberThreads();
if (!$memberships) {
    echo json_encode(['status' => 'success', 'threads' => []]);
    exit;
}
$marks = implode(',', array_fill(0, count($memberships), '?'));

$sql = "SELECT t.thread_uuid, t.created_by, t.status, t.title, t.created_at, t.locked_fields,
               COUNT(o.id) AS offer_count,
               COALESCE(MAX(o.version), 0) AS latest_version,
               COALESCE(SUM(CASE WHEN o.accepted_at IS NOT NULL THEN 1 ELSE 0 END), 0) AS accepted_count
        FROM threads t
        LEFT JOIN offers o ON o.thread_uuid = t.thread_uuid
        WHERE t.thread_uuid IN ($marks)
        GROUP BY t.id, t.thread_uuid, t.created_by, t.status, t.title, t.created_at, t.locked_fields
        ORDER BY t.created_at DESC";

try {
    $query = $pdo->prepare($sql);
    $query->execute(array_keys($memberships));
    $rows = $query->fetchAll();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Could not load negotiations.']);
    exit;
}

$out = [];
foreach ($rows as $r) {
    $locked = json_decode($r['locked_fields'] ?: '[]', true);
    if (!is_array($locked)) $locked = [];

    $offers   = (int)$r['offer_count'];
    $accepted = (int)$r['accepted_count'];

    if ($r['status'] === 'archived') $state = 'archived';
    elseif ($accepted > 0)           $state = 'agreed';
    elseif ($offers > 0)             $state = 'negotiating';
    else                             $state = 'open';

    $out[] = [
        'thread_uuid'    => $r['thread_uuid'],
        'title'          => $r['title'] ?: 'Negotiation',
        'created_by'     => $r['created_by'],
        'created_at'     => $r['created_at'],
        'offers'         => $offers,
        'latest_version' => (int)$r['latest_version'],
        'locked'         => count($locked),
        'state'          => $state,
        'can_manage'     => $memberships[$r['thread_uuid']] === 'host',
    ];
}

echo json_encode(['status' => 'success', 'threads' => $out]);
