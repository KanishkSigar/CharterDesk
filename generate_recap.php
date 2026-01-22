<?php
// Simple recap view — HTML and optional PDF (via Dompdf)
require 'db.php';

$uuid = $_GET['uuid'] ?? '';
 $asPdf = isset($_GET['pdf']) && ($_GET['pdf']=='1' || $_GET['pdf']=='true');
 $asDownload = isset($_GET['download']) && ($_GET['download']=='1' || $_GET['download']=='true');
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

$html = '<!doctype html><html><head><meta charset="utf-8"><title>Fixture Recap — '.htmlspecialchars($uuid).'</title>';
$html .= '<style>body{font-family:Arial,Helvetica,sans-serif;margin:24px;color:#0f172a}h1{margin:0 0 8px}h2{margin:16px 0 8px}table{border-collapse:collapse;width:100%}td,th{border:1px solid #e5e7eb;padding:8px;text-align:left;vertical-align:top}.muted{color:#6b7280}</style>';
$html .= '</head><body>';
$html .= '<h1>Fixture Recap</h1>';
$html .= '<div class="muted">Thread: '.htmlspecialchars($uuid).'</div>';

$html .= '<h2>Parties</h2>';
$html .= '<p><b>All:</b> '.htmlspecialchars(implode(', ', array_keys($parties))).'</p>';
$html .= '<p><b>By Role:</b> ';
foreach($roles as $role=>$name){ $html .= htmlspecialchars("$role: $name ") . '&nbsp;&nbsp;'; }
$html .= '</p>';

$html .= '<h2>Agreed Terms (Latest)</h2>';
$html .= '<table><tbody>';
foreach ($latest as $k=>$v){
  $html .= '<tr><th>'.htmlspecialchars($k).'</th><td>'.nl2br(htmlspecialchars(is_scalar($v)? (string)$v : json_encode($v))).'</td></tr>';
}
$html .= '</tbody></table>';

$html .= '<h2>History</h2>';
$html .= '<table><thead><tr><th>Version</th><th>Party</th><th>Role</th><th>Accepted By</th><th>Accepted At</th></tr></thead><tbody>';
foreach ($offers as $o){
  $html .= '<tr><td>'.(int)$o['version'].'</td><td>'.htmlspecialchars($o['party']?:'User').'</td><td>'.htmlspecialchars($o['role']?:'Unknown').'</td><td>'.htmlspecialchars($o['accepted_by']?:'').'</td><td>'.htmlspecialchars($o['accepted_at']?:'').'</td></tr>';
}
$html .= '</tbody></table>';

$html .= '</body></html>';

if ($asPdf) {
    if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
        echo "Dompdf (vendor) not available. Run: composer require dompdf/dompdf";
        exit;
    }
    require_once __DIR__ . '/vendor/autoload.php';
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4','portrait');
    $dompdf->render();
    $pdf = $dompdf->output();
  header('Content-Type: application/pdf');
  if ($asDownload) {
    header('Content-Disposition: attachment; filename="fixture_recap_'.htmlspecialchars($uuid).'.pdf"');
  } else {
    header('Content-Disposition: inline; filename="fixture_recap_'.htmlspecialchars($uuid).'.pdf"');
  }
  echo $pdf; exit;
}

echo $html;

?>
