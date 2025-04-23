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

// Debug: If there are no notifications but we have a count, something's wrong
if (empty($notifications_list) && $notification_count > 0) {
    error_log("Warning: Notifications count is $notification_count but no notifications were fetched.");
}

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
                            <a href="notifications.php" class="mark-all-read">Mark all as read</a>
                        </div>
                        <ul class="notification-list">
                            <?php if (empty($notifications_list)): ?>
                                <li class="no-notifications">No notifications to display</li>
                            <?php else: ?>
                                <?php foreach ($notifications_list as $notification): ?>
                                    <li class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" 
                                        data-id="<?php echo $notification['id']; ?>">
                                        <div class="title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                        <div class="message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                        <div class="time">
                                            <?php 
                                                $date = new DateTime($notification['created_at']);
                                                $now = new DateTime();
                                                $interval = $date->diff($now);
                                                
                                                if ($interval->d > 0) {
                                                    echo $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                                                } elseif ($interval->h > 0) {
                                                    echo $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
                                                } elseif ($interval->i > 0) {
                                                    echo $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
                                                } else {
                                                    echo 'Just now';
                                                }
                                            ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                        <div class="notification-footer">
                            <a href="notifications.php">View All Notifications</a>
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
                <a href="availability.php" class="nav-item <?php echo $current_page == 'availability' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="nav-item-text">Availability</span>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar toggle functionality
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
            
            // Save preference in cookie
            const isCollapsed = document.querySelector('.sidebar').classList.contains('collapsed');
            document.cookie = `sidebar_collapsed=${isCollapsed}; path=/; max-age=31536000`;
        });
        
        // Notification dropdown toggle
        document.getElementById('notification-toggle').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('notification-menu').classList.toggle('show');
        });
        
        // Close notification dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('notification-menu');
            if (dropdown.classList.contains('show') && !dropdown.contains(e.target) && e.target.id !== 'notification-toggle') {
                dropdown.classList.remove('show');
            }
        });
        
        // Handle notification click - mark as read
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.getAttribute('data-id');
                if (this.classList.contains('unread')) {
                    // AJAX request to mark notification as read
                    fetch('ajax/mark_notification_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'notification_id=' + notificationId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.classList.remove('unread');
                            // Update badge count
                            const badge = document.querySelector('.notification-badge');
                            if (badge) {
                                const currentCount = parseInt(badge.textContent);
                                if (currentCount > 1) {
                                    badge.textContent = currentCount - 1;
                                } else {
                                    badge.remove();
                                }
                            }
                        }
                    });
                }
            });
        });
        
        // Mark all as read functionality
        const markAllReadBtn = document.querySelector('.mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // AJAX request to mark all notifications as read
                fetch('ajax/mark_all_notifications_read.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        document.querySelectorAll('.notification-item.unread').forEach(item => {
                            item.classList.remove('unread');
                        });
                        
                        // Remove notification badge
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            badge.remove();
                        }
                    }
                });
            });
        }
    });
</script>
   