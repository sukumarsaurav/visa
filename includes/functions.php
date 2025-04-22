<?php
/**
 * Generate a random token
 * 
 * @param int $length Length of the token
 * @return string The generated token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Send verification email to user
 * 
 * @param string $email User's email address
 * @param string $name User's name
 * @param string $token Verification token
 * @return bool Whether the email was sent successfully
 */
function sendVerificationEmail($email, $name, $token) {
    $subject = "Verify Your Email - Visafy";
    
    // Use HTTPS for secure verification link
    $verification_link = "https://" . $_SERVER['HTTP_HOST'] . "/visafy-v2/verify_email.php?token=" . $token;
    
    $message = "
    <html>
    <head>
        <title>Verify Your Email</title>
    </head>
    <body>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
            <h2>Welcome to Visafy, $name!</h2>
            <p>Thank you for registering. Please verify your email address by clicking the link below:</p>
            <p><a href='$verification_link' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Verify Email</a></p>
            <p>If the button above doesn't work, copy and paste this URL into your browser:</p>
            <p>$verification_link</p>
            <p>This link will expire in 24 hours.</p>
            <p>If you didn't register for an account, please ignore this email.</p>
        </div>
    </body>
    </html>
    ";
    
    // To send HTML mail, the Content-type header must be set
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Visafy <no-reply@" . $_SERVER['HTTP_HOST'] . ">" . "\r\n"; // Use server's hostname for From
    $headers .= "Reply-To: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    // Send email
    return mail($email, $subject, $message, $headers);
}

/**
 * Validate password strength
 * 
 * @param string $password The password to validate
 * @return array Array with 'valid' flag and 'message'
 */
function validatePassword($password) {
    $result = ['valid' => true, 'message' => ''];
    
    // Check password length
    if (strlen($password) < 8) {
        $result['valid'] = false;
        $result['message'] = "Password must be at least 8 characters long";
        return $result;
    }
    
    // Check if password has at least one number
    if (!preg_match('/[0-9]/', $password)) {
        $result['valid'] = false;
        $result['message'] = "Password must include at least one number";
        return $result;
    }
    
    // Check if password has at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $result['valid'] = false;
        $result['message'] = "Password must include at least one uppercase letter";
        return $result;
    }
    
    return $result;
}

/**
 * Check if user is logged in
 * 
 * @return bool Whether the user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect user if not logged in
 * 
 * @param string $redirect_url URL to redirect to if not logged in
 */
function requireLogin($redirect_url = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Check if user has specific user type
 * 
 * @param string|array $allowed_types Allowed user type(s)
 * @return bool Whether the user has the required type
 */
function hasUserType($allowed_types) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (is_array($allowed_types)) {
        return in_array($_SESSION['user_type'], $allowed_types);
    } else {
        return $_SESSION['user_type'] == $allowed_types;
    }
}

/**
 * Redirect user if not having specific user type
 * 
 * @param string|array $allowed_types Allowed user type(s)
 * @param string $redirect_url URL to redirect to if not authorized
 */
function requireUserType($allowed_types, $redirect_url = 'login.php') {
    requireLogin($redirect_url);
    
    if (!hasUserType($allowed_types)) {
        header("Location: $redirect_url");
        exit();
    }
}
?>
