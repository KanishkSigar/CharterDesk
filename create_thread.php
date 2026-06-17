<?php
include 'db_connect.php';

// Generate unique UUID for each new negotiation
$uuid = 'th_' . uniqid();
$created_by = $_POST['created_by'] ?? 'Unknown';
$title = trim($_POST['title'] ?? '');
if ($title === '') $title = 'Negotiation';
$status = 'open';

// Prepare and insert thread
$stmt = $conn->prepare("INSERT INTO threads (thread_uuid, created_by, status, title, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("ssss", $uuid, $created_by, $status, $title);

if ($stmt->execute()) {
    $thread_id = $stmt->insert_id; // auto-generated ID

    echo json_encode([
        'status' => 'success',
        'message' => 'Thread created successfully',
        'thread_id' => $thread_id,
        'thread_uuid' => $uuid
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to create thread: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
