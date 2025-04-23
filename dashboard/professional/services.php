<?php
// Set page variables
$page_title = "Service Management";
$page_header = "Manage Services";

// Include header (handles session and authentication)
require_once 'includes/header.php';

// Get user_id from session (already verified in header.php)
$user_id = $_SESSION['user_id'];

// Initialize messages
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_services'])) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Get all active service types
        $service_types_query = "SELECT id FROM service_types WHERE is_active = 1";
        $service_types_result = $conn->query($service_types_query);
        
        while ($type = $service_types_result->fetch_assoc()) {
            $type_id = $type['id'];
            $is_offered = isset($_POST["offer_service_$type_id"]) ? 1 : 0;
            $base_price = isset($_POST["base_price_$type_id"]) ? floatval($_POST["base_price_$type_id"]) : 0;
            $description = isset($_POST["description_$type_id"]) ? $_POST["description_$type_id"] : '';
            
            // Check if service already exists for this professional
            $check_service_query = "SELECT id FROM professional_services 
                                  WHERE professional_id = ? AND service_type_id = ?";
            $stmt = $conn->prepare($check_service_query);
            $stmt->bind_param("ii", $user_id, $type_id);
            $stmt->execute();
            $check_result = $stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing service
                $service_row = $check_result->fetch_assoc();
                $service_id = $service_row['id'];
                
                $update_service_query = "UPDATE professional_services 
                                       SET is_offered = ?, custom_price = ?, service_description = ? 
                                       WHERE id = ?";
                $stmt = $conn->prepare($update_service_query);
                $stmt->bind_param("idsi", $is_offered, $base_price, $description, $service_id);
                $stmt->execute();
            } else {
                // Insert new service
                $insert_service_query = "INSERT INTO professional_services 
                                       (professional_id, service_type_id, is_offered, custom_price, service_description) 
                                       VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_service_query);
                $stmt->bind_param("iiids", $user_id, $type_id, $is_offered, $base_price, $description);
                $stmt->execute();
                $service_id = $conn->insert_id;
            }
            
            // Handle service modes
            if ($is_offered) {
                // Get all active service modes for this service type
                $modes_query = "SELECT sm.id 
                              FROM service_modes sm
                              INNER JOIN service_type_modes stm ON sm.id = stm.service_mode_id
                              WHERE stm.service_type_id = ? AND sm.is_active = 1";
                $stmt = $conn->prepare($modes_query);
                $stmt->bind_param("i", $type_id);
                $stmt->execute();
                $modes_result = $stmt->get_result();
                
                while ($mode = $modes_result->fetch_assoc()) {
                    $mode_id = $mode['id'];
                    $mode_is_offered = isset($_POST["offer_mode_{$type_id}_{$mode_id}"]) ? 1 : 0;
                    $additional_fee = isset($_POST["fee_{$type_id}_{$mode_id}"]) ? 
                                    floatval($_POST["fee_{$type_id}_{$mode_id}"]) : 0;
                    
                    // Check if pricing record exists
                    $check_pricing_query = "SELECT id FROM professional_service_mode_pricing 
                                          WHERE professional_service_id = ? AND service_mode_id = ?";
                    $stmt = $conn->prepare($check_pricing_query);
                    $stmt->bind_param("ii", $service_id, $mode_id);
                    $stmt->execute();
                    $check_pricing_result = $stmt->get_result();
                    
                    if ($check_pricing_result->num_rows > 0) {
                        // Update existing pricing
                        $update_pricing_query = "UPDATE professional_service_mode_pricing 
                                               SET is_offered = ?, additional_fee = ? 
                                               WHERE professional_service_id = ? AND service_mode_id = ?";
                        $stmt = $conn->prepare($update_pricing_query);
                        $stmt->bind_param("idii", $mode_is_offered, $additional_fee, $service_id, $mode_id);
                        $stmt->execute();
                    } else {
                        // Insert new pricing
                        $insert_pricing_query = "INSERT INTO professional_service_mode_pricing 
                                               (professional_service_id, service_mode_id, is_offered, additional_fee) 
                                               VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($insert_pricing_query);
                        $stmt->bind_param("iiid", $service_id, $mode_id, $mode_is_offered, $additional_fee);
                        $stmt->execute();
                    }
                }
            }
        }
        
        $conn->commit();
        $success_message = "Services and pricing updated successfully!";
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error updating services: " . $e->getMessage();
    }
}

// Get all active service types with current professional's settings
$services_query = "SELECT 
    st.id as type_id,
    st.name as type_name,
    st.description as type_description,
    COALESCE(ps.is_offered, 0) as is_offered,
    COALESCE(ps.custom_price, 0) as base_price,
    COALESCE(ps.service_description, '') as service_description,
    ps.id as professional_service_id
FROM service_types st
LEFT JOIN professional_services ps ON st.id = ps.service_type_id 
    AND ps.professional_id = ?
WHERE st.is_active = 1
ORDER BY st.name";

$stmt = $conn->prepare($services_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$services_result = $stmt->get_result();
$services = [];

while ($service = $services_result->fetch_assoc()) {
    // Get service modes for this service type
    $modes_query = "SELECT 
        sm.id as mode_id,
        sm.name as mode_name,
        sm.description as mode_description,
        COALESCE(psmp.is_offered, 0) as mode_is_offered,
        COALESCE(psmp.additional_fee, 0) as additional_fee
    FROM service_modes sm
    INNER JOIN service_type_modes stm ON sm.id = stm.service_mode_id
    LEFT JOIN professional_service_mode_pricing psmp ON sm.id = psmp.service_mode_id 
        AND psmp.professional_service_id = ?
    WHERE stm.service_type_id = ? AND sm.is_active = 1
    ORDER BY sm.name";
    
    $stmt = $conn->prepare($modes_query);
    $stmt->bind_param("ii", $service['professional_service_id'], $service['type_id']);
    $stmt->execute();
    $modes_result = $stmt->get_result();
    
    $service['modes'] = [];
    while ($mode = $modes_result->fetch_assoc()) {
        $service['modes'][] = $mode;
    }
    
    $services[] = $service;
}
?>

<div class="content-wrapper">
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2 class="card-title">Service Offerings</h2>
        <form method="POST" action="">
            <?php foreach ($services as $service): ?>
                <div class="service-item">
                    <div class="service-header">
                        <div class="service-title">
                            <label class="toggle-checkbox">
                                <input type="checkbox" name="offer_service_<?php echo $service['type_id']; ?>" 
                                    <?php echo $service['is_offered'] ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <h3><?php echo htmlspecialchars($service['type_name']); ?></h3>
                        </div>
                        <div class="service-price">
                            <label>Base Price ($):
                                <input type="number" name="base_price_<?php echo $service['type_id']; ?>" 
                                    value="<?php echo number_format($service['base_price'], 2); ?>" 
                                    min="0" step="0.01" class="form-control">
                            </label>
                        </div>
                    </div>
                    
                    <div class="service-description">
                        <p><?php echo htmlspecialchars($service['type_description']); ?></p>
                        <div class="form-group">
                            <label>Custom Description:</label>
                            <textarea name="description_<?php echo $service['type_id']; ?>" 
                                class="form-control" rows="2"><?php echo htmlspecialchars($service['service_description']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="service-modes">
                        <h4>Available Service Modes:</h4>
                        <div class="modes-grid">
                            <?php foreach ($service['modes'] as $mode): ?>
                                <div class="mode-item">
                                    <label class="toggle-checkbox">
                                        <input type="checkbox" 
                                            name="offer_mode_<?php echo $service['type_id'] . '_' . $mode['mode_id']; ?>" 
                                            <?php echo $mode['mode_is_offered'] ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <div class="mode-info">
                                        <span class="mode-name"><?php echo htmlspecialchars($mode['mode_name']); ?></span>
                                        <input type="number" 
                                            name="fee_<?php echo $service['type_id'] . '_' . $mode['mode_id']; ?>" 
                                            value="<?php echo number_format($mode['additional_fee'], 2); ?>" 
                                            min="0" step="0.01" class="form-control"
                                            placeholder="Additional Fee">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="form-actions">
                <button type="submit" name="update_services" class="button button-primary">Update Services</button>
            </div>
        </form>
    </div>
</div>

<style>
.content-wrapper {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.service-item {
    background: white;
    border: 1px solid #e1e8ed;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.service-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.service-title {
    display: flex;
    align-items: center;
    gap: 15px;
}

.service-title h3 {
    margin: 0;
    font-size: 1.2rem;
    color: #2c3e50;
}

.service-price {
    display: flex;
    align-items: center;
}

.service-price input {
    width: 120px;
    margin-left: 10px;
}

.service-description {
    margin-bottom: 20px;
}

.service-modes {
    border-top: 1px solid #e1e8ed;
    padding-top: 20px;
}

.modes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.mode-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
}

.mode-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.mode-name {
    font-weight: 500;
    color: #2c3e50;
}

.toggle-checkbox {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
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
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #3498db;
}

input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #dce4ec;
    border-radius: 4px;
    font-size: 14px;
}

.form-actions {
    margin-top: 30px;
    text-align: right;
}

.button-primary {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
}

.button-primary:hover {
    background-color: #2980b9;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?>
