<?php
session_start();

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../../login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];

// Database connection would be here
// $employer = getEmployerData($user_id);

// Handle job creation form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_job'])) {
    // Process job creation
    // For now, just simulate success
    $success_message = "Job posted successfully!";
    
    // Redirect to job listings page after successful creation
    // header("Location: index.php?success=job_created");
    // exit();
}

// Get visa types for dropdown
$visa_types = [
    'H-1B' => 'H-1B Specialty Occupation',
    'L-1' => 'L-1 Intracompany Transfer',
    'O-1' => 'O-1 Extraordinary Ability',
    'TN' => 'TN NAFTA Professional',
    'E-3' => 'E-3 Australian Professional',
    'All' => 'All Visa Types'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post New Job - Employer Dashboard - Visafy</title>
    <link rel="stylesheet" href="../../styles.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="profile-section">
                <img src="../../../assets/images/employer-avatar.png" alt="Profile" class="profile-image">
                <h3><?php echo $_SESSION['username']; ?></h3>
                <p>Employer</p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item"><a href="../index.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="index.php" class="nav-link active">Job Listings</a></li>
                <li class="nav-item"><a href="../applications/index.php" class="nav-link">Applications</a></li>
                <li class="nav-item"><a href="../profile.php" class="nav-link">Company Profile</a></li>
                <li class="nav-item"><a href="../messages.php" class="nav-link">Messages</a></li>
                <li class="nav-item"><a href="../settings.php" class="nav-link">Settings</a></li>
                <li class="nav-item"><a href="../../../logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>Post New Job</h1>
                <div>
                    <a href="index.php" class="button button-secondary">Back to Listings</a>
                </div>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2 class="card-title">Job Details</h2>
                
                <form method="POST" action="create.php">
                    <div class="form-group">
                        <label for="job_title">Job Title *</label>
                        <input type="text" id="job_title" name="title" class="form-control" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="job_location">Location *</label>
                            <input type="text" id="job_location" name="location" class="form-control" required>
                            <small>City, State or Remote</small>
                        </div>
                        
                        <div class="form-group half">
                            <label for="job_type">Employment Type *</label>
                            <select id="job_type" name="job_type" class="form-control" required>
                                <option value="">-- Select Type --</option>
                                <option value="full_time">Full-time</option>
                                <option value="part_time">Part-time</option>
                                <option value="contract">Contract</option>
                                <option value="temporary">Temporary</option>
                                <option value="internship">Internship</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="salary_min">Salary Range (USD) *</label>
                            <div class="input-group">
                                <input type="number" id="salary_min" name="salary_min" class="form-control" placeholder="Min" required>
                                <span class="input-group-text">to</span>
                                <input type="number" id="salary_max" name="salary_max" class="form-control" placeholder="Max" required>
                            </div>
                            <small>Annual salary range</small>
                        </div>
                        
                        <div class="form-group half">
                            <label for="visa_type">Visa Sponsorship *</label>
                            <select id="visa_type" name="visa_type" class="form-control" required>
                                <option value="">-- Select Visa Type --</option>
                                <?php foreach($visa_types as $key => $value): ?>
                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Job Description *</label>
                        <textarea id="description" name="description" class="form-control" rows="8" required></textarea>
                        <small>Describe the responsibilities, requirements, and qualifications for this role.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="requirements">Requirements *</label>
                        <textarea id="requirements" name="requirements" class="form-control" rows="5" required></textarea>
                        <small>List the essential qualifications, skills, and experience needed.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="benefits">Benefits (Optional)</label>
                        <textarea id="benefits" name="benefits" class="form-control" rows="4"></textarea>
                        <small>Describe health insurance, retirement plans, vacation policy, etc.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="application_deadline">Application Deadline *</label>
                        <input type="date" id="application_deadline" name="application_deadline" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="status" value="active" checked> 
                                <span>Active (Publish Now)</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="status" value="draft"> 
                                <span>Draft (Save for Later)</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="create_job" class="button">Post Job</button>
                        <a href="index.php" class="button button-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Form validation logic could be added here
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.getElementById('job_title').value.trim();
            const description = document.getElementById('description').value.trim();
            
            if (title.length < 5) {
                e.preventDefault();
                alert('Job title must be at least 5 characters long.');
                return;
            }
            
            if (description.length < 100) {
                e.preventDefault();
                alert('Job description must be at least 100 characters long.');
                return;
            }
        });
    </script>
</body>
</html> 