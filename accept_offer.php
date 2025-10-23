<?php
header('Content-Type: application/json');
require 'db.php';

$in = json_decode(file_get_contents('php://input'), true);
$offer_id = $in['offer_id'] ?? null;
$party    = $in['party']    ?? 'User';
if (!$offer_id) { echo json_encode(["status"=>"error","message"=>"Missing offer_id"]); exit; }

// mark accepted
$up = $pdo->prepare("UPDATE offers SET accepted_by=?, accepted_at=NOW() WHERE id=?");
$ok = $up->execute([$party, (int)$offer_id]);

// thread uuid for recap url
$getU = $pdo->prepare("SELECT thread_uuid FROM offers WHERE id=?");
$getU->execute([(int)$offer_id]);
$uuid = $getU->fetchColumn();

echo json_encode($ok ? [
  "status"=>"success",
  "message"=>"Offer accepted.",
  "recap_url"=>"generate_recap.php?uuid=".$uuid
] : ["status"=>"error","message"=>"Accept failed"]);
