<?php
session_start();

// Check if user is logged in and is a professional
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'professional') {
    header("Location: ../../login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$professional = null;
$specializations = [];
$languages = [];
$success_message = '';
$error_message = '';

// Database connection would be here
// Fetch professional data
// $stmt = $conn->prepare("SELECT p.*, u.name, u.email FROM professionals p
//                       JOIN users u ON p.user_id = u.id
//                       WHERE p.user_id = ?");
// $stmt->bind_param("i", $user_id);
// $stmt->execute();
// $result = $stmt->get_result();

// if ($result->num_rows > 0) {
//     $professional = $result->fetch_assoc();
//     
//     // Fetch professional specializations
//     $stmt = $conn->prepare("SELECT s.id, s.name FROM specializations s
//                          JOIN professional_specializations ps ON s.id = ps.specialization_id
//                          WHERE ps.professional_id = ?");
//     $stmt->bind_param("i", $professional['id']);
//     $stmt->execute();
//     $specializations_result = $stmt->get_result();
//     
//     while ($row = $specializations_result->fetch_assoc()) {
//         $specializations[] = $row;
//     }
//     
//     // Fetch professional languages
//     $stmt = $conn->prepare("SELECT l.id, l.name, pl.proficiency_level FROM languages l
//                          JOIN professional_languages pl ON l.id = pl.language_id
//                          WHERE pl.professional_id = ?");
//     $stmt->bind_param("i", $professional['id']);
//     $stmt->execute();
//     $languages_result = $stmt->get_result();
//     
//     while ($row = $languages_result->fetch_assoc()) {
//         $languages[] = $row;
//     }
// } else {
//     $error_message = "Professional profile not found.";
// }

// For demonstration purposes, create sample data
$professional = [
    'id' => 1,
    'user_id' => $user_id,
    'name' => 'John Smith',
    'email' => 'john.smith@example.com',
    'license_number' => 'ABC123456',
    'years_experience' => 5,
    'education' => 'J.D., Harvard Law School, 2015
B.A. in Political Science, Stanford University, 2012',
    'bio' => 'Experienced immigration attorney specializing in employment-based visas with a focus on the tech industry. I have helped hundreds of professionals secure H-1B, L-1, and O-1 visas over the past 5 years.',
    'phone' => '(555) 123-4567',
    'website' => 'www.johnsmithlegal.com',
    'profile_image' => '',
    'verification_status' => 'verified',
    'profile_completed' => 1
];

$specializations = [
    ['id' => 1, 'name' => 'H-1B Visas'],
    ['id' => 2, 'name' => 'L-1 Visas'],
    ['id' => 3, 'name' => 'O-1 Visas']
];

$languages = [
    ['id' => 1, 'name' => 'English', 'proficiency_level' => 'native'],
    ['id' => 2, 'name' => 'Spanish', 'proficiency_level' => 'fluent'],
    ['id' => 3, 'name' => 'French', 'proficiency_level' => 'intermediate']
];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    // Process profile update
    // In a real application, you would validate and save the data to the database
    $success_message = "Profile updated successfully!";
}

// All specializations for the form
$all_specializations = [
    ['id' => 1, 'name' => 'H-1B Visas'],
    ['id' => 2, 'name' => 'L-1 Visas'],
    ['id' => 3, 'name' => 'O-1 Visas'],
    ['id' => 4, 'name' => 'E-2 Visas'],
    ['id' => 5, 'name' => 'EB-5 Visas'],
    ['id' => 6, 'name' => 'Family-Based Immigration']
];

// All languages for the form
$all_languages = [
    ['id' => 1, 'name' => 'English'],
    ['id' => 2, 'name' => 'Spanish'],
    ['id' => 3, 'name' => 'French'],
    ['id' => 4, 'name' => 'German'],
    ['id' => 5, 'name' => 'Chinese'],
    ['id' => 6, 'name' => 'Japanese']
];

// Get current professional specializations IDs for easier checking in the form
$current_specializations = array_column($specializations, 'id');

// Get current professional languages IDs for easier checking in the form
$current_languages = array_column($languages, 'id');

// Get current professional languages proficiency levels
$proficiency_levels = [];
foreach ($languages as $lang) {
    $proficiency_levels[$lang['id']] = $lang['proficiency_level'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Professional Dashboard - Visafy</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="profile-section">
                <img src="../../assets/images/professional-avatar.png" alt="Profile" class="profile-image">
                <h3><?php echo $_SESSION['username'] ?? $professional['name']; ?></h3>
                <p>Visa Professional</p>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item"><a href="index.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="cases.php" class="nav-link">Cases</a></li>
                <li class="nav-item"><a href="clients.php" class="nav-link">Clients</a></li>
                <li class="nav-item"><a href="documents.php" class="nav-link">Documents</a></li>
                <li class="nav-item"><a href="calendar.php" class="nav-link">Calendar</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link active">Profile</a></li>
                <li class="nav-item"><a href="../../logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>My Profile</h1>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if ($professional): ?>
                <div class="profile-container">
                    <div class="profile-sidebar">
                        <div class="card">
                            <div class="profile-image-container">
                                <?php if (!empty($professional['profile_image'])): ?>
                                    <img src="../../uploads/profiles/<?php echo $professional['profile_image']; ?>" 
                                         alt="<?php echo $professional['name']; ?>" 
                                         class="profile-large">
                                <?php else: ?>
                                    <div class="profile-placeholder">
                                        <?php echo strtoupper(substr($professional['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="profile-info">
                                <h2><?php echo $professional['name']; ?></h2>
                                <p class="profile-email"><?php echo $professional['email']; ?></p>
                                
                                <div class="profile-contact">
                                    <p><i class="icon-phone"></i> <?php echo $professional['phone']; ?></p>
                                    <?php if (!empty($professional['website'])): ?>
                                        <p><i class="icon-globe"></i> <a href="http://<?php echo $professional['website']; ?>" target="_blank"><?php echo $professional['website']; ?></a></p>
                                    <?php endif; ?>
                                </div>
                                
                                <hr>
                                
                                <div class="profile-status">
                                    <h3>Account Status</h3>
                                    <div class="status-item">
                                        <span class="status-label">Verification:</span>
                                        <span class="badge <?php echo $professional['verification_status']; ?>">
                                            <?php echo ucfirst($professional['verification_status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="status-item">
                                        <span class="status-label">Profile:</span>
                                        <span class="badge <?php echo $professional['profile_completed'] ? 'active' : 'draft'; ?>">
                                            <?php echo $professional['profile_completed'] ? 'Complete' : 'Incomplete'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-content">
                        <div class="card">
                            <div class="profile-tabs">
                                <button class="tab-button active" data-tab="basic-info">Basic Info</button>
                                <button class="tab-button" data-tab="specializations">Specializations</button>
                                <button class="tab-button" data-tab="languages">Languages</button>
                            </div>
                            
                            <div class="tab-content">
                                <form method="POST" action="profile.php" enctype="multipart/form-data">
                                    <!-- Basic Info Tab -->
                                    <div class="tab-pane active" id="basic-info">
                                        <div class="form-group">
                                            <label for="profile_image">Profile Picture</label>
                                            <input type="file" id="profile_image" name="profile_image" class="form-control">
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group half">
                                                <label for="license_number">License Number</label>
                                                <input type="text" id="license_number" name="license_number" class="form-control" value="<?php echo $professional['license_number']; ?>" required>
                                            </div>
                                            
                                            <div class="form-group half">
                                                <label for="years_experience">Years of Experience</label>
                                                <input type="number" id="years_experience" name="years_experience" class="form-control" value="<?php echo $professional['years_experience']; ?>" min="0" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="education">Education</label>
                                            <textarea id="education" name="education" class="form-control" rows="3" required><?php echo $professional['education']; ?></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="bio">Professional Bio</label>
                                            <textarea id="bio" name="bio" class="form-control" rows="5" required><?php echo $professional['bio']; ?></textarea>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group half">
                                                <label for="phone">Phone Number</label>
                                                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo $professional['phone']; ?>" required>
                                            </div>
                                            
                                            <div class="form-group half">
                                                <label for="website">Website (Optional)</label>
                                                <input type="text" id="website" name="website" class="form-control" value="<?php echo $professional['website']; ?>" placeholder="www.example.com">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Specializations Tab -->
                                    <div class="tab-pane" id="specializations">
                                        <div class="form-group">
                                            <label>Your Specializations</label>
                                            <div class="checkbox-grid">
                                                <?php foreach ($all_specializations as $spec): ?>
                                                    <div class="checkbox-item">
                                                        <label>
                                                            <input type="checkbox" 
                                                                   name="specializations[]" 
                                                                   value="<?php echo $spec['id']; ?>" 
                                                                   <?php echo in_array($spec['id'], $current_specializations) ? 'checked' : ''; ?>>
                                                            <?php echo $spec['name']; ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Languages Tab -->
                                    <div class="tab-pane" id="languages">
                                        <div class="form-group">
                                            <label>Languages You Speak</label>
                                            <?php foreach ($all_languages as $lang): ?>
                                                <div class="language-item">
                                                    <div class="language-checkbox">
                                                        <label>
                                                            <input type="checkbox" 
                                                                   name="languages[]" 
                                                                   value="<?php echo $lang['id']; ?>" 
                                                                   data-lang-id="<?php echo $lang['id']; ?>"
                                                                   <?php echo in_array($lang['id'], $current_languages) ? 'checked' : ''; ?>>
                                                            <?php echo $lang['name']; ?>
                                                        </label>
                                                    </div>
                                                    
                                                    <div class="language-proficiency">
                                                        <select name="proficiency_<?php echo $lang['id']; ?>" 
                                                                class="form-control proficiency-select" 
                                                                data-for-lang="<?php echo $lang['id']; ?>"
                                                                <?php echo !in_array($lang['id'], $current_languages) ? 'disabled' : ''; ?>>
                                                            <option value="basic" <?php echo isset($proficiency_levels[$lang['id']]) && $proficiency_levels[$lang['id']] == 'basic' ? 'selected' : ''; ?>>Basic</option>
                                                            <option value="intermediate" <?php echo isset($proficiency_levels[$lang['id']]) && $proficiency_levels[$lang['id']] == 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                                            <option value="fluent" <?php echo isset($proficiency_levels[$lang['id']]) && $proficiency_levels[$lang['id']] == 'fluent' ? 'selected' : ''; ?>>Fluent</option>
                                                            <option value="native" <?php echo isset($proficiency_levels[$lang['id']]) && $proficiency_levels[$lang['id']] == 'native' ? 'selected' : ''; ?>>Native</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" name="update_profile" class="button">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons and panes
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabPanes.forEach(pane => pane.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding pane
                    button.classList.add('active');
                    const tabId = button.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // Language checkbox functionality
            const languageCheckboxes = document.querySelectorAll('input[name="languages[]"]');
            languageCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const langId = this.getAttribute('data-lang-id');
                    const proficiencySelect = document.querySelector(`select[data-for-lang="${langId}"]`);
                    
                    if (proficiencySelect) {
                        proficiencySelect.disabled = !this.checked;
                    }
                });
            });
        });
    </script>
</body>
</html>
