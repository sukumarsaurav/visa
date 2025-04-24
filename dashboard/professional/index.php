<?php
// Set page variables
$page_title = "Dashboard";
$page_header = "Professional Dashboard";

// Include header (handles session and authentication)
require_once 'includes/header.php';

// Get user_id from session (already verified in header.php)
$user_id = $_SESSION['user_id'];

// Get dashboard statistics
$stats = [
    'total_cases' => 0,
    'active_cases' => 0,
    'pending_documents' => 0,
    'upcoming_appointments' => 0,
    'unread_messages' => 0
];

// Query for total cases
$query = "SELECT COUNT(*) as count FROM case_applications 
          WHERE professional_id = ? AND deleted_at IS NULL";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $stats['total_cases'] = $row['count'];
}

// Query for active cases
$query = "SELECT COUNT(*) as count FROM case_applications 
          WHERE professional_id = ? AND status IN ('in_progress', 'pending_documents', 'review') 
          AND deleted_at IS NULL";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $stats['active_cases'] = $row['count'];
}

// Query for pending documents
$query = "SELECT COUNT(*) as count FROM case_applications 
          WHERE professional_id = ? AND status = 'pending_documents' 
          AND deleted_at IS NULL";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $stats['pending_documents'] = $row['count'];
}

// Query for upcoming appointments
$query = "SELECT COUNT(*) as count FROM bookings b
          INNER JOIN time_slots ts ON b.time_slot_id = ts.id 
          WHERE b.professional_id = ? AND b.status = 'confirmed' 
          AND ts.date >= CURDATE() AND b.deleted_at IS NULL";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $stats['upcoming_appointments'] = $row['count'];
}

// Query for unread messages
$query = "SELECT COUNT(*) as count FROM chat_messages cm 
          INNER JOIN conversations c ON cm.conversation_id = c.id 
          WHERE c.professional_id = ? AND cm.is_read = 0 
          AND cm.sender_id != ? AND cm.deleted_at IS NULL";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $stats['unread_messages'] = $row['count'];
}

// Get recent cases
$recent_cases = [];
$query = "SELECT ca.*, u.name as client_name, vt.name as visa_type_name
          FROM case_applications ca
          INNER JOIN users u ON ca.client_id = u.id
          INNER JOIN visa_types vt ON ca.visa_type_id = vt.id
          WHERE ca.professional_id = ? AND ca.deleted_at IS NULL
          ORDER BY ca.updated_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_cases[] = $row;
}

// Get upcoming appointments
$upcoming_appointments = [];
$query = "SELECT b.*, u.name as client_name, ts.date, ts.start_time, ts.end_time,
          st.name as service_type, sm.name as service_mode
          FROM bookings b
          INNER JOIN users u ON b.client_id = u.id
          INNER JOIN time_slots ts ON b.time_slot_id = ts.id
          INNER JOIN service_types st ON b.service_type_id = st.id
          INNER JOIN service_modes sm ON b.service_mode_id = sm.id
          WHERE b.professional_id = ? AND b.status IN ('pending', 'confirmed')
          AND ts.date >= CURDATE() AND b.deleted_at IS NULL
          ORDER BY ts.date, ts.start_time LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $upcoming_appointments[] = $row;
}
?>
<div class="content-wrapper">
<!-- Stats Cards -->
<div class="stats-container">
    <div class="row">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="icon icon-cases"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['total_cases']; ?></h3>
                <p>Total Cases</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="icon icon-active"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['active_cases']; ?></h3>
                <p>Active Cases</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="icon icon-documents"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['pending_documents']; ?></h3>
                <p>Pending Documents</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="icon icon-calendar"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['upcoming_appointments']; ?></h3>
                <p>Upcoming Appointments</p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Cases -->
<div class="card">
    <h2 class="card-title">Recent Cases</h2>
    
    <?php if (empty($recent_cases)): ?>
        <p class="no-data">No recent cases found.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Client</th>
                        <th>Visa Type</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_cases as $case): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($case['reference_number']); ?></td>
                            <td><?php echo htmlspecialchars($case['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($case['visa_type_name']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($case['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $case['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($case['updated_at'])); ?></td>
                            <td>
                                <a href="case_details.php?id=<?php echo $case['id']; ?>" class="button button-small">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a href="cases.php" class="button">View All Cases</a>
        </div>
    <?php endif; ?>
</div>

<!-- Upcoming Appointments -->
<div class="card">
    <h2 class="card-title">Upcoming Appointments</h2>
    
    <?php if (empty($upcoming_appointments)): ?>
        <p class="no-data">No upcoming appointments found.</p>
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
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcoming_appointments as $appointment): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($appointment['date'])); ?></td>
                            <td>
                                <?php 
                                    echo date('g:i A', strtotime($appointment['start_time'])) . ' - ' . 
                                         date('g:i A', strtotime($appointment['end_time'])); 
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($appointment['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['service_type']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['service_mode']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($appointment['status']); ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <a href="calendar.php" class="button">View Calendar</a>
        </div>
    <?php endif; ?>
</div>
</div>
<!-- Custom CSS for this page -->
<style>
    .content-wrapper {
        padding: 20px;
        margin: 0 auto;
    }
    .stats-container {
        margin-bottom: 30px;
        width: 100%;
    }
    
    .row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -15px;
        width: 100%;
    }
    
    .stat-card {
        flex: 1;
        min-width: 200px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin: 0 15px 30px;
        display: flex;
        align-items: center;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        background-color: #e3f2fd;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        color: #3498db;
        font-size: 24px;
    }
    
    .stat-info h3 {
        margin: 0;
        font-size: 24px;
        color: #2c3e50;
    }
    
    .stat-info p {
        margin: 5px 0 0;
        color: #7f8c8d;
        font-size: 14px;
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table th, .table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .table th {
        font-weight: 500;
        color: #2c3e50;
        background-color: #f5f7fa;
    }
    
    .card-footer {
        margin-top: 20px;
        text-align: right;
    }
    
    .no-data {
        padding: 20px;
        text-align: center;
        color: #7f8c8d;
        font-style: italic;
    }
    
    @media (max-width: 768px) {
        .stat-card {
            min-width: calc(50% - 30px);
        }
    }
    
    @media (max-width: 576px) {
        .stat-card {
            min-width: calc(100% - 30px);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
    }
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?>
