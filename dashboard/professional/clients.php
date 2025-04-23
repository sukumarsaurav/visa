<?php
// Set page variables
$page_title = "My Clients";
$page_header = "Client Management";

// Include header (handles session and authentication)
require_once 'includes/header.php';

// Get user_id from session (already verified in header.php)
$user_id = $_SESSION['user_id'];

// Initialize variables for filtering
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query based on filters
$query_where = " WHERE pc.professional_id = ? AND pc.deleted_at IS NULL";

if ($filter_status != 'all') {
    $query_where .= " AND pc.status = ?";
}

if (!empty($search_term)) {
    $query_where .= " AND (u.name LIKE ? OR u.email LIKE ?)";
}

// Count total clients
$count_query = "SELECT COUNT(*) as total FROM professional_clients pc 
                JOIN users u ON pc.client_id = u.id 
                LEFT JOIN bookings b ON b.client_id = pc.client_id AND b.professional_id = pc.professional_id" . $query_where;

$stmt = $conn->prepare($count_query);

if ($filter_status != 'all' && !empty($search_term)) {
    $search_param = "%" . $search_term . "%";
    $stmt->bind_param("isss", $user_id, $filter_status, $search_param, $search_param);
} elseif ($filter_status != 'all') {
    $stmt->bind_param("is", $user_id, $filter_status);
} elseif (!empty($search_term)) {
    $search_param = "%" . $search_term . "%";
    $stmt->bind_param("iss", $user_id, $search_param, $search_param);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$count_result = $stmt->get_result();
$total_clients = $count_result->fetch_assoc()['total'];
$stmt->close();

// Pagination
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$total_pages = ceil($total_clients / $items_per_page);
$offset = ($current_page - 1) * $items_per_page;

// Get clients with pagination
$query = "SELECT pc.id, pc.client_id, pc.status, pc.created_at, pc.initial_message,
          u.name, u.email, u.profile_picture,
          COUNT(DISTINCT b.id) as total_bookings,
          SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
          COUNT(DISTINCT ca.id) as total_cases,
          MAX(b.created_at) as last_booking_date
          FROM professional_clients pc
          JOIN users u ON pc.client_id = u.id
          LEFT JOIN bookings b ON b.client_id = pc.client_id AND b.professional_id = pc.professional_id AND b.deleted_at IS NULL
          LEFT JOIN case_applications ca ON ca.client_id = pc.client_id AND ca.professional_id = pc.professional_id AND ca.deleted_at IS NULL"
          . $query_where .
          " GROUP BY pc.id
          ORDER BY last_booking_date DESC
          LIMIT ?, ?";

$stmt = $conn->prepare($query);

if ($filter_status != 'all' && !empty($search_term)) {
    $search_param = "%" . $search_term . "%";
    $stmt->bind_param("isssii", $user_id, $filter_status, $search_param, $search_param, $offset, $items_per_page);
} elseif ($filter_status != 'all') {
    $stmt->bind_param("isii", $user_id, $filter_status, $offset, $items_per_page);
} elseif (!empty($search_term)) {
    $search_param = "%" . $search_term . "%";
    $stmt->bind_param("issii", $user_id, $search_param, $search_param, $offset, $items_per_page);
} else {
    $stmt->bind_param("iii", $user_id, $offset, $items_per_page);
}

$stmt->execute();
$clients_result = $stmt->get_result();
$clients = [];
while ($row = $clients_result->fetch_assoc()) {
    $clients[] = $row;
}
$stmt->close();

// Check if there are unread messages from clients
$unread_messages_query = "SELECT COUNT(cm.id) as unread_count, c.client_id 
                         FROM chat_messages cm 
                         JOIN conversations c ON cm.conversation_id = c.id 
                         WHERE c.professional_id = ? AND cm.is_read = 0 
                         AND cm.sender_id != ? AND cm.deleted_at IS NULL
                         GROUP BY c.client_id";
$stmt = $conn->prepare($unread_messages_query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$unread_result = $stmt->get_result();
$unread_messages = [];
while ($row = $unread_result->fetch_assoc()) {
    $unread_messages[$row['client_id']] = $row['unread_count'];
}
$stmt->close();

// Get client document counts 
$documents_query = "SELECT d.client_id, COUNT(d.id) as doc_count 
                   FROM documents d 
                   WHERE d.professional_id = ? AND d.deleted_at IS NULL 
                   GROUP BY d.client_id";
$stmt = $conn->prepare($documents_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$docs_result = $stmt->get_result();
$client_documents = [];
while ($row = $docs_result->fetch_assoc()) {
    $client_documents[$row['client_id']] = $row['doc_count'];
}
$stmt->close();
?>

<!-- Filter and Search Section -->
<div class="filter-container">
    <form action="" method="get" class="filter-form">
        <div class="form-group">
            <label for="status">Filter by Status:</label>
            <select name="status" id="status" class="form-control" onchange="this.form.submit()">
                <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Clients</option>
                <option value="active" <?php echo $filter_status == 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="archived" <?php echo $filter_status == 'archived' ? 'selected' : ''; ?>>Archived</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="search">Search:</label>
            <div class="search-box">
                <input type="text" name="search" id="search" class="form-control" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
            </div>
        </div>
    </form>
</div>

<!-- Stats Container -->
<div class="stats-container">
    <div class="row">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $total_clients; ?></h3>
                <p>Total Clients</p>
            </div>
        </div>
        
        <?php
        // Count clients by status
        $status_query = "SELECT status, COUNT(*) as count FROM professional_clients 
                        WHERE professional_id = ? AND deleted_at IS NULL 
                        GROUP BY status";
        $stmt = $conn->prepare($status_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $status_result = $stmt->get_result();
        $status_counts = [];
        while ($row = $status_result->fetch_assoc()) {
            $status_counts[$row['status']] = $row['count'];
        }
        $stmt->close();
        
        $active_count = isset($status_counts['active']) ? $status_counts['active'] : 0;
        $pending_count = isset($status_counts['pending']) ? $status_counts['pending'] : 0;
        ?>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $active_count; ?></h3>
                <p>Active Clients</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $pending_count; ?></h3>
                <p>Pending Clients</p>
            </div>
        </div>
        
        <?php
        // Get bookings count
        $bookings_query = "SELECT COUNT(*) as count FROM bookings 
                          WHERE professional_id = ? AND deleted_at IS NULL";
        $stmt = $conn->prepare($bookings_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $bookings_result = $stmt->get_result();
        $total_bookings = $bookings_result->fetch_assoc()['count'];
        $stmt->close();
        ?>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $total_bookings; ?></h3>
                <p>Total Bookings</p>
            </div>
        </div>
    </div>
</div>

<!-- Clients List -->
<div class="card">
    <h2 class="card-title">My Clients</h2>
    
    <?php if (empty($clients)): ?>
        <p class="no-data">No clients found. <?php echo !empty($search_term) ? 'Try a different search term.' : ''; ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Status</th>
                        <th>Bookings</th>
                        <th>Cases</th>
                        <th>Documents</th>
                        <th>Last Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td class="client-info">
                                <?php
                                $profile_img = '../assets/img/default-profile.jpg';
                                if (!empty($client['profile_picture'])) {
                                    if (file_exists('../../uploads/profiles/' . $client['profile_picture'])) {
                                        $profile_img = '../../uploads/profiles/' . $client['profile_picture'];
                                    }
                                }
                                ?>
                                <img src="<?php echo $profile_img; ?>" alt="Profile" class="client-avatar">
                                <div>
                                    <div class="client-name"><?php echo htmlspecialchars($client['name']); ?></div>
                                    <div class="client-email"><?php echo htmlspecialchars($client['email']); ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $client['status']; ?>">
                                    <?php echo ucfirst($client['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="booking-count">
                                    <span class="total"><?php echo $client['total_bookings']; ?></span> total /
                                    <span class="completed"><?php echo $client['completed_bookings']; ?></span> completed
                                </div>
                            </td>
                            <td><?php echo $client['total_cases']; ?></td>
                            <td>
                                <?php 
                                $doc_count = isset($client_documents[$client['client_id']]) ? $client_documents[$client['client_id']] : 0;
                                echo $doc_count; 
                                ?>
                            </td>
                            <td>
                                <?php 
                                if (!empty($client['last_booking_date'])) {
                                    echo date('M j, Y', strtotime($client['last_booking_date']));
                                } else {
                                    echo date('M j, Y', strtotime($client['created_at']));
                                }
                                ?>
                            </td>
                            <td class="actions">
                                <div class="dropdown">
                                    <button class="action-button"><i class="fas fa-ellipsis-v"></i></button>
                                    <div class="dropdown-content">
                                        <a href="client_profile.php?id=<?php echo $client['client_id']; ?>">
                                            <i class="fas fa-user"></i> View Profile
                                        </a>
                                        
                                        <a href="messages.php?client_id=<?php echo $client['client_id']; ?>">
                                            <i class="fas fa-envelope"></i> Message 
                                            <?php if (isset($unread_messages[$client['client_id']]) && $unread_messages[$client['client_id']] > 0): ?>
                                                <span class="unread-badge"><?php echo $unread_messages[$client['client_id']]; ?></span>
                                            <?php endif; ?>
                                        </a>
                                        
                                        <a href="appointments.php?client_id=<?php echo $client['client_id']; ?>">
                                            <i class="fas fa-calendar-alt"></i> Appointments
                                        </a>
                                        
                                        <a href="documents.php?client_id=<?php echo $client['client_id']; ?>">
                                            <i class="fas fa-file-alt"></i> Documents
                                        </a>
                                        
                                        <a href="request_document.php?client_id=<?php echo $client['client_id']; ?>">
                                            <i class="fas fa-upload"></i> Request Document
                                        </a>
                                        
                                        <?php if ($client['status'] == 'active'): ?>
                                            <a href="cases.php?new=1&client_id=<?php echo $client['client_id']; ?>">
                                                <i class="fas fa-folder-plus"></i> Create Case
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($client['status'] != 'archived'): ?>
                                            <a href="update_client_status.php?id=<?php echo $client['id']; ?>&status=archived" class="warning-action">
                                                <i class="fas fa-archive"></i> Archive
                                            </a>
                                        <?php else: ?>
                                            <a href="update_client_status.php?id=<?php echo $client['id']; ?>&status=active">
                                                <i class="fas fa-box-open"></i> Unarchive
                                            </a>
                                        <?php endif; ?>
                                    </div>
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
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search_term); ?>" class="page-link">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $start_page + 4);
                if ($end_page - $start_page < 4) {
                    $start_page = max(1, $end_page - 4);
                }
                ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search_term); ?>" 
                       class="page-link <?php echo $i == $current_page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search_term); ?>" class="page-link">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Custom CSS for this page -->
<style>
    .filter-container {
        margin-bottom: 20px;
        display: flex;
        align-items: center;
    }
    
    .filter-form {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        width: 100%;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-width: 200px;
    }
    
    .form-control {
        padding: 8px 12px;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .search-box {
        display: flex;
        position: relative;
    }
    
    .search-box input {
        padding-right: 40px;
        width: 100%;
    }
    
    .search-btn {
        position: absolute;
        right: 5px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #7f8c8d;
        cursor: pointer;
    }
    
    /* Client table styles */
    .client-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .client-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .client-name {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .client-email {
        font-size: 12px;
        color: #7f8c8d;
    }
    
    .status-badge {
        display: inline-block;
        padding: 5px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-active {
        background-color: #e3f2fd;
        color: #2196f3;
    }
    
    .status-pending {
        background-color: #fff8e1;
        color: #ffc107;
    }
    
    .status-completed {
        background-color: #e8f5e9;
        color: #4caf50;
    }
    
    .status-archived {
        background-color: #f5f5f5;
        color: #9e9e9e;
    }
    
    .status-rejected {
        background-color: #ffebee;
        color: #f44336;
    }
    
    .booking-count {
        font-size: 13px;
    }
    
    .booking-count .total {
        font-weight: 600;
    }
    
    .booking-count .completed {
        color: #4caf50;
    }
    
    .actions {
        position: relative;
    }
    
    .action-button {
        background: none;
        border: none;
        color: #7f8c8d;
        cursor: pointer;
        padding: 5px 10px;
    }
    
    .dropdown {
        position: relative;
        display: inline-block;
    }
    
    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: white;
        min-width: 200px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        z-index: 1;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .dropdown:hover .dropdown-content {
        display: block;
    }
    
    .dropdown-content a {
        color: #2c3e50;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
        font-size: 13px;
        transition: background-color 0.2s;
    }
    
    .dropdown-content a:hover {
        background-color: #f5f7fa;
    }
    
    .dropdown-content .warning-action {
        color: #e74c3c;
    }
    
    .dropdown-content i {
        margin-right: 8px;
        width: 16px;
        text-align: center;
    }
    
    .unread-badge {
        display: inline-block;
        background-color: #e74c3c;
        color: white;
        border-radius: 10px;
        padding: 2px 6px;
        font-size: 10px;
        margin-left: 5px;
    }
    
    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        gap: 5px;
    }
    
    .page-link {
        display: inline-block;
        padding: 8px 12px;
        background-color: white;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        color: #2c3e50;
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .page-link.active {
        background-color: #3498db;
        color: white;
        border-color: #3498db;
    }
    
    .page-link:hover:not(.active) {
        background-color: #f5f7fa;
    }
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?>
