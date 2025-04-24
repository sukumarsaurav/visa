<?php
// Set page variables
$page_title = "Profile";
$page_header = "My Profile";

// Include header (handles session and authentication)
require_once 'includes/header.php';

// Get user data
$user_id = $_SESSION['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $success_message = '';
    $error_message = '';

    // Basic validation
    if (empty($name) || empty($email)) {
        $error_message = "Name and email are required fields.";
    } else {
        // Check if email is already taken by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "This email is already in use by another account.";
        } else {
            // Handle profile picture upload
            $profile_picture = $user['profile_picture']; // Keep existing picture by default
            
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($_FILES['profile_picture']['type'], $allowed_types)) {
                    $error_message = "Invalid file type. Please upload a JPEG, PNG, or GIF image.";
                } elseif ($_FILES['profile_picture']['size'] > $max_size) {
                    $error_message = "File is too large. Maximum size is 5MB.";
                } else {
                    $upload_dir = '../../uploads/profiles/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                        $profile_picture = $new_filename;
                    } else {
                        $error_message = "Failed to upload profile picture.";
                    }
                }
            }
            
            if (empty($error_message)) {
                // Update user profile - removed phone field as it's not in the users table
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_picture = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $email, $profile_picture, $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "Profile updated successfully!";
                    // Update session data
                    $_SESSION['name'] = $name;
                    $_SESSION['profile_picture'] = $profile_picture;
                } else {
                    $error_message = "Failed to update profile. Please try again.";
                }
            }
        }
    }
}

// Get latest user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Profile Information</h2>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <form action="" method="POST" enctype="multipart/form-data" class="profile-form">
        <div class="form-group">
            <label for="profile_picture">Profile Picture</label>
            <div class="profile-picture-container">
                <img src="<?php echo !empty($user['profile_picture']) ? '../../uploads/profiles/' . $user['profile_picture'] : '../../assets/images/default-avatar.png'; ?>" 
                     alt="Profile Picture" class="current-profile-picture">
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="form-control">
            </div>
        </div>
        
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="form-control">
        </div>
        
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="form-control">
        </div>
        
        <div class="form-actions">
            <button type="submit" class="button button-primary">Save Changes</button>
        </div>
    </form>
</div>

<style>
.profile-form {
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #2d3748;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    font-size: 1rem;
}

.form-control:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

.profile-picture-container {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 10px;
}

.current-profile-picture {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
}

.form-actions {
    margin-top: 30px;
}

.button-primary {
    background-color: #3498db;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
}

.button-primary:hover {
    background-color: #2980b9;
}

.alert {
    padding: 15px;
    margin: 20px;
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

<?php
// Include footer
require_once 'includes/footer.php';
?>
