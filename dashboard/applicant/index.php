<?php
session_start();

// Check if user is logged in and is an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'applicant') {
    header("Location: ../../login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
// Database connection would be here
// Get user's applications
// $applications = getUserApplications($user_id);

// Set page variables
$page_title = "Dashboard";
$page_header = "Welcome to Your Dashboard";

// Include header (handles session and authentication)
require_once 'includes/header.php';

// Get user data
$user_id = $_SESSION['user_id'];

// Get user's applications
$sql = "SELECT ca.*, vt.name as visa_type, u.name as professional_name 
        FROM case_applications ca 
        LEFT JOIN visa_types vt ON ca.visa_type_id = vt.id
        LEFT JOIN users u ON ca.professional_id = u.id
        WHERE ca.client_id = ? AND ca.deleted_at IS NULL
        ORDER BY ca.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get documents for the user's applications
$sql = "SELECT d.*, dt.name as document_type_name
        FROM documents d
        JOIN document_types dt ON d.document_type_id = dt.id
        WHERE d.client_id = ? AND d.deleted_at IS NULL
        ORDER BY d.uploaded_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent notifications
$sql = "SELECT * FROM notifications 
        WHERE user_id = ? AND deleted_at IS NULL 
        ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="dashboard-content">
    <div class="welcome-header">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h2>
        <p>Here's an overview of your immigration journey</p>
    </div>
    
    <div class="dashboard-cards">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Active Applications</h2>
            </div>
            <div class="application-summary">
                <?php if (empty($applications)): ?>
                    <p class="no-data">No active applications found.</p>
                <?php else: ?>
                    <?php foreach ($applications as $app): ?>
                        <div class="application-item">
                            <div class="status-badge status-<?php echo htmlspecialchars($app['status']); ?>">
                                <?php echo ucwords(str_replace('_', ' ', $app['status'])); ?>
                            </div>
                            <h3><?php echo htmlspecialchars($app['visa_type']); ?></h3>
                            <p>Reference: <?php echo htmlspecialchars($app['reference_number']); ?></p>
                            <p>Professional: <?php echo htmlspecialchars($app['professional_name']); ?></p>
                            <a href="applications.php?id=<?php echo urlencode($app['id']); ?>" class="btn btn-primary">View Details</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Documents</h2>
            </div>
            <div class="document-checklist">
                <?php if (empty($documents)): ?>
                    <p class="no-data">No documents found.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($documents as $doc): ?>
                            <li>
                                <span class="document-type"><?php echo htmlspecialchars($doc['document_type_name']); ?></span>
                                <span class="document-name"><?php echo htmlspecialchars($doc['name']); ?></span>
                                <span class="document-date"><?php echo date('M j, Y', strtotime($doc['uploaded_at'])); ?></span>
                                <a href="documents.php?view=<?php echo urlencode($doc['id']); ?>" class="btn btn-sm btn-primary">View</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <div class="document-actions">
                    <a href="documents.php" class="button button-primary">Manage Documents</a>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Updates</h2>
            </div>
            <div class="updates-list">
                <?php if (empty($notifications)): ?>
                    <p class="no-data">No recent updates found.</p>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="update-item">
                            <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                            <p><?php echo htmlspecialchars($notification['message']); ?></p>
                            <small><?php echo date('F j, Y g:i A', strtotime($notification['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-content {
    padding: 20px;
}

.welcome-header {
    margin-bottom: 30px;
}

.welcome-header h2 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 1.8rem;
}

.welcome-header p {
    color: #7f8c8d;
    margin: 0;
}

.dashboard-cards {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #e0e0e0;
    background-color: #f8f9fa;
}

.card-title {
    margin: 0;
    color: #2c3e50;
    font-size: 1.2rem;
}

.application-summary {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
}

.application-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 10px;
}

.status-pending_documents { background: #fff3cd; color: #856404; }
.status-under_review { background: #cce5ff; color: #004085; }
.status-approved { background: #d4edda; color: #155724; }
.status-rejected { background: #f8d7da; color: #721c24; }

.document-checklist {
    padding: 20px;
}

.document-checklist ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.document-checklist li {
    display: flex;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #eee;
    gap: 15px;
}

.document-checklist li:last-child {
    border-bottom: none;
}

.document-type {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
}

.document-name {
    flex: 1;
    color: #2c3e50;
}

.document-date {
    color: #7f8c8d;
    font-size: 0.85rem;
}

.document-actions {
    margin-top: 20px;
    text-align: right;
}

.updates-list {
    padding: 20px;
}

.update-item {
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.update-item:last-child {
    border-bottom: none;
}

.update-item h4 {
    margin: 0 0 5px 0;
    color: #2d3748;
}

.update-item p {
    margin: 0 0 5px 0;
    color: #4a5568;
}

.update-item small {
    color: #718096;
}

.no-data {
    text-align: center;
    color: #718096;
    padding: 20px;
    font-style: italic;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 0.8rem;
    margin-left: auto;
}

.btn-primary, .button-primary {
    background-color: #3498db;
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 0.9rem;
    transition: background-color 0.2s;
}

.btn-primary:hover, .button-primary:hover {
    background-color: #2980b9;
}

@media (min-width: 992px) {
    .dashboard-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .dashboard-cards .card:last-child {
        grid-column: span 2;
    }
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?>
