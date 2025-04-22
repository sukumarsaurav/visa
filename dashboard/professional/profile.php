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

// Include database connection
require_once '../../config/db_connect.php';

try {
    // Check if service_type_modes table has data - if not, insert sample data
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM service_type_modes");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Start a transaction
        $conn->begin_transaction();
        
        try {
            // Get service types and modes IDs first
            $service_types = [];
            $stmt = $conn->prepare("SELECT id, name FROM service_types WHERE is_active = 1");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $service_types[$row['name']] = $row['id'];
            }
            
            $service_modes = [];
            $stmt = $conn->prepare("SELECT id, name FROM service_modes WHERE is_active = 1");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $service_modes[$row['name']] = $row['id'];
            }
            
            // Insert default mappings
            $stmt = $conn->prepare("INSERT INTO service_type_modes (service_type_id, service_mode_id, is_included) VALUES (?, ?, 1)");
            
            // DIY - Document Review
            if (isset($service_types['DIY']) && isset($service_modes['Document Review'])) {
                $stmt->bind_param("ii", $service_types['DIY'], $service_modes['Document Review']);
                $stmt->execute();
            }
            
            // Consultation - All communication modes
            if (isset($service_types['Consultation'])) {
                $consultation_id = $service_types['Consultation'];
                $communication_modes = ['Chat', 'Video Call', 'Phone Call', 'Email'];
                
                foreach ($communication_modes as $mode_name) {
                    if (isset($service_modes[$mode_name])) {
                        $stmt->bind_param("ii", $consultation_id, $service_modes[$mode_name]);
                        $stmt->execute();
                    }
                }
            }
            
            // Complete Process - All modes
            if (isset($service_types['Complete Process'])) {
                $complete_process_id = $service_types['Complete Process'];
                
                foreach ($service_modes as $mode_name => $mode_id) {
                    $stmt->bind_param("ii", $complete_process_id, $mode_id);
                    $stmt->execute();
                }
            }
            
            // Commit the transaction
            $conn->commit();
        } catch (Exception $e) {
            // Rollback in case of error
            $conn->rollback();
            error_log("Error populating service_type_modes: " . $e->getMessage());
        }
    }
    
    // Fetch professional data from both users and professionals tables using JOIN
    $stmt = $conn->prepare("
        SELECT u.*, p.*,
               p.profile_image as prof_image,
               p.verification_status,
               p.availability_status
        FROM users u 
        JOIN professionals p ON u.id = p.user_id 
        WHERE u.id = ? AND u.user_type = 'professional'
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $professional = $result->fetch_assoc();

    if (!$professional) {
        $error_message = "Professional profile not found.";
    } else {
        // Fetch professional specializations
        $stmt = $conn->prepare("
            SELECT s.id, s.name 
            FROM specializations s
            JOIN professional_specializations ps ON s.id = ps.specialization_id
            WHERE ps.professional_id = ?
        ");
        $stmt->bind_param("i", $professional['id']);
        $stmt->execute();
        $specializations_result = $stmt->get_result();
        
        while ($row = $specializations_result->fetch_assoc()) {
            $specializations[] = $row;
        }
        
        // Fetch professional languages
        $stmt = $conn->prepare("
            SELECT l.id, l.name, pl.proficiency_level 
            FROM languages l
            JOIN professional_languages pl ON l.id = pl.language_id
            WHERE pl.professional_id = ?
        ");
        $stmt->bind_param("i", $professional['id']);
        $stmt->execute();
        $languages_result = $stmt->get_result();
        
        while ($row = $languages_result->fetch_assoc()) {
            $languages[] = $row;
        }
    }
    
    // Fetch all available specializations
    $all_specializations = [];
    $stmt = $conn->prepare("SELECT id, name FROM specializations WHERE is_active = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_specializations[] = $row;
    }
    
    // Fetch all available languages
    $all_languages = [];
    $stmt = $conn->prepare("SELECT id, name FROM languages WHERE is_active = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $all_languages[] = $row;
    }
    
    // Get current professional specializations IDs for easier checking in the form
    $current_specializations = array_column($specializations, 'id');
    
    // Get current professional languages IDs for easier checking in the form
    $current_languages = array_column($languages, 'id');
    
    // Get current professional languages proficiency levels
    $proficiency_levels = [];
    foreach ($languages as $lang) {
        $proficiency_levels[$lang['id']] = $lang['proficiency_level'];
    }
    
} catch(Exception $e) {
    // Log error and show generic message
    error_log("Database Error: " . $e->getMessage());
    $error_message = "System is temporarily unavailable. Please try again later.";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Handle file upload if present
        $profile_image = $professional['prof_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['size'] > 0) {
            $upload_dir = '../../uploads/profiles/';
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'prof_' . $user_id . '_' . time() . '.' . $file_extension;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_filename)) {
                $profile_image = $new_filename;
            } else {
                throw new Exception("Failed to upload profile image.");
            }
        }
        
        // Update professional information
        $stmt = $conn->prepare("
            UPDATE professionals SET
            profile_image = ?,
            license_number = ?,
            years_experience = ?,
            education = ?,
            bio = ?,
            phone = ?,
            website = ?,
            profile_completed = 1
            WHERE user_id = ?
        ");
        
        $stmt->bind_param("ssissssi", 
            $profile_image,
            $_POST['license_number'],
            $_POST['years_experience'],
            $_POST['education'],
            $_POST['bio'],
            $_POST['phone'],
            $_POST['website'],
            $user_id
        );
        
        $stmt->execute();
        
        // Update specializations - first delete existing ones
        $stmt = $conn->prepare("DELETE FROM professional_specializations WHERE professional_id = ?");
        $stmt->bind_param("i", $professional['id']);
        $stmt->execute();
        
        // Then insert selected specializations
        if (isset($_POST['specializations']) && is_array($_POST['specializations'])) {
            $stmt = $conn->prepare("INSERT INTO professional_specializations (professional_id, specialization_id) VALUES (?, ?)");
            
            foreach ($_POST['specializations'] as $spec_id) {
                $stmt->bind_param("ii", $professional['id'], $spec_id);
                $stmt->execute();
            }
        }
        
        // Update languages - first delete existing ones
        $stmt = $conn->prepare("DELETE FROM professional_languages WHERE professional_id = ?");
        $stmt->bind_param("i", $professional['id']);
        $stmt->execute();
        
        // Then insert selected languages with proficiency levels
        if (isset($_POST['languages']) && is_array($_POST['languages'])) {
            $stmt = $conn->prepare("INSERT INTO professional_languages (professional_id, language_id, proficiency_level) VALUES (?, ?, ?)");
            
            foreach ($_POST['languages'] as $lang_id) {
                $proficiency = $_POST['proficiency_' . $lang_id] ?? 'intermediate';
                $stmt->bind_param("iis", $professional['id'], $lang_id, $proficiency);
                $stmt->execute();
            }
        }
        
        // Update service offerings
        
        // First, get existing professional services to compare
        $existing_services = [];
        $stmt = $conn->prepare("SELECT id, service_type_id FROM professional_services WHERE professional_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $existing_services[$row['service_type_id']] = $row['id'];
        }
        
        // Process service types
        if (isset($_POST['service_types']) && is_array($_POST['service_types'])) {
            foreach ($_POST['service_types'] as $service_type_id) {
                $custom_price = isset($_POST['service_price_' . $service_type_id]) ? 
                               $_POST['service_price_' . $service_type_id] : 0;
                $service_description = isset($_POST['service_desc_' . $service_type_id]) ? 
                                     $_POST['service_desc_' . $service_type_id] : '';
                
                // Check if service already exists for this professional
                if (isset($existing_services[$service_type_id])) {
                    // Update existing service
                    $stmt = $conn->prepare("
                        UPDATE professional_services 
                        SET custom_price = ?, service_description = ?, is_offered = 1
                        WHERE id = ?
                    ");
                    $stmt->bind_param("dsi", $custom_price, $service_description, $existing_services[$service_type_id]);
                    $stmt->execute();
                    
                    // Remove from existing services array to track which ones to set as not offered
                    unset($existing_services[$service_type_id]);
                } else {
                    // Insert new service
                    $stmt = $conn->prepare("
                        INSERT INTO professional_services 
                        (professional_id, service_type_id, custom_price, service_description, is_offered)
                        VALUES (?, ?, ?, ?, 1)
                    ");
                    $stmt->bind_param("idds", $user_id, $service_type_id, $custom_price, $service_description);
                    $stmt->execute();
                    
                    $professional_service_id = $conn->insert_id;
                } 
                
                // Process service modes for this service type
                $service_mode_ids = [];
                $stmt = $conn->prepare("
                    SELECT service_mode_id FROM service_type_modes
                    WHERE service_type_id = ? AND is_included = 1
                ");
                $stmt->bind_param("i", $service_type_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $service_mode_ids[] = $row['service_mode_id'];
                }
                
                // Now handle each mode's pricing
                foreach ($service_mode_ids as $mode_id) {
                    $mode_key = $service_type_id . '_' . $mode_id;
                    $is_mode_offered = isset($_POST['service_mode_' . $mode_key]) ? 1 : 0;
                    $additional_fee = isset($_POST['fee_' . $mode_key]) ? $_POST['fee_' . $mode_key] : 0;
                    
                    // Get the professional_service_id (either existing or newly created)
                    $prof_service_id = isset($existing_services[$service_type_id]) ? 
                                     $existing_services[$service_type_id] : $professional_service_id;
                    
                    // Check if pricing entry exists
                    $stmt = $conn->prepare("
                        SELECT id FROM professional_service_mode_pricing
                        WHERE professional_service_id = ? AND service_mode_id = ?
                    ");
                    $stmt->bind_param("ii", $prof_service_id, $mode_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        // Update existing pricing entry
                        $pricing_id = $result->fetch_assoc()['id'];
                        $stmt = $conn->prepare("
                            UPDATE professional_service_mode_pricing
                            SET is_offered = ?, additional_fee = ?
                            WHERE id = ?
                        ");
                        $stmt->bind_param("idi", $is_mode_offered, $additional_fee, $pricing_id);
                        $stmt->execute();
                    } else {
                        // Insert new pricing entry
                        $stmt = $conn->prepare("
                            INSERT INTO professional_service_mode_pricing
                            (professional_service_id, service_mode_id, is_offered, additional_fee)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->bind_param("iiid", $prof_service_id, $mode_id, $is_mode_offered, $additional_fee);
                        $stmt->execute();
                    }
                }
            }
        }
        
        // Set any remaining existing services as not offered
        foreach ($existing_services as $service_type_id => $prof_service_id) {
            $stmt = $conn->prepare("UPDATE professional_services SET is_offered = 0 WHERE id = ?");
            $stmt->bind_param("i", $prof_service_id);
            $stmt->execute();
        }
        
        // Commit the transaction
        $conn->commit();
        
        $success_message = "Profile updated successfully!";
        
        // Reload professional data
        header("Location: profile.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Update Error: " . $e->getMessage());
        $error_message = "Failed to update profile. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Professional Dashboard - Visafy</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        /* Service Tab Styles */
        .service-type-section {
            margin-bottom: 30px;
            border: 1px solid #e1e1e1;
            border-radius: 5px;
        }
        
        .service-type-header {
            padding: 15px;
            background-color: #f9f9f9;
            border-bottom: 1px solid #e1e1e1;
            cursor: pointer;
        }
        
        .service-type-checkbox {
            display: flex;
            align-items: center;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .service-type-name {
            margin-left: 10px;
            font-size: 16px;
        }
        
        .service-type-description {
            margin-left: 25px;
            color: #666;
            font-size: 14px;
        }
        
        .service-type-details {
            padding: 15px;
            background-color: #fff;
        }
        
        .service-modes {
            margin-top: 15px;
        }
        
        .service-modes h4 {
            margin-bottom: 10px;
            font-size: 15px;
            color: #444;
        }
        
        .service-modes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .service-mode-item {
            padding: 12px;
            background-color: #f9f9f9;
            border-radius: 4px;
            border: 1px solid #e1e1e1;
        }
        
        .service-mode-checkbox {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .service-mode-name {
            margin-left: 8px;
            font-size: 14px;
        }
        
        .service-mode-fee {
            margin-top: 8px;
        }
        
        .service-mode-fee label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .service-mode-fee input {
            width: 100%;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .form-info {
            color: #666;
            margin-bottom: 15px;
            font-size: 14px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="profile-section">
                <?php if (!empty($professional['prof_image'])): ?>
                    <img src="../../uploads/profiles/<?php echo htmlspecialchars($professional['prof_image']); ?>" 
                         alt="Profile" class="profile-image">
                <?php else: ?>
                    <img src="../../assets/images/professional-avatar.png" alt="Profile" class="profile-image">
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($professional['name']); ?></h3>
                <p>Visa Professional</p>
                <?php if ($professional['verification_status'] === 'verified'): ?>
                    <span class="verification-badge">Verified</span>
                <?php endif; ?>
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
                                <?php if (!empty($professional['prof_image'])): ?>
                                    <img src="../../uploads/profiles/<?php echo htmlspecialchars($professional['prof_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($professional['name']); ?>" 
                                         class="profile-large">
                                <?php else: ?>
                                    <div class="profile-placeholder">
                                        <?php echo strtoupper(substr($professional['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="profile-info">
                                <h2><?php echo htmlspecialchars($professional['name']); ?></h2>
                                <p class="profile-email"><?php echo htmlspecialchars($professional['email']); ?></p>
                                
                                <div class="profile-contact">
                                    <p><i class="icon-phone"></i> <?php echo htmlspecialchars($professional['phone'] ?? 'Not set'); ?></p>
                                    <?php if (!empty($professional['website'])): ?>
                                        <p><i class="icon-globe"></i> <a href="http://<?php echo htmlspecialchars($professional['website']); ?>" target="_blank"><?php echo htmlspecialchars($professional['website']); ?></a></p>
                                    <?php endif; ?>
                                </div>
                                
                                <hr>
                                
                                <div class="profile-status">
                                    <h3>Account Status</h3>
                                    <div class="status-item">
                                        <span class="status-label">Verification:</span>
                                        <span class="badge <?php echo htmlspecialchars($professional['verification_status']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($professional['verification_status'])); ?>
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
                                <button class="tab-button" data-tab="services">Services</button>
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
                                                <input type="text" id="license_number" name="license_number" class="form-control" value="<?php echo htmlspecialchars($professional['license_number'] ?? ''); ?>" required>
                                            </div>
                                            
                                            <div class="form-group half">
                                                <label for="years_experience">Years of Experience</label>
                                                <input type="number" id="years_experience" name="years_experience" class="form-control" value="<?php echo htmlspecialchars($professional['years_experience'] ?? 0); ?>" min="0" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="education">Education</label>
                                            <textarea id="education" name="education" class="form-control" rows="3" required><?php echo htmlspecialchars($professional['education'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="bio">Professional Bio</label>
                                            <textarea id="bio" name="bio" class="form-control" rows="5" required><?php echo htmlspecialchars($professional['bio'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group half">
                                                <label for="phone">Phone Number</label>
                                                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($professional['phone'] ?? ''); ?>" required>
                                            </div>
                                            
                                            <div class="form-group half">
                                                <label for="website">Website (Optional)</label>
                                                <input type="text" id="website" name="website" class="form-control" value="<?php echo htmlspecialchars($professional['website'] ?? ''); ?>" placeholder="www.example.com">
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
                                                                   value="<?php echo htmlspecialchars($spec['id']); ?>" 
                                                                   <?php echo in_array($spec['id'], $current_specializations) ? 'checked' : ''; ?>>
                                                            <?php echo htmlspecialchars($spec['name']); ?>
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
                                                                   value="<?php echo htmlspecialchars($lang['id']); ?>" 
                                                                   data-lang-id="<?php echo htmlspecialchars($lang['id']); ?>"
                                                                   <?php echo in_array($lang['id'], $current_languages) ? 'checked' : ''; ?>>
                                                            <?php echo htmlspecialchars($lang['name']); ?>
                                                        </label>
                                                    </div>
                                                    
                                                    <div class="language-proficiency">
                                                        <select name="proficiency_<?php echo htmlspecialchars($lang['id']); ?>" 
                                                                class="form-control proficiency-select" 
                                                                data-for-lang="<?php echo htmlspecialchars($lang['id']); ?>"
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
                                    
                                    <!-- Services Tab -->
                                    <div class="tab-pane" id="services">
                                        <div class="form-group">
                                            <label>Services You Offer</label>
                                            <p class="form-info">Select the services you offer and set your pricing for each service type and mode.</p>
                                            
                                            <?php
                                            // Fetch service types
                                            $service_types = [];
                                            try {
                                                $stmt = $conn->prepare("SELECT id, name, description FROM service_types WHERE is_active = 1");
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                while ($row = $result->fetch_assoc()) {
                                                    $service_types[] = $row;
                                                }
                                                
                                                // Fetch professional's current services
                                                $professional_services = [];
                                                $stmt = $conn->prepare("
                                                    SELECT ps.*, st.name as service_name 
                                                    FROM professional_services ps
                                                    JOIN service_types st ON ps.service_type_id = st.id
                                                    WHERE ps.professional_id = ?
                                                ");
                                                $stmt->bind_param("i", $user_id);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                while ($row = $result->fetch_assoc()) {
                                                    $professional_services[$row['service_type_id']] = $row;
                                                }
                                                
                                                // Fetch service modes
                                                $service_modes = [];
                                                $stmt = $conn->prepare("SELECT id, name, description FROM service_modes WHERE is_active = 1");
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                while ($row = $result->fetch_assoc()) {
                                                    $service_modes[] = $row;
                                                }
                                                
                                                // Fetch service_type_modes - which modes are available for each service type
                                                $service_type_modes = [];
                                                $stmt = $conn->prepare("
                                                    SELECT * FROM service_type_modes 
                                                    WHERE is_included = 1
                                                ");
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                while ($row = $result->fetch_assoc()) {
                                                    $service_type_modes[$row['service_type_id']][] = $row['service_mode_id'];
                                                }
                                                
                                                // Fetch professional service mode pricing
                                                $mode_pricing = [];
                                                $stmt = $conn->prepare("
                                                    SELECT psmp.*, ps.service_type_id
                                                    FROM professional_service_mode_pricing psmp
                                                    JOIN professional_services ps ON psmp.professional_service_id = ps.id
                                                    WHERE ps.professional_id = ?
                                                ");
                                                $stmt->bind_param("i", $user_id);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                while ($row = $result->fetch_assoc()) {
                                                    $key = $row['service_type_id'] . '_' . $row['service_mode_id'];
                                                    $mode_pricing[$key] = $row;
                                                }
                                            } catch (Exception $e) {
                                                echo '<div class="alert error">Error loading service data: ' . htmlspecialchars($e->getMessage()) . '</div>';
                                            }
                                            
                                            // Display each service type with modes
                                            foreach ($service_types as $service_type):
                                                $is_offered = isset($professional_services[$service_type['id']]);
                                                $base_price = $is_offered ? $professional_services[$service_type['id']]['custom_price'] : 0;
                                                $service_description = $is_offered ? $professional_services[$service_type['id']]['service_description'] : '';
                                            ?>
                                                <div class="service-type-section">
                                                    <div class="service-type-header">
                                                        <label class="service-type-checkbox">
                                                            <input type="checkbox" 
                                                                   name="service_types[]" 
                                                                   value="<?php echo htmlspecialchars($service_type['id']); ?>"
                                                                   data-service-id="<?php echo htmlspecialchars($service_type['id']); ?>"
                                                                   <?php echo $is_offered ? 'checked' : ''; ?>>
                                                            <span class="service-type-name"><?php echo htmlspecialchars($service_type['name']); ?></span>
                                                        </label>
                                                        <div class="service-type-description"><?php echo htmlspecialchars($service_type['description']); ?></div>
                                                    </div>
                                                    
                                                    <div class="service-type-details" id="service-details-<?php echo htmlspecialchars($service_type['id']); ?>" 
                                                         style="<?php echo $is_offered ? '' : 'display: none;'; ?>">
                                                        
                                                        <div class="form-group">
                                                            <label for="service_price_<?php echo htmlspecialchars($service_type['id']); ?>">Base Price ($)</label>
                                                            <input type="number" 
                                                                   id="service_price_<?php echo htmlspecialchars($service_type['id']); ?>" 
                                                                   name="service_price_<?php echo htmlspecialchars($service_type['id']); ?>" 
                                                                   class="form-control" 
                                                                   min="0" 
                                                                   step="0.01" 
                                                                   value="<?php echo htmlspecialchars($base_price); ?>">
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label for="service_desc_<?php echo htmlspecialchars($service_type['id']); ?>">Service Description</label>
                                                            <textarea id="service_desc_<?php echo htmlspecialchars($service_type['id']); ?>" 
                                                                      name="service_desc_<?php echo htmlspecialchars($service_type['id']); ?>" 
                                                                      class="form-control" 
                                                                      rows="2"><?php echo htmlspecialchars($service_description); ?></textarea>
                                                        </div>
                                                        
                                                        <div class="service-modes">
                                                            <h4>Service Modes</h4>
                                                            <div class="service-modes-grid">
                                                                <?php 
                                                                // Only show modes available for this service type
                                                                $available_modes = $service_type_modes[$service_type['id']] ?? [];
                                                                
                                                                // If no modes are defined in the database for this service type,
                                                                // show all available modes as a fallback
                                                                if (empty($available_modes)) {
                                                                    // Check if this is a DIY service (usually id=1)
                                                                    if ($service_type['name'] === 'DIY') {
                                                                        // DIY typically uses Document Review mode
                                                                        foreach ($service_modes as $mode) {
                                                                            if ($mode['name'] === 'Document Review') {
                                                                                $available_modes[] = $mode['id'];
                                                                            }
                                                                        }
                                                                    } elseif ($service_type['name'] === 'Consultation') {
                                                                        // Consultation typically uses all communication modes
                                                                        foreach ($service_modes as $mode) {
                                                                            if (in_array($mode['name'], ['Chat', 'Video Call', 'Phone Call', 'Email'])) {
                                                                                $available_modes[] = $mode['id'];
                                                                            }
                                                                        }
                                                                    } elseif ($service_type['name'] === 'Complete Process') {
                                                                        // Complete Process typically uses all modes
                                                                        foreach ($service_modes as $mode) {
                                                                            $available_modes[] = $mode['id'];
                                                                        }
                                                                    } else {
                                                                        // For any other service type, show all modes
                                                                        foreach ($service_modes as $mode) {
                                                                            $available_modes[] = $mode['id'];
                                                                        }
                                                                    }
                                                                }
                                                                
                                                                // If still no modes available, show a message
                                                                if (empty($available_modes)) {
                                                                    echo '<p>No service modes available for this service type.</p>';
                                                                } else {
                                                                    foreach ($service_modes as $mode):
                                                                        if (!in_array($mode['id'], $available_modes)) continue;
                                                                        
                                                                        $mode_key = $service_type['id'] . '_' . $mode['id'];
                                                                        $mode_is_offered = isset($mode_pricing[$mode_key]) ? $mode_pricing[$mode_key]['is_offered'] : 1;
                                                                        $additional_fee = isset($mode_pricing[$mode_key]) ? $mode_pricing[$mode_key]['additional_fee'] : 0;
                                                                ?>
                                                                        <div class="service-mode-item">
                                                                            <label class="service-mode-checkbox">
                                                                                <input type="checkbox" 
                                                                                       name="service_mode_<?php echo htmlspecialchars($mode_key); ?>" 
                                                                                       <?php echo $mode_is_offered ? 'checked' : ''; ?>>
                                                                                <span class="service-mode-name"><?php echo htmlspecialchars($mode['name']); ?></span>
                                                                            </label>
                                                                            
                                                                            <div class="service-mode-fee">
                                                                                <label for="fee_<?php echo htmlspecialchars($mode_key); ?>">Additional Fee ($)</label>
                                                                                <input type="number" 
                                                                                       id="fee_<?php echo htmlspecialchars($mode_key); ?>" 
                                                                                       name="fee_<?php echo htmlspecialchars($mode_key); ?>" 
                                                                                       class="form-control" 
                                                                                       min="0" 
                                                                                       step="0.01" 
                                                                                       value="<?php echo htmlspecialchars($additional_fee); ?>">
                                                                            </div>
                                                                        </div>
                                                                <?php 
                                                                    endforeach;
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>
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
            
            // Service type checkbox functionality
            const serviceTypeCheckboxes = document.querySelectorAll('input[name="service_types[]"]');
            serviceTypeCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const serviceId = this.getAttribute('data-service-id');
                    const detailsDiv = document.getElementById(`service-details-${serviceId}`);
                    
                    if (detailsDiv) {
                        if (this.checked) {
                            detailsDiv.style.display = 'block';
                        } else {
                            detailsDiv.style.display = 'none';
                        }
                    }
                });
                
                // Also add click handler to the header for toggling
                const header = checkbox.closest('.service-type-header');
                if (header) {
                    header.addEventListener('click', function(e) {
                        // Don't toggle if clicking the checkbox itself
                        if (e.target.type !== 'checkbox') {
                            const serviceId = checkbox.getAttribute('data-service-id');
                            const detailsDiv = document.getElementById(`service-details-${serviceId}`);
                            
                            if (detailsDiv) {
                                if (detailsDiv.style.display === 'none') {
                                    detailsDiv.style.display = 'block';
                                    checkbox.checked = true;
                                } else {
                                    detailsDiv.style.display = 'none';
                                    checkbox.checked = false;
                                }
                            }
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
