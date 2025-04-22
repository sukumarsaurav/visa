<?php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

session_start();
requireUserType('employer', '../../login.php');

// Get employer's information
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch employer data from users table since there's no separate table for employers in the database schema
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'employer'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $error = "Employer profile not found.";
}

$employer = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
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
    try {
        $stmt = $conn->prepare("UPDATE users SET name = ?, profile_picture = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $profile_picture, $user_id);
        $stmt->execute();
        
        // Check if we have a meta table for storing additional employer information
        // For now, we'll just update the fields we have
        $success = "Profile updated successfully.";
        header("Location: profile.php?success=updated");
        exit();
    } catch (Exception $e) {
        $error = "Profile update failed: " . $e->getMessage();
    }
}

// Include header
$page_title = "Employer Profile";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Profile</h1>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($employer)): ?>
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <?php if (!empty($employer['profile_picture'])): ?>
                                    <img src="../../uploads/profiles/<?php echo $employer['profile_picture']; ?>" 
                                         alt="<?php echo $employer['name']; ?>" 
                                         class="rounded-circle profile-img mb-3" 
                                         style="width: 150px; height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle profile-placeholder mb-3 d-flex align-items-center justify-content-center" 
                                         style="width: 150px; height: 150px; background-color: #e9ecef; margin: 0 auto;">
                                        <span style="font-size: 3rem; color: #6c757d;">
                                            <?php echo strtoupper(substr($employer['name'], 0, 1)); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <h4><?php echo $employer['name']; ?></h4>
                                <p class="text-muted"><?php echo $employer['email']; ?></p>
                                
                                <hr>
                                
                                <div class="verification-status text-start">
                                    <h5>Account Status</h5>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="me-2">Email Verification:</span>
                                        <?php if ($employer['email_verified']): ?>
                                            <span class="badge bg-success">Verified</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex align-items-center">
                                        <span class="me-2">Account Status:</span>
                                        <?php if ($employer['status'] == 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Suspended</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Edit Profile</h5>
                            </div>
                            
                            <div class="card-body">
                                <form action="" method="post" enctype="multipart/form-data">
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label for="profile_picture" class="form-label">Profile Picture</label>
                                            <input type="file" class="form-control" id="profile_picture" name="profile_picture">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $employer['name']; ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" value="<?php echo $employer['email']; ?>" disabled>
                                            <div class="form-text">Email cannot be changed.</div>
                                        </div>
                                    </div>
                                    
                                    <!-- Additional fields could be stored in a meta table in the future -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="company" class="form-label">Company Name</label>
                                            <input type="text" class="form-control" id="company" name="company" value="">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="position" class="form-label">Position</label>
                                            <input type="text" class="form-control" id="position" name="position" value="">
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="">
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 