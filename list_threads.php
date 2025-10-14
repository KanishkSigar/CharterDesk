<?php
header('Content-Type: application/json');
include 'db_connect.php';
$res = $conn->query("SELECT * FROM threads ORDER BY created_at DESC");
$out = [];
while($r = $res->fetch_assoc()) $out[] = $r;
echo json_encode(['status'=>'success','threads'=>$out]);
$conn->close();
?>
