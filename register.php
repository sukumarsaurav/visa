<?php
require_once 'config/db_connect.php';
require_once 'includes/functions.php';
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to appropriate dashboard based on user type
    if ($_SESSION['user_type'] == 'applicant') {
        header("Location: dashboard/applicant/index.php");
    } elseif ($_SESSION['user_type'] == 'employer') {
        header("Location: dashboard/employer/index.php");
    } elseif ($_SESSION['user_type'] == 'professional') {
        header("Location: dashboard/professional/index.php");
    }
    exit();
}

// Initialize variables
$error = '';
$success = '';
$user_type = isset($_GET['type']) ? $_GET['type'] : 'applicant';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$google_data = isset($_SESSION['google_data']) ? $_SESSION['google_data'] : null;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // For all user types - basic information (step 1)
    if (isset($_POST['register_step1'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $user_type = $conn->real_escape_string($_POST['user_type']);
        
        // Validate input
        if (empty($name) || empty($email) || (empty($password) && !$google_data)) {
            $error = "Please fill in all required fields";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address";
        } elseif (!$google_data && $password !== $confirm_password) {
            $error = "Passwords do not match";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email is already registered. Please login or use a different email.";
            } else {
                // Validate password strength if not using Google login
                if (!$google_data) {
                    $password_validation = validatePassword($password);
                    if (!$password_validation['valid']) {
                        $error = $password_validation['message'];
                    }
                }
                
                if (empty($error)) {
                    // If registering as professional, store data in session and move to next step
                    if ($user_type == 'professional') {
                        $_SESSION['registration'] = [
                            'name' => $name,
                            'email' => $email,
                            'password' => $password,
                            'user_type' => $user_type,
                            'google_data' => $google_data
                        ];
                        header("Location: register.php?type=professional&step=2");
                        exit();
                    } else {
                        // For applicant and employer, complete registration directly
                        $hashed_password = $google_data ? null : password_hash($password, PASSWORD_DEFAULT);
                        $verification_token = generateToken();
                        $token_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                        
                        // Start transaction
                        $conn->begin_transaction();
                        
                        try {
                            if ($google_data) {
                                // OAuth registration
                                $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, auth_provider, google_id, profile_picture, email_verified, email_verification_token, email_verification_expires) VALUES (?, ?, NULL, ?, 'google', ?, ?, 1, NULL, NULL)");
                                $stmt->bind_param("sssss", $name, $email, $user_type, $google_data['google_id'], $google_data['picture']);
                            } else {
                                // Regular registration
                                $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, email_verification_token, email_verification_expires) VALUES (?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param("ssssss", $name, $email, $hashed_password, $user_type, $verification_token, $token_expires);
                            }
                            
                            $stmt->execute();
                            $user_id = $conn->insert_id;
                            
                            $conn->commit();
                            
                            // Send verification email for regular registration
                            if (!$google_data) {
                                sendVerificationEmail($email, $name, $verification_token);
                                $success = "Registration successful! Please check your email to verify your account.";
                                
                                // For development: Display the verification link
                                if ($_SERVER['SERVER_NAME'] == 'localhost' || strpos($_SERVER['SERVER_NAME'], '.local') !== false) {
                                    $protocol = 'http';
                                    $verification_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/visafy-v2/verify_email.php?token=" . $verification_token;
                                    $success .= "<br><br><strong>Development Mode:</strong> <a href='$verification_link' target='_blank'>Click here to verify</a>";
                                    $success .= "<br><small>Emails are logged in the /logs directory instead of being sent.</small>";
                                }
                            } else {
                                // Log in the user immediately for Google registration
                                $_SESSION['user_id'] = $user_id;
                                $_SESSION['user_name'] = $name;
                                $_SESSION['user_email'] = $email;
                                $_SESSION['user_type'] = $user_type;
                                
                                // Clear Google data
                                unset($_SESSION['google_data']);
                                
                                // Redirect to dashboard
                                if ($user_type == 'applicant') {
                                    header("Location: dashboard/applicant/index.php");
                                } else {
                                    header("Location: dashboard/employer/index.php");
                                }
                                exit();
                            }
                        } catch (Exception $e) {
                            $conn->rollback();
                            $error = "Registration failed: " . $e->getMessage();
                        }
                    }
                }
            }
        }
    }
    
    // For professional - step 2 (professional details)
    else if (isset($_POST['register_step2']) && isset($_SESSION['registration'])) {
        $registration = $_SESSION['registration'];
        
        $license_number = $conn->real_escape_string($_POST['license_number']);
        $years_experience = $conn->real_escape_string($_POST['years_experience']);
        $education = $conn->real_escape_string($_POST['education']);
        $bio = $conn->real_escape_string($_POST['bio']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $website = $conn->real_escape_string($_POST['website']);
        
        // Validate input
        if (empty($license_number) || empty($years_experience) || empty($education) || empty($bio) || empty($phone)) {
            $error = "Please fill in all required fields";
        } else {
            // Store in session and move to next step
            $_SESSION['registration']['professional'] = [
                'license_number' => $license_number,
                'years_experience' => $years_experience,
                'education' => $education,
                'bio' => $bio,
                'phone' => $phone,
                'website' => $website
            ];
            
            header("Location: register.php?type=professional&step=3");
            exit();
        }
    }
    
    // For professional - step 3 (specializations and languages)
    else if (isset($_POST['register_step3']) && isset($_SESSION['registration'])) {
        $registration = $_SESSION['registration'];
        
        $specializations = isset($_POST['specializations']) ? $_POST['specializations'] : [];
        $languages = isset($_POST['languages']) ? $_POST['languages'] : [];
        
        // Validate input
        if (empty($specializations) || empty($languages)) {
            $error = "Please select at least one specialization and language";
        } else {
            // Complete the registration
            $name = $registration['name'];
            $email = $registration['email'];
            $password = $registration['password'];
            $user_type = $registration['user_type'];
            $google_data = $registration['google_data'];
            $professional = $registration['professional'];
            
            $hashed_password = $google_data ? null : password_hash($password, PASSWORD_DEFAULT);
            $verification_token = generateToken();
            $token_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                if ($google_data) {
                    // OAuth registration
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, auth_provider, google_id, profile_picture, email_verified, email_verification_token, email_verification_expires) VALUES (?, ?, NULL, ?, 'google', ?, ?, 1, NULL, NULL)");
                    $stmt->bind_param("sssss", $name, $email, $user_type, $google_data['google_id'], $google_data['picture']);
                } else {
                    // Regular registration
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, email_verification_token, email_verification_expires) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssss", $name, $email, $hashed_password, $user_type, $verification_token, $token_expires);
                }
                
                $stmt->execute();
                $user_id = $conn->insert_id;
                
                // Insert professional data
                $stmt = $conn->prepare("INSERT INTO professionals (user_id, license_number, years_experience, education, bio, phone, website) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isisss", $user_id, $professional['license_number'], $professional['years_experience'], $professional['education'], $professional['bio'], $professional['phone'], $professional['website']);
                $stmt->execute();
                $professional_id = $conn->insert_id;
                
                // Insert specializations
                foreach ($specializations as $specialization_id) {
                    $stmt = $conn->prepare("INSERT INTO professional_specializations (professional_id, specialization_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $professional_id, $specialization_id);
                    $stmt->execute();
                }
                
                // Insert languages
                foreach ($languages as $language_id) {
                    $stmt = $conn->prepare("INSERT INTO professional_languages (professional_id, language_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $professional_id, $language_id);
                    $stmt->execute();
                }
                
                $conn->commit();
                
                // Send verification email for regular registration
                if (!$google_data) {
                    sendVerificationEmail($email, $name, $verification_token);
                    $success = "Registration successful! Please check your email to verify your account.";
                    
                    // For development: Display the verification link
                    if ($_SERVER['SERVER_NAME'] == 'localhost' || strpos($_SERVER['SERVER_NAME'], '.local') !== false) {
                        $protocol = 'http';
                        $verification_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/visafy-v2/verify_email.php?token=" . $verification_token;
                        $success .= "<br><br><strong>Development Mode:</strong> <a href='$verification_link' target='_blank'>Click here to verify</a>";
                        $success .= "<br><small>Emails are logged in the /logs directory instead of being sent.</small>";
                    }
                } else {
                    // Log in the user immediately for Google registration
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_type'] = $user_type;
                    
                    // Clear Google data and registration data
                    unset($_SESSION['google_data']);
                    unset($_SESSION['registration']);
                    
                    // Redirect to dashboard
                    header("Location: dashboard/professional/index.php");
                    exit();
                }
                
                // Clear registration data
                unset($_SESSION['registration']);
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}

// Include header
$page_title = "Register - Visafy";
include 'includes/header.php';

// Fetch data for professional registration
$specializations = [];
$languages = [];

if ($user_type == 'professional' && $step == 3) {
    // Get specializations
    $result = $conn->query("SELECT id, name FROM specializations WHERE is_active = 1");
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $specializations[] = $row;
        }
    }
    
    // Get languages
    $result = $conn->query("SELECT id, name FROM languages WHERE is_active = 1");
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $languages[] = $row;
        }
    }
}
?>

<div class="register-container">
    <div class="container">
        <div class="register-card">
            <div class="register-header">
                <h2>Create an Account</h2>
            </div>
            <div class="register-body">
                <?php if ($error): ?>
                    <div class="auth-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="auth-success">
                        <?php echo $success; ?>
                        <p class="mt-3"><a href="login.php" class="btn btn-primary">Go to Login</a></p>
                    </div>
                <?php else: ?>
                
                <!-- Type selection tabs -->
                <div class="register-tabs">
                    <a class="register-tab <?php echo $user_type == 'applicant' ? 'active' : ''; ?>" href="register.php?type=applicant">Applicant</a>
                    <a class="register-tab <?php echo $user_type == 'employer' ? 'active' : ''; ?>" href="register.php?type=employer">Employer</a>
                    <a class="register-tab <?php echo $user_type == 'professional' ? 'active' : ''; ?>" href="register.php?type=professional">Professional</a>
                </div>
                
                <?php if ($user_type == 'professional'): ?>
                    <!-- Professional registration - multi-step form -->
                    <div class="register-progress">
                        <div class="register-progress-bar" style="width: <?php echo $step * 33.33; ?>%"></div>
                    </div>
                    
                    <p class="register-step-title">Step <?php echo $step; ?> of 3: 
                        <?php 
                        if ($step == 1) echo "Basic Information";
                        elseif ($step == 2) echo "Professional Details";
                        else echo "Specializations & Languages";
                        ?>
                    </p>
                    
                    <?php if ($step == 1): ?>
                        <!-- Step 1: Basic Information -->
                        <form method="post" action="" class="register-form">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo $google_data ? $google_data['name'] : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo $google_data ? $google_data['email'] : ''; ?>" <?php echo $google_data ? 'readonly' : ''; ?> required>
                            </div>
                            
                            <?php if (!$google_data): ?>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" required>
                                <div class="help-text">Password must be at least 8 characters, include at least one number and one uppercase letter.</div>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            <?php endif; ?>
                            
                            <input type="hidden" name="user_type" value="professional">
                            <div class="register-nav">
                                <div></div> <!-- Empty div for spacing -->
                                <button type="submit" name="register_step1" class="btn btn-primary btn-next">Continue</button>
                            </div>
                        </form>
                    
                    <?php elseif ($step == 2): ?>
                        <!-- Step 2: Professional Details -->
                        <form method="post" action="" class="register-form">
                            <div class="form-group">
                                <label for="license_number">License Number</label>
                                <input type="text" id="license_number" name="license_number" required>
                            </div>
                            <div class="form-group">
                                <label for="years_experience">Years of Experience</label>
                                <input type="number" id="years_experience" name="years_experience" min="0" required>
                            </div>
                            <div class="form-group">
                                <label for="education">Education</label>
                                <textarea id="education" name="education" rows="2" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="bio">Professional Bio</label>
                                <textarea id="bio" name="bio" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" required>
                            </div>
                            <div class="form-group">
                                <label for="website">Website (Optional)</label>
                                <input type="url" id="website" name="website" placeholder="https://...">
                            </div>
                            
                            <div class="register-nav">
                                <a href="register.php?type=professional&step=1" class="btn btn-back">Back</a>
                                <button type="submit" name="register_step2" class="btn btn-primary btn-next">Continue</button>
                            </div>
                        </form>
                        
                    <?php elseif ($step == 3): ?>
                        <!-- Step 3: Specializations & Languages -->
                        <form method="post" action="" class="register-form">
                            <div class="form-group">
                                <label>Specializations</label>
                                <div class="checkbox-group">
                                    <?php foreach ($specializations as $specialization): ?>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="specializations[]" value="<?php echo $specialization['id']; ?>" id="spec_<?php echo $specialization['id']; ?>">
                                        <label for="spec_<?php echo $specialization['id']; ?>">
                                            <?php echo $specialization['name']; ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Languages</label>
                                <div class="checkbox-group">
                                    <?php foreach ($languages as $language): ?>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="languages[]" value="<?php echo $language['id']; ?>" id="lang_<?php echo $language['id']; ?>">
                                        <label for="lang_<?php echo $language['id']; ?>">
                                            <?php echo $language['name']; ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="register-nav">
                                <a href="register.php?type=professional&step=2" class="btn btn-back">Back</a>
                                <button type="submit" name="register_step3" class="btn btn-primary btn-next">Complete Registration</button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- Applicant or Employer registration - single step form -->
                    <form method="post" action="" class="register-form">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo $google_data ? $google_data['name'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo $google_data ? $google_data['email'] : ''; ?>" <?php echo $google_data ? 'readonly' : ''; ?> required>
                        </div>
                        
                        <?php if (!$google_data): ?>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                            <div class="help-text">Password must be at least 8 characters, include at least one number and one uppercase letter.</div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <?php endif; ?>
                        
                        <input type="hidden" name="user_type" value="<?php echo $user_type; ?>">
                        <button type="submit" name="register_step1" class="btn btn-primary btn-next">Register</button>
                    </form>
                <?php endif; ?>
                
                <div class="auth-divider">
                    <span>OR</span>
                </div>
                
                <?php
                // Generate Google OAuth URL
                require_once 'config/google_auth.php';
                require_once 'vendor/autoload.php';
                
                $client = new Google_Client();
                $client->setClientId(GOOGLE_CLIENT_ID);
                $client->setClientSecret(GOOGLE_CLIENT_SECRET);
                $client->setRedirectUri(GOOGLE_REDIRECT_URI);
                $client->addScope("email");
                $client->addScope("profile");
                
                $google_auth_url = $client->createAuthUrl();
                ?>
                
                <a href="<?php echo $google_auth_url; ?>" class="social-login-btn">
                    <i class="fab fa-google"></i> Sign up with Google
                </a>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
