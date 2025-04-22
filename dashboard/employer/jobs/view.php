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
    header("Location: index.php");
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

// For demo purposes, create a sample job
$job = [
    'id' => $job_id,
    'title' => 'Senior Software Engineer',
    'location' => 'San Francisco, CA',
    'type' => 'full_time',
    'salary_min' => 120000,
    'salary_max' => 160000,
    'description' => "We are looking for a Senior Software Engineer to join our dynamic team. The ideal candidate will have extensive experience in full-stack development, with a focus on scalable web applications.\n\nResponsibilities include designing and implementing new features, optimizing application performance, and mentoring junior developers.",
    'requirements' => "- 5+ years of experience in software development\n- Strong proficiency in JavaScript, React, and Node.js\n- Experience with database design and SQL\n- Excellent problem-solving abilities\n- Bachelor's degree in Computer Science or related field",
    'benefits' => "- Competitive salary and equity\n- Health, dental, and vision insurance\n- 401(k) matching\n- Flexible work arrangements\n- Professional development budget",
    'status' => 'active',
    'visa_type' => 'H-1B',
    'created_at' => '2023-08-15',
    'deadline' => '2023-10-15',
    'applications_count' => 12
];

// Handle status update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    // Update job status in the database
    // updateJobStatus($job_id, $new_status);
    
    $success_message = "Job status updated successfully!";
    $job['status'] = $new_status; // Update the local job data
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $job['title']; ?> - Employer Dashboard - Visafy</title>
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
                <h1><?php echo $job['title']; ?></h1>
                <div>
                    <a href="edit.php?id=<?php echo $job['id']; ?>" class="button">Edit Job</a>
                    <a href="index.php" class="button button-secondary">Back to Listings</a>
                </div>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card mb-20">
                <div class="job-header">
                    <div class="job-status">
                        <span class="badge <?php echo $job['status']; ?>"><?php echo ucfirst($job['status']); ?></span>
                        <span class="job-date">Posted on <?php echo date('M d, Y', strtotime($job['created_at'])); ?></span>
                    </div>
                    
                    <form method="POST" action="view.php?id=<?php echo $job['id']; ?>" class="status-form">
                        <div class="form-row">
                            <div class="form-group">
                                <select name="status" class="form-control">
                                    <option value="active" <?php echo $job['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="draft" <?php echo $job['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="closed" <?php echo $job['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    <option value="expired" <?php echo $job['status'] == 'expired' ? 'selected' : ''; ?>>Expired</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="button button-small">Update Status</button>
                        </div>
                    </form>
                </div>
                
                <div class="job-info">
                    <div class="job-detail">
                        <span class="label">Location:</span>
                        <span class="value"><?php echo $job['location']; ?></span>
                    </div>
                    
                    <div class="job-detail">
                        <span class="label">Job Type:</span>
                        <span class="value"><?php echo ucwords(str_replace('_', ' ', $job['type'])); ?></span>
                    </div>
                    
                    <div class="job-detail">
                        <span class="label">Salary Range:</span>
                        <span class="value">$<?php echo number_format($job['salary_min']); ?> - $<?php echo number_format($job['salary_max']); ?></span>
                    </div>
                    
                    <div class="job-detail">
                        <span class="label">Visa Sponsorship:</span>
                        <span class="value"><?php echo $job['visa_type']; ?></span>
                    </div>
                    
                    <div class="job-detail">
                        <span class="label">Application Deadline:</span>
                        <span class="value"><?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                    </div>
                    
                    <div class="job-detail">
                        <span class="label">Applications:</span>
                        <span class="value">
                            <a href="../applications/index.php?job_id=<?php echo $job['id']; ?>">
                                <?php echo $job['applications_count']; ?> applications received
                            </a>
                        </span>
                    </div>
                </div>
                
                <div class="job-content">
                    <h3>Job Description</h3>
                    <div class="content-section">
                        <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                    </div>
                    
                    <h3>Requirements</h3>
                    <div class="content-section">
                        <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                    </div>
                    
                    <?php if (!empty($job['benefits'])): ?>
                    <h3>Benefits</h3>
                    <div class="content-section">
                        <?php echo nl2br(htmlspecialchars($job['benefits'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="action-buttons">
                    <a href="edit.php?id=<?php echo $job['id']; ?>" class="button">Edit Job</a>
                    <a href="#" class="button button-danger" id="deleteJob">Delete Job</a>
                </div>
            </div>
            
            <div class="card">
                <h2 class="card-title">Recent Applications</h2>
                
                <?php if ($job['applications_count'] > 0): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Applicant</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Applied Date</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Status</th>
                        <th style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                    </tr>
                    <!-- Sample applications - would be populated from database -->
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">John Doe</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">2023-08-18</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                            <span class="badge active">Pending</span>
                        </td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">
                            <a href="../applications/view.php?id=101" class="button button-small">View</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Jane Smith</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">2023-08-17</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                            <span class="badge active">Pending</span>
                        </td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">
                            <a href="../applications/view.php?id=102" class="button button-small">View</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Alex Johnson</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">2023-08-16</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                            <span class="badge closed">Rejected</span>
                        </td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">
                            <a href="../applications/view.php?id=103" class="button button-small">View</a>
                        </td>
                    </tr>
                </table>
                
                <div class="view-all">
                    <a href="../applications/index.php?job_id=<?php echo $job['id']; ?>" class="button button-secondary">View All Applications</a>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <p>No applications have been received for this job yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Delete confirmation
        document.getElementById('deleteJob').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to delete this job listing? This action cannot be undone.')) {
                window.location.href = 'delete_job.php?id=<?php echo $job['id']; ?>';
            }
        });
    </script>
</body>
</html> 