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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard - Visafy</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="profile-section">
                <img src="../../assets/images/company-logo.png" alt="Company" class="profile-image">
                <h3><?php echo $_SESSION['company_name']; ?></h3>
                <p>Employer</p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item"><a href="index.php" class="nav-link active">Dashboard</a></li>
                <li class="nav-item"><a href="job-postings.php" class="nav-link">Job Postings</a></li>
                <li class="nav-item"><a href="applicants.php" class="nav-link">Applicants</a></li>
                <li class="nav-item"><a href="company-profile.php" class="nav-link">Company Profile</a></li>
                <li class="nav-item"><a href="visa-requirements.php" class="nav-link">Visa Requirements</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Welcome, <?php echo $_SESSION['company_name']; ?></h1>
                <div>
                    <a href="post-job.php" class="button">Post New Job</a>
                </div>
            </div>
            
            <div class="card">
                <h2 class="card-title">Active Job Postings</h2>
                <div>
                    <!-- This would be populated from database -->
                    <p>You have <strong>3</strong> active job postings</p>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Position</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Applicants</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Posted Date</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">Senior Developer</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">12</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">2023-05-15</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <a href="view-applicants.php?job_id=1" class="button">View</a>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">UX Designer</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">8</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">2023-05-20</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <a href="view-applicants.php?job_id=2" class="button">View</a>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 8px;">Project Manager</td>
                            <td style="padding: 8px;">5</td>
                            <td style="padding: 8px;">2023-05-25</td>
                            <td style="padding: 8px;">
                                <a href="view-applicants.php?job_id=3" class="button">View</a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="card">
                <h2 class="card-title">Recent Applications</h2>
                <div>
                    <!-- This would be populated from database -->
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Applicant</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Position</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Applied On</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Status</th>
                            <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">John Doe</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">Senior Developer</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">2023-06-01</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <div class="status-badge status-pending">Pending Review</div>
                            </td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <a href="applicant-profile.php?id=101" class="button">Review</a>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">Jane Smith</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">UX Designer</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">2023-06-02</td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <div class="status-badge status-approved">Approved</div>
                            </td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <a href="applicant-profile.php?id=102" class="button">View</a>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
