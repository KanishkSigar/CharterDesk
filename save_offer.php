<?php
header('Content-Type: application/json');
require 'db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { echo json_encode(['status'=>'error','message'=>'invalid json']); exit; }

$uuid  = $input['thread_uuid'] ?? '';
$party = $input['party'] ?? 'User';
$role  = $input['role'] ?? 'Charterer';
$data  = $input['data'] ?? [];
$riders= $input['riders'] ?? '';

if ($uuid==='') { echo json_encode(['status'=>'error','message'=>'missing thread']); exit; }

$res = $conn->query("SELECT id FROM threads WHERE thread_uuid='".$conn->real_escape_string($uuid)."' LIMIT 1");
if (!$res || $res->num_rows===0) { echo json_encode(['status'=>'error','message'=>'thread not found']); exit; }
$row = $res->fetch_assoc(); $thread_id = (int)$row['id'];

/* next version */
$rver = $conn->query("SELECT COALESCE(MAX(version),0)+1 AS v FROM offers WHERE thread_id=$thread_id");
$vrow = $rver->fetch_assoc(); $version = (int)$vrow['v'];

/* store FULL data json */
$json = $conn->real_escape_string(json_encode($data, JSON_UNESCAPED_UNICODE));
$rp   = $conn->real_escape_string($party);
$rr   = $conn->real_escape_string($role);
$rd   = $conn->real_escape_string($riders);

$ins = "INSERT INTO offers(thread_id,version,party,role,data,riders,created_at)
        VALUES ($thread_id,$version,'$rp','$rr','$json','$rd',NOW())";
if (!$conn->query($ins)) {
  echo json_encode(['status'=>'error','message'=>'db insert failed']); exit;
}

echo json_encode(['status'=>'success','version'=>$version]);
