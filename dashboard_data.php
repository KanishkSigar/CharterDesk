<?php
// dashboard_data.php — aggregated overview of all negotiations for the dashboard.
header('Content-Type: application/json');
require 'db.php';

$sql = "SELECT t.thread_uuid, t.created_by, t.status, t.created_at, t.locked_fields,
               COUNT(o.id) AS offer_count,
               COALESCE(MAX(o.version), 0) AS latest_version,
               COALESCE(SUM(CASE WHEN o.accepted_at IS NOT NULL THEN 1 ELSE 0 END), 0) AS accepted_count
        FROM threads t
        LEFT JOIN offers o ON o.thread_uuid = t.thread_uuid
        GROUP BY t.id, t.thread_uuid, t.created_by, t.status, t.created_at, t.locked_fields
        ORDER BY t.created_at DESC";

try {
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

$out = [];
foreach ($rows as $r) {
    $locked = json_decode($r['locked_fields'] ?: '[]', true);
    if (!is_array($locked)) $locked = [];

    $offers   = (int)$r['offer_count'];
    $accepted = (int)$r['accepted_count'];

    if ($accepted > 0)      $state = 'agreed';
    elseif ($offers > 0)    $state = 'negotiating';
    else                    $state = 'open';

    $out[] = [
        'thread_uuid'    => $r['thread_uuid'],
        'created_by'     => $r['created_by'],
        'created_at'     => $r['created_at'],
        'offers'         => $offers,
        'latest_version' => (int)$r['latest_version'],
        'locked'         => count($locked),
        'state'          => $state,
    ];
}

echo json_encode(['status' => 'success', 'threads' => $out]);
