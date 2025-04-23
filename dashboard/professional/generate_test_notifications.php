<?php
// Start session
session_start();

// Include database connection
require_once '../../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "User not authenticated. Please log in.";
    exit;
}

$user_id = $_SESSION['user_id'];

// Sample notification titles and messages
$notifications = [
    [
        'title' => 'New Booking Request',
        'message' => 'You have received a new booking request from John Doe for a consultation on ' . date('F j, Y', strtotime('+2 days')) . '.'
    ],
    [
        'title' => 'Document Uploaded',
        'message' => 'A new document "Visa Application Form" has been uploaded by your client Maria Garcia.'
    ],
    [
        'title' => 'Message Received',
        'message' => 'You have a new message from Alexander Williams regarding case #VF-' . rand(1000, 9999) . '.'
    ],
    [
        'title' => 'Appointment Reminder',
        'message' => 'Reminder: You have a video call scheduled with Emma Thompson tomorrow at ' . date('g:i A', strtotime('+1 day +' . rand(9, 17) . ' hours')) . '.'
    ],
    [
        'title' => 'Case Status Updated',
        'message' => 'The status of case #VF-' . rand(1000, 9999) . ' has been updated to "In Progress".'
    ],
    [
        'title' => 'New Review',
        'message' => 'You received a new ' . rand(3, 5) . '-star review from Robert Johnson.'
    ],
    [
        'title' => 'Payment Received',
        'message' => 'You have received a payment of $' . rand(150, 500) . ' for consultation services.'
    ],
    [
        'title' => 'System Update',
        'message' => 'Visafy platform will undergo maintenance on ' . date('F j', strtotime('+5 days')) . ' from 2:00 AM to 4:00 AM UTC.'
    ]
];

// Number of test notifications to generate
$count = isset($_GET['count']) ? intval($_GET['count']) : 5;
if ($count > 20) $count = 20; // Limit to 20 notifications at a time

// Generate random timestamps within the past 30 days
function generateRandomTimestamp() {
    $days = rand(0, 30);
    $hours = rand(0, 23);
    $minutes = rand(0, 59);
    return date('Y-m-d H:i:s', strtotime("-$days days -$hours hours -$minutes minutes"));
}

// Insert test notifications
$insert_query = "INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert_query);

$success_count = 0;
for ($i = 0; $i < $count; $i++) {
    $notification = $notifications[array_rand($notifications)];
    $title = $notification['title'];
    $message = $notification['message'];
    $is_read = rand(0, 1); // Randomly set as read or unread
    $created_at = generateRandomTimestamp();
    
    $stmt->bind_param("issis", $user_id, $title, $message, $is_read, $created_at);
    if ($stmt->execute()) {
        $success_count++;
    }
}

$stmt->close();
$conn->close();

// Output result
echo "<h1>Test Notifications Generator</h1>";
echo "<p>Successfully generated $success_count test notifications for user ID: $user_id</p>";
echo "<p><a href='notifications.php'>Go to Notifications Page</a></p>";
?> 