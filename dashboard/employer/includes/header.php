<?php
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Check if user is an employer
requireUserType('employer', '../../login.php');

// Get unread messages count
$unread_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS unread FROM messages WHERE recipient_id = ? AND is_read = 0");
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $unread_count = $row['unread'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard - Visafy</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        /* Dashboard specific styles */
        .sidebar {
            min-height: calc(100vh - 56px);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }
        
        .sidebar .nav-link {
            color: #495057;
            font-weight: 500;
            padding: 0.7rem 1rem;
            border-radius: 0.25rem;
            margin-bottom: 0.2rem;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }
        
        .sidebar .nav-link.active {
            color: #007bff;
            background-color: rgba(0, 123, 255, 0.1);
        }
        
        .content-wrapper {
            padding: 1.5rem;
        }
        
        .stat-card {
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 56px;
                left: 0;
                width: 100%;
                height: 100%;
                padding-top: 0.5rem;
            }
            
            .content-wrapper {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body>
    <header class="navbar navbar-dark sticky-top bg-primary flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="../../index.php">
            <img src="../../assets/images/logo-white.png" alt="Visafy" height="30">
        </a>
        
        <button class="navbar-toggler d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="w-100 d-none d-md-block"></div>
        
        <div class="navbar-nav">
            <div class="nav-item text-nowrap d-flex align-items-center">
                <a class="nav-link px-3 position-relative" href="messages.php">
                    <i class="bi bi-envelope fs-5"></i>
                    <?php if ($unread_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?php echo $unread_count; ?>
                    </span>
                    <?php endif; ?>
                </a>
                
                <a class="nav-link px-3" href="notifications.php">
                    <i class="bi bi-bell fs-5"></i>
                </a>
                
                <div class="dropdown">
                    <a class="nav-link px-3 dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="profile.php">Company Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../../logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar will be included separately -->
        </div>
    </div>
</body>
</html>
