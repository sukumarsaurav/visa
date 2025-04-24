<?php
// Start session only if one isn't already active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an applicant
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'applicant') {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: ../../login.php");
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

// Get recent notifications (limit to 5)
$stmt = $conn->prepare("SELECT id, title, message, is_read, created_at FROM notifications 
                       WHERE user_id = ? AND deleted_at IS NULL 
                       ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();
$notifications_list = [];
while ($notification = $notifications->fetch_assoc()) {
    $notifications_list[] = $notification;
}
$stmt->close();

// Determine if sidebar should be collapsed based on user preference or default
$sidebar_collapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';
$sidebar_class = $sidebar_collapsed ? 'collapsed' : '';
$main_content_class = $sidebar_collapsed ? 'expanded' : '';

// Prepare profile image
$profile_img = '../assets/img/default-profile.jpg';
$profile_image = !empty($user['profile_picture']) ? $user['profile_picture'] : '';

if (!empty($profile_image)) {
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
    <title><?php echo isset($page_title) ? $page_title : 'Applicant Dashboard'; ?> - Visafy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../professional/assets/css/styles.css">
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
        
        /* Notification dropdown styles */
        .notification-dropdown {
            position: relative;
        }
        
        .notification-icon {
            cursor: pointer;
            position: relative;
            padding: 8px;
        }
        
        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background-color: #e53e3e;
            color: white;
            border-radius: 50%;
            padding: 2px 5px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .notification-menu {
            position: absolute;
            right: 0;
            top: 100%;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            width: 320px;
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .notification-menu.show {
            display: block;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .notification-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .notification-header a {
            color: #3498db;
            font-size: 0.8rem;
            text-decoration: none;
        }
        
        .notification-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .notification-item {
            padding: 10px 15px;
            border-bottom: 1px solid #f1f1f1;
            transition: background-color 0.2s;
        }
        
        .notification-item:hover {
            background-color: #f9fafb;
        }
        
        .notification-item.unread {
            background-color: #ebf8ff;
        }
        
        .notification-item .title {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #2d3748;
        }
        
        .notification-item .message {
            font-size: 0.85rem;
            color: #4a5568;
            margin-bottom: 5px;
        }
        
        .notification-item .time {
            font-size: 0.75rem;
            color: #a0aec0;
        }
        
        .notification-footer {
            padding: 10px 15px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .notification-footer a {
            color: #3498db;
            font-size: 0.9rem;
            text-decoration: none;
        }
        
        .no-notifications {
            padding: 20px;
            text-align: center;
            color: #718096;
            font-style: italic;
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
                    <div class="notification-icon" id="notification-toggle">
                        <i class="fas fa-bell"></i>
                        <?php if ($notification_count > 0): ?>
                        <span class="notification-badge"><?php echo $notification_count; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="notification-menu" id="notification-menu">
                        <div class="notification-header">
                            <h3>Notifications</h3>
                            <a href="notifications.php">View All</a>
                        </div>
                        <ul class="notification-list">
                            <?php if (empty($notifications_list)): ?>
                            <li class="no-notifications">No notifications yet</li>
                            <?php else: ?>
                                <?php foreach ($notifications_list as $notification): ?>
                                <li class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" 
                                    data-id="<?php echo $notification['id']; ?>">
                                    <div class="title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                    <div class="message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                    <div class="time"><?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?></div>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                        <div class="notification-footer">
                            <a href="#" id="mark-all-read">Mark all as read</a>
                        </div>
                    </div>
                </div>
                <div class="user-dropdown">
                    <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                    <img src="<?php echo $profile_img; ?>" alt="Profile" class="profile-img-header" style="width: 32px; height: 32px;">
                </div>
            </div>
        </header>

        <!-- Sidebar -->
        <div class="sidebar <?php echo $sidebar_class; ?>">
            <div class="profile-section">
                <img src="<?php echo $profile_img; ?>" alt="Profile" class="profile-img">
                <div class="profile-info">
                    <div class="profile-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="verification-status <?php echo $user['email_verified'] ? 'verified' : 'unverified'; ?>">
                        <?php echo $user['email_verified'] ? 'Verified' : 'Unverified'; ?>
                    </div>
                </div>
            </div>
            <div class="sidebar-nav">
                <a href="index.php" class="nav-item <?php echo $current_page == 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="nav-item-text">Dashboard</span>
                </a>
                <a href="profile.php" class="nav-item <?php echo $current_page == 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span class="nav-item-text">Profile</span>
                </a>
                <a href="messages.php" class="nav-item <?php echo $current_page == 'messages' ? 'active' : ''; ?>">
                    <i class="fas fa-comments"></i>
                    <span class="nav-item-text">Messages</span>
                </a>
                <div class="sidebar-divider"></div>
                <a href="../../logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="nav-item-text">Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content <?php echo $main_content_class; ?>">
            <div class="content-wrapper">
                <?php if (isset($page_header)): ?>
                <div class="page-header">
                    <h1><?php echo $page_header; ?></h1>
                </div>
                <?php endif; ?>
