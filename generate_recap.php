<?php
// Simple HTML recap view; you can print to PDF via browser or Dompdf.
require 'db.php';

$uuid = $_GET['uuid'] ?? '';
if (!$uuid) { echo "Missing uuid"; exit; }

$off = $pdo->prepare("SELECT party, role, version, data, accepted_by, accepted_at
                      FROM offers WHERE thread_uuid=? ORDER BY version ASC");
$off->execute([$uuid]);
$offers = $off->fetchAll();
if (!$offers) { echo "No offers for this thread."; exit; }

$latest = json_decode(end($offers)['data'] ?: '{}', true);
$parties = [];
$roles   = [];
foreach ($offers as $o) {
  $p = $o['party'] ?: 'User';
  $r = $o['role']  ?: 'Unknown';
  $parties[$p] = true;
  if (!isset($roles[$r])) $roles[$r] = $p;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Fixture Recap — <?=htmlspecialchars($uuid)?></title>
<style>
  body{font-family:Arial,Helvetica,sans-serif;margin:24px;color:#0f172a}
  h1{margin:0 0 8px}
  h2{margin:16px 0 8px}
  table{border-collapse:collapse;width:100%}
  td,th{border:1px solid #e5e7eb;padding:8px;text-align:left;vertical-align:top}
  .muted{color:#6b7280}
</style>
</head>
<body>
<h1>Fixture Recap</h1>
<div class="muted">Thread: <?=htmlspecialchars($uuid)?></div>

<h2>Parties</h2>
<p><b>All:</b> <?=htmlspecialchars(implode(', ', array_keys($parties)))?></p>
<p><b>By Role:</b>
  <?php foreach($roles as $role=>$name){ echo htmlspecialchars("$role: $name "),'&nbsp;&nbsp;'; } ?>
</p>

<h2>Agreed Terms (Latest)</h2>
<table>
  <tbody>
  <?php foreach ($latest as $k=>$v): ?>
    <tr><th><?=htmlspecialchars($k)?></th><td><?=nl2br(htmlspecialchars(is_scalar($v)? (string)$v : json_encode($v)))?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>

<h2>History</h2>
<table>
  <thead><tr><th>Version</th><th>Party</th><th>Role</th><th>Accepted By</th><th>Accepted At</th></tr></thead>
  <tbody>
  <?php foreach ($offers as $o): ?>
    <tr>
      <td><?= (int)$o['version'] ?></td>
      <td><?= htmlspecialchars($o['party'] ?: 'User') ?></td>
      <td><?= htmlspecialchars($o['role'] ?: 'Unknown') ?></td>
      <td><?= htmlspecialchars($o['accepted_by'] ?: '') ?></td>
      <td><?= htmlspecialchars($o['accepted_at'] ?: '') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

</body>
</html>
