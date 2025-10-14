<?php
include 'db_connect.php';

// generate unique UUID for thread
$uuid = 'th_' . uniqid();
$created_by = $_POST['created_by'] ?? 'Unknown';
$status = 'pending';

// Insert thread into DB
$stmt = $conn->prepare("INSERT INTO threads (thread_uuid, created_by, status, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("sss", $uuid, $created_by, $status);
$stmt->execute();

// Get the auto-increment thread ID
$thread_id = $stmt->insert_id;

// Return thread_uuid to frontend
echo json_encode([
  'status' => 'success',
  'thread_id' => $thread_id,
  'thread_uuid' => $uuid
]);

$stmt->close();
$conn->close();
?>
