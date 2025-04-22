<?php
session_start();

// Check if user is logged in and is an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'applicant') {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// Database connection would be here
// Get user's applications from case_applications table
// $applications = getUserApplications($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - Applicant Dashboard - Visafy</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="profile-section">
                <img src="<?php echo isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : '../../assets/images/default-avatar.png'; ?>" alt="Profile" class="profile-image">
                <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
                <p>Applicant</p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item"><a href="index.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="applications.php" class="nav-link active">Applications</a></li>
                <li class="nav-item"><a href="documents.php" class="nav-link">Documents</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link">Profile</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>My Applications</h1>
                <div>
                    <a href="new_application.php" class="button">Start New Application</a>
                </div>
            </div>
            
            <div class="card">
                <h2 class="card-title">Active Applications</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Reference #</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Visa Type</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Professional</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Status</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Created</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                    </tr>
                    <?php
                    // Sample data - would be populated from case_applications table
                    $active_applications = [
                        [
                            'reference_number' => 'VISA-2023-001',
                            'visa_type' => 'H-1B Work Visa',
                            'professional' => 'Jane Smith',
                            'status' => 'pending_documents',
                            'created_at' => '2023-06-15'
                        ]
                    ];
                    
                    foreach ($active_applications as $app): ?>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($app['reference_number']); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($app['visa_type']); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($app['professional']); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <div class="status-badge status-<?php echo $app['status']; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $app['status'])); ?>
                                </div>
                            </td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo date('M j, Y', strtotime($app['created_at'])); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <a href="application_details.php?ref=<?php echo urlencode($app['reference_number']); ?>" class="button button-small">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            
            <div class="card">
                <h2 class="card-title">Completed Applications</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Reference #</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Visa Type</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Professional</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Status</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Completed</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                    </tr>
                    <?php
                    // Sample data - would be populated from case_applications table
                    $completed_applications = [
                        [
                            'reference_number' => 'VISA-2022-099',
                            'visa_type' => 'B-2 Tourist Visa',
                            'professional' => 'Michael Brown',
                            'status' => 'approved',
                            'completed_at' => '2023-01-10'
                        ]
                    ];
                    
                    foreach ($completed_applications as $app): ?>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($app['reference_number']); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($app['visa_type']); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($app['professional']); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <div class="status-badge status-<?php echo $app['status']; ?>">
                                    <?php echo ucwords($app['status']); ?>
                                </div>
                            </td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo date('M j, Y', strtotime($app['completed_at'])); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <a href="application_details.php?ref=<?php echo urlencode($app['reference_number']); ?>" class="button button-small">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 