<?php
// Set page variables
$page_title = "Documents";
$page_header = "Document Management";

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

// Get client list for the filter
$clients_query = "SELECT DISTINCT u.id, u.name
                 FROM users u
                 INNER JOIN case_applications ca ON u.id = ca.client_id
                 WHERE ca.professional_id = ?
                 AND ca.deleted_at IS NULL
                 ORDER BY u.name";
$stmt = $conn->prepare($clients_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$clients_result = $stmt->get_result();
$clients = [];
while ($client = $clients_result->fetch_assoc()) {
    $clients[] = $client;
}

// Handle document upload if form submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload'])) {
    // Validate inputs
    $case_id = isset($_POST['case_id']) ? intval($_POST['case_id']) : 0;
    $document_type_id = isset($_POST['document_type_id']) ? intval($_POST['document_type_id']) : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    // Check if file was uploaded
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // Process file upload
        $upload_dir = '../../uploads/documents/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = basename($_FILES['file']['name']);
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_file_name = 'doc_' . time() . '_' . mt_rand(1000, 9999) . '.' . $file_extension;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_dir . $new_file_name)) {
            // Insert document info into database
            $stmt = $conn->prepare("
                INSERT INTO documents 
                (case_id, professional_id, document_type_id, name, description, file_path, uploaded_by, uploaded_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->bind_param(
                "iiisssi",
                $case_id,
                $user_id,
                $document_type_id,
                $name,
                $description,
                $new_file_name,
                $user_id
            );
            
            if ($stmt->execute()) {
                $success_message = "Document uploaded successfully!";
            } else {
                $error_message = "Error saving document: " . $conn->error;
            }
        } else {
            $error_message = "Error uploading file.";
        }
    } else {
        $error_message = "Please select a file to upload.";
    }
}

// Get documents - filter by client if specified
$client_id = isset($_GET['client']) ? intval($_GET['client']) : 0;
$documents_query = "
    SELECT d.*, dt.name as doc_type_name, ca.reference_number, u.name as client_name
    FROM documents d
    INNER JOIN document_types dt ON d.document_type_id = dt.id
    INNER JOIN case_applications ca ON d.case_id = ca.id
    INNER JOIN users u ON ca.client_id = u.id
    WHERE d.professional_id = ?
    AND d.deleted_at IS NULL
";

if ($client_id > 0) {
    $documents_query .= " AND ca.client_id = ?";
    $stmt = $conn->prepare($documents_query);
    $stmt->bind_param("ii", $user_id, $client_id);
} else {
    $stmt = $conn->prepare($documents_query);
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$documents_result = $stmt->get_result();

// Add upload button to header
$header_buttons = '<button id="uploadBtn" class="button">Upload Document</button>';

// Include page specific CSS
$page_specific_css = '
.filter-section {
    margin-bottom: 20px;
}

.filter-form {
    display: flex;
    align-items: center;
}

.form-group.inline {
    display: flex;
    align-items: center;
    margin-bottom: 0;
}

.form-group.inline label {
    margin-right: 10px;
    margin-bottom: 0;
}

.button-delete {
    background-color: #e74c3c;
}

.button-delete:hover {
    background-color: #c0392b;
}
';

// Include page specific JavaScript
$page_js = '
// Document-specific scripts
document.getElementById("uploadBtn").addEventListener("click", function() {
    document.getElementById("uploadFormContainer").style.display = "block";
});

document.getElementById("cancelUpload").addEventListener("click", function() {
    document.getElementById("uploadFormContainer").style.display = "none";
});

// Delete confirmation
document.querySelectorAll(".button-delete").forEach(button => {
    button.addEventListener("click", function(e) {
        e.preventDefault();
        const documentId = this.getAttribute("data-id");
        if (confirm("Are you sure you want to delete this document? This action cannot be undone.")) {
            window.location.href = "delete_document.php?id=" + documentId;
        }
    });
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

<?php if (isset($header_buttons)): ?>
<div class="header-actions">
    <?php echo $header_buttons; ?>
</div>
<?php endif; ?>
<div class="content-wrapper">
    <div id="uploadFormContainer" class="card" style="display: none;">
        <h2 class="card-title">Upload New Document</h2>
        <form method="POST" action="documents.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="case_id">Select Case</label>
                <select id="case_id" name="case_id" class="form-control" required>
                    <option value="">-- Select Case --</option>
                    <?php
                // Get cases for dropdown
                $cases_query = "SELECT ca.id, ca.reference_number, u.name as client_name, vt.name as visa_type
                               FROM case_applications ca
                               INNER JOIN users u ON ca.client_id = u.id
                               INNER JOIN visa_types vt ON ca.visa_type_id = vt.id
                               WHERE ca.professional_id = ?
                               AND ca.deleted_at IS NULL
                               ORDER BY ca.created_at DESC";
                $stmt = $conn->prepare($cases_query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $cases_result = $stmt->get_result();
                
                while ($case = $cases_result->fetch_assoc()) {
                    echo '<option value="' . $case['id'] . '">' . htmlspecialchars($case['reference_number']) . ' - ' . htmlspecialchars($case['client_name']) . ' (' . htmlspecialchars($case['visa_type']) . ')</option>';
                }
                ?>
                </select>
            </div>

            <div class="form-group">
                <label for="document_type">Document Type</label>
                <select id="document_type" name="document_type_id" class="form-control" required>
                    <option value="">-- Select Type --</option>
                    <?php
                // Get document types for dropdown
                $types_query = "SELECT id, name FROM document_types WHERE is_active = 1 ORDER BY name";
                $stmt = $conn->prepare($types_query);
                $stmt->execute();
                $types_result = $stmt->get_result();
                
                while ($type = $types_result->fetch_assoc()) {
                    echo '<option value="' . $type['id'] . '">' . htmlspecialchars($type['name']) . '</option>';
                }
                ?>
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
                        <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>"
                            <?php echo $client_id == $client['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Document Name</th>
                    <th>Type</th>
                    <th>Case</th>
                    <th>Client</th>
                    <th>Uploaded</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
            if ($documents_result->num_rows === 0) {
                echo '<tr><td colspan="6" class="no-data">No documents found.</td></tr>';
            } else {
                while ($document = $documents_result->fetch_assoc()) {
                    ?>
                <tr>
                    <td><?php echo htmlspecialchars($document['name']); ?></td>
                    <td><?php echo htmlspecialchars($document['doc_type_name']); ?></td>
                    <td><?php echo htmlspecialchars($document['reference_number']); ?></td>
                    <td><?php echo htmlspecialchars($document['client_name']); ?></td>
                    <td><?php echo date('M j, Y', strtotime($document['uploaded_at'])); ?></td>
                    <td>
                        <a href="download_document.php?id=<?php echo $document['id']; ?>"
                            class="button button-small">Download</a>
                        <a href="#" class="button button-small button-delete"
                            data-id="<?php echo $document['id']; ?>">Delete</a>
                    </td>
                </tr>
                <?php
                }
            }
            ?>
            </tbody>
        </table>
    </div>
</div>
<?php
// Include footer
include_once('includes/footer.php');
?>