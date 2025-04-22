<?php
session_start();

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../../login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];

// Database connection would be here
// $employer = getEmployerData($user_id);

// Handle job status updates if applicable
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    // Process status update
    $success_message = "Job status updated successfully!";
}

// Get jobs - filter by status if specified
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
// $jobs = getJobsByEmployer($user_id, $status);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Listings - Employer Dashboard - Visafy</title>
    <link rel="stylesheet" href="../../styles.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="profile-section">
                <img src="../../../assets/images/employer-avatar.png" alt="Profile" class="profile-image">
                <h3><?php echo $_SESSION['username']; ?></h3>
                <p>Employer</p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item"><a href="../index.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="index.php" class="nav-link active">Job Listings</a></li>
                <li class="nav-item"><a href="../applications/index.php" class="nav-link">Applications</a></li>
                <li class="nav-item"><a href="../profile.php" class="nav-link">Company Profile</a></li>
                <li class="nav-item"><a href="../messages.php" class="nav-link">Messages</a></li>
                <li class="nav-item"><a href="../settings.php" class="nav-link">Settings</a></li>
                <li class="nav-item"><a href="../../../logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Job Listings</h1>
                <div>
                    <a href="create.php" class="button">Post New Job</a>
                </div>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2 class="card-title">Your Job Listings</h2>
                
                <div class="filter-section">
                    <form method="GET" action="index.php" class="filter-form">
                        <div class="form-group inline">
                            <label for="status_filter">Filter by Status:</label>
                            <select id="status_filter" name="status" class="form-control" onchange="this.form.submit()">
                                <option value="all" <?php echo ($status == 'all') ? 'selected' : ''; ?>>All Jobs</option>
                                <option value="active" <?php echo ($status == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="draft" <?php echo ($status == 'draft') ? 'selected' : ''; ?>>Draft</option>
                                <option value="expired" <?php echo ($status == 'expired') ? 'selected' : ''; ?>>Expired</option>
                                <option value="closed" <?php echo ($status == 'closed') ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>
                    </form>
                </div>
                
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Job Title</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Location</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Posted Date</th>
                        <th style="text-align: center; padding: 8px; border-bottom: 1px solid #ddd;">Applications</th>
                        <th style="text-align: center; padding: 8px; border-bottom: 1px solid #ddd;">Status</th>
                        <th style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                    </tr>
                    <!-- Sample jobs - would be populated from database -->
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Senior Software Engineer</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">San Francisco, CA</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">2023-08-15</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: center;">12</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: center;">
                            <span class="badge active">Active</span>
                        </td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">
                            <a href="view.php?id=1" class="button button-small">View</a>
                            <a href="edit.php?id=1" class="button button-small">Edit</a>
                            <a href="#" class="button button-small button-delete" data-id="1">Delete</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Marketing Manager</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Remote</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">2023-07-28</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: center;">8</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: center;">
                            <span class="badge active">Active</span>
                        </td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">
                            <a href="view.php?id=2" class="button button-small">View</a>
                            <a href="edit.php?id=2" class="button button-small">Edit</a>
                            <a href="#" class="button button-small button-delete" data-id="2">Delete</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">UX Designer</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">New York, NY</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">2023-07-10</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: center;">4</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: center;">
                            <span class="badge draft">Draft</span>
                        </td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">
                            <a href="view.php?id=3" class="button button-small">View</a>
                            <a href="edit.php?id=3" class="button button-small">Edit</a>
                            <a href="#" class="button button-small button-delete" data-id="3">Delete</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Data Scientist</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Austin, TX</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">2023-06-05</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: center;">15</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: center;">
                            <span class="badge closed">Closed</span>
                        </td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">
                            <a href="view.php?id=4" class="button button-small">View</a>
                            <a href="edit.php?id=4" class="button button-small">Edit</a>
                            <a href="#" class="button button-small button-delete" data-id="4">Delete</a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Delete confirmation
        document.querySelectorAll('.button-delete').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const jobId = this.getAttribute('data-id');
                if (confirm('Are you sure you want to delete this job listing? This action cannot be undone.')) {
                    window.location.href = 'delete_job.php?id=' + jobId;
                }
            });
        });
    </script>
</body>
</html> 