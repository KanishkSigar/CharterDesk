<?php
header('Content-Type: application/json');
include 'db_connect.php';

$input = json_decode(file_get_contents("php://input"), true);
$thread_uuid = $conn->real_escape_string($input['thread_uuid'] ?? '');
$fields = $input['fields'] ?? [];

if(!$thread_uuid){ echo json_encode(['status'=>'error','message'=>'Missing thread_uuid']); exit; }
if(!is_array($fields) || empty($fields)){ echo json_encode(['status'=>'error','message'=>'No fields to lock']); exit; }

$res = $conn->query("SELECT id, locked_fields FROM threads WHERE thread_uuid='$thread_uuid' LIMIT 1");
if($res->num_rows==0){ echo json_encode(['status'=>'error','message'=>'Thread not found']); exit; }
$row = $res->fetch_assoc();
$thread_id = (int)$row['id'];

$current = [];
if (!empty($row['locked_fields'])) {
  $tmp = json_decode($row['locked_fields'], true);
  if (is_array($tmp)) $current = $tmp;
}

$merged = array_values(array_unique(array_merge($current, array_map('strval',$fields))));
$new_json = $conn->real_escape_string(json_encode($merged, JSON_UNESCAPED_UNICODE));

$ok = $conn->query("UPDATE threads SET locked_fields='$new_json' WHERE id=$thread_id");
if($ok){
  echo json_encode(['status'=>'success','locked_fields'=>$merged]);
}else{
  echo json_encode(['status'=>'error','message'=>$conn->error]);
}
$conn->close();
