<?php
require_once 'config/db_connect.php';
require_once 'config/google_auth.php';
require_once 'vendor/autoload.php';
session_start();

// Initialize Google Client
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");

// Handle the callback from Google
if (isset($_GET['code'])) {
    // Exchange authorization code for an access token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);
    
    // Get user profile
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    
    // Extract profile data
    $google_id = $google_account_info->id;
    $email = $google_account_info->email;
    $name = $google_account_info->name;
    $picture = $google_account_info->picture;
    
    // Check if user exists with this email
    $stmt = $conn->prepare("SELECT id, name, email, user_type, status, google_id, auth_provider FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User exists, update google_id if needed and login
        $user = $result->fetch_assoc();
        
        // Check if user is active
        if ($user['status'] != 'active') {
            $_SESSION['error'] = "Your account is suspended. Please contact support.";
            header("Location: login.php");
            exit();
        }
        
        // Update google_id if user previously registered with email+password
        if ($user['google_id'] === null || $user['auth_provider'] === 'local') {
            $update = $conn->prepare("UPDATE users SET google_id = ?, auth_provider = 'google', profile_picture = ? WHERE id = ?");
            $update->bind_param("ssi", $google_id, $picture, $user['id']);
            $update->execute();
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type'] = $user['user_type'];
        
        // Log activity
        $sql = "INSERT INTO activity_logs (user_id, action, entity_type, ip_address, user_agent) VALUES (?, 'login', 'users', ?, ?)";
        $stmt = $conn->prepare($sql);
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $stmt->bind_param("iss", $user['id'], $ip, $userAgent);
        $stmt->execute();
        
        // Redirect to appropriate dashboard
        if ($user['user_type'] == 'applicant') {
            header("Location: dashboard/applicant/index.php");
        } elseif ($user['user_type'] == 'employer') {
            header("Location: dashboard/employer/index.php");
        } elseif ($user['user_type'] == 'professional') {
            header("Location: dashboard/professional/index.php");
        }
        exit();
        
    } else {
        // New user - store Google ID and redirect to registration to complete profile
        $_SESSION['google_data'] = [
            'google_id' => $google_id,
            'name' => $name,
            'email' => $email,
            'picture' => $picture
        ];
        
        // Redirect to registration with Google data
        header("Location: register.php?oauth=google");
        exit();
    }
} else {
    // No authorization code, redirect to login
    header("Location: login.php");
    exit();
}
?> 