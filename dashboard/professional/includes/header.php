<?php
// Start session only if one isn't already active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is a professional
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'professional') {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT u.*, p.* FROM users u 
                        LEFT JOIN professionals p ON u.id = p.user_id 
                        WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: ../login.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Check for unread notifications
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notif_result = $stmt->get_result();
$notification_count = $notif_result->fetch_assoc()['count'];
$stmt->close();

// Determine if sidebar should be collapsed based on user preference or default
$sidebar_collapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';
$sidebar_class = $sidebar_collapsed ? 'collapsed' : '';
$main_content_class = $sidebar_collapsed ? 'expanded' : '';

// Prepare profile image
$profile_img = '../assets/img/default-profile.jpg';
// Check both possible column names from the database
$profile_image = !empty($user['profile_image']) ? $user['profile_image'] : 
                (!empty($user['profile_picture']) ? $user['profile_picture'] : '');

if (!empty($profile_image)) {
    // Check both possible locations
    if (file_exists('../../uploads/profiles/' . $profile_image)) {
        $profile_img = '../../uploads/profiles/' . $profile_image;
    } else if (file_exists('../uploads/profiles/' . $profile_image)) {
        $profile_img = '../uploads/profiles/' . $profile_image;
    } else if (file_exists('../uploads/profile/' . $profile_image)) {
        $profile_img = '../uploads/profile/' . $profile_image;
    }
}

// Get the current page name
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Professional Dashboard'; ?> - Visafy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* Add styles for sidebar section title */
        .sidebar-section-title {
            padding: 10px 15px;
            color: #a0aec0;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        <?php if (isset($page_specific_css)): ?>
            <?php echo $page_specific_css; ?>
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button id="sidebar-toggle" class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="index.php" class="header-logo">
                    <img src="/assets/images/logo-Visafy-light.png" alt="Visafy Logo" class="desktop-logo">
                </a>
            </div>
            <div class="header-right">
                <div class="notification-dropdown">
                    <div class="notification-icon">
                        <i class="fas fa-bell"></i>
                        <?php if ($notification_count > 0): ?>
                        <span class="notification-badge"><?php echo $notification_count; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="user-dropdown">
                    <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                    <img src="<?php echo $profile_img; ?>" alt="Profile" class="profile-img-header" style="width: 32px; height: 32px;">
                </div>
            </div>
        </header>

        <!-- Sidebar -->
        <aside class="sidebar <?php echo $sidebar_class; ?>">
            <div class="profile-section">
                <img src="<?php echo $profile_img; ?>" alt="Profile" class="profile-img">
                <div class="profile-info">
                    <h3 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h3>
                    <span class="verification-status <?php echo $user['verification_status'] == 'verified' ? 'verified' : 'unverified'; ?>">
                        <?php echo $user['verification_status'] == 'verified' ? 'Verified' : 'Unverified'; ?>
                    </span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item <?php echo $current_page == 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-item-text">Dashboard</span>
                </a>
                
                <!-- Visafy AI Section -->
                <div class="sidebar-divider"></div>
                <a href="ai-chat.php" class="nav-item <?php echo $current_page == 'ai-chat' ? 'active' : ''; ?>">
                    <i class="fas fa-robot"></i>
                    <span class="nav-item-text">Visafy Ai</span>
                </a>
                <a href="ai-documents.php" class="nav-item <?php echo $current_page == 'ai-documents' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span class="nav-item-text">Draft Documents</span>
                </a>
                
                <div class="sidebar-divider"></div>
                <!-- End Visafy AI Section -->

                <a href="profile.php" class="nav-item <?php echo $current_page == 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span class="nav-item-text">Profile</span>
                </a>
                <a href="services.php" class="nav-item <?php echo $current_page == 'services' ? 'active' : ''; ?>">
                    <i class="fas fa-briefcase"></i>
                    <span class="nav-item-text">Services</span>
                </a>
                <a href="calendar.php" class="nav-item <?php echo $current_page == 'calendar' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="nav-item-text">Calendar</span>
                </a>
                <a href="appointments.php" class="nav-item <?php echo $current_page == 'appointments' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i>
                    <span class="nav-item-text">Appointments</span>
                </a>
                <div class="sidebar-divider"></div>
                <a href="clients.php" class="nav-item <?php echo $current_page == 'clients' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span class="nav-item-text">Clients</span>
                </a>
                <a href="cases.php" class="nav-item <?php echo $current_page == 'cases' ? 'active' : ''; ?>">
                    <i class="fas fa-folder-open"></i>
                    <span class="nav-item-text">Cases</span>
                </a>
                <a href="documents.php" class="nav-item <?php echo $current_page == 'documents' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span class="nav-item-text">Documents</span>
                </a>
                <div class="sidebar-divider"></div>
                <a href="messages.php" class="nav-item <?php echo $current_page == 'messages' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i>
                    <span class="nav-item-text">Messages</span>
                </a>
                <a href="reviews.php" class="nav-item <?php echo $current_page == 'reviews' ? 'active' : ''; ?>">
                    <i class="fas fa-star"></i>
                    <span class="nav-item-text">Reviews</span>
                </a>
                <a href="earnings.php" class="nav-item <?php echo $current_page == 'earnings' ? 'active' : ''; ?>">
                    <i class="fas fa-money-bill-wave"></i>
                    <span class="nav-item-text">Earnings</span>
                </a>
                <div class="sidebar-divider"></div>
                <a href="settings.php" class="nav-item <?php echo $current_page == 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span class="nav-item-text">Settings</span>
                </a>
                <a href="../logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-item-text">Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content Container -->
        <main class="main-content <?php echo $main_content_class; ?>">
           
            
<!-- End of header - page content will be inserted here --> 