<?php
// Set page variables
$page_title = "Availability";
$page_header = "Manage Availability";

// Start output buffering to prevent "headers already sent" errors
ob_start();

// Include header (handles session and authentication)
require_once 'includes/header.php';

// Get user_id from session (already verified in header.php)
$user_id = $_SESSION['user_id'];

// Default to today if no date specified
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$date_obj = new DateTime($selected_date);

// Get previous and next days
$prev_date_obj = clone $date_obj;
$prev_date_obj->modify('-1 day');
$prev_date = $prev_date_obj->format('Y-m-d');

$next_date_obj = clone $date_obj;
$next_date_obj->modify('+1 day');
$next_date = $next_date_obj->format('Y-m-d');

// Initialize messages
$success_message = '';
$error_message = '';

// Process form submissions first, before any content is output
$redirect_needed = false;
$redirect_url = '';

// Get all service modes
$service_modes_query = "SELECT * FROM service_modes WHERE is_active = 1";
$service_modes_result = $conn->query($service_modes_query);
$service_modes = [];
while ($mode = $service_modes_result->fetch_assoc()) {
    $service_modes[$mode['id']] = $mode;
}

// Get professional's available service types
$service_types_query = "SELECT st.* FROM service_types st 
                       INNER JOIN professional_services ps ON st.id = ps.service_type_id
                       WHERE ps.professional_id = ? AND ps.is_offered = 1 AND st.is_active = 1";
$stmt = $conn->prepare($service_types_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$service_types_result = $stmt->get_result();
$service_types = [];
while ($type = $service_types_result->fetch_assoc()) {
    $service_types[$type['id']] = $type;
}

// Get service type-mode mappings
$type_modes_query = "SELECT stm.service_type_id, stm.service_mode_id, stm.is_included 
                    FROM service_type_modes stm
                    INNER JOIN professional_services ps ON stm.service_type_id = ps.service_type_id
                    WHERE ps.professional_id = ? AND ps.is_offered = 1";
$stmt = $conn->prepare($type_modes_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$type_modes_result = $stmt->get_result();
$type_modes = [];
while ($mapping = $type_modes_result->fetch_assoc()) {
    if ($mapping['is_included']) {
        $type_id = $mapping['service_type_id'];
        $mode_id = $mapping['service_mode_id'];
        if (!isset($type_modes[$type_id])) {
            $type_modes[$type_id] = [];
        }
        $type_modes[$type_id][] = $mode_id;
    }
}

// Check if day is marked as available
$availability_query = "SELECT id, is_available FROM consultant_availability 
                      WHERE professional_id = ? AND date = ?";
$stmt = $conn->prepare($availability_query);
$stmt->bind_param("is", $user_id, $selected_date);
$stmt->execute();
$availability_result = $stmt->get_result();

if ($availability_result->num_rows > 0) {
    $availability = $availability_result->fetch_assoc();
    $availability_id = $availability['id'];
    $is_day_available = $availability['is_available'];
} else {
    $availability_id = null;
    $is_day_available = 0;
}

// Get time slots for the selected date
$time_slots_query = "SELECT ts.*, sm.name as mode_name, sm.id as mode_id
                    FROM time_slots ts
                    JOIN service_modes sm ON ts.service_mode_id = sm.id 
                    WHERE ts.professional_id = ? AND ts.date = ?
                    ORDER BY ts.start_time, sm.id";
$stmt = $conn->prepare($time_slots_query);
$stmt->bind_param("is", $user_id, $selected_date);
$stmt->execute();
$time_slots_result = $stmt->get_result();

// Organize time slots by time and mode
$time_slots = [];
while ($slot = $time_slots_result->fetch_assoc()) {
    $time_key = $slot['start_time'] . '-' . $slot['end_time'];
    if (!isset($time_slots[$time_key])) {
        $time_slots[$time_key] = [
            'start_time' => $slot['start_time'],
            'end_time' => $slot['end_time'],
            'modes' => []
        ];
    }
    $time_slots[$time_key]['modes'][$slot['mode_id']] = [
        'id' => $slot['id'],
        'mode_name' => $slot['mode_name'],
        'is_booked' => $slot['is_booked'],
        'is_enabled' => true // By default, if the slot exists, it's enabled
    ];
}

// Handle mode toggles for entire day
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_day_modes'])) {
    try {
        $conn->begin_transaction();
        
        // First ensure the day is available
        if (!$availability_id) {
            $insert_query = "INSERT INTO consultant_availability (professional_id, date, is_available) VALUES (?, ?, 1)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("is", $user_id, $selected_date);
            $stmt->execute();
            $availability_id = $conn->insert_id;
            $is_day_available = 1;
        } else if ($is_day_available == 0) {
            $update_query = "UPDATE consultant_availability SET is_available = 1 WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("i", $availability_id);
            $stmt->execute();
            $is_day_available = 1;
        }
        
        // Process each mode toggle
        foreach ($service_modes as $mode_id => $mode) {
            $is_mode_enabled = isset($_POST['mode_' . $mode_id]) ? 1 : 0;
            
            if ($is_mode_enabled) {
                // Check if we need to create time slots for this mode
                $slot_count_query = "SELECT COUNT(*) as count FROM time_slots 
                                    WHERE professional_id = ? AND date = ? AND service_mode_id = ?";
                $stmt = $conn->prepare($slot_count_query);
                $stmt->bind_param("isi", $user_id, $selected_date, $mode_id);
                $stmt->execute();
                $count_result = $stmt->get_result();
                $slot_count = $count_result->fetch_assoc()['count'];
                
                if ($slot_count == 0) {
                    // Get all distinct time slots that currently exist for this date
                    $distinct_slots_query = "SELECT DISTINCT start_time, end_time 
                                            FROM time_slots 
                                            WHERE professional_id = ? AND date = ?
                                            ORDER BY start_time";
                    $stmt = $conn->prepare($distinct_slots_query);
                    $stmt->bind_param("is", $user_id, $selected_date);
                    $stmt->execute();
                    $distinct_slots_result = $stmt->get_result();
                    $existing_slots = [];
                    
                    while ($row = $distinct_slots_result->fetch_assoc()) {
                        $existing_slots[] = [
                            'start_time' => $row['start_time'],
                            'end_time' => $row['end_time']
                        ];
                    }
                    
                    // If no existing slots, create default time slots
                    if (empty($existing_slots)) {
                        // Default time slots (9am-5pm, 1-hour slots)
                        $start_time = '09:00:00';
                        $end_time = '17:00:00';
                        $slot_duration = 60; // minutes
                        
                        $start = new DateTime($selected_date . ' ' . $start_time);
                        $end = new DateTime($selected_date . ' ' . $end_time);
                        $interval = new DateInterval('PT' . $slot_duration . 'M');
                        
                        $insert_slot_query = "INSERT INTO time_slots 
                                           (professional_id, availability_id, date, start_time, end_time, service_mode_id, is_booked) 
                                           VALUES (?, ?, ?, ?, ?, ?, 0)";
                        $stmt = $conn->prepare($insert_slot_query);
                        
                        while ($start < $end) {
                            $slot_start = $start->format('H:i:s');
                            $start->add($interval);
                            $slot_end = $start->format('H:i:s');
                            
                            // Check if a slot at this start_time exists with a different service_mode_id
                            $check_slot_query = "SELECT id FROM time_slots
                                              WHERE professional_id = ? AND date = ? AND start_time = ?";
                            $check_stmt = $conn->prepare($check_slot_query);
                            $check_stmt->bind_param("iss", $user_id, $selected_date, $slot_start);
                            $check_stmt->execute();
                            $existing_slot = $check_stmt->get_result()->fetch_assoc();
                            
                            if ($existing_slot) {
                                // Skip this slot as it already exists with another service mode
                                continue;
                            }
                            
                            // Insert the new slot
                            $stmt->bind_param("iisssi", 
                                $user_id, 
                                $availability_id, 
                                $selected_date, 
                                $slot_start, 
                                $slot_end, 
                                $mode_id
                            );
                            
                            try {
                                $stmt->execute();
                            } catch (Exception $e) {
                                // If it's a duplicate key error, just move on
                                if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                                    throw $e;
                                }
                            }
                        }
                    } else {
                        // Use existing time patterns - but stagger the start times slightly to avoid collision
                        // Each service mode will start 1 second later than the previous one
                        // This allows us to maintain the uniqueness constraint while keeping the visual time the same
                        
                        // First, get the highest used mode_id for existing slots with the same start_time pattern
                        $max_mode_query = "SELECT MAX(service_mode_id) as max_mode FROM time_slots
                                          WHERE professional_id = ? AND date = ? AND start_time IN 
                                          (SELECT DISTINCT start_time FROM time_slots
                                           WHERE professional_id = ? AND date = ?)";
                        $max_stmt = $conn->prepare($max_mode_query);
                        $max_stmt->bind_param("isis", $user_id, $selected_date, $user_id, $selected_date);
                        $max_stmt->execute();
                        $max_result = $max_stmt->get_result();
                        $max_mode = $max_result->fetch_assoc()['max_mode'];
                        
                        $insert_slot_query = "INSERT INTO time_slots 
                                           (professional_id, availability_id, date, start_time, end_time, service_mode_id, is_booked) 
                                           VALUES (?, ?, ?, ?, ?, ?, 0)";
                        $stmt = $conn->prepare($insert_slot_query);
                        
                        foreach ($existing_slots as $slot) {
                            // Offset the start time by 1 second per service mode to avoid collisions
                            // while keeping the visual display time essentially the same
                            $original_start = new DateTime($slot['start_time']);
                            $mode_offset = $mode_id - $max_mode - 1;
                            if ($mode_offset < 0) $mode_offset = abs($mode_offset);
                            
                            // Only offset if there's a collision risk
                            $adjusted_start = clone $original_start;
                            if ($mode_offset > 0) {
                                $adjusted_start->modify("+{$mode_offset} second");
                            }
                            
                            $adjusted_start_str = $adjusted_start->format('H:i:s');
                            
                            // Check if a slot with this exact time and service mode already exists
                            $check_query = "SELECT COUNT(*) as count FROM time_slots 
                                          WHERE professional_id = ? AND date = ? AND start_time = ? AND service_mode_id = ?";
                            $check_stmt = $conn->prepare($check_query);
                            $check_stmt->bind_param("issi", $user_id, $selected_date, $adjusted_start_str, $mode_id);
                            $check_stmt->execute();
                            $check_result = $check_stmt->get_result();
                            $exists = $check_result->fetch_assoc()['count'] > 0;
                            
                            if (!$exists) {
                                $stmt->bind_param("iisssi", 
                                    $user_id, 
                                    $availability_id, 
                                    $selected_date, 
                                    $adjusted_start_str, 
                                    $slot['end_time'], 
                                    $mode_id
                                );
                                
                                try {
                                    $stmt->execute();
                                } catch (Exception $e) {
                                    // If it's a duplicate key error, just move on
                                    if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                                        throw $e;
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                // Delete any unbooked time slots for this mode
                $delete_slots_query = "DELETE FROM time_slots 
                                      WHERE professional_id = ? 
                                      AND date = ? 
                                      AND service_mode_id = ? 
                                      AND is_booked = 0";
                $stmt = $conn->prepare($delete_slots_query);
                $stmt->bind_param("isi", $user_id, $selected_date, $mode_id);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        $success_message = "Service mode availability updated successfully.";
        
        // Set redirect flag instead of immediately redirecting
        $redirect_needed = true;
        $redirect_url = "availability.php?date=$selected_date";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error updating mode availability: " . $e->getMessage();
    }
}

// Handle create/edit time slots
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_time_slots'])) {
    try {
        $conn->begin_transaction();
        
        // Ensure we have an availability record
        if (!$availability_id) {
            $insert_query = "INSERT INTO consultant_availability (professional_id, date, is_available) VALUES (?, ?, 1)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("is", $user_id, $selected_date);
            $stmt->execute();
            $availability_id = $conn->insert_id;
            $is_day_available = 1;
        }
        
        // Process time slot creation
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $slot_duration = $_POST['slot_duration'];
        
        // Create time slots for all enabled modes
        $enabled_modes = [];
        foreach ($service_modes as $mode_id => $mode) {
            if (isset($_POST['slot_mode_' . $mode_id])) {
                $enabled_modes[] = $mode_id;
            }
        }
        
        if (!empty($enabled_modes)) {
            $start = new DateTime($selected_date . ' ' . $start_time);
            $end = new DateTime($selected_date . ' ' . $end_time);
            $interval = new DateInterval('PT' . $slot_duration . 'M');
            
            // First, check for any existing slots in the same time range to avoid conflicts
            $check_existing_query = "SELECT id FROM time_slots 
                                    WHERE professional_id = ? 
                                    AND date = ? 
                                    AND ((start_time >= ? AND start_time < ?) 
                                    OR (end_time > ? AND end_time <= ?) 
                                    OR (start_time <= ? AND end_time >= ?))";
            $stmt = $conn->prepare($check_existing_query);
            
            // Delete existing slots that overlap with the new time range (only if not booked)
            $delete_overlap_query = "DELETE FROM time_slots 
                                   WHERE professional_id = ? 
                                   AND date = ? 
                                   AND service_mode_id = ? 
                                   AND is_booked = 0 
                                   AND ((start_time >= ? AND start_time < ?) 
                                   OR (end_time > ? AND end_time <= ?) 
                                   OR (start_time <= ? AND end_time >= ?))";
            $delete_stmt = $conn->prepare($delete_overlap_query);
            
            // Insert new time slots
            $insert_slot_query = "INSERT INTO time_slots 
                                (professional_id, availability_id, date, start_time, end_time, service_mode_id, is_booked) 
                                VALUES (?, ?, ?, ?, ?, ?, 0)";
            $insert_stmt = $conn->prepare($insert_slot_query);
            
            while ($start < $end) {
                $slot_start = $start->format('H:i:s');
                $start->add($interval);
                $slot_end = $start->format('H:i:s');
                
                foreach ($enabled_modes as $mode_id) {
                    // Delete any existing unbooked slots for this time range and mode
                    $delete_stmt->bind_param("isississs", 
                        $user_id, 
                        $selected_date, 
                        $mode_id,
                        $slot_start,
                        $slot_end,
                        $slot_start,
                        $slot_end,
                        $slot_start,
                        $slot_end
                    );
                    $delete_stmt->execute();
                    
                    // Insert the new slot
                    $insert_stmt->bind_param("iisssi", 
                        $user_id, 
                        $availability_id, 
                        $selected_date, 
                        $slot_start, 
                        $slot_end, 
                        $mode_id
                    );
                    try {
                        $insert_stmt->execute();
                    } catch (Exception $e) {
                        // If it's a duplicate key error, just ignore and continue
                        if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                            throw $e;
                        }
                    }
                }
            }
            
            $conn->commit();
            $success_message = "Time slots created successfully.";
            
            // Set redirect flag instead of immediately redirecting
            $redirect_needed = true;
            $redirect_url = "availability.php?date=$selected_date";
        } else {
            $error_message = "Please select at least one service mode.";
            $conn->rollback();
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error creating time slots: " . $e->getMessage();
    }
}

// Handle toggle day availability
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_day_availability'])) {
    $new_status = isset($_POST['is_day_available']) ? 1 : 0;
    
    try {
        $conn->begin_transaction();
        
        if ($availability_id) {
            // Update existing record
            $update_query = "UPDATE consultant_availability SET is_available = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ii", $new_status, $availability_id);
            $stmt->execute();
        } else {
            // Insert new record
            $insert_query = "INSERT INTO consultant_availability (professional_id, date, is_available) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("isi", $user_id, $selected_date, $new_status);
            $stmt->execute();
            $availability_id = $conn->insert_id;
        }
        
        // If toggling off, cancel any pending bookings
        if ($new_status == 0) {
            $cancel_bookings_query = "UPDATE bookings b 
                                    JOIN time_slots ts ON b.time_slot_id = ts.id 
                                    SET b.status = 'cancelled' 
                                    WHERE ts.professional_id = ? 
                                    AND ts.date = ? 
                                    AND b.status = 'pending'";
            $stmt = $conn->prepare($cancel_bookings_query);
            $stmt->bind_param("is", $user_id, $selected_date);
            $stmt->execute();
            
            // If toggling off, also delete time slots that aren't booked
            $delete_slots_query = "DELETE FROM time_slots 
                                  WHERE professional_id = ? 
                                  AND date = ? 
                                  AND is_booked = 0";
            $stmt = $conn->prepare($delete_slots_query);
            $stmt->bind_param("is", $user_id, $selected_date);
            $stmt->execute();
        }
        
        $conn->commit();
        $is_day_available = $new_status;
        $success_message = "Day availability updated successfully.";
        
        // Set redirect flag instead of immediately redirecting
        $redirect_needed = true;
        $redirect_url = "availability.php?date=$selected_date";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error updating availability: " . $e->getMessage();
    }
}

// Perform redirects if needed, before any content is output
if ($redirect_needed && !empty($redirect_url)) {
    // Clean any buffered output
    ob_clean();
    header("Location: $redirect_url");
    exit;
}

// Load data for display
// Get data for weekly calendar
$week_start = clone $date_obj;
$week_start->modify('Monday this week');
$week_dates = [];
for ($i = 0; $i < 7; $i++) {
    $day = clone $week_start;
    $day->modify("+$i days");
    $week_dates[] = $day->format('Y-m-d');
}

// Get availability for the week
$week_availability_query = "SELECT date, is_available FROM consultant_availability 
                          WHERE professional_id = ? AND date IN ('" . implode("','", $week_dates) . "')";
$stmt = $conn->prepare($week_availability_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$week_availability_result = $stmt->get_result();
$week_availability = [];
while ($row = $week_availability_result->fetch_assoc()) {
    $week_availability[$row['date']] = $row['is_available'];
}

// Get bookings for the week
$week_bookings_query = "SELECT b.id, b.status, b.client_id, ts.date, ts.start_time, ts.end_time
                       FROM bookings b
                       JOIN time_slots ts ON b.time_slot_id = ts.id
                       WHERE b.professional_id = ? 
                       AND ts.date IN ('" . implode("','", $week_dates) . "')
                       AND b.status != 'cancelled'";
$stmt = $conn->prepare($week_bookings_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$week_bookings_result = $stmt->get_result();
$week_bookings = [];
while ($row = $week_bookings_result->fetch_assoc()) {
    $date = $row['date'];
    if (!isset($week_bookings[$date])) {
        $week_bookings[$date] = [];
    }
    $week_bookings[$date][] = $row;
}

// Page specific CSS
$page_specific_css = '
/* Calendar-specific styles */
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

.header-actions {
    margin-bottom: 20px;
}
';

// Page specific JavaScript
$page_js = '
// Calendar-specific functions
function showAvailabilityForm() {
    document.getElementById("availabilityFormContainer").style.display = "block";
}

function hideAvailabilityForm() {
    document.getElementById("availabilityFormContainer").style.display = "none";
}

// Set up event handlers
document.getElementById("addAvailabilityBtn").addEventListener("click", showAvailabilityForm);

document.querySelectorAll(".set-availability").forEach(link => {
    link.addEventListener("click", function(e) {
        e.preventDefault();
        const dateString = this.getAttribute("data-date");
        document.getElementById("date").value = dateString;
        document.getElementById("availabilityFormContainer").style.display = "block";
        document.getElementById("date").scrollIntoView();
    });
});
';

// Include header
include_once('includes/header.php');
?>


            
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

<div id="availabilityFormContainer" class="card" style="display: none;">
    <h2 class="card-title">Set Availability</h2>
    <form method="POST" action="availability.php">
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
            <button type="submit" name="update_time_slots" class="button">Create Time Slots</button>
            <button type="button" onclick="hideAvailabilityForm()" class="button button-secondary">Cancel</button>
        </div>
    </form>
</div>

<div class="content-wrapper">
    <!-- Day Navigation -->
    <div class="day-navigation">
        <a href="?date=<?php echo $prev_date; ?>" class="nav-button"><i class="fas fa-chevron-left"></i> Previous Day</a>
        <h2><?php echo $date_obj->format('l, F j, Y'); ?></h2>
        <a href="?date=<?php echo $next_date; ?>" class="nav-button">Next Day <i class="fas fa-chevron-right"></i></a>
    </div>

    <!-- Day Availability Toggle -->
    <div class="card">
        <div class="card-header">
            <h3>Set your availability for <?php echo $date_obj->format('l'); ?></h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="toggle-container">
                    <label class="toggle-label">
                        <span>Make this day available</span>
                        <label class="toggle-checkbox">
                            <input type="checkbox" name="is_day_available" <?php echo $is_day_available ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </label>
                </div>
                <button type="submit" name="toggle_day_availability" class="button">Save Day Availability</button>
            </form>
        </div>
    </div>

    <?php if ($is_day_available): ?>
    <!-- Service Mode Toggles -->
    <div class="card">
        <div class="card-header">
            <h3>Service Mode Availability</h3>
            <p>Toggle which service modes you're available for on this day</p>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="service-mode-toggles">
                    <?php foreach ($service_modes as $mode_id => $mode): 
                        // Check if any slots exist for this mode
                        $mode_exists = false;
                        foreach ($time_slots as $time_key => $slot_info) {
                            if (isset($slot_info['modes'][$mode_id])) {
                                $mode_exists = true;
                                break;
                            }
                        }
                    ?>
                    <div class="service-mode-toggle">
                        <label class="toggle-label">
                            <span class="mode-name"><?php echo htmlspecialchars($mode['name']); ?></span>
                            <label class="toggle-checkbox">
                                <input type="checkbox" name="mode_<?php echo $mode_id; ?>" <?php echo $mode_exists ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </label>
                        <p class="mode-description"><?php echo htmlspecialchars($mode['description']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="toggle_day_modes" class="button">Update Service Mode Availability</button>
            </form>
        </div>
    </div>

    <!-- Time Slot Management -->
    <div class="card">
        <div class="card-header">
            <h3>Create Time Slots</h3>
            <p>Add new time slots for this day</p>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="time-slot-form">
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" class="form-control" required value="09:00">
                    </div>
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" class="form-control" required value="17:00">
                    </div>
                    <div class="form-group">
                        <label for="slot_duration">Slot Duration</label>
                        <select id="slot_duration" name="slot_duration" class="form-control">
                            <option value="15">15 minutes</option>
                            <option value="30" selected>30 minutes</option>
                            <option value="45">45 minutes</option>
                            <option value="60">1 hour</option>
                            <option value="90">1.5 hours</option>
                            <option value="120">2 hours</option>
                        </select>
                    </div>
                </div>
                <div class="service-mode-selection">
                    <h4>Select Service Modes for These Time Slots</h4>
                    <div class="mode-checkbox-grid">
                        <?php foreach ($service_modes as $mode_id => $mode): 
                            // Check if any time slots exist for this mode
                            $mode_exists = false;
                            foreach ($time_slots as $time_key => $slot_info) {
                                if (isset($slot_info['modes'][$mode_id])) {
                                    $mode_exists = true;
                                    break;
                                }
                            }
                            // Skip if mode is not enabled for the day
                            if (!$mode_exists) continue;
                        ?>
                        <div class="mode-checkbox">
                            <input type="checkbox" id="slot_mode_<?php echo $mode_id; ?>" name="slot_mode_<?php echo $mode_id; ?>" checked>
                            <label for="slot_mode_<?php echo $mode_id; ?>"><?php echo htmlspecialchars($mode['name']); ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" name="update_time_slots" class="button">Create Time Slots</button>
            </form>
        </div>
    </div>

    <!-- Time Slots Display -->
    <div class="card">
        <div class="card-header">
            <h3>Current Time Slots</h3>
        </div>
        <div class="card-body">
            <?php if (empty($time_slots)): ?>
                <div class="no-slots-message">
                    <p>No time slots have been created for this day yet.</p>
                </div>
            <?php else: ?>
                <div class="time-slots-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <?php foreach ($service_modes as $mode_id => $mode): 
                                    // Skip if mode is not enabled for the day
                                    $mode_exists = false;
                                    foreach ($time_slots as $time_key => $slot_info) {
                                        if (isset($slot_info['modes'][$mode_id])) {
                                            $mode_exists = true;
                                            break;
                                        }
                                    }
                                    if (!$mode_exists) continue;
                                ?>
                                <th><?php echo htmlspecialchars($mode['name']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($time_slots as $time_key => $slot_info): ?>
                            <tr>
                                <td class="time-cell">
                                    <?php 
                                        echo date('g:i A', strtotime($slot_info['start_time'])) . ' - ' . 
                                             date('g:i A', strtotime($slot_info['end_time'])); 
                                    ?>
                                </td>
                                <?php foreach ($service_modes as $mode_id => $mode): 
                                    // Skip if mode is not enabled for the day
                                    $mode_exists = false;
                                    foreach ($time_slots as $time_key_check => $slot_info_check) {
                                        if (isset($slot_info_check['modes'][$mode_id])) {
                                            $mode_exists = true;
                                            break;
                                        }
                                    }
                                    if (!$mode_exists) continue;
                                    
                                    $mode_slot = isset($slot_info['modes'][$mode_id]) ? $slot_info['modes'][$mode_id] : null;
                                ?>
                                <td class="mode-cell <?php echo $mode_slot ? ($mode_slot['is_booked'] ? 'booked' : 'available') : 'disabled'; ?>">
                                    <?php if ($mode_slot): ?>
                                        <?php if ($mode_slot['is_booked']): ?>
                                            <span class="slot-status booked">Booked</span>
                                        <?php else: ?>
                                            <span class="slot-status available">Available</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="slot-status disabled">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Weekly Calendar -->
    <div class="card">
        <div class="card-header">
            <h3>Weekly Availability</h3>
        </div>
        <div class="card-body">
            <div class="weekly-calendar">
                <?php foreach ($week_dates as $date): 
                    $day_obj = new DateTime($date);
                    $is_available = isset($week_availability[$date]) && $week_availability[$date] == 1;
                    $is_selected = $date === $selected_date;
                    $has_bookings = isset($week_bookings[$date]) && !empty($week_bookings[$date]);
                    
                    $day_class = "calendar-day";
                    if ($is_selected) $day_class .= " selected";
                    if ($is_available) $day_class .= " available";
                    if ($has_bookings) $day_class .= " has-bookings";
                ?>
                <a href="?date=<?php echo $date; ?>" class="<?php echo $day_class; ?>">
                    <div class="day-name"><?php echo $day_obj->format('D'); ?></div>
                    <div class="day-number"><?php echo $day_obj->format('j'); ?></div>
                    <div class="day-status">
                        <?php if ($is_available): ?>
                            <span class="status-dot available"></span>
                            <span class="status-text">Available</span>
                        <?php else: ?>
                            <span class="status-dot unavailable"></span>
                            <span class="status-text">Unavailable</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($has_bookings): ?>
                        <div class="booking-count">
                            <span class="badge"><?php echo count($week_bookings[$date]); ?> booking<?php echo count($week_bookings[$date]) > 1 ? 's' : ''; ?></span>
                        </div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
.content-wrapper {
    padding: 20px;
    margin: 0 auto;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
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

.day-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.day-navigation h2 {
    margin: 0;
    font-size: 1.5rem;
    color: #2c3e50;
}

.nav-button {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
}

.nav-button:hover {
    background-color: #2980b9;
}

.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    overflow: hidden;
}

.card-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e1e8ed;
}

.card-header h3 {
    margin: 0;
    font-size: 1.2rem;
    color: #2c3e50;
}

.card-header p {
    margin: 5px 0 0 0;
    color: #7f8c8d;
    font-size: 0.9rem;
}

.card-body {
    padding: 20px;
}

.toggle-container {
    margin-bottom: 20px;
}

.toggle-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 0;
    font-weight: 500;
    color: #2c3e50;
}

.toggle-checkbox {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
    margin-left: 15px;
}

.toggle-checkbox input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #3498db;
}

input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

.button {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    font-size: 14px;
}

.button:hover {
    background-color: #2980b9;
}

.service-mode-toggles {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.service-mode-toggle {
    padding: 15px;
    border: 1px solid #e1e8ed;
    border-radius: 6px;
    background-color: #f8f9fa;
}

.mode-name {
    font-weight: 500;
    color: #2c3e50;
}

.mode-description {
    margin-top: 10px;
    font-size: 0.85rem;
    color: #7f8c8d;
}

.time-slot-form {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.form-group label {
    font-weight: 500;
    color: #2c3e50;
}

.form-control {
    padding: 8px 12px;
    border: 1px solid #dce4ec;
    border-radius: 4px;
    font-size: 14px;
}

.service-mode-selection {
    margin-bottom: 20px;
}

.service-mode-selection h4 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 1rem;
    color: #2c3e50;
}

.mode-checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
}

.mode-checkbox {
    display: flex;
    align-items: center;
    gap: 6px;
}

.time-slots-table {
    overflow-x: auto;
}

.time-slots-table table {
    width: 100%;
    border-collapse: collapse;
}

.time-slots-table th,
.time-slots-table td {
    padding: 12px 15px;
    text-align: center;
    border: 1px solid #e1e8ed;
}

.time-slots-table th {
    background-color: #f5f7fa;
    color: #2c3e50;
    font-weight: 500;
}

.time-cell {
    font-weight: 500;
    color: #2c3e50;
    white-space: nowrap;
}

.mode-cell.available {
    background-color: #d4edda;
}

.mode-cell.booked {
    background-color: #f8d7da;
}

.mode-cell.disabled {
    background-color: #f8f9fa;
    color: #adb5bd;
}

.slot-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.slot-status.available {
    background-color: #c3e6cb;
    color: #155724;
}

.slot-status.booked {
    background-color: #f5c6cb;
    color: #721c24;
}

.slot-status.disabled {
    background-color: #e9ecef;
    color: #6c757d;
}

.no-slots-message {
    text-align: center;
    padding: 30px;
    color: #7f8c8d;
    font-style: italic;
}

.weekly-calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
}

.calendar-day {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px;
    border: 1px solid #e1e8ed;
    border-radius: 6px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
}

.calendar-day:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.calendar-day.selected {
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

.calendar-day.available {
    background-color: #f0f9f3;
}

.day-name {
    font-weight: 500;
    color: #7f8c8d;
    font-size: 0.9rem;
}

.day-number {
    font-size: 1.5rem;
    font-weight: 500;
    color: #2c3e50;
    margin: 5px 0;
}

.day-status {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 5px;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-dot.available {
    background-color: #2ecc71;
}

.status-dot.unavailable {
    background-color: #e74c3c;
}

.status-text {
    font-size: 0.75rem;
    color: #7f8c8d;
}

.booking-count {
    margin-top: 10px;
}

.badge {
    display: inline-block;
    padding: 3px 6px;
    background-color: #3498db;
    color: white;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

@media (max-width: 768px) {
    .service-mode-toggles {
        grid-template-columns: 1fr;
    }
    
    .time-slot-form {
        grid-template-columns: 1fr;
    }
    
    .weekly-calendar {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .calendar-day {
        margin-bottom: 10px;
    }
}

@media (max-width: 480px) {
    .weekly-calendar {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .day-navigation {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
    }
    
    .nav-button {
        text-align: center;
    }
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?> 