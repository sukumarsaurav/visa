<?php
// Set page variables
$page_title = "Appointments";
$page_header = "Manage Appointments";

// Include header (handles session and authentication)
require_once 'includes/header.php';

// Get user_id from session (already verified in header.php)
$user_id = $_SESSION['user_id'];

// Initialize variables
$success_message = '';
$error_message = '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Handle appointment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
    $action = $_POST['action'];
    
    if ($booking_id > 0) {
        try {
            $conn->begin_transaction();
            
            // Check if booking exists and belongs to this professional
            $check_query = "SELECT b.id, b.status, t.date, t.start_time 
                          FROM bookings b
                          INNER JOIN time_slots t ON b.time_slot_id = t.id
                          WHERE b.id = ? AND b.professional_id = ? AND b.deleted_at IS NULL";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("ii", $booking_id, $user_id);
            $stmt->execute();
            $booking_result = $stmt->get_result();
            
            if ($booking_result->num_rows > 0) {
                $booking = $booking_result->fetch_assoc();
                $new_status = '';
                $notification_message = '';
                $client_id = 0;
                
                // Get client ID for notification
                $client_query = "SELECT client_id FROM bookings WHERE id = ?";
                $stmt = $conn->prepare($client_query);
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                $client_result = $stmt->get_result();
                if ($client_row = $client_result->fetch_assoc()) {
                    $client_id = $client_row['client_id'];
                }
                
                // Process the action
                switch ($action) {
                    case 'confirm':
                        if ($booking['status'] === 'pending') {
                            $new_status = 'confirmed';
                            $notification_message = "Your appointment on " . date('F j, Y', strtotime($booking['date'])) . 
                                                   " at " . date('g:i A', strtotime($booking['start_time'])) . 
                                                   " has been confirmed by the professional.";
                        }
                        break;
                        
                    case 'complete':
                        if ($booking['status'] === 'confirmed') {
                            $new_status = 'completed';
                            $notification_message = "Your appointment on " . date('F j, Y', strtotime($booking['date'])) . 
                                                   " has been marked as completed. Thank you for using our services.";
                        }
                        break;
                        
                    case 'cancel':
                        if (in_array($booking['status'], ['pending', 'confirmed'])) {
                            $new_status = 'cancelled';
                            $notification_message = "Your appointment on " . date('F j, Y', strtotime($booking['date'])) . 
                                                   " at " . date('g:i A', strtotime($booking['start_time'])) . 
                                                   " has been cancelled by the professional.";
                        }
                        break;
                }
                
                if (!empty($new_status)) {
                    // Update booking status
                    $update_query = "UPDATE bookings SET status = ? WHERE id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("si", $new_status, $booking_id);
                    $stmt->execute();
                    
                    // Send notification to client
                    if (!empty($notification_message) && $client_id > 0) {
                        $notification_title = "Appointment Update";
                        $insert_notification = "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($insert_notification);
                        $stmt->bind_param("iss", $client_id, $notification_title, $notification_message);
                        $stmt->execute();
                    }
                    
                    $conn->commit();
                    $success_message = "Appointment status updated successfully.";
                } else {
                    $error_message = "Invalid action for the current appointment status.";
                    $conn->rollback();
                }
            } else {
                $error_message = "Appointment not found or does not belong to you.";
                $conn->rollback();
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating appointment: " . $e->getMessage();
        }
    } else {
        $error_message = "Invalid appointment ID.";
    }
}

// Build query based on filters
$where_clauses = ["b.professional_id = ? AND b.deleted_at IS NULL"];
$params = [$user_id];
$param_types = "i";

if ($filter_status !== 'all') {
    $where_clauses[] = "b.status = ?";
    $params[] = $filter_status;
    $param_types .= "s";
}

if (!empty($filter_date)) {
    $where_clauses[] = "ts.date = ?";
    $params[] = $filter_date;
    $param_types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Count total appointments for pagination
$count_query = "SELECT COUNT(*) as total 
               FROM bookings b
               INNER JOIN time_slots ts ON b.time_slot_id = ts.id
               WHERE $where_sql";
               
$stmt = $conn->prepare($count_query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$count_result = $stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_appointments = $count_row['total'];
$total_pages = ceil($total_appointments / $per_page);

// Get appointments
$query = "SELECT b.*, u.name as client_name, u.email as client_email, 
         ts.date, ts.start_time, ts.end_time,
         st.name as service_type, sm.name as service_mode
         FROM bookings b
         INNER JOIN users u ON b.client_id = u.id
         INNER JOIN time_slots ts ON b.time_slot_id = ts.id
         INNER JOIN service_types st ON b.service_type_id = st.id
         INNER JOIN service_modes sm ON b.service_mode_id = sm.id
         WHERE $where_sql
         ORDER BY ts.date DESC, ts.start_time ASC
         LIMIT ?, ?";

$params[] = $offset;
$params[] = $per_page;
$param_types .= "ii";

$stmt = $conn->prepare($query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$appointments_result = $stmt->get_result();
$appointments = [];

while ($row = $appointments_result->fetch_assoc()) {
    $appointments[] = $row;
}

// Get unique dates for filter
$dates_query = "SELECT DISTINCT ts.date
               FROM bookings b
               INNER JOIN time_slots ts ON b.time_slot_id = ts.id
               WHERE b.professional_id = ? AND b.deleted_at IS NULL
               ORDER BY ts.date DESC
               LIMIT 30";
               
$stmt = $conn->prepare($dates_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$dates_result = $stmt->get_result();
$available_dates = [];

while ($row = $dates_result->fetch_assoc()) {
    $available_dates[] = $row['date'];
}
?>

<div class="content-wrapper">
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

    <div class="filter-container">
        <form method="GET" action="" class="filter-form">
            <div class="filter-group">
                <label for="status">Status:</label>
                <select id="status" name="status" class="form-control">
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?php echo $filter_status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="date">Date:</label>
                <select id="date" name="date" class="form-control">
                    <option value="">All Dates</option>
                    <?php foreach ($available_dates as $date): ?>
                        <option value="<?php echo $date; ?>" <?php echo $filter_date === $date ? 'selected' : ''; ?>>
                            <?php echo date('F j, Y', strtotime($date)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="button">Apply Filters</button>
            <?php if (!empty($filter_status) && $filter_status !== 'all' || !empty($filter_date)): ?>
                <a href="appointments.php" class="button button-text">Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title">Your Appointments</h2>
        
        <?php if (empty($appointments)): ?>
            <div class="no-data">
                <p>No appointments found matching your filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Mode</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($appointment['date'])); ?></td>
                                <td>
                                    <?php 
                                        echo date('g:i A', strtotime($appointment['start_time'])) . ' - ' . 
                                            date('g:i A', strtotime($appointment['end_time'])); 
                                    ?>
                                </td>
                                <td>
                                    <div class="client-info">
                                        <?php echo htmlspecialchars($appointment['client_name']); ?>
                                        <span class="client-email"><?php echo htmlspecialchars($appointment['client_email']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['service_type']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['service_mode']); ?></td>
                                <td>$<?php echo number_format($appointment['price'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="payment-badge payment-<?php echo $appointment['payment_status']; ?>">
                                        <?php echo ucfirst($appointment['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($appointment['status'] === 'pending'): ?>
                                            <form method="POST" action="" class="inline-form">
                                                <input type="hidden" name="booking_id" value="<?php echo $appointment['id']; ?>">
                                                <input type="hidden" name="action" value="confirm">
                                                <button type="submit" class="button button-small button-success">Confirm</button>
                                            </form>
                                            
                                            <form method="POST" action="" class="inline-form">
                                                <input type="hidden" name="booking_id" value="<?php echo $appointment['id']; ?>">
                                                <input type="hidden" name="action" value="cancel">
                                                <button type="submit" class="button button-small button-danger" 
                                                    onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                    Cancel
                                                </button>
                                            </form>
                                        <?php elseif ($appointment['status'] === 'confirmed'): ?>
                                            <form method="POST" action="" class="inline-form">
                                                <input type="hidden" name="booking_id" value="<?php echo $appointment['id']; ?>">
                                                <input type="hidden" name="action" value="complete">
                                                <button type="submit" class="button button-small button-success">Complete</button>
                                            </form>
                                            
                                            <form method="POST" action="" class="inline-form">
                                                <input type="hidden" name="booking_id" value="<?php echo $appointment['id']; ?>">
                                                <input type="hidden" name="action" value="cancel">
                                                <button type="submit" class="button button-small button-danger" 
                                                    onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                    Cancel
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="no-actions">No actions available</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $filter_status; ?>&date=<?php echo $filter_date; ?>" class="page-link">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&date=<?php echo $filter_date; ?>" 
                           class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $filter_status; ?>&date=<?php echo $filter_date; ?>" class="page-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.content-wrapper {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.filter-container {
    margin-bottom: 20px;
}

.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-weight: 500;
    color: #2c3e50;
}

.form-control {
    padding: 8px 12px;
    border: 1px solid #dce4ec;
    border-radius: 4px;
    font-size: 14px;
    min-width: 200px;
}

.button {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
}

.button:hover {
    background-color: #2980b9;
}

.button-text {
    background: none;
    color: #3498db;
    text-decoration: underline;
}

.button-text:hover {
    background: none;
    color: #2980b9;
}

.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-title {
    padding: 20px;
    margin: 0;
    font-size: 1.2rem;
    color: #2c3e50;
    border-bottom: 1px solid #e1e8ed;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #e1e8ed;
}

.table th {
    background-color: #f8f9fa;
    color: #2c3e50;
    font-weight: 500;
}

.client-info {
    display: flex;
    flex-direction: column;
}

.client-email {
    font-size: 0.85rem;
    color: #7f8c8d;
}

.status-badge,
.payment-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-pending {
    background-color: #ffeeba;
    color: #856404;
}

.status-confirmed {
    background-color: #b8daff;
    color: #004085;
}

.status-completed {
    background-color: #c3e6cb;
    color: #155724;
}

.status-cancelled {
    background-color: #f5c6cb;
    color: #721c24;
}

.payment-unpaid {
    background-color: #f5c6cb;
    color: #721c24;
}

.payment-paid {
    background-color: #c3e6cb;
    color: #155724;
}

.payment-refunded {
    background-color: #ffeeba;
    color: #856404;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.inline-form {
    display: inline;
}

.button-small {
    padding: 4px 8px;
    font-size: 0.75rem;
}

.button-success {
    background-color: #2ecc71;
}

.button-success:hover {
    background-color: #27ae60;
}

.button-danger {
    background-color: #e74c3c;
}

.button-danger:hover {
    background-color: #c0392b;
}

.no-actions {
    color: #7f8c8d;
    font-style: italic;
    font-size: 0.85rem;
}

.no-data {
    padding: 30px;
    text-align: center;
    color: #7f8c8d;
    font-style: italic;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 20px;
    padding: 10px;
}

.page-link {
    padding: 6px 12px;
    border: 1px solid #dce4ec;
    text-decoration: none;
    color: #3498db;
    border-radius: 4px;
}

.page-link:hover {
    background-color: #f8f9fa;
}

.page-link.active {
    background-color: #3498db;
    color: white;
    border-color: #3498db;
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

.table-responsive {
    overflow-x: auto;
}

@media (max-width: 768px) {
    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .form-control {
        width: 100%;
        min-width: auto;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?>
