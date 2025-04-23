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

$user_id = $_SESSION['user_id'];

// Prepare statement to update all unread notifications
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();

// Check if update was successful (there might be no unread notifications)
if ($stmt->affected_rows >= 0) {
    echo json_encode(['success' => true, 'count' => $stmt->affected_rows]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notifications as read']);
}

$stmt->close();
$conn->close();
?> 