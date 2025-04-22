<?php
session_start();

// Check if user is logged in and is an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'applicant') {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// Database connection would be here
// Get user data from users table
// $user = getUserData($user_id);

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update user profile
        // updateUserProfile($user_id, $_POST);
        $success_message = "Profile updated successfully!";
    } elseif (isset($_POST['change_password'])) {
        // Change password logic
        // changePassword($user_id, $_POST);
        $success_message = "Password changed successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Applicant Dashboard - Visafy</title>
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
                <li class="nav-item"><a href="applications.php" class="nav-link">Applications</a></li>
                <li class="nav-item"><a href="documents.php" class="nav-link">Documents</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link active">Profile</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>My Profile</h1>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2 class="card-title">Personal Information</h2>
                <form method="POST" action="profile.php" enctype="multipart/form-data">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_SESSION['name']); ?>" required class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" readonly class="form-control">
                        <p class="form-hint">Email cannot be changed</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="profile_picture">Profile Picture</label>
                        <input type="file" id="profile_picture" name="profile_picture" class="form-control" accept="image/*">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="button">Update Profile</button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <h2 class="card-title">Change Password</h2>
                <form method="POST" action="profile.php">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required class="form-control">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="button">Change Password</button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <h2 class="card-title">Account Settings</h2>
                <form method="POST" action="profile.php">
                    <div class="form-group">
                        <label for="notifications">Email Notifications</label>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="notifications[]" value="application_updates" checked>
                                Application Updates
                            </label>
                            <label>
                                <input type="checkbox" name="notifications[]" value="document_requests" checked>
                                Document Requests
                            </label>
                            <label>
                                <input type="checkbox" name="notifications[]" value="professional_messages" checked>
                                Messages from Professionals
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="button">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 