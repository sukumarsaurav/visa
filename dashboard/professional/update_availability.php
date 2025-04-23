<?php
// This file is for AJAX requests, so we don't need the full header/footer
// Include necessary authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a professional
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'professional') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$status = $data['status'] ?? null;

// Validate status
$valid_statuses = ['available', 'busy', 'unavailable'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// Include database connection
require_once '../../config/db_connect.php';

try {
    // Update availability status
    $stmt = $conn->prepare("
        UPDATE professionals 
        SET availability_status = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE user_id = ?
    ");
    
    $stmt->bind_param("si", $status, $_SESSION['user_id']);
    $success = $stmt->execute();
    
    if ($success) {
        $_SESSION['availability_status'] = $status;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    
} catch(Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System error']);
}

// Close the statement
$stmt->close(); 