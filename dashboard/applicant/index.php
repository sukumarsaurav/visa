<?php
session_start();

// Check if user is logged in and is an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'applicant') {
    header("Location: ../../login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
// Database connection would be here
// Get user's applications
// $applications = getUserApplications($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Dashboard - Visafy</title>
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
                <li class="nav-item"><a href="index.php" class="nav-link active">Dashboard</a></li>
                <li class="nav-item"><a href="applications.php" class="nav-link">Applications</a></li>
                <li class="nav-item"><a href="documents.php" class="nav-link">Documents</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link">Profile</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
            </div>
            
            <div class="card">
                <h2 class="card-title">Active Applications</h2>
                <div class="application-summary">
                    <?php
                    // This would be populated from the case_applications table
                    $sample_applications = [
                        [
                            'reference_number' => 'VISA-2023-001',
                            'status' => 'pending_documents',
                            'visa_type' => 'H-1B Work Visa',
                            'professional' => 'Jane Smith'
                        ]
                    ];
                    
                    foreach ($sample_applications as $app): ?>
                        <div class="application-item">
                            <div class="status-badge status-<?php echo $app['status']; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $app['status'])); ?>
                            </div>
                            <h3><?php echo htmlspecialchars($app['visa_type']); ?></h3>
                            <p>Reference: <?php echo htmlspecialchars($app['reference_number']); ?></p>
                            <p>Professional: <?php echo htmlspecialchars($app['professional']); ?></p>
                            <a href="applications.php?id=<?php echo urlencode($app['reference_number']); ?>" class="button">View Details</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="card">
                <h2 class="card-title">Required Documents</h2>
                <div class="document-checklist">
                    <ul>
                        <li class="completed">
                            <span class="checkmark">✓</span>
                            Passport
                        </li>
                        <li class="completed">
                            <span class="checkmark">✓</span>
                            Resume/CV
                        </li>
                        <li class="pending">
                            <span class="cross">✗</span>
                            Educational Certificates
                            <a href="documents.php" class="button button-small">Upload</a>
                        </li>
                        <li class="pending">
                            <span class="cross">✗</span>
                            Employment Letter
                            <a href="documents.php" class="button button-small">Upload</a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <h2 class="card-title">Recent Updates</h2>
                <div class="updates-list">
                    <?php
                    // This would be populated from the notifications table
                    $notifications = [
                        [
                            'title' => 'Document Review Complete',
                            'message' => 'Your passport has been verified successfully.',
                            'created_at' => '2023-07-05 14:30:00'
                        ],
                        [
                            'title' => 'Application Status Update',
                            'message' => 'Your H-1B application is now under review.',
                            'created_at' => '2023-07-04 09:15:00'
                        ]
                    ];
                    
                    foreach ($notifications as $notification): ?>
                        <div class="update-item">
                            <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                            <p><?php echo htmlspecialchars($notification['message']); ?></p>
                            <small><?php echo date('F j, Y g:i A', strtotime($notification['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
