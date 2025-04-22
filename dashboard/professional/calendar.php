<?php
session_start();

// Check if user is logged in and is a professional
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'professional') {
    header("Location: ../../login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
// Database connection would be here
// $professional = getProfessionalData($user_id);

// Get current month and year - default to current month
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get first day of the month
$first_day = mktime(0, 0, 0, $month, 1, $year);

// Get number of days in the month
$num_days = date('t', $first_day);

// Get day of the week for the first day (0 = Sunday, 6 = Saturday)
$day_of_week = date('w', $first_day);

// Database connection
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Get service types and modes for the form
$service_types_query = "SELECT st.* FROM service_types st 
                       INNER JOIN professional_services ps ON st.id = ps.service_type_id
                       WHERE ps.professional_id = ? AND ps.is_offered = 1 AND st.is_active = 1";
$stmt = $conn->prepare($service_types_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$service_types_result = $stmt->get_result();
$service_types = [];
while ($type = $service_types_result->fetch_assoc()) {
    $service_types[] = $type;
}

// Handle time slot creation if form submitted
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_slots'])) {
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $slot_duration = $_POST['slot_duration'];
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // First, create or get availability record
        $avail_query = "INSERT INTO consultant_availability (professional_id, date, is_available) 
                       VALUES (?, ?, 1) 
                       ON DUPLICATE KEY UPDATE is_available = 1";
        $stmt = $conn->prepare($avail_query);
        $stmt->bind_param("is", $user_id, $date);
        $stmt->execute();
        
        // Get availability ID
        $avail_id_query = "SELECT id FROM consultant_availability 
                          WHERE professional_id = ? AND date = ?";
        $stmt = $conn->prepare($avail_id_query);
        $stmt->bind_param("is", $user_id, $date);
        $stmt->execute();
        $avail_result = $stmt->get_result();
        $availability_id = $avail_result->fetch_assoc()['id'];
        
        // Get all service modes available for the professional's service types
        $modes_query = "SELECT DISTINCT sm.id 
                       FROM service_modes sm
                       INNER JOIN service_type_modes stm ON sm.id = stm.service_mode_id
                       INNER JOIN professional_services ps ON stm.service_type_id = ps.service_type_id
                       WHERE ps.professional_id = ? 
                       AND ps.is_offered = 1 
                       AND sm.is_active = 1
                       AND stm.is_included = 1";
        $stmt = $conn->prepare($modes_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $modes_result = $stmt->get_result();
        $service_modes = [];
        while ($mode = $modes_result->fetch_assoc()) {
            $service_modes[] = $mode['id'];
        }
        
        // Generate time slots
        $start = new DateTime($date . ' ' . $start_time);
        $end = new DateTime($date . ' ' . $end_time);
        $interval = new DateInterval('PT' . $slot_duration . 'M');
        
        $insert_slot_query = "INSERT INTO time_slots 
                            (professional_id, availability_id, date, start_time, end_time, service_mode_id, is_booked) 
                            VALUES (?, ?, ?, ?, ?, ?, 0)
                            ON DUPLICATE KEY UPDATE is_booked = is_booked"; // Keep existing booking status
        $stmt = $conn->prepare($insert_slot_query);
        
        while ($start < $end) {
            $slot_start = $start->format('H:i:s');
            $start->add($interval);
            $slot_end = $start->format('H:i:s');
            
            // Create a slot for each service mode
            foreach ($service_modes as $mode_id) {
                $stmt->bind_param("iisssi", 
                    $user_id, 
                    $availability_id, 
                    $date, 
                    $slot_start, 
                    $slot_end, 
                    $mode_id
                );
                $stmt->execute();
            }
        }
        
        $conn->commit();
        $success_message = "Time slots created successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error creating time slots: " . $e->getMessage();
    }
}

// Process removing availability
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['date'])) {
    $date = $_GET['date'];
    
    // Update consultant_availability to mark day as unavailable
    $stmt = $conn->prepare("UPDATE consultant_availability SET is_available = 0 WHERE professional_id = ? AND date = ?");
    $stmt->bind_param("is", $user_id, $date);
    $stmt->execute();
    
    // Redirect to remove query params
    header("Location: calendar.php?month=$month&year=$year");
    exit();
}

// Get available days and booked slots
$available_days_query = "SELECT DISTINCT ca.date 
                        FROM consultant_availability ca
                        WHERE ca.professional_id = ? 
                        AND ca.is_available = 1 
                        AND ca.date >= CURDATE()";
$stmt = $conn->prepare($available_days_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$available_days = [];
while ($row = $result->fetch_assoc()) {
    $available_days[] = $row['date'];
}

// Get booked appointments with time slot status
$appointments_query = "SELECT b.*, u.name as client_name, st.name as service_type, sm.name as service_mode,
                      ts.date, ts.start_time, ts.end_time, ts.is_booked,
                      (SELECT COUNT(*) FROM time_slots ts2 
                       WHERE ts2.professional_id = ts.professional_id 
                       AND ts2.date = ts.date 
                       AND ts2.start_time = ts.start_time 
                       AND ts2.is_booked = 1) as total_booked_modes
                      FROM bookings b
                      INNER JOIN users u ON b.client_id = u.id
                      INNER JOIN service_types st ON b.service_type_id = st.id
                      INNER JOIN service_modes sm ON b.service_mode_id = sm.id
                      INNER JOIN time_slots ts ON b.time_slot_id = ts.id
                      WHERE b.professional_id = ? 
                      AND ts.date >= CURDATE()
                      AND b.status != 'cancelled'
                      ORDER BY ts.date, ts.start_time";
$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = [];
while ($row = $result->fetch_assoc()) {
    $key = $row['date'] . ' ' . $row['start_time'];
    $appointments[$key] = [
        'client_name' => $row['client_name'],
        'service_type' => $row['service_type'],
        'service_mode' => $row['service_mode'],
        'is_fully_booked' => $row['total_booked_modes'] > 0 // If any mode is booked, slot is unavailable
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - Professional Dashboard - Visafy</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        .calendar {
            width: 100%;
            border-collapse: collapse;
        }
        
        .calendar th, .calendar td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        
        .calendar th {
            background-color: #f5f7fa;
        }
        
        .calendar .other-month {
            color: #ccc;
        }
        
        .calendar .today {
            background-color: #e8f4ff;
            font-weight: bold;
        }
        
        .calendar .available {
            background-color: #e6f7e6;
        }
        
        .calendar .booked {
            background-color: #f7e6e6;
        }
        
        .calendar-day {
            min-height: 80px;
            position: relative;
        }
        
        .day-number {
            position: absolute;
            top: 5px;
            left: 5px;
            font-weight: bold;
        }
        
        .day-content {
            margin-top: 25px;
            font-size: 12px;
        }
        
        .appointment {
            background-color: #3498db;
            color: white;
            border-radius: 3px;
            padding: 2px 4px;
            margin-bottom: 2px;
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .availability-controls {
            margin-top: 5px;
        }
        
        .month-nav {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="profile-section">
                <img src="../../assets/images/professional-avatar.png" alt="Profile" class="profile-image">
                <h3><?php echo $_SESSION['username']; ?></h3>
                <p>Visa Professional</p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item"><a href="index.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="cases.php" class="nav-link">Cases</a></li>
                <li class="nav-item"><a href="clients.php" class="nav-link">Clients</a></li>
                <li class="nav-item"><a href="documents.php" class="nav-link">Documents</a></li>
                <li class="nav-item"><a href="calendar.php" class="nav-link active">Calendar</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link">Profile</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Calendar & Availability</h1>
                <div>
                    <button id="addAvailabilityBtn" class="button">Set Availability</button>
                </div>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div id="availabilityFormContainer" class="card" style="display: none;">
                <h2 class="card-title">Set Availability</h2>
                <form method="POST" action="calendar.php">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" class="form-control" required value="09:00">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" class="form-control" required value="17:00">
                    </div>
                    
                    <div class="form-group">
                        <label for="slot_duration">Slot Duration (minutes)</label>
                        <select id="slot_duration" name="slot_duration" class="form-control" required>
                            <option value="30">30 minutes</option>
                            <option value="60" selected>1 hour</option>
                            <option value="90">1.5 hours</option>
                            <option value="120">2 hours</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="create_slots" class="button">Create Time Slots</button>
                        <button type="button" onclick="hideAvailabilityForm()" class="button button-secondary">Cancel</button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="month-nav">
                    <a href="?month=<?php echo $month == 1 ? 12 : $month - 1; ?>&year=<?php echo $month == 1 ? $year - 1 : $year; ?>" class="button button-small">&lt; Previous Month</a>
                    <h2><?php echo date('F Y', $first_day); ?></h2>
                    <a href="?month=<?php echo $month == 12 ? 1 : $month + 1; ?>&year=<?php echo $month == 12 ? $year + 1 : $year; ?>" class="button button-small">Next Month &gt;</a>
                </div>
                
                <table class="calendar">
                    <tr>
                        <th>Sunday</th>
                        <th>Monday</th>
                        <th>Tuesday</th>
                        <th>Wednesday</th>
                        <th>Thursday</th>
                        <th>Friday</th>
                        <th>Saturday</th>
                    </tr>
                    
                    <?php
                    // Calendar generation logic
                    $day_count = 1;
                    $calendar_rows = ceil(($num_days + $day_of_week) / 7);
                    
                    for ($i = 0; $i < $calendar_rows; $i++) {
                        echo "<tr>";
                        
                        for ($j = 0; $j < 7; $j++) {
                            $current_day = $day_count - $day_of_week;
                            
                            // Determine if this day belongs to the current month
                            if ($current_day <= 0 || $current_day > $num_days) {
                                echo '<td class="other-month"></td>';
                            } else {
                                // Format the date for comparison
                                $date_string = sprintf('%04d-%02d-%02d', $year, $month, $current_day);
                                
                                // Determine if this day is available
                                $is_available = in_array($date_string, $available_days);
                                
                                // Determine if this day has appointments
                                $day_appointments = [];
                                foreach ($appointments as $time => $appointment) {
                                    if (strpos($time, $date_string) === 0) {
                                        $day_appointments[] = [
                                            'time' => date('H:i', strtotime($time)),
                                            'client_name' => $appointment['client_name'],
                                            'service_type' => $appointment['service_type']
                                        ];
                                    }
                                }
                                
                                // Determine if this is today
                                $is_today = date('Y-m-d') === $date_string;
                                
                                // Build the class string
                                $class = 'calendar-day';
                                if ($is_today) $class .= ' today';
                                if ($is_available) $class .= ' available';
                                if (!empty($day_appointments)) $class .= ' booked';
                                
                                echo '<td class="' . $class . '">';
                                echo '<div class="day-number">' . $current_day . '</div>';
                                echo '<div class="day-content">';
                                
                                // Show appointments for this day
                                foreach ($day_appointments as $apt) {
                                    echo '<div class="appointment" title="' . $apt['client_name'] . ' - ' . $apt['service_type'] . '">';
                                    echo $apt['time'] . ' - ' . $apt['client_name'];
                                    echo '</div>';
                                }
                                
                                // Show availability controls if available or not past date
                                if (strtotime($date_string) >= strtotime(date('Y-m-d'))) {
                                    echo '<div class="availability-controls">';
                                    if ($is_available) {
                                        echo '<a href="?action=remove&date=' . $date_string . '&month=' . $month . '&year=' . $year . '" class="button button-small button-delete" onclick="return confirm(\'Remove availability for this date?\')">Remove</a>';
                                    } else {
                                        echo '<a href="#" class="button button-small set-availability" data-date="' . $date_string . '">Set Available</a>';
                                    }
                                    echo '</div>';
                                }
                                
                                echo '</div>';
                                echo '</td>';
                            }
                            
                            $day_count++;
                        }
                        
                        echo "</tr>";
                    }
                    ?>
                </table>
            </div>
            
            <div class="card">
                <h2 class="card-title">Upcoming Appointments</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Date & Time</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Client</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Service Type</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Mode</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Status</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">July 10, 2023 10:00 AM</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">John Doe</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Consultation</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Video Call</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Confirmed</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                            <a href="appointment_details.php?id=1" class="button button-small">Details</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">July 15, 2023 2:30 PM</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Jane Smith</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Document Review</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Video Call</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Confirmed</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                            <a href="appointment_details.php?id=2" class="button button-small">Details</a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function showAvailabilityForm() {
            document.getElementById('availabilityFormContainer').style.display = 'block';
        }
        
        function hideAvailabilityForm() {
            document.getElementById('availabilityFormContainer').style.display = 'none';
        }
        
        document.getElementById('addAvailabilityBtn').addEventListener('click', showAvailabilityForm);
        
        // Set date for quick availability setting
        document.querySelectorAll('.set-availability').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const dateString = this.getAttribute('data-date');
                document.getElementById('date').value = dateString;
                document.getElementById('availabilityFormContainer').style.display = 'block';
                document.getElementById('date').scrollIntoView();
            });
        });
    </script>
</body>
</html> 