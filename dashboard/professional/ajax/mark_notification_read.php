<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Require database connection
require_once '../../../config/db_connect.php';

// Check if notification_id is provided
if (!isset($_POST['notification_id']) || empty($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
    exit;
}

$notification_id = intval($_POST['notification_id']);
$user_id = $_SESSION['user_id'];

// Prepare statement to update notification
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $notification_id, $user_id);
$stmt->execute();

// Check if update was successful
if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
}

$stmt->close();
$conn->close();
?> 