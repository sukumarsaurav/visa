<?php
session_start();

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../../login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];

// Check if job ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=invalid_id");
    exit();
}

$job_id = intval($_GET['id']);

// Database connection would be here
// $job = getJobById($job_id, $user_id);
// if (!$job) {
//     // Job not found or doesn't belong to this employer
//     header("Location: index.php?error=job_not_found");
//     exit();
// }

// Delete job
// deleteJob($job_id, $user_id);

// Redirect with success message
header("Location: index.php?success=job_deleted");
exit();
?> 