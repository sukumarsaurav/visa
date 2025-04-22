<?php
session_start();

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../../login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];

// Check if job ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$job_id = intval($_GET['id']);

// Database connection would be here
// $job = getJobById($job_id, $user_id);
// if (!$job) {
//     // Job not found or doesn't belong to this employer
//     header("Location: index.php?error=job_not_found");
//     exit();
// }

// For demo purposes, create a sample job
$job = [
    'id' => $job_id,
    'title' => 'Senior Software Engineer',
    'location' => 'San Francisco, CA',
    'type' => 'full_time',
    'salary_min' => 120000,
    'salary_max' => 160000,
    'description' => "We are looking for a Senior Software Engineer to join our dynamic team. The ideal candidate will have extensive experience in full-stack development, with a focus on scalable web applications.\n\nResponsibilities include designing and implementing new features, optimizing application performance, and mentoring junior developers.",
    'requirements' => "- 5+ years of experience in software development\n- Strong proficiency in JavaScript, React, and Node.js\n- Experience with database design and SQL\n- Excellent problem-solving abilities\n- Bachelor's degree in Computer Science or related field",
    'benefits' => "- Competitive salary and equity\n- Health, dental, and vision insurance\n- 401(k) matching\n- Flexible work arrangements\n- Professional development budget",
    'status' => 'active',
    'visa_type' => 'H-1B',
    'created_at' => '2023-08-15',
    'deadline' => '2023-10-15',
    'applications_count' => 12
];

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_job'])) {
    // Process job update
    // For now, just simulate success
    $success_message = "Job updated successfully!";
    
    // Redirect to view page after successful update
    // header("Location: view.php?id={$job_id}&success=updated");
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
    <title>Edit Job - Employer Dashboard - Visafy</title>
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
                <h1>Edit Job</h1>
                <div>
                    <a href="view.php?id=<?php echo $job_id; ?>" class="button button-secondary">Back to Job Details</a>
                </div>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2 class="card-title">Edit Job Details</h2>
                
                <form method="POST" action="edit.php?id=<?php echo $job_id; ?>">
                    <div class="form-group">
                        <label for="job_title">Job Title *</label>
                        <input type="text" id="job_title" name="title" class="form-control" value="<?php echo $job['title']; ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="job_location">Location *</label>
                            <input type="text" id="job_location" name="location" class="form-control" value="<?php echo $job['location']; ?>" required>
                            <small>City, State or Remote</small>
                        </div>
                        
                        <div class="form-group half">
                            <label for="job_type">Employment Type *</label>
                            <select id="job_type" name="job_type" class="form-control" required>
                                <option value="">-- Select Type --</option>
                                <option value="full_time" <?php echo $job['type'] == 'full_time' ? 'selected' : ''; ?>>Full-time</option>
                                <option value="part_time" <?php echo $job['type'] == 'part_time' ? 'selected' : ''; ?>>Part-time</option>
                                <option value="contract" <?php echo $job['type'] == 'contract' ? 'selected' : ''; ?>>Contract</option>
                                <option value="temporary" <?php echo $job['type'] == 'temporary' ? 'selected' : ''; ?>>Temporary</option>
                                <option value="internship" <?php echo $job['type'] == 'internship' ? 'selected' : ''; ?>>Internship</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half">
                            <label for="salary_min">Salary Range (USD) *</label>
                            <div class="input-group">
                                <input type="number" id="salary_min" name="salary_min" class="form-control" value="<?php echo $job['salary_min']; ?>" placeholder="Min" required>
                                <span class="input-group-text">to</span>
                                <input type="number" id="salary_max" name="salary_max" class="form-control" value="<?php echo $job['salary_max']; ?>" placeholder="Max" required>
                            </div>
                            <small>Annual salary range</small>
                        </div>
                        
                        <div class="form-group half">
                            <label for="visa_type">Visa Sponsorship *</label>
                            <select id="visa_type" name="visa_type" class="form-control" required>
                                <option value="">-- Select Visa Type --</option>
                                <?php foreach($visa_types as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo $job['visa_type'] == $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Job Description *</label>
                        <textarea id="description" name="description" class="form-control" rows="8" required><?php echo $job['description']; ?></textarea>
                        <small>Describe the responsibilities, requirements, and qualifications for this role.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="requirements">Requirements *</label>
                        <textarea id="requirements" name="requirements" class="form-control" rows="5" required><?php echo $job['requirements']; ?></textarea>
                        <small>List the essential qualifications, skills, and experience needed.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="benefits">Benefits (Optional)</label>
                        <textarea id="benefits" name="benefits" class="form-control" rows="4"><?php echo $job['benefits']; ?></textarea>
                        <small>Describe health insurance, retirement plans, vacation policy, etc.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="application_deadline">Application Deadline *</label>
                        <input type="date" id="application_deadline" name="application_deadline" class="form-control" value="<?php echo $job['deadline']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="status" value="active" <?php echo $job['status'] == 'active' ? 'checked' : ''; ?>> 
                                <span>Active</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="status" value="draft" <?php echo $job['status'] == 'draft' ? 'checked' : ''; ?>> 
                                <span>Draft</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="status" value="closed" <?php echo $job['status'] == 'closed' ? 'checked' : ''; ?>> 
                                <span>Closed</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_job" class="button">Update Job</button>
                        <a href="view.php?id=<?php echo $job_id; ?>" class="button button-secondary">Cancel</a>
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