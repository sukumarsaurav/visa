<?php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

session_start();
requireUserType('employer', '../../login.php');

$user_id = $_SESSION['user_id'];

// Fetch active job listings count
$stmt = $conn->prepare("SELECT COUNT(*) AS active_jobs FROM job_listings WHERE employer_id = ? AND status = 'active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$active_jobs = $result->fetch_assoc()['active_jobs'] ?? 0;

// Fetch total applications count
$stmt = $conn->prepare("SELECT COUNT(*) AS total_applications FROM job_applications ja
                        JOIN job_listings jl ON ja.job_id = jl.id
                        WHERE jl.employer_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_applications = $result->fetch_assoc()['total_applications'] ?? 0;

// Fetch pending applications
$stmt = $conn->prepare("SELECT COUNT(*) AS pending_applications FROM job_applications ja
                        JOIN job_listings jl ON ja.job_id = jl.id
                        WHERE jl.employer_id = ? AND ja.status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$pending_applications = $result->fetch_assoc()['pending_applications'] ?? 0;

// Fetch recent applications
$stmt = $conn->prepare("SELECT ja.id, ja.created_at, ja.status, jl.title, u.name as applicant_name, p.profile_image
                        FROM job_applications ja
                        JOIN job_listings jl ON ja.job_id = jl.id
                        JOIN professionals p ON ja.professional_id = p.user_id
                        JOIN users u ON p.user_id = u.id
                        WHERE jl.employer_id = ?
                        ORDER BY ja.created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_applications = $stmt->get_result();

// Fetch recent job listings
$stmt = $conn->prepare("SELECT id, title, created_at, status, 
                        (SELECT COUNT(*) FROM job_applications WHERE job_id = job_listings.id) as applications_count 
                        FROM job_listings
                        WHERE employer_id = ?
                        ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_jobs = $stmt->get_result();

// Include header
$page_title = "Employer Dashboard";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="jobs/create.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg"></i> Post New Job
                    </a>
                </div>
            </div>
            
            <!-- Stats cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-white">Active Job Listings</h6>
                                    <h2 class="card-text fw-bold text-white"><?php echo $active_jobs; ?></h2>
                                </div>
                                <i class="bi bi-briefcase fs-1 text-white-50"></i>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="jobs/index.php" class="text-white text-decoration-none small">View all jobs <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card text-bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-white">Total Applications</h6>
                                    <h2 class="card-text fw-bold text-white"><?php echo $total_applications; ?></h2>
                                </div>
                                <i class="bi bi-file-earmark-person fs-1 text-white-50"></i>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="applications/index.php" class="text-white text-decoration-none small">View all applications <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card text-bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-white">Pending Applications</h6>
                                    <h2 class="card-text fw-bold text-white"><?php echo $pending_applications; ?></h2>
                                </div>
                                <i class="bi bi-hourglass-split fs-1 text-white-50"></i>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="applications/index.php?status=pending" class="text-white text-decoration-none small">Review pending <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Recent applications -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Recent Applications</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if ($recent_applications->num_rows > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php while ($application = $recent_applications->fetch_assoc()): ?>
                                        <a href="applications/view.php?id=<?php echo $application['id']; ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($application['profile_image'])): ?>
                                                        <img src="../../uploads/profiles/<?php echo $application['profile_image']; ?>" 
                                                             class="rounded-circle me-3" width="40" height="40" alt="Applicant">
                                                    <?php else: ?>
                                                        <div class="rounded-circle me-3 d-flex align-items-center justify-content-center text-white bg-secondary" 
                                                             style="width: 40px; height: 40px;">
                                                            <?php echo strtoupper(substr($application['applicant_name'], 0, 1)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo $application['applicant_name']; ?></h6>
                                                        <p class="mb-0 text-muted small"><?php echo $application['title']; ?></p>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-<?php 
                                                        echo ($application['status'] === 'pending') ? 'warning' : 
                                                            (($application['status'] === 'approved') ? 'success' : 'danger'); 
                                                    ?>">
                                                        <?php echo ucfirst($application['status']); ?>
                                                    </span>
                                                    <div class="small text-muted">
                                                        <?php echo timeAgo($application['created_at']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center p-4">
                                    <p class="text-muted">No applications received yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="applications/index.php" class="text-decoration-none">View all applications <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent job listings -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Recent Jobs</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if ($recent_jobs->num_rows > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php while ($job = $recent_jobs->fetch_assoc()): ?>
                                        <a href="jobs/view.php?id=<?php echo $job['id']; ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0"><?php echo $job['title']; ?></h6>
                                                    <div class="mt-1">
                                                        <span class="badge bg-<?php 
                                                            echo ($job['status'] === 'active') ? 'success' : 
                                                                (($job['status'] === 'draft') ? 'secondary' : 'danger'); 
                                                        ?> me-2">
                                                            <?php echo ucfirst($job['status']); ?>
                                                        </span>
                                                        <span class="text-muted small">
                                                            Posted <?php echo timeAgo($job['created_at']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="text-center">
                                                    <div class="fs-5 fw-bold"><?php echo $job['applications_count']; ?></div>
                                                    <div class="small text-muted">Applications</div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center p-4">
                                    <p class="text-muted">No job listings yet.</p>
                                    <a href="jobs/create.php" class="btn btn-primary btn-sm">Post Your First Job</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="jobs/index.php" class="text-decoration-none">View all jobs <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
