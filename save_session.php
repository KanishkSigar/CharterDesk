<?php
header('Content-Type: application/json');
include 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
  echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
  exit;
}

$uuid     = uniqid();
$role     = $conn->real_escape_string($data['role']);
$vessel   = $conn->real_escape_string($data['vessel']);
$cargo    = $conn->real_escape_string($data['cargo']);
$quantity = $conn->real_escape_string($data['quantity']);
$laycan   = $conn->real_escape_string($data['laycan']);
$freight  = $conn->real_escape_string($data['freight']);
$riders   = $conn->real_escape_string($data['riders']);

$sql = "INSERT INTO sessions (session_uuid, role, vessel, cargo, quantity, laycan, freight, riders)
        VALUES ('$uuid','$role','$vessel','$cargo','$quantity','$laycan','$freight','$riders')";

if ($conn->query($sql) === TRUE) {
  echo json_encode(["status" => "success", "uuid" => $uuid]);
} else {
  echo json_encode(["status" => "error", "message" => $conn->error]);
}

$conn->close();
?>
