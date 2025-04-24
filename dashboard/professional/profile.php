<?php
// Start session
session_start();

// Set page variables
$page_title = "Professional Profile";
$page_header = "Professional Profile";

// Database connections and includes
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is a professional
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'professional') {
    header("Location: ../../login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];

// Get user and professional data
$user_query = "SELECT u.*, p.* FROM users u
              LEFT JOIN professionals p ON u.id = p.user_id
              WHERE u.id = ? AND u.user_type = 'professional'";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User not found or not a professional
    header("Location: ../../login.php");
    exit();
}

$user = $result->fetch_assoc();

// Initialize default values for undefined fields
$user['name'] = $user['name'] ?? '';
$user['email'] = $user['email'] ?? '';
$user['phone'] = $user['phone'] ?? '';
$user['bio'] = $user['bio'] ?? '';
$user['years_experience'] = $user['years_experience'] ?? 0;
$user['license_number'] = $user['license_number'] ?? '';
$user['location'] = $user['location'] ?? '';
$user['consultation_fee'] = $user['consultation_fee'] ?? 0;

// Get current specializations
$spec_query = "SELECT s.id, s.name FROM specializations s
              INNER JOIN professional_specializations ps ON s.id = ps.specialization_id
              WHERE ps.professional_id = ?";
$stmt = $conn->prepare($spec_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$spec_result = $stmt->get_result();
$current_specializations = [];
while ($spec = $spec_result->fetch_assoc()) {
    $current_specializations[$spec['id']] = $spec['name'];
}

// Get all specializations for the dropdown
$all_spec_query = "SELECT id, name FROM specializations WHERE is_active = 1 ORDER BY name";
$all_spec_result = $conn->query($all_spec_query);
$all_specializations = [];
while ($spec = $all_spec_result->fetch_assoc()) {
    $all_specializations[$spec['id']] = $spec['name'];
}

// Get current languages
$lang_query = "SELECT l.id, l.name FROM languages l
              INNER JOIN professional_languages pl ON l.id = pl.language_id
              WHERE pl.professional_id = ?";
$stmt = $conn->prepare($lang_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$lang_result = $stmt->get_result();
$current_languages = [];
while ($lang = $lang_result->fetch_assoc()) {
    $current_languages[$lang['id']] = $lang['name'];
}

// Get all languages for the dropdown
$all_lang_query = "SELECT id, name FROM languages ORDER BY name";
$all_lang_result = $conn->query($all_lang_query);
$all_languages = [];
while ($lang = $all_lang_result->fetch_assoc()) {
    $all_languages[$lang['id']] = $lang['name'];
}

// Get all service types
$service_types_query = "SELECT * FROM service_types WHERE is_active = 1 ORDER BY name";
$service_types_result = $conn->query($service_types_query);
$service_types = [];
while ($type = $service_types_result->fetch_assoc()) {
    $service_types[$type['id']] = $type;
}

// Get professional's current services
$prof_services_query = "SELECT ps.*, st.name as type_name 
                       FROM professional_services ps
                       INNER JOIN service_types st ON ps.service_type_id = st.id
                       WHERE ps.professional_id = ?";
$stmt = $conn->prepare($prof_services_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$prof_services_result = $stmt->get_result();
$professional_services = [];
while ($service = $prof_services_result->fetch_assoc()) {
    $professional_services[$service['service_type_id']] = $service;
}

// Form submission handling
$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $bio = $_POST['bio'];
    $years_experience = $_POST['years_experience'];
    $license_number = $_POST['license_number'];
    $location = $_POST['location'];
    $consultation_fee = isset($_POST['consultation_fee']) ? $_POST['consultation_fee'] : 0;
    
    // Check if email is already in use by another user
    $check_email_query = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = $conn->prepare($check_email_query);
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $email_check_result = $stmt->get_result();
    
    if ($email_check_result->num_rows > 0) {
        $error_message = "Email is already in use by another user.";
    } else {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Update user table
            $update_user_query = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
            $stmt = $conn->prepare($update_user_query);
            $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
            $stmt->execute();
            
            // Update professional table
            $update_prof_query = "UPDATE professionals SET bio = ?, years_experience = ?, license_number = ?, location = ?, consultation_fee = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_prof_query);
            $stmt->bind_param("sisidi", $bio, $years_experience, $license_number, $location, $consultation_fee, $user_id);
            $stmt->execute();
            
            // Handle file upload for profile image
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_image']['name'];
                $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
                
                if (in_array(strtolower($file_ext), $allowed)) {
                    // Create uploads directory if it doesn't exist
                    $upload_dir = '../uploads/profiles/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $new_filename = 'prof_' . $user_id . '_' . time() . '.' . $file_ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                        $profile_image = $new_filename;
                        
                        // Update the profile image in the database
                        $update_img_query = "UPDATE professionals SET profile_image = ? WHERE user_id = ?";
                        $stmt = $conn->prepare($update_img_query);
                        $stmt->bind_param("si", $profile_image, $user_id);
                        $stmt->execute();
                    } else {
                        $error_message = "Failed to upload profile image. Please try again.";
                        throw new Exception($error_message);
                    }
                } else {
                    $error_message = "Invalid file type. Allowed types: JPG, JPEG, PNG, GIF.";
                    throw new Exception($error_message);
                }
            }
            
            // Handle specializations
            $selected_specializations = isset($_POST['specializations']) ? $_POST['specializations'] : [];
            
            // Delete existing specializations
            $delete_spec_query = "DELETE FROM professional_specializations WHERE professional_id = ?";
            $stmt = $conn->prepare($delete_spec_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Insert new specializations
            if (!empty($selected_specializations)) {
                $insert_spec_query = "INSERT INTO professional_specializations (professional_id, specialization_id) VALUES (?, ?)";
                $stmt = $conn->prepare($insert_spec_query);
                
                foreach ($selected_specializations as $spec_id) {
                    $stmt->bind_param("ii", $user_id, $spec_id);
                    $stmt->execute();
                }
            }
            
            // Handle languages
            $selected_languages = isset($_POST['languages']) ? $_POST['languages'] : [];
            
            // Delete existing languages
            $delete_lang_query = "DELETE FROM professional_languages WHERE professional_id = ?";
            $stmt = $conn->prepare($delete_lang_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Insert new languages
            if (!empty($selected_languages)) {
                $insert_lang_query = "INSERT INTO professional_languages (professional_id, language_id) VALUES (?, ?)";
                $stmt = $conn->prepare($insert_lang_query);
                
                foreach ($selected_languages as $lang_id) {
                    $stmt->bind_param("ii", $user_id, $lang_id);
                    $stmt->execute();
                }
            }
            
            $conn->commit();
            $success_message = "Profile updated successfully!";
            
            // Refresh user data
            $stmt = $conn->prepare($user_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            // Refresh specializations
            $stmt = $conn->prepare($spec_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $spec_result = $stmt->get_result();
            $current_specializations = [];
            while ($spec = $spec_result->fetch_assoc()) {
                $current_specializations[$spec['id']] = $spec['name'];
            }
            
            // Refresh languages
            $stmt = $conn->prepare($lang_query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $lang_result = $stmt->get_result();
            $current_languages = [];
            while ($lang = $lang_result->fetch_assoc()) {
                $current_languages[$lang['id']] = $lang['name'];
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Handle service offerings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_services'])) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        foreach ($service_types as $type_id => $type) {
            $is_offered = isset($_POST["offer_service_$type_id"]) ? 1 : 0;
            $price = isset($_POST["price_$type_id"]) ? $_POST["price_$type_id"] : 0;
            
            // Check if record exists for this professional and service type
            $check_service_query = "SELECT id FROM professional_services WHERE professional_id = ? AND service_type_id = ?";
            $stmt = $conn->prepare($check_service_query);
            $stmt->bind_param("ii", $user_id, $type_id);
            $stmt->execute();
            $check_result = $stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing record
                $update_service_query = "UPDATE professional_services SET is_offered = ?, price = ? WHERE professional_id = ? AND service_type_id = ?";
                $stmt = $conn->prepare($update_service_query);
                $stmt->bind_param("idii", $is_offered, $price, $user_id, $type_id);
                $stmt->execute();
            } else {
                // Insert new record
                $insert_service_query = "INSERT INTO professional_services (professional_id, service_type_id, is_offered, price) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_service_query);
                $stmt->bind_param("iiid", $user_id, $type_id, $is_offered, $price);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        $success_message = "Service offerings updated successfully!";
        
        // Refresh professional services
        $stmt = $conn->prepare($prof_services_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $prof_services_result = $stmt->get_result();
        $professional_services = [];
        while ($service = $prof_services_result->fetch_assoc()) {
            $professional_services[$service['service_type_id']] = $service;
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error updating services: " . $e->getMessage();
    }
}

// Check for service type-mode mappings in the DB and create defaults if none exist
$check_stm_query = "SELECT COUNT(*) as count FROM service_type_modes";
$stm_count_result = $conn->query($check_stm_query);
$stm_count = $stm_count_result->fetch_assoc()['count'];

if ($stm_count == 0) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Get active service types
        $active_types_query = "SELECT id FROM service_types WHERE is_active = 1";
        $active_types_result = $conn->query($active_types_query);
        $type_ids = [];
        while ($type = $active_types_result->fetch_assoc()) {
            $type_ids[] = $type['id'];
        }
        
        // Get active service modes
        $active_modes_query = "SELECT id FROM service_modes WHERE is_active = 1";
        $active_modes_result = $conn->query($active_modes_query);
        $mode_ids = [];
        while ($mode = $active_modes_result->fetch_assoc()) {
            $mode_ids[] = $mode['id'];
        }
        
        // Define default mappings
        $mappings = [];
        
        foreach ($type_ids as $type_id) {
            foreach ($mode_ids as $mode_id) {
                // For DIY service type, only include Document Review mode
                if ($type_id == 1) { // Assuming ID 1 is DIY
                    if ($mode_id == 5) { // Assuming ID 5 is Document Review
                        $mappings[] = [$type_id, $mode_id, 1]; // is_included = 1
                    }
                } 
                // For Consultation service type, include all communication modes
                else if ($type_id == 2) { // Assuming ID 2 is Consultation
                    if (in_array($mode_id, [1, 2, 3, 4])) { // Assuming IDs 1-4 are communication modes
                        $mappings[] = [$type_id, $mode_id, 1];
                    }
                }
                // For Complete Process, include all modes
                else if ($type_id == 3) { // Assuming ID 3 is Complete Process
                    $mappings[] = [$type_id, $mode_id, 1];
                }
                // For any other service type, include all modes
                else {
                    $mappings[] = [$type_id, $mode_id, 1];
                }
            }
        }
        
        // Insert mappings
        $insert_mapping_query = "INSERT INTO service_type_modes (service_type_id, service_mode_id, is_included) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_mapping_query);
        
        foreach ($mappings as $mapping) {
            $stmt->bind_param("iii", $mapping[0], $mapping[1], $mapping[2]);
            $stmt->execute();
        }
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        // Don't show error to user, just log it
        error_log("Error creating service type-mode mappings: " . $e->getMessage());
    }
}

// Get service modes for each service type
$service_modes_by_type = [];

foreach ($service_types as $type_id => $type) {
    $modes_query = "SELECT sm.id, sm.name 
                  FROM service_modes sm
                  INNER JOIN service_type_modes stm ON sm.id = stm.service_mode_id
                  WHERE stm.service_type_id = ? 
                  AND stm.is_included = 1
                  AND sm.is_active = 1";
    $stmt = $conn->prepare($modes_query);
    $stmt->bind_param("i", $type_id);
    $stmt->execute();
    $modes_result = $stmt->get_result();
    
    $service_modes_by_type[$type_id] = [];
    
    if ($modes_result->num_rows > 0) {
        while ($mode = $modes_result->fetch_assoc()) {
            $service_modes_by_type[$type_id][] = $mode;
        }
    } else {
        // Fallback logic if no modes are found for a service type
        // Get all active service modes
        $all_modes_query = "SELECT id, name FROM service_modes WHERE is_active = 1";
        $all_modes_result = $conn->query($all_modes_query);
        $all_modes = [];
        while ($mode = $all_modes_result->fetch_assoc()) {
            $all_modes[$mode['id']] = $mode;
        }
        
        // For DIY service, only show Document Review mode
        if ($type_id == 1) { // Assuming ID 1 is DIY
            foreach ($all_modes as $mode_id => $mode) {
                if ($mode_id == 5) { // Assuming ID 5 is Document Review
                    $service_modes_by_type[$type_id][] = $mode;
                }
            }
        }
        // For Consultation service, show all communication modes
        else if ($type_id == 2) { // Assuming ID 2 is Consultation
            foreach ($all_modes as $mode_id => $mode) {
                if (in_array($mode_id, [1, 2, 3, 4])) { // Assuming IDs 1-4 are communication modes
                    $service_modes_by_type[$type_id][] = $mode;
                }
            }
        }
        // For Complete Process, show all modes
        else if ($type_id == 3) { // Assuming ID 3 is Complete Process
            foreach ($all_modes as $mode) {
                $service_modes_by_type[$type_id][] = $mode;
            }
        }
        // For any other service type, show all modes
        else {
            foreach ($all_modes as $mode) {
                $service_modes_by_type[$type_id][] = $mode;
            }
        }
        
        // If still no modes, display a message
        if (empty($service_modes_by_type[$type_id])) {
            // This will be handled in the UI to show "No service modes available"
        }
    }
}

// CSS for the profile page
$page_specific_css = '
/* Profile specific styles */
.profile-image-container {
    position: relative;
    width: 150px;
    height: 150px;
    margin-bottom: 20px;
}

.profile-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #4a6fdc;
}

.profile-image-upload {
    position: absolute;
    bottom: 0;
    right: 0;
    background-color: #4a6fdc;
    color: white;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    overflow: hidden;
}

.profile-image-upload input {
    position: absolute;
    font-size: 100px;
    opacity: 0;
    right: 0;
    top: 0;
    cursor: pointer;
}

.profile-tabs {
    display: flex;
    border-bottom: 1px solid #ddd;
    margin-bottom: 20px;
}

.profile-tab {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-right: 10px;
}

.profile-tab.active {
    border-bottom-color: #4a6fdc;
    color: #4a6fdc;
    font-weight: 500;
}

.profile-tab-content {
    display: none;
}

.profile-tab-content.active {
    display: block;
}

.services-container {
    margin-top: 20px;
}

.service-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.service-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.service-modes {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #eee;
}

.service-modes-title {
    font-weight: 500;
    margin-bottom: 8px;
}

.service-mode-tag {
    display: inline-block;
    background-color: #e8f4ff;
    color: #4a6fdc;
    padding: 3px 8px;
    border-radius: 4px;
    margin-right: 5px;
    margin-bottom: 5px;
    font-size: 0.85rem;
}

.tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 10px;
}

.tag {
    background-color: #e8f4ff;
    color: #4a6fdc;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.9rem;
}

.toggle-checkbox {
    --toggle-width: 50px;
    --toggle-height: 25px;
    display: inline-block;
    vertical-align: middle;
    position: relative;
    width: var(--toggle-width);
    height: var(--toggle-height);
    margin-right: 10px;
}

.toggle-checkbox input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: var(--toggle-height);
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: calc(var(--toggle-height) - 8px);
    width: calc(var(--toggle-height) - 8px);
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #4a6fdc;
}

input:checked + .toggle-slider:before {
    transform: translateX(calc(var(--toggle-width) - var(--toggle-height)));
}

.price-input {
    width: 100px;
    margin-left: 10px;
}

.multiselect {
    height: 120px !important;
}

.profile-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
    padding: 20px;
    margin-bottom: 20px;
}

.profile-card-title {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.2rem;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}
';

// JavaScript for the profile page
$page_js = '
// Initialize profile tabs
function showTab(tabId) {
    // Hide all tab contents
    document.querySelectorAll(".profile-tab-content").forEach(function(content) {
        content.classList.remove("active");
    });
    
    // Deactivate all tabs
    document.querySelectorAll(".profile-tab").forEach(function(tab) {
        tab.classList.remove("active");
    });
    
    // Show the selected tab content
    document.getElementById(tabId).classList.add("active");
    
    // Activate the clicked tab
    document.querySelector("[data-tab=\'" + tabId + "\']").classList.add("active");
}

// Set up tab event listeners
document.querySelectorAll(".profile-tab").forEach(function(tab) {
    tab.addEventListener("click", function() {
        showTab(this.getAttribute("data-tab"));
    });
});

// Show the first tab by default
showTab("basic-info");
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
<div class="content-wrapper">
<div class="profile-tabs">
    <div class="profile-tab active" data-tab="basic-info">Basic Information</div>
    <div class="profile-tab" data-tab="specializations">Specializations & Languages</div>
    <div class="profile-tab" data-tab="services">Services</div>
</div>

<div class="profile-tab-content active" id="basic-info">
    <div class="profile-card">
        <h2 class="profile-card-title">Personal Information</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="profile-image-container">
                <?php
                // Determine profile image path
                $display_img = '../../assets/images/default-profile.png';
                if (!empty($user['profile_image'])) {
                    if (file_exists('../../uploads/profiles/' . $user['profile_image'])) {
                        $display_img = '../../uploads/profiles/' . $user['profile_image'];
                    } else if (file_exists('../uploads/profiles/' . $user['profile_image'])) {
                        $display_img = '../uploads/profiles/' . $user['profile_image'];
                    }
                } else if (!empty($user['profile_picture'])) {
                    if (file_exists('../../uploads/profiles/' . $user['profile_picture'])) {
                        $display_img = '../../uploads/profiles/' . $user['profile_picture'];
                    }
                }
                ?>
                <img src="<?php echo $display_img; ?>" alt="Profile Image" class="profile-image">
                <div class="profile-image-upload">
                    <i class="fas fa-camera"></i>
                    <input type="file" name="profile_image" accept="image/*">
                </div>
            </div>
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>
            <div class="form-group">
                <label for="bio">Professional Bio</label>
                <textarea id="bio" name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($user['bio']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="years_experience">Years of Experience</label>
                <input type="number" id="years_experience" name="years_experience" class="form-control" value="<?php echo (int)$user['years_experience']; ?>" min="0">
            </div>
            <div class="form-group">
                <label for="license_number">License Number</label>
                <input type="text" id="license_number" name="license_number" class="form-control" value="<?php echo htmlspecialchars($user['license_number'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="consultation_fee">Base Consultation Fee ($)</label>
                <input type="number" id="consultation_fee" name="consultation_fee" class="form-control" value="<?php echo number_format((float)($user['consultation_fee'] ?? 0), 2); ?>" min="0" step="0.01">
            </div>
            <div class="form-actions">
                <button type="submit" name="update_profile" class="button">Update Profile</button>
            </div>
        </form>
    </div>
</div>

<div class="profile-tab-content" id="specializations">
    <div class="profile-card">
        <h2 class="profile-card-title">Specializations & Languages</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="specializations">Specializations</label>
                <select id="specializations" name="specializations[]" class="form-control multiselect" multiple>
                    <?php foreach ($all_specializations as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo isset($current_specializations[$id]) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Hold Ctrl (or Cmd) to select multiple</small>
            </div>
            
            <div class="form-group">
                <label for="languages">Languages</label>
                <select id="languages" name="languages[]" class="form-control multiselect" multiple>
                    <?php foreach ($all_languages as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo isset($current_languages[$id]) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Hold Ctrl (or Cmd) to select multiple</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="update_profile" class="button">Update Specializations & Languages</button>
            </div>
        </form>
    </div>
</div>

<div class="profile-tab-content" id="services">
    <div class="profile-card">
        <h2 class="profile-card-title">Service Offerings</h2>
        <form method="POST" action="">
            <div class="services-container">
                <?php foreach ($service_types as $type_id => $type): ?>
                    <div class="service-item">
                        <div class="service-item-header">
                            <div class="service-item-title">
                                <label class="toggle-checkbox">
                                    <input type="checkbox" name="offer_service_<?php echo $type_id; ?>" 
                                        <?php echo isset($professional_services[$type_id]['is_offered']) && $professional_services[$type_id]['is_offered'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                                <strong><?php echo htmlspecialchars($type['name'] ?? ''); ?></strong>
                            </div>
                            <div class="service-price">
                                <label>Price ($): 
                                    <input type="number" name="price_<?php echo $type_id; ?>" class="form-control" 
                                        value="<?php echo isset($professional_services[$type_id]['price']) ? number_format((float)$professional_services[$type_id]['price'], 2) : '0.00'; ?>" 
                                        min="0" step="0.01">
                                </label>
                            </div>
                        </div>
                        <p><?php echo htmlspecialchars($type['description'] ?? ''); ?></p>
                        <div class="service-modes">
                            <div class="service-modes-title">Available Service Modes:</div>
                            <?php if (isset($service_modes_by_type[$type_id]) && !empty($service_modes_by_type[$type_id])): ?>
                                <?php foreach ($service_modes_by_type[$type_id] as $mode): ?>
                                    <span class="service-mode-tag"><?php echo htmlspecialchars($mode['name'] ?? ''); ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No service modes available for this service type.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="form-actions">
                <button type="submit" name="update_services" class="button">Update Service Offerings</button>
            </div>
        </form>
    </div>
</div>
</div>
<?php
// Include footer
include_once('includes/footer.php');
?>