<?php
// Set page variables
$page_title = "Cases";
$page_header = "Case Management";

// Start session
session_start();

// Check if user is logged in and is a professional
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'professional') {
    header("Location: ../../login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Include database connection
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Handle case status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $case_id = $_POST['case_id'];
    $new_status = $_POST['status'];
    
    try {
        $stmt = $conn->prepare("UPDATE case_applications SET status = ? WHERE id = ? AND professional_id = ?");
        $stmt->bind_param("sii", $new_status, $case_id, $user_id);
        if ($stmt->execute()) {
            $success_message = "Case status updated successfully.";
        } else {
            $error_message = "Failed to update case status.";
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get filter value
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Page specific CSS
$page_specific_css = '
/* Cases-specific styles */
.filters {
    margin-bottom: 20px;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: white;
    margin: 15% auto;
    padding: 20px;
    border-radius: 8px;
    width: 80%;
    max-width: 500px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
}

.status-new {
    background-color: #cff4fc;
    color: #055160;
}

.status-in_progress {
    background-color: #fff3cd;
    color: #856404;
}

.status-pending_documents {
    background-color: #d1ecf1;
    color: #0c5460;
}

.status-review {
    background-color: #e2e3e5;
    color: #383d41;
}

.status-approved {
    background-color: #d4edda;
    color: #155724;
}

.status-rejected {
    background-color: #f8d7da;
    color: #721c24;
}
';

// Page specific JavaScript
$page_js = '
// Case status filter
const statusFilter = document.getElementById("status_filter");
if (statusFilter) {
    statusFilter.addEventListener("change", function() {
        window.location.href = "cases.php" + (this.value ? "?status=" + this.value : "");
    });
}

// Update status buttons
const updateStatusBtns = document.querySelectorAll(".update-status-btn");
updateStatusBtns.forEach(btn => {
    btn.addEventListener("click", function() {
        const caseId = this.getAttribute("data-id");
        document.getElementById("case_id").value = caseId;
        document.getElementById("statusModal").style.display = "block";
    });
});

// Cancel button in modal
const cancelStatusBtn = document.getElementById("cancelStatusBtn");
if (cancelStatusBtn) {
    cancelStatusBtn.addEventListener("click", function() {
        document.getElementById("statusModal").style.display = "none";
    });
}

// Close modal when clicking outside
window.addEventListener("click", function(event) {
    const modal = document.getElementById("statusModal");
    if (event.target === modal) {
        modal.style.display = "none";
    }
});
';

// Include header
include_once('includes/header.php');
?>

<h1 class="page-title"><?php echo $page_header; ?></h1>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger">
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<div class="card">
    <h2 class="card-title">Active Cases</h2>
    <div class="filters">
        <select id="status_filter">
            <option value="" <?php echo $status_filter == '' ? 'selected' : ''; ?>>All Statuses</option>
            <option value="new" <?php echo $status_filter == 'new' ? 'selected' : ''; ?>>New</option>
            <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
            <option value="pending_documents" <?php echo $status_filter == 'pending_documents' ? 'selected' : ''; ?>>Pending Documents</option>
            <option value="review" <?php echo $status_filter == 'review' ? 'selected' : ''; ?>>Under Review</option>
            <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
        </select>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Reference #</th>
                <th>Client</th>
                <th>Visa Type</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Get active cases from database
            $where_clause = "";
            if (!empty($status_filter)) {
                $where_clause = " AND ca.status = '$status_filter'";
            } else {
                $where_clause = " AND ca.status NOT IN ('approved', 'rejected')";
            }
            
            $active_cases_query = "SELECT ca.*, u.name as client_name, vt.name as visa_type_name
                                FROM case_applications ca
                                INNER JOIN users u ON ca.client_id = u.id
                                INNER JOIN visa_types vt ON ca.visa_type_id = vt.id
                                WHERE ca.professional_id = ? $where_clause
                                AND ca.deleted_at IS NULL
                                ORDER BY ca.created_at DESC";
            
            $stmt = $conn->prepare($active_cases_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo '<tr><td colspan="6" class="no-data">No active cases found.</td></tr>';
            } else {
                while ($case = $result->fetch_assoc()) {
                    ?>
            <tr>
                <td><?php echo htmlspecialchars($case['reference_number']); ?></td>
                <td><?php echo htmlspecialchars($case['client_name']); ?></td>
                <td><?php echo htmlspecialchars($case['visa_type_name']); ?></td>
                <td>
                    <div class="status-badge status-<?php echo $case['status']; ?>">
                        <?php echo ucwords(str_replace('_', ' ', $case['status'])); ?>
                    </div>
                </td>
                <td><?php echo date('M j, Y', strtotime($case['created_at'])); ?></td>
                <td>
                    <a href="case_details.php?id=<?php echo $case['id']; ?>" class="button button-small">View</a>
                    <button class="update-status-btn button button-small" data-id="<?php echo $case['id']; ?>">Update Status</button>
                </td>
            </tr>
            <?php
                }
            }
            ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2 class="card-title">Completed Cases</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Reference #</th>
                <th>Client</th>
                <th>Visa Type</th>
                <th>Status</th>
                <th>Completed</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Get completed cases from database
            $completed_cases_query = "SELECT ca.*, u.name as client_name, vt.name as visa_type_name
                                    FROM case_applications ca
                                    INNER JOIN users u ON ca.client_id = u.id
                                    INNER JOIN visa_types vt ON ca.visa_type_id = vt.id
                                    WHERE ca.professional_id = ? 
                                    AND ca.status IN ('approved', 'rejected')
                                    AND ca.deleted_at IS NULL
                                    ORDER BY ca.updated_at DESC
                                    LIMIT 10";
            
            $stmt = $conn->prepare($completed_cases_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo '<tr><td colspan="6" class="no-data">No completed cases found.</td></tr>';
            } else {
                while ($case = $result->fetch_assoc()) {
                    ?>
            <tr>
                <td><?php echo htmlspecialchars($case['reference_number']); ?></td>
                <td><?php echo htmlspecialchars($case['client_name']); ?></td>
                <td><?php echo htmlspecialchars($case['visa_type_name']); ?></td>
                <td>
                    <div class="status-badge status-<?php echo $case['status']; ?>">
                        <?php echo ucwords(str_replace('_', ' ', $case['status'])); ?>
                    </div>
                </td>
                <td><?php echo date('M j, Y', strtotime($case['updated_at'])); ?></td>
                <td>
                    <a href="case_details.php?id=<?php echo $case['id']; ?>" class="button button-small">View</a>
                </td>
            </tr>
            <?php
                }
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <h3>Update Case Status</h3>
        <form id="statusUpdateForm" method="POST">
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="case_id" id="case_id">

            <div class="form-group">
                <label for="status">New Status</label>
                <select name="status" id="status" class="form-control" required>
                    <option value="new">New</option>
                    <option value="in_progress">In Progress</option>
                    <option value="pending_documents">Pending Documents</option>
                    <option value="review">Under Review</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="button">Update</button>
                <button type="button" id="cancelStatusBtn" class="button button-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php
// Include footer
include_once('includes/footer.php');
?>