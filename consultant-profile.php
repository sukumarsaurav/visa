<?php
session_start();
require_once 'config/db_connect.php';
require_once 'includes/functions.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Get professional ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: book-service.php");
    exit();
}

$professional_id = intval($_GET['id']);

// Fetch professional details
$query = "SELECT p.*, u.name, u.email, u.profile_picture
          FROM professionals p
          INNER JOIN users u ON p.user_id = u.id
          WHERE p.user_id = ? 
          AND p.verification_status = 'verified'
          AND u.status = 'active'
          AND u.deleted_at IS NULL";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $professional_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Professional not found or not verified
    header("Location: book-service.php");
    exit();
}

$professional = $result->fetch_assoc();

// Fetch specializations
$spec_query = "SELECT s.id, s.name 
              FROM specializations s
              INNER JOIN professional_specializations ps ON s.id = ps.specialization_id
              WHERE ps.professional_id = ?";
$stmt = $conn->prepare($spec_query);
$stmt->bind_param("i", $professional['id']);
$stmt->execute();
$spec_result = $stmt->get_result();

$specializations = [];
while ($spec = $spec_result->fetch_assoc()) {
    $specializations[] = $spec;
}

// Fetch languages
$lang_query = "SELECT l.id, l.name, pl.proficiency_level
               FROM languages l
               INNER JOIN professional_languages pl ON l.id = pl.language_id
               WHERE pl.professional_id = ?";
$stmt = $conn->prepare($lang_query);
$stmt->bind_param("i", $professional['id']);
$stmt->execute();
$lang_result = $stmt->get_result();

$languages = [];
while ($lang = $lang_result->fetch_assoc()) {
    $languages[] = $lang;
}

// Fetch reviews
$reviews_query = "SELECT r.*, u.name as reviewer_name, u.profile_picture
                 FROM reviews r
                 INNER JOIN users u ON r.user_id = u.id
                 WHERE r.professional_id = ? AND r.deleted_at IS NULL
                 ORDER BY r.created_at DESC
                 LIMIT 5";
$stmt = $conn->prepare($reviews_query);
$stmt->bind_param("i", $professional_id);
$stmt->execute();
$reviews_result = $stmt->get_result();

$reviews = [];
while ($review = $reviews_result->fetch_assoc()) {
    $reviews[] = $review;
}

// Fetch service types offered by the professional
$services_query = "SELECT ps.*, st.name as service_type_name, st.description as service_type_description
                  FROM professional_services ps
                  INNER JOIN service_types st ON ps.service_type_id = st.id
                  WHERE ps.professional_id = ? AND ps.is_offered = 1 AND st.is_active = 1";
$stmt = $conn->prepare($services_query);
$stmt->bind_param("i", $professional_id);
$stmt->execute();
$services_result = $stmt->get_result();

$services = [];
while ($service = $services_result->fetch_assoc()) {
    $services[] = $service;
}

// Handle form submission for appointment booking
$success_message = '';
$error_message = '';
$selected_service = null;
$selected_mode = null;
$selected_date = null;
$available_slots = [];

// If a service is selected, fetch service modes
if (isset($_GET['service_id']) && is_numeric($_GET['service_id'])) {
    $selected_service = intval($_GET['service_id']);
    
    // Fetch service modes for this service type
    $modes_query = "SELECT sm.id, sm.name, psmp.additional_fee, 
                    (ps.custom_price + COALESCE(psmp.additional_fee, 0)) as total_price
                    FROM service_modes sm
                    INNER JOIN service_type_modes stm ON sm.id = stm.service_mode_id
                    INNER JOIN professional_services ps ON stm.service_type_id = ps.service_type_id
                    LEFT JOIN professional_service_mode_pricing psmp ON ps.id = psmp.professional_service_id AND sm.id = psmp.service_mode_id
                    WHERE ps.professional_id = ? 
                    AND ps.service_type_id = ? 
                    AND stm.is_included = 1
                    AND sm.is_active = 1
                    AND (psmp.is_offered = 1 OR psmp.is_offered IS NULL)";
    $stmt = $conn->prepare($modes_query);
    $stmt->bind_param("ii", $professional_id, $selected_service);
    $stmt->execute();
    $modes_result = $stmt->get_result();
    
    $service_modes = [];
    while ($mode = $modes_result->fetch_assoc()) {
        $service_modes[] = $mode;
    }
}

// If service mode is selected, show date selector
if (isset($_GET['mode_id']) && is_numeric($_GET['mode_id'])) {
    $selected_mode = intval($_GET['mode_id']);
}

// If date is selected, fetch available time slots
if (isset($_GET['date']) && !empty($_GET['date'])) {
    $selected_date = $_GET['date'];
    
    // Validate date format
    $date_obj = DateTime::createFromFormat('Y-m-d', $selected_date);
    if ($date_obj && $date_obj->format('Y-m-d') === $selected_date) {
        // Date is valid, fetch slots
        $slots_query = "SELECT ts.id, ts.start_time, ts.end_time, ts.is_booked
                       FROM time_slots ts
                       WHERE ts.professional_id = ? 
                       AND ts.date = ? 
                       AND ts.service_mode_id = ?";
        $stmt = $conn->prepare($slots_query);
        $stmt->bind_param("isi", $professional_id, $selected_date, $selected_mode);
        $stmt->execute();
        $slots_result = $stmt->get_result();
        
        while ($slot = $slots_result->fetch_assoc()) {
            $available_slots[] = $slot;
        }
    }
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_slot'])) {
    if (!$is_logged_in) {
        $error_message = "Please login to book an appointment.";
    } else {
        $slot_id = intval($_POST['slot_id']);
        $service_id = intval($_POST['service_id']);
        $mode_id = intval($_POST['mode_id']);
        
        // Get pricing
        $price_query = "SELECT (ps.custom_price + COALESCE(psmp.additional_fee, 0)) as total_price
                       FROM professional_services ps
                       LEFT JOIN professional_service_mode_pricing psmp 
                       ON ps.id = psmp.professional_service_id AND psmp.service_mode_id = ?
                       WHERE ps.professional_id = ? AND ps.service_type_id = ?";
        $stmt = $conn->prepare($price_query);
        $stmt->bind_param("iii", $mode_id, $professional_id, $service_id);
        $stmt->execute();
        $price_result = $stmt->get_result();
        $price_data = $price_result->fetch_assoc();
        $price = $price_data ? $price_data['total_price'] : 0;
        
        // Check if slot is still available
        $check_query = "SELECT is_booked FROM time_slots WHERE id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("i", $slot_id);
        $stmt->execute();
        $check_result = $stmt->get_result();
        $slot_data = $check_result->fetch_assoc();
        
        if (!$slot_data || $slot_data['is_booked']) {
            $error_message = "Sorry, this time slot is no longer available.";
        } else {
            // Create booking
            $stmt = $conn->prepare("INSERT INTO bookings (professional_id, client_id, time_slot_id, service_type_id, service_mode_id, status, price, payment_status) VALUES (?, ?, ?, ?, ?, 'pending', ?, 'unpaid')");
            $stmt->bind_param("iiiiid", $professional_id, $user_id, $slot_id, $service_id, $mode_id, $price);
            
            if ($stmt->execute()) {
                $booking_id = $conn->insert_id;
                $success_message = "Your appointment has been scheduled. Please proceed to payment to confirm.";
                
                // Redirect to payment page (in a real app)
                // header("Location: payment.php?booking_id=" . $booking_id);
                // exit();
            } else {
                $error_message = "There was an error booking your appointment. Please try again.";
            }
        }
    }
}

// Page title
$page_title = "Book Consultation with " . $professional['name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Visafy</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* Profile section */
        .consultant-profile {
            display: flex;
            flex-wrap: wrap;
            margin-top: 30px;
            gap: 30px;
        }
        
        .profile-sidebar {
            flex: 1;
            min-width: 300px;
            max-width: 400px;
        }
        
        .profile-main {
            flex: 2;
            min-width: 300px;
        }
        
        .profile-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .profile-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 15px;
            border: 3px solid #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-placeholder {
            width: 100%;
            height: 100%;
            background-color: #3498db;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 4rem;
            font-weight: bold;
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .profile-license {
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .profile-rating {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .stars {
            color: #f39c12;
            margin-right: 5px;
            font-size: 1.2rem;
        }
        
        .profile-body {
            padding: 20px;
        }
        
        .profile-section {
            margin-bottom: 20px;
        }
        
        .profile-section-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 5px;
        }
        
        .profile-info-item {
            display: flex;
            margin-bottom: 8px;
        }
        
        .profile-info-label {
            font-weight: bold;
            min-width: 120px;
            color: #555;
        }
        
        .profile-info-value {
            color: #333;
            flex: 1;
        }
        
        .language-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .language-name {
            color: #333;
        }
        
        .language-level {
            color: #3498db;
            font-size: 0.9rem;
        }
        
        .specialization-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .tag {
            background-color: #e0e7ff;
            color: #4361ee;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.9rem;
        }
        
        /* Booking section */
        .booking-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
        }
        
        .service-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .service-card {
            flex: 1;
            min-width: 200px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            background-color: #fff;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .service-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .service-card.selected {
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.3);
        }
        
        .service-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .service-description {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 10px;
        }
        
        .service-price {
            font-weight: bold;
            color: #3498db;
        }
        
        .booking-step {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fff;
        }
        
        .step-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }
        
        .service-mode-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .mode-button {
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .mode-button:hover {
            background-color: #e9ecef;
        }
        
        .mode-button.selected {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .mode-name {
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .mode-price {
            font-size: 0.9rem;
            color: #555;
        }
        
        .date-selector {
            margin-bottom: 20px;
        }
        
        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .time-slot {
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .time-slot:hover:not(.booked) {
            background-color: #e3f2fd;
            border-color: #2196f3;
        }
        
        .time-slot.selected {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .time-slot.booked {
            background-color: #ffebee;
            border-color: #ffcdd2;
            color: #d32f2f;
            cursor: not-allowed;
            text-decoration: line-through;
            opacity: 0.7;
        }
        
        .booking-button {
            background-color: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            font-size: 1rem;
        }
        
        .booking-button:hover {
            background-color: #2980b9;
        }
        
        .booking-button:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
        }
        
        .login-prompt {
            text-align: center;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        .login-button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 10px;
            font-weight: bold;
        }
        
        /* Reviews section */
        .reviews-section {
            margin-bottom: 30px;
        }
        
        .review-card {
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 15px;
            background-color: #fff;
        }
        
        .review-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .reviewer-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 10px;
        }
        
        .reviewer-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .reviewer-info {
            flex: 1;
        }
        
        .reviewer-name {
            font-weight: bold;
            color: #333;
        }
        
        .review-date {
            font-size: 0.8rem;
            color: #777;
        }
        
        .review-rating {
            color: #f39c12;
            margin-bottom: 5px;
        }
        
        .review-content {
            color: #555;
            line-height: 1.5;
        }
        
        /* About section */
        .about-section {
            margin-bottom: 30px;
        }
        
        .bio {
            line-height: 1.6;
            color: #333;
        }
        
        /* Messages */
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .consultant-profile {
                flex-direction: column;
            }
            
            .profile-sidebar {
                max-width: 100%;
            }
            
            .service-cards {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="breadcrumbs">
            <a href="index.php">Home</a> &gt; 
            <a href="book-service.php">Find Consultant</a> &gt; 
            <span><?php echo $professional['name']; ?></span>
        </div>
        
        <?php if ($success_message): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="consultant-profile">
            <!-- Profile Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-image">
                            <?php if (!empty($professional['profile_picture'])): ?>
                                <img src="uploads/profiles/<?php echo $professional['profile_picture']; ?>" 
                                     alt="<?php echo $professional['name']; ?>">
                            <?php else: ?>
                                <div class="profile-placeholder">
                                    <?php echo strtoupper(substr($professional['name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <h2 class="profile-name"><?php echo $professional['name']; ?></h2>
                        <div class="profile-license">License #: <?php echo $professional['license_number']; ?></div>
                        
                        <div class="profile-rating">
                            <div class="stars">
                                <?php
                                    $rating = round($professional['rating'] ?? 0);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '★';
                                        } else {
                                            echo '☆';
                                        }
                                    }
                                ?>
                            </div>
                            <span><?php echo number_format($professional['rating'] ?? 0, 1); ?></span>
                        </div>
                    </div>
                    
                    <div class="profile-body">
                        <div class="profile-section">
                            <h3 class="profile-section-title">Contact Information</h3>
                            <div class="profile-info-item">
                                <div class="profile-info-label">Email:</div>
                                <div class="profile-info-value"><?php echo $professional['email']; ?></div>
                            </div>
                            <?php if (!empty($professional['phone'])): ?>
                                <div class="profile-info-item">
                                    <div class="profile-info-label">Phone:</div>
                                    <div class="profile-info-value"><?php echo $professional['phone']; ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($professional['website'])): ?>
                                <div class="profile-info-item">
                                    <div class="profile-info-label">Website:</div>
                                    <div class="profile-info-value">
                                        <a href="http://<?php echo $professional['website']; ?>" target="_blank">
                                            <?php echo $professional['website']; ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="profile-section">
                            <h3 class="profile-section-title">Languages</h3>
                            <?php if (!empty($languages)): ?>
                                <?php foreach ($languages as $language): ?>
                                    <div class="language-item">
                                        <div class="language-name"><?php echo $language['name']; ?></div>
                                        <div class="language-level"><?php echo ucfirst($language['proficiency_level']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No languages specified.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="profile-section">
                            <h3 class="profile-section-title">Specializations</h3>
                            <?php if (!empty($specializations)): ?>
                                <div class="specialization-tags">
                                    <?php foreach ($specializations as $specialization): ?>
                                        <span class="tag"><?php echo $specialization['name']; ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p>No specializations specified.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="profile-main">
                <!-- About Section -->
                <div class="about-section">
                    <h2 class="section-title">About</h2>
                    <div class="profile-card">
                        <div class="profile-body">
                            <?php if (!empty($professional['bio'])): ?>
                                <div class="bio"><?php echo nl2br(htmlspecialchars($professional['bio'])); ?></div>
                            <?php else: ?>
                                <p>No bio available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Booking Section -->
                <div class="booking-section">
                    <h2 class="section-title">Book a Consultation</h2>
                    
                    <!-- Step 1: Choose Service Type -->
                    <div class="profile-card">
                        <div class="profile-body">
                            <div class="step-title">Step 1: Choose Service Type</div>
                            <div class="service-cards">
                                <?php if (!empty($services)): ?>
                                    <?php foreach ($services as $service): ?>
                                        <div class="service-card <?php echo ($selected_service == $service['service_type_id']) ? 'selected' : ''; ?>" 
                                             onclick="location.href='consultant-profile.php?id=<?php echo $professional_id; ?>&service_id=<?php echo $service['service_type_id']; ?>'">
                                            <div class="service-title"><?php echo $service['service_type_name']; ?></div>
                                            <div class="service-description"><?php echo $service['service_type_description']; ?></div>
                                            <div class="service-price">$<?php echo number_format($service['custom_price'], 2); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>No services are currently offered by this consultant.</p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($selected_service): ?>
                                <!-- Step 2: Choose Service Mode -->
                                <div class="booking-step">
                                    <div class="step-title">Step 2: Choose Consultation Mode</div>
                                    <div class="service-mode-buttons">
                                        <?php if (!empty($service_modes)): ?>
                                            <?php foreach ($service_modes as $mode): ?>
                                                <div class="mode-button <?php echo ($selected_mode == $mode['id']) ? 'selected' : ''; ?>"
                                                     onclick="location.href='consultant-profile.php?id=<?php echo $professional_id; ?>&service_id=<?php echo $selected_service; ?>&mode_id=<?php echo $mode['id']; ?>'">
                                                    <div class="mode-name"><?php echo $mode['name']; ?></div>
                                                    <div class="mode-price">$<?php echo number_format($mode['total_price'], 2); ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p>No consultation modes available for this service.</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($selected_mode): ?>
                                        <!-- Step 3: Choose Date -->
                                        <div class="booking-step">
                                            <div class="step-title">Step 3: Choose Date</div>
                                            <div class="date-selector">
                                                <form method="get" action="consultant-profile.php">
                                                    <input type="hidden" name="id" value="<?php echo $professional_id; ?>">
                                                    <input type="hidden" name="service_id" value="<?php echo $selected_service; ?>">
                                                    <input type="hidden" name="mode_id" value="<?php echo $selected_mode; ?>">
                                                    
                                                    <label for="date">Select a date:</label>
                                                    <input type="date" id="date" name="date" class="form-control" 
                                                           min="<?php echo date('Y-m-d'); ?>" 
                                                           value="<?php echo $selected_date; ?>" required
                                                           onchange="this.form.submit()">
                                                </form>
                                            </div>
                                            
                                            <?php if ($selected_date): ?>
                                                <!-- Step 4: Choose Time Slot -->
                                                <div class="booking-step">
                                                    <div class="step-title">Step 4: Choose Time Slot</div>
                                                    
                                                    <?php if (!empty($available_slots)): ?>
                                                        <div class="time-slots">
                                                            <?php foreach ($available_slots as $slot): ?>
                                                                <div class="time-slot <?php echo $slot['is_booked'] ? 'booked' : ''; ?>"
                                                                     <?php if (!$slot['is_booked']): ?>
                                                                     onclick="selectTimeSlot(<?php echo $slot['id']; ?>, '<?php echo date('h:i A', strtotime($slot['start_time'])); ?> - <?php echo date('h:i A', strtotime($slot['end_time'])); ?>')"
                                                                     <?php endif; ?>
                                                                     id="slot-<?php echo $slot['id']; ?>">
                                                                    <?php echo date('h:i A', strtotime($slot['start_time'])); ?> - <?php echo date('h:i A', strtotime($slot['end_time'])); ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        
                                                        <form method="post" action="consultant-profile.php?id=<?php echo $professional_id; ?>&service_id=<?php echo $selected_service; ?>&mode_id=<?php echo $selected_mode; ?>&date=<?php echo $selected_date; ?>" id="bookingForm">
                                                            <input type="hidden" name="slot_id" id="selected_slot_id">
                                                            <input type="hidden" name="service_id" value="<?php echo $selected_service; ?>">
                                                            <input type="hidden" name="mode_id" value="<?php echo $selected_mode; ?>">
                                                            
                                                            <div class="selected-time" id="selected-time-display" style="margin: 15px 0; font-weight: bold; display: none;"></div>
                                                            
                                                            <?php if ($is_logged_in): ?>
                                                                <button type="submit" name="book_slot" class="booking-button" id="bookButton" disabled>Book Appointment</button>
                                                            <?php else: ?>
                                                                <div class="login-prompt">
                                                                    <p>You need to be logged in to book an appointment.</p>
                                                                    <a href="login.php?redirect=consultant-profile.php?id=<?php echo $professional_id; ?>" class="login-button">Log In</a>
                                                                </div>
                                                            <?php endif; ?>
                                                        </form>
                                                    <?php else: ?>
                                                        <p>No time slots available for the selected date. Please choose another date.</p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Reviews Section -->
                <div class="reviews-section">
                    <h2 class="section-title">Reviews</h2>
                    <div class="profile-card">
                        <div class="profile-body">
                            <?php if (!empty($reviews)): ?>
                                <?php foreach ($reviews as $review): ?>
                                    <div class="review-card">
                                        <div class="review-header">
                                            <div class="reviewer-image">
                                                <?php if (!empty($review['profile_picture'])): ?>
                                                    <img src="uploads/profiles/<?php echo $review['profile_picture']; ?>" alt="<?php echo $review['reviewer_name']; ?>">
                                                <?php else: ?>
                                                    <div class="profile-placeholder" style="width: 100%; height: 100%; font-size: 1.5rem;">
                                                        <?php echo strtoupper(substr($review['reviewer_name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="reviewer-info">
                                                <div class="reviewer-name"><?php echo $review['reviewer_name']; ?></div>
                                                <div class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></div>
                                            </div>
                                        </div>
                                        <div class="review-rating">
                                            <?php
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $review['rating']) {
                                                        echo '★';
                                                    } else {
                                                        echo '☆';
                                                    }
                                                }
                                            ?>
                                        </div>
                                        <div class="review-content"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No reviews yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function selectTimeSlot(slotId, timeText) {
            // Remove selection from all slots
            const slots = document.querySelectorAll('.time-slot:not(.booked)');
            slots.forEach(slot => slot.classList.remove('selected'));
            
            // Add selection to clicked slot
            document.getElementById('slot-' + slotId).classList.add('selected');
            
            // Update form values
            document.getElementById('selected_slot_id').value = slotId;
            
            // Show selected time
            const displayElement = document.getElementById('selected-time-display');
            displayElement.textContent = 'Selected time: ' + timeText;
            displayElement.style.display = 'block';
            
            // Enable booking button
            document.getElementById('bookButton').disabled = false;
        }
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
