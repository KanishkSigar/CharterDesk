<?php
include 'db_connect.php';

$uuid = $_GET['uuid'] ?? '';

$sql = "SELECT * FROM sessions WHERE session_uuid='$uuid'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  echo json_encode($result->fetch_assoc());
} else {
  echo json_encode(["status" => "not_found"]);
}

$conn->close();
?>
