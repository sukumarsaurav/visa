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

// Get client list for the filter
// $clients = getProfessionalClients($user_id);

// Handle document upload if form submitted
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload'])) {
    // Process document upload
    $success_message = "Document uploaded successfully!";
}

// Get documents - filter by client if specified
$client_id = isset($_GET['client']) ? intval($_GET['client']) : 0;
// $documents = getDocumentsByProfessional($user_id, $client_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents - Professional Dashboard - Visafy</title>
    <link rel="stylesheet" href="../styles.css">
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
                <li class="nav-item"><a href="documents.php" class="nav-link active">Documents</a></li>
                <li class="nav-item"><a href="calendar.php" class="nav-link">Calendar</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link">Profile</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Document Management</h1>
                <div>
                    <button id="uploadBtn" class="button">Upload Document</button>
                </div>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div id="uploadFormContainer" class="card" style="display: none;">
                <h2 class="card-title">Upload New Document</h2>
                <form method="POST" action="documents.php" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="case_id">Select Case</label>
                        <select id="case_id" name="case_id" class="form-control" required>
                            <option value="">-- Select Case --</option>
                            <option value="1">VISA-2023-001 - John Doe (H-1B)</option>
                            <option value="2">VISA-2023-015 - Jane Smith (L-1)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="document_type">Document Type</label>
                        <select id="document_type" name="document_type_id" class="form-control" required>
                            <option value="">-- Select Type --</option>
                            <option value="1">Passport</option>
                            <option value="2">Resume/CV</option>
                            <option value="3">Educational Certificate</option>
                            <option value="4">Employment Letter</option>
                            <option value="5">Immigration Form</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="document_name">Document Name</label>
                        <input type="text" id="document_name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="document_file">File</label>
                        <input type="file" id="document_file" name="file" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="document_description">Description (Optional)</label>
                        <textarea id="document_description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="upload" class="button">Upload</button>
                        <button type="button" id="cancelUpload" class="button button-secondary">Cancel</button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <h2 class="card-title">Document Library</h2>
                
                <div class="filter-section">
                    <form method="GET" action="documents.php" class="filter-form">
                        <div class="form-group inline">
                            <label for="client_filter">Filter by Client:</label>
                            <select id="client_filter" name="client" class="form-control" onchange="this.form.submit()">
                                <option value="0">All Clients</option>
                                <option value="101">John Doe</option>
                                <option value="102">Jane Smith</option>
                            </select>
                        </div>
                    </form>
                </div>
                
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Document Name</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Type</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Case</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Client</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Uploaded</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                    </tr>
                    <!-- Sample documents - would be populated from database -->
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Passport Copy</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Passport</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">VISA-2023-001</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">John Doe</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">2023-06-15</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                            <a href="download_document.php?id=1" class="button button-small">Download</a>
                            <a href="#" class="button button-small button-delete" data-id="1">Delete</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Employment Verification</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Employment Letter</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">VISA-2023-001</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">John Doe</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">2023-06-16</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                            <a href="download_document.php?id=2" class="button button-small">Download</a>
                            <a href="#" class="button button-small button-delete" data-id="2">Delete</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Form I-129</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Immigration Form</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">VISA-2023-015</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Jane Smith</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">2023-07-03</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                            <a href="download_document.php?id=3" class="button button-small">Download</a>
                            <a href="#" class="button button-small button-delete" data-id="3">Delete</a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('uploadBtn').addEventListener('click', function() {
            document.getElementById('uploadFormContainer').style.display = 'block';
        });
        
        document.getElementById('cancelUpload').addEventListener('click', function() {
            document.getElementById('uploadFormContainer').style.display = 'none';
        });
        
        // Delete confirmation
        document.querySelectorAll('.button-delete').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const documentId = this.getAttribute('data-id');
                if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
                    window.location.href = 'delete_document.php?id=' + documentId;
                }
            });
        });
    </script>
</body>
</html> 