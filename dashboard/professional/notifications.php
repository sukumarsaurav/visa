<?php
// Set page variables
$page_title = "Notifications";
$page_header = "Notifications";

// Start output buffering to prevent "headers already sent" errors
ob_start();

// Include header (handles session and authentication)
require_once 'includes/header.php';

// Get user_id from session (already verified in header.php)
$user_id = $_SESSION['user_id'];

// Initialize success and error messages
$success_message = '';
$error_message = '';

// Handle mark all as read action
if (isset($_GET['mark_all_read']) && $_GET['mark_all_read'] == 1) {
    $mark_all_query = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($mark_all_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $success_message = "All notifications marked as read.";
    }
    $stmt->close();
}

// Handle delete notification action
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $notification_id = intval($_GET['delete']);
    
    $delete_query = "UPDATE notifications SET deleted_at = NOW() WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $success_message = "Notification deleted successfully.";
    } else {
        $error_message = "Failed to delete notification.";
    }
    $stmt->close();
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20; // Items per page
$offset = ($page - 1) * $limit;

// Get total notifications count
$count_query = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND deleted_at IS NULL";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_notifications = $result->fetch_assoc()['total'];
$total_pages = ceil($total_notifications / $limit);
$stmt->close();

// Get notifications with pagination
$notifications_query = "SELECT id, title, message, is_read, created_at FROM notifications 
                      WHERE user_id = ? AND deleted_at IS NULL 
                      ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($notifications_query);
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$notifications_result = $stmt->get_result();
$notifications = [];
while ($notification = $notifications_result->fetch_assoc()) {
    $notifications[] = $notification;
}
$stmt->close();

// Debug: Insert a sample notification if none exist
if (empty($notifications) && $page == 1) {
    // Check if there are any notifications at all for this user
    $check_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notification_count = $result->fetch_assoc()['count'];
    $stmt->close();
    
    if ($notification_count == 0) {
        // Insert a sample notification for demonstration
        $insert_query = "INSERT INTO notifications (user_id, title, message, is_read, created_at) 
                        VALUES (?, 'Welcome to Visafy', 'Thank you for joining our platform. This is your notifications center.', 0, NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Refresh the notifications list
        $notifications_query = "SELECT id, title, message, is_read, created_at FROM notifications 
                              WHERE user_id = ? AND deleted_at IS NULL 
                              ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($notifications_query);
        $stmt->bind_param("iii", $user_id, $limit, $offset);
        $stmt->execute();
        $notifications_result = $stmt->get_result();
        $notifications = [];
        while ($notification = $notifications_result->fetch_assoc()) {
            $notifications[] = $notification;
        }
        $stmt->close();
    }
}

// Update total count after potential insertion
if (count($notifications) > 0 && $total_notifications == 0) {
    $count_query = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_notifications = $result->fetch_assoc()['total'];
    $total_pages = ceil($total_notifications / $limit);
    $stmt->close();
}
?>

<!-- Page Header -->
<h1 class="page-title"><?php echo $page_header; ?></h1>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<!-- Notifications Card -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Your Notifications</h2>
        <div class="card-actions">
            <?php if ($total_notifications > 0): ?>
                <a href="notifications.php?mark_all_read=1" class="button button-small">Mark All as Read</a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (empty($notifications)): ?>
        <p class="no-data">You don't have any notifications.</p>
    <?php else: ?>
        <div class="notification-list">
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $notification['id']; ?>">
                    <div class="notification-content">
                        <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                        <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                        <div class="notification-time">
                            <?php 
                                $date = new DateTime($notification['created_at']);
                                echo $date->format('F j, Y, g:i a');
                            ?>
                        </div>
                    </div>
                    <div class="notification-actions">
                        <?php if (!$notification['is_read']): ?>
                            <button class="button button-small button-outline mark-read-btn" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                <i class="fas fa-check"></i> Mark as Read
                            </button>
                        <?php endif; ?>
                        <button class="button button-small button-danger" onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1" class="pagination-link"><i class="fas fa-angle-double-left"></i></a>
                    <a href="?page=<?php echo $page - 1; ?>" class="pagination-link"><i class="fas fa-angle-left"></i></a>
                <?php else: ?>
                    <span class="pagination-link disabled"><i class="fas fa-angle-double-left"></i></span>
                    <span class="pagination-link disabled"><i class="fas fa-angle-left"></i></span>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $start_page + 4);
                
                if ($end_page - $start_page < 4 && $start_page > 1) {
                    $start_page = max(1, $end_page - 4);
                }
                
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="pagination-link current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>" class="pagination-link"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="pagination-link"><i class="fas fa-angle-right"></i></a>
                    <a href="?page=<?php echo $total_pages; ?>" class="pagination-link"><i class="fas fa-angle-double-right"></i></a>
                <?php else: ?>
                    <span class="pagination-link disabled"><i class="fas fa-angle-right"></i></span>
                    <span class="pagination-link disabled"><i class="fas fa-angle-double-right"></i></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Custom CSS for notifications page -->
<style>
    .card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .card-title {
        margin: 0;
        font-size: 1.2rem;
        color: #2c3e50;
    }
    
    .card-actions {
        display: flex;
        gap: 10px;
    }
    
    .notification-list {
        padding: 0;
    }
    
    .notification-item {
        padding: 15px 20px;
        border-bottom: 1px solid #f1f1f1;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        transition: background-color 0.2s;
    }
    
    .notification-item:last-child {
        border-bottom: none;
    }
    
    .notification-item:hover {
        background-color: #f9fafb;
    }
    
    .notification-item.unread {
        background-color: #ebf8ff;
        border-left: 4px solid #3498db;
    }
    
    .notification-content {
        flex: 1;
        padding-right: 15px;
    }
    
    .notification-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 5px;
        color: #2c3748;
    }
    
    .notification-message {
        font-size: 0.9rem;
        color: #4a5568;
        margin-bottom: 10px;
        line-height: 1.5;
    }
    
    .notification-time {
        font-size: 0.8rem;
        color: #a0aec0;
    }
    
    .notification-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 120px;
    }
    
    .button {
        display: inline-block;
        padding: 8px 16px;
        background-color: #3498db;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        text-align: center;
        text-decoration: none;
        transition: background-color 0.2s;
    }
    
    .button:hover {
        background-color: #2980b9;
    }
    
    .button-small {
        padding: 5px 10px;
        font-size: 0.8rem;
    }
    
    .button-outline {
        background-color: transparent;
        color: #3498db;
        border: 1px solid #3498db;
    }
    
    .button-outline:hover {
        background-color: #ebf8ff;
    }
    
    .button-danger {
        background-color: transparent;
        color: #e53e3e;
        border: 1px solid #e53e3e;
    }
    
    .button-danger:hover {
        background-color: #fff5f5;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        margin-bottom: 20px;
        padding: 0 20px 20px;
    }
    
    .pagination-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 35px;
        height: 35px;
        margin: 0 3px;
        padding: 0 5px;
        border-radius: 4px;
        text-decoration: none;
        color: #3498db;
        background-color: white;
        border: 1px solid #e2e8f0;
    }
    
    .pagination-link:hover {
        background-color: #f8fafc;
    }
    
    .pagination-link.current {
        background-color: #3498db;
        color: white;
        border-color: #3498db;
    }
    
    .pagination-link.disabled {
        color: #cbd5e0;
        pointer-events: none;
        border-color: #f0f0f0;
    }
    
    .no-data {
        padding: 40px 20px;
        text-align: center;
        color: #7f8c8d;
        font-style: italic;
    }
    
    .alert {
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
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
    
    @media (max-width: 768px) {
        .notification-item {
            flex-direction: column;
        }
        
        .notification-content {
            padding-right: 0;
            margin-bottom: 15px;
        }
        
        .notification-actions {
            width: 100%;
            flex-direction: row;
        }
    }
</style>

<script>
    function markAsRead(notificationId) {
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
                // Remove unread class and hide the mark as read button
                const card = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                card.classList.remove('unread');
                card.querySelector('.mark-read-btn').style.display = 'none';
            }
        });
    }
    
    function deleteNotification(notificationId) {
        if (confirm('Are you sure you want to delete this notification?')) {
            window.location.href = `notifications.php?delete=${notificationId}`;
        }
    }
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?> 