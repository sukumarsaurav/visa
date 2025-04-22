<?php
session_start();
require_once 'config/db_connect.php';
require_once 'includes/functions.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Fetch all verified professionals
$query = "SELECT p.*, u.name, u.email, 
          (SELECT COUNT(*) FROM reviews WHERE professional_id = p.user_id) as review_count
          FROM professionals p
          INNER JOIN users u ON p.user_id = u.id
          WHERE p.verification_status = 'verified'
          AND u.status = 'active'
          AND p.availability_status = 'available'
          AND u.deleted_at IS NULL
          ORDER BY p.is_featured DESC, p.rating DESC";
          
$result = $conn->query($query);
$professionals = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Fetch specializations for each professional
        $spec_query = "SELECT s.name 
                      FROM specializations s
                      INNER JOIN professional_specializations ps ON s.id = ps.specialization_id
                      WHERE ps.professional_id = ?";
        $stmt = $conn->prepare($spec_query);
        $stmt->bind_param("i", $row['id']);
        $stmt->execute();
        $spec_result = $stmt->get_result();
        
        $specializations = [];
        while ($spec = $spec_result->fetch_assoc()) {
            $specializations[] = $spec['name'];
        }
        
        $row['specializations'] = $specializations;
        $professionals[] = $row;
    }
}

// Page title
$page_title = "Book a Consultation";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Visafy</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .consultant-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .consultant-card {
            width: 300px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background-color: #fff;
        }
        
        .consultant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .consultant-image {
            height: 160px;
            width: 100%;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f8f9fa;
        }
        
        .consultant-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #3498db;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .consultant-details {
            padding: 15px;
        }
        
        .consultant-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }
        
        .consultant-info {
            color: #555;
            margin-bottom: 12px;
            font-size: 0.9rem;
        }
        
        .rating {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .stars {
            color: #f39c12;
            margin-right: 5px;
        }
        
        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 15px;
        }
        
        .tag {
            background-color: #e0e7ff;
            color: #4361ee;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .view-profile-btn {
            display: block;
            width: 100%;
            background-color: #3498db;
            color: white;
            text-align: center;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        
        .view-profile-btn:hover {
            background-color: #2980b9;
        }
        
        .featured-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #f39c12;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .page-heading {
            text-align: center;
            margin: 30px 0;
        }
        
        .empty-state {
            text-align: center;
            margin: 40px 0;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <h1 class="page-heading">Find a Visa Consultant</h1>
        
        <div class="consultant-container">
            <?php if (empty($professionals)): ?>
                <div class="empty-state">
                    <h3>No consultants available at the moment</h3>
                    <p>Please check back later for available visa consultants.</p>
                </div>
            <?php else: ?>
                <?php foreach ($professionals as $professional): ?>
                    <div class="consultant-card">
                        <?php if ($professional['is_featured']): ?>
                            <div class="featured-badge">Featured</div>
                        <?php endif; ?>
                        
                        <div class="consultant-image">
                            <?php if (!empty($professional['profile_image'])): ?>
                                <img src="uploads/profiles/<?php echo $professional['profile_image']; ?>" 
                                     alt="<?php echo $professional['name']; ?>">
                            <?php else: ?>
                                <div class="profile-placeholder">
                                    <?php echo strtoupper(substr($professional['name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="consultant-details">
                            <h3 class="consultant-name"><?php echo $professional['name']; ?></h3>
                            
                            <div class="consultant-info">
                                <p><strong>Experience:</strong> <?php echo $professional['years_experience']; ?> years</p>
                            </div>
                            
                            <div class="rating">
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
                                <span><?php echo number_format($professional['rating'] ?? 0, 1); ?> (<?php echo $professional['review_count']; ?> reviews)</span>
                            </div>
                            
                            <div class="tags">
                                <?php foreach (array_slice($professional['specializations'], 0, 3) as $specialization): ?>
                                    <span class="tag"><?php echo $specialization; ?></span>
                                <?php endforeach; ?>
                                
                                <?php if (count($professional['specializations']) > 3): ?>
                                    <span class="tag">+<?php echo count($professional['specializations']) - 3; ?> more</span>
                                <?php endif; ?>
                            </div>
                            
                            <a href="consultant-profile.php?id=<?php echo $professional['user_id']; ?>" class="view-profile-btn">
                                View Profile & Book
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
