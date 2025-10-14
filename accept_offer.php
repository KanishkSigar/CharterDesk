<?php
header('Content-Type: application/json');
include 'db_connect.php';

$input = json_decode(file_get_contents("php://input"), true);
$offer_id = (int)($input['offer_id'] ?? 0);
$party = $conn->real_escape_string($input['party'] ?? 'Unknown');

if(!$offer_id) { echo json_encode(['status'=>'error','message'=>'No offer_id']); exit; }

// insert acceptance
$stmt = $conn->prepare("INSERT INTO acceptances (offer_id, party) VALUES (?, ?)");
$stmt->bind_param("is", $offer_id, $party);
$stmt->execute();
$stmt->close();

// check how many unique parties accepted this offer
$res = $conn->query("SELECT COUNT(DISTINCT party) as cnt FROM acceptances WHERE offer_id=$offer_id");
$row = $res->fetch_assoc();
$count = (int)$row['cnt'];

// fetch thread id for this offer
$res2 = $conn->query("SELECT thread_id, version FROM offers WHERE id=$offer_id");
$row2 = $res2->fetch_assoc();
$thread_id = (int)$row2['thread_id'];
$version = (int)$row2['version'];

if($count >= 2){
  // mark thread as agreed
  $conn->query("UPDATE threads SET status='agreed' WHERE id=$thread_id");
  // you can call generate_recap here or leave to manual trigger
  // include 'generate_recap.php' or send a call to it
  echo json_encode(['status'=>'success','message'=>'Offer accepted by at least two parties. Thread marked agreed.','version'=>$version]);
} else {
  echo json_encode(['status'=>'success','message'=>'Your acceptance was recorded. Waiting for counterparty.']);
}

$conn->close();
?>
