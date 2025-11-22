<?php
// generate_cp.php — richer Charter Party generator
require 'db.php';

$uuid = $_GET['uuid'] ?? '';
$asPdf = isset($_GET['pdf']) && ($_GET['pdf']=='1' || $_GET['pdf']=='true');
if (!$uuid) { echo "Missing uuid"; exit; }

$off = $pdo->prepare("SELECT party, role, version, data, accepted_by, accepted_at
                      FROM offers WHERE thread_uuid=? ORDER BY version ASC");
$off->execute([$uuid]);
$offers = $off->fetchAll();
if (!$offers) { echo "No offers for this thread."; exit; }

$latest = json_decode(end($offers)['data'] ?: '{}', true);
if (!is_array($latest)) $latest = [];

$parties = [];
$roles   = [];
foreach ($offers as $o) {
  $p = $o['party'] ?: 'User';
  $r = $o['role']  ?: 'Unknown';
  $parties[$p] = true;
  if (!isset($roles[$r])) $roles[$r] = $p;
}

function f($arr, $key, $default=''){
    if (isset($arr[$key]) && $arr[$key] !== null && $arr[$key] !== '') return $arr[$key];
    return $default;
}

// Map common fields
$V = [];
$V['vessel'] = f($latest,'vessel','[Vessel name]');
$V['gear'] = f($latest,'gear','');
$V['class_flag'] = f($latest,'class_flag','');
$V['built_year'] = f($latest,'built_year','');
$V['pni'] = f($latest,'pni','');
$V['cargo'] = f($latest,'cargo','Cargo');
$V['qty'] = f($latest,'qty','Quantity');
$V['imo'] = f($latest,'imo','Yes');
$V['load_port'] = f($latest,'load_port','Load Port');
$V['dis_port'] = f($latest,'dis_port','Disport');
$V['laycan_start'] = f($latest,'laycan_start','');
$V['laycan_end'] = f($latest,'laycan_end','');
$V['freight'] = f($latest,'freight','Freight to be agreed');
$V['load_rate'] = f($latest,'load_rate','');
$V['dis_rate'] = f($latest,'dis_rate','');
$V['demurrage'] = f($latest,'demurrage','');
$V['despatch'] = f($latest,'despatch','');
$V['laytime'] = f($latest,'laytime','');
$V['cp_base'] = f($latest,'cp_base','GENCON 1994');
$V['riders'] = f($latest,'riders','');
$V['add_clause'] = f($latest,'add_clause','');
$V['contact'] = f($latest,'contact','');
$V['company'] = f($latest,'company','');
$V['email'] = f($latest,'email','');

$partiesList = implode(', ', array_keys($parties));

// Long-form clause templates (simplified but complete)
$clauses = [];
$clauses[] = [ 'title' => 'Preamble', 'text' => "This Charter Party is agreed between the parties shown below for the carriage of the cargo described herein on the terms set out in this Charter Party." ];
$clauses[] = [ 'title' => '1. Parties', 'text' => "Charterers: " . implode(', ', array_keys($parties)) . ". Owner: as named in this fixture." ];
$clauses[] = [ 'title' => '2. Vessel', 'text' => "The vessel to be nominated: {$V['vessel']}" . ($V['gear']? ", Gear: {$V['gear']}" : '') . ($V['class_flag']? ", Class/Flag: {$V['class_flag']}" : '') . ($V['built_year']? ", Built: {$V['built_year']}" : '') ];
$clauses[] = [ 'title' => '3. Cargo and Quantity', 'text' => "Cargo: {$V['cargo']}. Quantity: {$V['qty']} (tolerance as stated). The cargo to be lawful and shipped in accordance with applicable regulations." ];
$clauses[] = [ 'title' => '4. Load Port(s) and Discharge Port(s)', 'text' => "Loading at: {$V['load_port']}. Discharging at: {$V['dis_port']}. Parties agree safe berth/spot arrangements as customary." ];
$clauses[] = [ 'title' => '5. Laycan', 'text' => "Laycan: " . trim($V['laycan_start'] . ' to ' . $V['laycan_end']) . ". Charterers to provide NOR within the laycan period." ];
$clauses[] = [ 'title' => '6. Freight and Payment', 'text' => "Freight: {$V['freight']}. Payment terms: to be as per parties' agreement (banking and any guarantees to be provided)." ];
$clauses[] = [ 'title' => '7. Load/Discharge Rates', 'text' => "Load rate: {$V['load_rate']}. Discharge rate: {$V['dis_rate']}. Any excess time to be subject to laytime/demurrage." ];
$clauses[] = [ 'title' => '8. Laytime, Demurrage and Despatch', 'text' => "Laytime: {$V['laytime']}. Demurrage: {$V['demurrage']}. Despatch: {$V['despatch']}. Demurrage to be paid at the rate and prorata method agreed." ];
$clauses[] = [ 'title' => '9. Charter Party Base', 'text' => "This Charter Party is based on: {$V['cp_base']}. Riders and amendments where agreed form part of the contract." ];
$clauses[] = [ 'title' => '10. Riders & Additional Clauses', 'text' => ($V['riders']? $V['riders'] . "\n\n" : '') . ($V['add_clause']? $V['add_clause'] : 'No additional clauses agreed.') ];
$clauses[] = [ 'title' => '11. Notices', 'text' => "All notices shall be sent to: " . ($V['contact']? $V['contact'] . ', ' : '') . ($V['company']? $V['company'] . ', ' : '') . ($V['email']? $V['email'] : '') ];
$clauses[] = [ 'title' => '12. Bills of Lading and Documentation', 'text' => "Bills of Lading to be issued as per normal trading practice. The Charterers/owners to agree split B/L or switch B/L where applicable." ];
$clauses[] = [ 'title' => '13. Safety, Sanctions and Insurance', 'text' => "The cargo and trading to comply with applicable sanctions and safety regulations. P&I cover: {$V['pni']} (if provided)." ];
$clauses[] = [ 'title' => '14. Force Majeure', 'text' => "Force majeure events to be handled as per the CP base and applicable riders." ];
$clauses[] = [ 'title' => '15. Law and Arbitration', 'text' => "This Charter Party shall be governed by English law and any disputes referred to arbitration in London unless otherwise agreed in riders." ];
$clauses[] = [ 'title' => '16. Miscellaneous', 'text' => "All other usual clauses apply including performance warranties, warranty of seaworthiness at delivery, and crew matters." ];

$html = '<!doctype html><html><head><meta charset="utf-8"><title>Charter Party — '.htmlspecialchars($uuid).'</title>';
$html .= '<style>body{font-family:Times, serif;margin:36px;color:#000}h1{font-size:20px;text-align:center;margin-bottom:4px}h2{font-size:14px;margin-top:20px}p{margin:8px 0;line-height:1.35} .muted{color:#444;font-size:12px} .clause{margin:12px 0} .clause h3{margin:6px 0;font-size:13px} .sig{margin-top:36px;display:flex;justify-content:space-between}</style>';
$html .= '</head><body>';
$html .= '<h1>CHARTER PARTY (FIXTURE)</h1>';
$html .= '<div class="muted">Thread: '.htmlspecialchars($uuid).' &nbsp; | &nbsp; Generated: '.date('Y-m-d H:i').'</div>';

$html .= '<h2>Parties</h2>';
$html .= '<p><strong>All Parties:</strong> '.htmlspecialchars($partiesList).'</p>';
$html .= '<p><strong>By Role:</strong><br/>';
foreach($roles as $role=>$name){ $html .= htmlspecialchars("$role: $name") . '<br/>'; }
$html .= '</p>';

// Insert clauses
foreach($clauses as $idx => $cl){
    $num = $idx + 1;
    $html .= '<div class="clause"><h3>'.$num.'. '.htmlspecialchars($cl['title']).'</h3>';
    $html .= '<p>'.nl2br(htmlspecialchars($cl['text'])).'</p></div>';
}

$html .= '<h2>Agreed Terms (Latest Offer)</h2>';
$html .= '<table style="width:100%;border-collapse:collapse;border:1px solid #ddd"><tbody>';
foreach ($latest as $k=>$v){
    $html .= '<tr><th style="width:28%;text-align:left;padding:8px;border:1px solid #ddd">'.htmlspecialchars($k).'</th>';
    $html .= '<td style="padding:8px;border:1px solid #ddd">'.nl2br(htmlspecialchars(is_scalar($v)? (string)$v : json_encode($v))).'</td></tr>';
}
$html .= '</tbody></table>';

$html .= '<h2>Offers History</h2>';
$html .= '<table style="width:100%;border-collapse:collapse;border:1px solid #ddd"><thead><tr><th style="padding:6px;border:1px solid #ddd">Version</th><th style="padding:6px;border:1px solid #ddd">Party</th><th style="padding:6px;border:1px solid #ddd">Role</th><th style="padding:6px;border:1px solid #ddd">Accepted By</th><th style="padding:6px;border:1px solid #ddd">Accepted At</th></tr></thead><tbody>';
foreach ($offers as $o) {
    $html .= '<tr>';
    $html .= '<td style="padding:6px;border:1px solid #ddd">' . (int)$o['version'] . '</td>';
    $html .= '<td style="padding:6px;border:1px solid #ddd">' . htmlspecialchars($o['party'] ?: '') . '</td>';
    $html .= '<td style="padding:6px;border:1px solid #ddd">' . htmlspecialchars($o['role'] ?: '') . '</td>';
    $html .= '<td style="padding:6px;border:1px solid #ddd">' . htmlspecialchars($o['accepted_by'] ?: '') . '</td>';
    $html .= '<td style="padding:6px;border:1px solid #ddd">' . htmlspecialchars($o['accepted_at'] ?: '') . '</td>';
    $html .= '</tr>';
}
$html .= '</tbody></table>';

$html .= '<div class="sig"><div>______________________<br/>Charterer</div><div>______________________<br/>Owner</div></div>';

$html .= '</body></html>';

if ($asPdf) {
    if (!file_exists(__DIR__ . '/vendor/autoload.php')) { echo "Dompdf (vendor) not available. Install composer dependencies."; exit; }
    require_once __DIR__ . '/vendor/autoload.php';
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4','portrait');
    $dompdf->render();
    $pdfOutput = $dompdf->output();
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="charter_party_'.htmlspecialchars($uuid).'.pdf"');
    echo $pdfOutput; exit;
}

echo $html;

?>
