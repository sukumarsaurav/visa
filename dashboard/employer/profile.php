<?php
session_start();

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../../login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$employer = null;
$success_message = '';
$error_message = '';

// Include database connection
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

try {
    // Fetch employer data from users table 
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'employer'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $error_message = "Employer profile not found.";
    } else {
        $employer = $result->fetch_assoc();
    }
} catch(Exception $e) {
    // Log error and show generic message
    error_log("Database Error: " . $e->getMessage());
    $error_message = "System is temporarily unavailable. Please try again later.";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        $name = $conn->real_escape_string($_POST['name']);
        $phone = $conn->real_escape_string($_POST['phone'] ?? '');
        $company = $conn->real_escape_string($_POST['company'] ?? '');
        $position = $conn->real_escape_string($_POST['position'] ?? '');
        
        // Handle profile picture upload
        $profile_picture = $employer['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_picture']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = 'employer_' . $user_id . '_' . time() . '.' . $ext;
                $upload_dir = '../../uploads/profiles/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $new_filename)) {
                    $profile_picture = $new_filename;
                }
            }
        }
        
        // Update employer data
        $stmt = $conn->prepare("UPDATE users SET name = ?, profile_picture = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $profile_picture, $user_id);
        
        if ($stmt->execute()) {
            // Commit transaction
            $conn->commit();
            $success_message = "Profile updated successfully.";
            
            // Refresh employer data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'employer'");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $employer = $result->fetch_assoc();
        } else {
            throw new Exception("Failed to update profile.");
        }
    } catch (Exception $e) {
        // Rollback in case of error
        $conn->rollback();
        $error_message = "Profile update failed: " . $e->getMessage();
    }
}

// Set page variables
$page_title = "My Profile";
$page_header = "Employer Profile";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Visafy</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        /* Page-specific CSS */
        .profile-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .profile-sidebar {
            flex: 1;
            min-width: 250px;
        }
        
        .profile-main {
            flex: 3;
            min-width: 400px;
        }
        
        .profile-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .profile-image-container {
            text-align: center;
            padding: 20px;
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f0f0f0;
        }
        
        .profile-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #6c757d;
            margin: 0 auto;
        }
        
        .profile-header {
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
        }
        
        .profile-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }
        
        .profile-body {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-hint {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 4px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-row {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .status-row span:first-child {
            margin-right: 8px;
            min-width: 120px;
        }
        
        .button {
            background-color: #4a6fdc;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            display: inline-block;
            text-decoration: none;
        }
        
        .button:hover {
            background-color: #3a5fcc;
        }
        
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="main-header">
            <div class="header-logo">
                <a href="../index.php">
                    <img src="../../assets/images/logo.png" alt="Visafy">
                </a>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="profile.php" class="active">Profile</a></li>
                    <li><a href="document.php">Documents</a></li>
                    <li><a href="../../logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>
        
        <main class="main-content">
            <h1 class="page-title"><?php echo $page_header; ?></h1>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($employer): ?>
                <div class="profile-container">
                    <div class="profile-sidebar">
                        <div class="profile-card">
                            <div class="profile-image-container">
                                <?php if (!empty($employer['profile_picture'])): ?>
                                    <img src="../../uploads/profiles/<?php echo htmlspecialchars($employer['profile_picture']); ?>" 
                                         alt="<?php echo htmlspecialchars($employer['name']); ?>" class="profile-image">
                                <?php else: ?>
                                    <div class="profile-placeholder">
                                        <?php echo strtoupper(substr($employer['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <h3><?php echo htmlspecialchars($employer['name']); ?></h3>
                                <p><?php echo htmlspecialchars($employer['email']); ?></p>
                            </div>
                            
                            <div class="profile-body">
                                <h4>Account Status</h4>
                                <div class="status-row">
                                    <span>Email Verification:</span>
                                    <?php if ($employer['email_verified']): ?>
                                        <span class="status-badge badge-success">Verified</span>
                                    <?php else: ?>
                                        <span class="status-badge badge-warning">Pending</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="status-row">
                                    <span>Account Status:</span>
                                    <?php if ($employer['status'] == 'active'): ?>
                                        <span class="status-badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge badge-danger">Suspended</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-main">
                        <div class="profile-card">
                            <div class="profile-header">
                                <h3>Edit Profile</h3>
                            </div>
                            
                            <div class="profile-body">
                                <form action="" method="post" enctype="multipart/form-data">
                                    <div class="form-group">
                                        <label for="profile_picture">Profile Picture</label>
                                        <input type="file" class="form-control" id="profile_picture" name="profile_picture">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="name">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($employer['name']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" class="form-control" id="email" 
                                               value="<?php echo htmlspecialchars($employer['email']); ?>" disabled>
                                        <div class="form-hint">Email cannot be changed.</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="company">Company Name</label>
                                        <input type="text" class="form-control" id="company" name="company" 
                                               value="<?php echo isset($employer['company']) ? htmlspecialchars($employer['company']) : ''; ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="position">Position</label>
                                        <input type="text" class="form-control" id="position" name="position" 
                                               value="<?php echo isset($employer['position']) ? htmlspecialchars($employer['position']) : ''; ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo isset($employer['phone']) ? htmlspecialchars($employer['phone']) : ''; ?>">
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" name="update_profile" class="button">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
        
        <footer class="main-footer">
            <p>&copy; <?php echo date('Y'); ?> Visafy. All rights reserved.</p>
        </footer>
    </div>
    
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            if (alerts.length > 0) {
                setTimeout(function() {
                    alerts.forEach(function(alert) {
                        alert.style.opacity = '0';
                        setTimeout(function() {
                            alert.style.display = 'none';
                        }, 300);
                    });
                }, 5000);
            }
        });
    </script>
</body>
</html> 