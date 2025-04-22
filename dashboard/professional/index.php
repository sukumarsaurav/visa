<?php
session_start();

// Check if user is logged in and is a professional
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'professional') {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Include database connection
require_once '../../config/db_connect.php';

try {
    // Get professional data from both users and professionals tables using JOIN
    $stmt = $conn->prepare("
        SELECT u.*, p.*, 
               p.profile_image as prof_image,
               p.verification_status,
               p.availability_status
        FROM users u 
        JOIN professionals p ON u.id = p.user_id 
        WHERE u.id = ? AND u.user_type = 'professional'
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $professional = $result->fetch_assoc();

    if (!$professional) {
        // If no professional data found, redirect to login
        session_destroy();
        header("Location: ../../login.php");
        exit();
    }

    // Store essential data in session if not already stored
    $_SESSION['name'] = $professional['name'];
    $_SESSION['email'] = $professional['email'];
    $_SESSION['profile_picture'] = $professional['prof_image'] ?? null;
    $_SESSION['verification_status'] = $professional['verification_status'];
    $_SESSION['availability_status'] = $professional['availability_status'];

} catch(Exception $e) {
    // Log error and show generic message
    error_log("Database Error: " . $e->getMessage());
    $error_message = "System is temporarily unavailable. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Dashboard - Visafy</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="profile-section">
                <img src="<?php echo isset($professional['prof_image']) ? htmlspecialchars($professional['prof_image']) : '../../assets/images/default-avatar.png'; ?>" 
                     alt="Profile" class="profile-image">
                <h3><?php echo htmlspecialchars($professional['name']); ?></h3>
                <p>Visa Professional</p>
                <?php if ($professional['verification_status'] === 'verified'): ?>
                    <span class="verification-badge">Verified</span>
                <?php endif; ?>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item"><a href="index.php" class="nav-link active">Dashboard</a></li>
                <li class="nav-item"><a href="cases.php" class="nav-link">Cases</a></li>
                <li class="nav-item"><a href="documents.php" class="nav-link">Documents</a></li>
                <li class="nav-item"><a href="calendar.php" class="nav-link">Calendar</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link">Profile</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($professional['name']); ?></h1>
                <div class="status-selector">
                    <select id="availability_status" 
                            onchange="updateAvailability(this.value)"
                            data-current="<?php echo htmlspecialchars($professional['availability_status']); ?>">
                        <option value="available" <?php echo $professional['availability_status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="busy" <?php echo $professional['availability_status'] === 'busy' ? 'selected' : ''; ?>>Busy</option>
                        <option value="unavailable" <?php echo $professional['availability_status'] === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                    </select>
                </div>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="alert error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <?php
            // Only show active cases if professional is verified
            if ($professional['verification_status'] === 'verified'):
            ?>
            <div class="card">
                <h2 class="card-title">Active Cases</h2>
                <div class="case-summary">
                    <?php
                    try {
                        // Get active cases from database
                        $stmt = $conn->prepare("
                            SELECT ca.*, u.name as client_name, vt.name as visa_type_name
                            FROM case_applications ca
                            JOIN users u ON ca.client_id = u.id
                            JOIN visa_types vt ON ca.visa_type_id = vt.id
                            WHERE ca.professional_id = ?
                            AND ca.status NOT IN ('approved', 'rejected')
                            AND ca.deleted_at IS NULL
                            ORDER BY ca.created_at DESC
                            LIMIT 5
                        ");
                        
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $active_cases = $result->fetch_all(MYSQLI_ASSOC);
                        
                        foreach ($active_cases as $case): ?>
                            <div class="case-item">
                                <div class="status-badge status-<?php echo htmlspecialchars($case['status']); ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $case['status'])); ?>
                                </div>
                                <h3><?php echo htmlspecialchars($case['visa_type_name']); ?></h3>
                                <p>Reference: <?php echo htmlspecialchars($case['reference_number']); ?></p>
                                <p>Client: <?php echo htmlspecialchars($case['client_name']); ?></p>
                                <p>Created: <?php echo date('M j, Y', strtotime($case['created_at'])); ?></p>
                                <a href="case_details.php?ref=<?php echo urlencode($case['reference_number']); ?>" class="button">View Details</a>
                            </div>
                        <?php endforeach;
                        
                        if (empty($active_cases)): ?>
                            <p class="no-data">No active cases at the moment.</p>
                        <?php endif;
                        
                    } catch(Exception $e) {
                        error_log("Database Error: " . $e->getMessage());
                        echo '<p class="error">Unable to load active cases. Please try again later.</p>';
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <h2 class="card-title">Today's Appointments</h2>
                <div class="appointments-list">
                    <?php
                    try {
                        // Get today's appointments from database
                        $stmt = $conn->prepare("
                            SELECT b.*, u.name as client_name, 
                                   st.name as service_type_name,
                                   sm.name as service_mode_name,
                                   ts.start_time, ts.end_time
                            FROM bookings b
                            JOIN users u ON b.client_id = u.id
                            JOIN service_types st ON b.service_type_id = st.id
                            JOIN service_modes sm ON b.service_mode_id = sm.id
                            JOIN time_slots ts ON b.time_slot_id = ts.id
                            WHERE b.professional_id = ?
                            AND DATE(ts.date) = CURDATE()
                            AND b.status = 'confirmed'
                            AND b.deleted_at IS NULL
                            ORDER BY ts.start_time ASC
                        ");
                        
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $appointments = $result->fetch_all(MYSQLI_ASSOC);
                        
                        foreach ($appointments as $appointment): ?>
                            <div class="appointment-item">
                                <div class="time">
                                    <?php echo date('H:i', strtotime($appointment['start_time'])); ?>
                                </div>
                                <div class="details">
                                    <h4><?php echo htmlspecialchars($appointment['client_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($appointment['service_type_name']); ?> - 
                                       <?php echo htmlspecialchars($appointment['service_mode_name']); ?></p>
                                </div>
                                <?php if ($appointment['service_mode_name'] === 'Video Call'): ?>
                                    <a href="meeting.php?booking_id=<?php echo $appointment['id']; ?>" class="button">Join Meeting</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach;
                        
                        if (empty($appointments)): ?>
                            <p class="no-data">No appointments scheduled for today.</p>
                        <?php endif;
                        
                    } catch(Exception $e) {
                        error_log("Database Error: " . $e->getMessage());
                        echo '<p class="error">Unable to load appointments. Please try again later.</p>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="card">
                <h2 class="card-title">Recent Updates</h2>
                <div class="updates-list">
                    <?php
                    try {
                        // Get recent notifications from database
                        $stmt = $conn->prepare("
                            SELECT *
                            FROM notifications
                            WHERE user_id = ?
                            AND deleted_at IS NULL
                            ORDER BY created_at DESC
                            LIMIT 5
                        ");
                        
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $notifications = $result->fetch_all(MYSQLI_ASSOC);
                        
                        foreach ($notifications as $notification): ?>
                            <div class="update-item">
                                <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                <small><?php echo date('F j, Y g:i A', strtotime($notification['created_at'])); ?></small>
                            </div>
                        <?php endforeach;
                        
                        if (empty($notifications)): ?>
                            <p class="no-data">No recent updates.</p>
                        <?php endif;
                        
                    } catch(Exception $e) {
                        error_log("Database Error: " . $e->getMessage());
                        echo '<p class="error">Unable to load notifications. Please try again later.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function updateAvailability(status) {
            fetch('update_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert('Availability status updated successfully');
                } else {
                    // Show error message
                    alert('Failed to update availability status');
                    // Reset select to previous value
                    const select = document.getElementById('availability_status');
                    select.value = select.getAttribute('data-current');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating availability status');
                // Reset select to previous value
                const select = document.getElementById('availability_status');
                select.value = select.getAttribute('data-current');
            });
        }
    </script>
</body>
</html>
