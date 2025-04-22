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

// Handle time slot creation if form submitted
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_slots'])) {
    // Process time slot creation
    $success_message = "Time slots created successfully!";
}

// Process removing availability if requested
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['date'])) {
    $date = $_GET['date'];
    // Remove availability for this date
    // removeAvailability($user_id, $date);
    
    // Redirect to remove query params
    header("Location: calendar.php?month=$month&year=$year");
    exit();
}

// Get booked appointments - would come from the database
$appointments = [
    '2023-07-10 10:00:00' => [
        'client_name' => 'John Doe',
        'service_type' => 'Consultation',
        'service_mode' => 'Video Call'
    ],
    '2023-07-15 14:30:00' => [
        'client_name' => 'Jane Smith',
        'service_type' => 'Document Review',
        'service_mode' => 'Video Call'
    ]
];

// Get available days - would come from the database
$available_days = [
    '2023-07-05', '2023-07-06', '2023-07-07',
    '2023-07-10', '2023-07-11', '2023-07-12', '2023-07-13', '2023-07-14',
    '2023-07-17', '2023-07-18', '2023-07-19', '2023-07-20', '2023-07-21',
    '2023-07-24', '2023-07-25', '2023-07-26', '2023-07-27', '2023-07-28'
];
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
                        <label for="service_mode">Service Mode</label>
                        <select id="service_mode" name="service_mode_id" class="form-control" required>
                            <option value="">-- Select Mode --</option>
                            <option value="1">Chat</option>
                            <option value="2">Video Call</option>
                            <option value="3">Phone Call</option>
                            <option value="4">Email</option>
                            <option value="5">Document Review</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="slot_duration">Slot Duration (minutes)</label>
                        <select id="slot_duration" name="slot_duration" class="form-control" required>
                            <option value="30">30 minutes</option>
                            <option value="60" selected>1 hour</option>
                            <option value="90">1 hour 30 minutes</option>
                            <option value="120">2 hours</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="create_slots" class="button">Create Time Slots</button>
                        <button type="button" id="cancelAvailability" class="button button-secondary">Cancel</button>
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
        document.getElementById('addAvailabilityBtn').addEventListener('click', function() {
            document.getElementById('availabilityFormContainer').style.display = 'block';
        });
        
        document.getElementById('cancelAvailability').addEventListener('click', function() {
            document.getElementById('availabilityFormContainer').style.display = 'none';
        });
        
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