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
// $user = getUserData($user_id);
// $documents = getUserDocuments($user_id);

// Handle document upload if form submitted
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload'])) {
    // Process document upload
    $success_message = "Document uploaded successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Documents - Applicant Dashboard - Visafy</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="profile-section">
                <img src="../../assets/images/default-avatar.png" alt="Profile" class="profile-image">
                <h3><?php echo $_SESSION['username']; ?></h3>
                <p>Applicant</p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item"><a href="index.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="applications.php" class="nav-link">My Applications</a></li>
                <li class="nav-item"><a href="jobs.php" class="nav-link">Browse Jobs</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link">Profile</a></li>
                <li class="nav-item"><a href="documents.php" class="nav-link active">Documents</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>My Documents</h1>
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
                        <label for="document_type">Document Type</label>
                        <select id="document_type" name="document_type_id" class="form-control" required>
                            <option value="">-- Select Type --</option>
                            <option value="1">Passport</option>
                            <option value="2">Resume/CV</option>
                            <option value="3">Educational Certificate</option>
                            <option value="4">Employment Letter</option>
                            <option value="5">Reference Letter</option>
                            <option value="6">Financial Statement</option>
                            <option value="7">Other</option>
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
                <h2 class="card-title">Required Documents for Applications</h2>
                <div class="document-requirements">
                    <div class="requirement-group">
                        <h3>H-1B Work Visa Application</h3>
                        <ul class="requirement-list">
                            <li class="completed">
                                <span class="checkmark">✓</span>
                                Passport (Uploaded on June 10, 2023)
                            </li>
                            <li class="completed">
                                <span class="checkmark">✓</span>
                                Resume/CV (Uploaded on June 11, 2023)
                            </li>
                            <li class="pending">
                                <span class="cross">✗</span>
                                Educational Certificates
                            </li>
                            <li class="pending">
                                <span class="cross">✗</span>
                                Employment Verification Letter
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2 class="card-title">My Document Library</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Document Name</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Type</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Uploaded</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Used In</th>
                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Actions</th>
                    </tr>
                    <!-- Sample documents - would be populated from database -->
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">My Passport</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Passport</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">June 10, 2023</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">H-1B Application</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                            <a href="download_document.php?id=1" class="button button-small">Download</a>
                            <a href="#" class="button button-small button-delete" data-id="1">Delete</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Professional Resume</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Resume/CV</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">June 11, 2023</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">H-1B Application</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                            <a href="download_document.php?id=2" class="button button-small">Download</a>
                            <a href="#" class="button button-small button-delete" data-id="2">Delete</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Bank Statement</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Financial Statement</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">June 15, 2023</td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">Not used yet</td>
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