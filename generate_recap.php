<?php
require 'vendor/autoload.php';
require 'db_connect.php';
use Dompdf\Dompdf;

$uuid = $_GET['uuid'] ?? '';
if ($uuid==='') { die('Missing uuid'); }

$res = $conn->query("SELECT id FROM threads WHERE thread_uuid='".$conn->real_escape_string($uuid)."' LIMIT 1");
if (!$res || $res->num_rows===0) { die('Thread not found'); }
$row = $res->fetch_assoc(); $thread_id = (int)$row['id'];

/* latest offer */
$o = $conn->query("SELECT id,version,party,role,data,riders,created_at
                   FROM offers WHERE thread_id=$thread_id
                   ORDER BY version DESC LIMIT 1")->fetch_assoc();
if (!$o) { die('No offers yet'); }
$data = json_decode($o['data'] ?? '{}', true) ?: [];

/* Simple labels map (fallbacks if missing) */
$labels = [
 'vessel_name'=>'Vessel Name / TBN',
 'gear'=>'Gear Type & Capacity',
 'class_flag'=>'Class & Flag',
 'built_year'=>'Built Year',
 'last_3_cargoes'=>'Last 3 cargoes',
 'pi_coverage'=>'P&I Coverage',
 'sanctions_ok'=>'Sanctions/Warranties',
 'cargo_type'=>'Cargo Type',
 'quantity_tol'=>'Quantity & Tolerance',
 'imo_ok'=>'Lawful/IMO compliant',
 'load_port'=>'Load Port',
 'disch_port'=>'Discharge Port',
 'disch_max_draft'=>'Max Draft Guaranteed (Disch)',
 'allow_other_ports'=>'Other Ports on Open Book?',
 'laycan_start'=>'Laycan Start',
 'laycan_end'=>'Laycan End',
 'option_to_narrow'=>'Option to Narrow',
 'freight'=>'Freight',
 'load_rate'=>'Load Rate',
 'disch_rate'=>'Discharge Rate',
 'load_rate_opt'=>'Load Rate Option',
 'demurrage'=>'Demurrage',
 'despatch'=>'Despatch',
 'laytime_type'=>'Laytime Type',
 'nor_rule'=>'NOR/Laytime Rule',
 'holiday_clause'=>'Holiday Exclusions',
 'payment_terms'=>'Freight Payment Terms',
 'dem_settlement'=>'Dem/Des Settlement',
 'commission'=>'Commission',
 'cp_base'=>'Base CP Format',
 'attach_clauses'=>'Attach Custom Clauses',
 'editable_riders'=>'Allow Editable Riders',
 'riders'=>'Riders / Special Clauses',
 'contact_person'=>'Ops Contact Person',
 'ops_company'=>'Company',
 'ops_email'=>'Email',
 'ops_notes'=>'Operational Notes',
 'fp_clause'=>'Free Pratique Delay Counts?',
 'agent_nomination'=>'Agent Nomination',
 'eta_notices'=>'ETA Notices',
 'final_disport_rule'=>'Final Disport Declaration Rule'
];

/* Preferred order for printing */
$order = array_keys($labels);

/* Build HTML */
$style = "
  <style>
    body{font-family:DejaVu Sans,Arial,sans-serif;font-size:12px;color:#0f172a}
    h1{font-size:18px;margin:0 0 6px}
    h2{font-size:14px;margin:14px 0 6px}
    table{width:100%;border-collapse:collapse;margin:6px 0 12px}
    th,td{border:1px solid #e5e7eb;padding:6px;vertical-align:top}
    th{background:#f3f4f6;text-align:left;width:28%}
    .muted{color:#6b7280}
  </style>
";
$head = "<h1>Fixture Recap</h1>
<div class='muted'>Thread: ".htmlspecialchars($uuid)." &nbsp; | &nbsp; Latest Version: v".$o['version']." &nbsp; | &nbsp; ".$o['created_at']."</div>
<h2>Main Terms</h2>";

$rows = '';
foreach ($order as $k){
  if (!array_key_exists($k,$data)) continue; // skip unknowns
  $label = htmlspecialchars($labels[$k] ?? ucwords(str_replace('_',' ',$k)));
  $val = htmlspecialchars((string)($data[$k] ?? ''));
  $rows .= "<tr><th>$label</th><td>$val</td></tr>";
}

/* also append any extra custom keys not in our map */
foreach ($data as $k=>$v){
  if (in_array($k,$order, true)) continue;
  $label = htmlspecialchars(ucwords(str_replace('_',' ',$k)));
  $val = htmlspecialchars((string)$v);
  $rows .= "<tr><th>$label</th><td>$val</td></tr>";
}

$ridersHtml = '';
if (!empty($o['riders'])) {
  $ridersHtml = "<h2>Riders / Special Clauses</h2><table><tr><td>".nl2br(htmlspecialchars($o['riders']))."</td></tr></table>";
}

/* acceptances (optional) */
$accRows = '';
$acc = $conn->query("SELECT party,accepted_at FROM acceptances WHERE offer_id=".$o['id']." ORDER BY accepted_at ASC");
if ($acc && $acc->num_rows){
  while($a = $acc->fetch_assoc()){
    $accRows .= "<tr><td>".htmlspecialchars($a['party'])."</td><td>".htmlspecialchars($a['accepted_at'])."</td></tr>";
  }
}

$accHtml = $accRows ? "<h2>Acceptances</h2><table><tr><th>Party</th><th>Accepted At</th></tr>$accRows</table>" : '';

$html = $style . $head . "<table>$rows</table>" . $ridersHtml . $accHtml;

$dompdf = new Dompdf();
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4','portrait');
$dompdf->render();
$dompdf->stream('fixture_recap.pdf', ['Attachment'=>false]);
