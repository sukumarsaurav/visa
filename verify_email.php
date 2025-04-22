<?php
require_once 'config/db_connect.php';
session_start();

$error = '';
$success = '';

// Check if token is provided
if (isset($_GET['token'])) {
    $token = $conn->real_escape_string($_GET['token']);
    
    // Verify token
    $stmt = $conn->prepare("SELECT id, name, email, email_verification_expires FROM users WHERE email_verification_token = ? AND email_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Check if token has expired
        if (strtotime($user['email_verification_expires']) < time()) {
            $error = "Verification link has expired. Please request a new one.";
        } else {
            // Update user to verified
            $stmt = $conn->prepare("UPDATE users SET email_verified = 1, email_verification_token = NULL, email_verification_expires = NULL WHERE id = ?");
            $stmt->bind_param("i", $user['id']);
            
            if ($stmt->execute()) {
                $success = "Your email has been verified successfully! You can now login to your account.";
                
                // Log activity
                $sql = "INSERT INTO activity_logs (user_id, action, entity_type, ip_address, user_agent) VALUES (?, 'email_verified', 'users', ?, ?)";
                $stmt = $conn->prepare($sql);
                $ip = $_SERVER['REMOTE_ADDR'];
                $userAgent = $_SERVER['HTTP_USER_AGENT'];
                $stmt->bind_param("iss", $user['id'], $ip, $userAgent);
                $stmt->execute();
            } else {
                $error = "An error occurred. Please try again later.";
            }
        }
    } else {
        $error = "Invalid verification link or account already verified.";
    }
} else {
    $error = "No verification token provided.";
}

// Include header
$page_title = "Verify Email - Visafy";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-5 text-center">
                    <h2 class="mb-4">Email Verification</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <p class="mt-4">
                            <a href="login.php" class="btn btn-primary">Go to Login</a>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <p class="mt-4">
                            <a href="login.php" class="btn btn-primary">Login Now</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 