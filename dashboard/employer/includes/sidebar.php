<?php
// Get current page to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);
$current_directory = basename(dirname($_SERVER['PHP_SELF']));

// Fetch employer data for sidebar
$stmt = $conn->prepare("SELECT company_name, company_logo FROM employers WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$employer_result = $stmt->get_result();
$employer = $employer_result->fetch_assoc();

// Set default values if data not found
$company_name = $employer['company_name'] ?? 'Company Name';
$company_logo = $employer['company_logo'] ?? '';

// Helper function to determine if menu item is active
function isActive($page, $current_page, $directory = null, $current_directory = null) {
    if ($page === $current_page) {
        return true;
    }
    
    if ($directory && $directory === $current_directory) {
        return true;
    }
    
    return false;
}
?>

<div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <?php if (!empty($company_logo)): ?>
                <img src="../../uploads/logos/<?php echo $company_logo; ?>" alt="<?php echo $company_name; ?>" class="img-fluid rounded-circle mb-3" style="max-width: 100px; max-height: 100px;">
            <?php else: ?>
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2.5rem;">
                    <?php echo strtoupper(substr($company_name, 0, 1)); ?>
                </div>
            <?php endif; ?>
            <h6 class="sidebar-heading d-flex justify-content-center align-items-center px-3 mt-1 mb-1 text-muted text-truncate">
                <?php echo $company_name; ?>
            </h6>
            <div class="text-muted small">Employer Account</div>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo isActive('index.php', $current_page) ? 'active' : ''; ?>" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/index.php">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo isActive(null, $current_page, 'jobs', $current_directory) ? 'active' : ''; ?>" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/jobs/index.php">
                    <i class="bi bi-briefcase me-2"></i>
                    Job Listings
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo isActive(null, $current_page, 'applications', $current_directory) ? 'active' : ''; ?>" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/applications/index.php">
                    <i class="bi bi-file-earmark-person me-2"></i>
                    Applications
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo isActive('profile.php', $current_page) ? 'active' : ''; ?>" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/profile.php">
                    <i class="bi bi-building me-2"></i>
                    Company Profile
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo isActive('messages.php', $current_page) ? 'active' : ''; ?>" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/messages.php">
                    <i class="bi bi-chat-dots me-2"></i>
                    Messages
                    <span class="badge bg-danger rounded-pill ms-2">3</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo isActive('settings.php', $current_page) ? 'active' : ''; ?>" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/settings.php">
                    <i class="bi bi-gear me-2"></i>
                    Settings
                </a>
            </li>
        </ul>
        
        <hr class="my-3">
        
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo dirname(dirname($_SERVER['PHP_SELF'])); ?>/help/visa-info.php">
                    <i class="bi bi-question-circle me-2"></i>
                    Visa Information
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="<?php echo dirname(dirname($_SERVER['PHP_SELF'])); ?>/help/support.php">
                    <i class="bi bi-life-preserver me-2"></i>
                    Support
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-danger" href="../../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</div> 