<?php
session_start();

// Check if user is logged in and is a professional
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'professional') {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// Database connection would be here
// Get professional's cases
// $cases = getProfessionalCases($user_id);

// Handle case status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $case_id = $_POST['case_id'];
    $new_status = $_POST['status'];
    // updateCaseStatus($case_id, $new_status);
    header("Location: cases.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cases - Professional Dashboard - Visafy</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="profile-section">
                <img src="<?php echo isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : '../../assets/images/default-avatar.png'; ?>" alt="Profile" class="profile-image">
                <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
                <p>Visa Professional</p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item"><a href="index.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="cases.php" class="nav-link active">Cases</a></li>
                <li class="nav-item"><a href="documents.php" class="nav-link">Documents</a></li>
                <li class="nav-item"><a href="calendar.php" class="nav-link">Calendar</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link">Profile</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Case Management</h1>
                <div class="filters">
                    <select id="status_filter" onchange="filterCases(this.value)">
                        <option value="">All Statuses</option>
                        <option value="new">New</option>
                        <option value="in_progress">In Progress</option>
                        <option value="pending_documents">Pending Documents</option>
                        <option value="review">Under Review</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>
            
            <div class="card">
                <h2 class="card-title">Active Cases</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Reference #</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Client</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Visa Type</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Status</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Created</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                    </tr>
                    <?php
                    // Sample data - would be populated from case_applications table
                    $active_cases = [
                        [
                            'id' => 1,
                            'reference_number' => 'VISA-2023-001',
                            'client_name' => 'John Doe',
                            'visa_type' => 'H-1B Work Visa',
                            'status' => 'pending_documents',
                            'created_at' => '2023-06-15'
                        ]
                    ];
                    
                    foreach ($active_cases as $case): ?>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($case['reference_number']); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($case['client_name']); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($case['visa_type']); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <div class="status-badge status-<?php echo $case['status']; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $case['status'])); ?>
                                </div>
                            </td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo date('M j, Y', strtotime($case['created_at'])); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <a href="case_details.php?id=<?php echo $case['id']; ?>" class="button button-small">View</a>
                                <button onclick="openStatusUpdate(<?php echo $case['id']; ?>)" class="button button-small">Update Status</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            
            <div class="card">
                <h2 class="card-title">Completed Cases</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Reference #</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Client</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Visa Type</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Status</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Completed</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                    </tr>
                    <?php
                    // Sample data - would be populated from case_applications table
                    $completed_cases = [
                        [
                            'id' => 2,
                            'reference_number' => 'VISA-2022-099',
                            'client_name' => 'Jane Smith',
                            'visa_type' => 'B-2 Tourist Visa',
                            'status' => 'approved',
                            'completed_at' => '2023-01-10'
                        ]
                    ];
                    
                    foreach ($completed_cases as $case): ?>
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($case['reference_number']); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($case['client_name']); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($case['visa_type']); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <div class="status-badge status-<?php echo $case['status']; ?>">
                                    <?php echo ucwords($case['status']); ?>
                                </div>
                            </td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo date('M j, Y', strtotime($case['completed_at'])); ?></td>
                            <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                                <a href="case_details.php?id=<?php echo $case['id']; ?>" class="button button-small">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Status Update Modal -->
    <div id="statusModal" class="modal" style="display: none;">
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
                    <button type="button" onclick="closeStatusModal()" class="button button-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openStatusUpdate(caseId) {
            document.getElementById('case_id').value = caseId;
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }
        
        function filterCases(status) {
            // Implement case filtering based on status
            window.location.href = 'cases.php' + (status ? '?status=' + status : '');
        }
        
        // Close modal if clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('statusModal')) {
                closeStatusModal();
            }
        }
    </script>
</body>
</html> 