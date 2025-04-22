<?php
// This file handles the individual news article display
// Debug output (remove after testing)
// echo "<pre>Processing slug: " . $slug . "</pre>";

include('admin/includes/db_connection.php');

// Set the base URL to be absolute from the document root
$base_url = '';  // Empty means root-relative paths

// Get article details
$sql = "SELECT * FROM news_articles WHERE slug = '$slug' AND status = 'published'";
$result = executeQuery($sql);

// Debug output (remove after testing)
// if (!$result || $result->num_rows === 0) {
//     echo "<pre>No article found with slug: " . $slug . "</pre>";
// } else {
//     echo "<pre>Found article with slug: " . $slug . "</pre>";
// }

if (!$result || $result->num_rows === 0) {
    // Article not found, redirect to news listing
    header('Location: /resources/immigration-news.php');
    exit;
}

$article = $result->fetch_assoc();
$page_title = $article['title'] . " | CANEXT Immigration";
include('includes/header.php');

// Get related articles
$related_sql = "SELECT * FROM news_articles 
                WHERE status = 'published' 
                AND id != {$article['id']} 
                ORDER BY publish_date DESC 
                LIMIT 3";
$related_result = executeQuery($related_sql);
?>

<!-- Page Header -->
<section class="page-header" style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('/<?php echo $article['image'] ? 'images/news/' . $article['image'] : 'images/resources/news-header.jpg'; ?>'); background-size: cover; background-position: center; padding: 100px 0; color: var(--color-light); text-align: center;">
    <div class="container">  <div style="position: absolute; width: 300px; height: 300px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.1); top: -100px; left: -100px;"></div>
    <div style="position: absolute; width: 200px; height: 200px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.1); bottom: -50px; right: 10%; animation: pulse 4s infinite alternate;"></div>
    <div style="position: absolute; width: 100px; height: 100px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.1); top: 30%; right: 20%; animation: pulse 3s infinite alternate;"></div>
        <h1 data-aos="fade-up"><?php echo $article['title']; ?></h1>
        <nav aria-label="breadcrumb" data-aos="fade-up" data-aos-delay="100">
            <ol class="breadcrumb" style="display: flex; justify-content: center; list-style: none; padding: 0; margin: 20px 0 0 0;">
                <li class="breadcrumb-item"><a href="/index.php" style="color: var(--color-cream);">Home</a></li>
                <li class="breadcrumb-item"><a href="/resources/immigration-news.php" style="color: var(--color-cream);">Immigration News</a></li>
                <li class="breadcrumb-item active" aria-current="page" style="color: var(--color-light);"><?php echo $article['title']; ?></li>
            </ol>
        </nav>
        
        <div class="article-meta" data-aos="fade-up" data-aos-delay="200" style="margin-top: 20px; display: flex; justify-content: center; align-items: center; gap: 20px;">
            <span style="display: flex; align-items: center;">
                <i class="far fa-calendar-alt" style="margin-right: 5px;"></i>
                <?php echo date('F j, Y', strtotime($article['publish_date'])); ?>
            </span>
        </div>
    </div>
</section>

<!-- Article Content -->
<section class="section article-section">
    <div class="container">
        <div class="article-layout" style="display: grid; grid-template-columns: 1fr 300px; gap: 40px; align-items: start;">
            <!-- Main Content -->
            <div class="article-content" data-aos="fade-up" style="background: white; border-radius: 10px; padding: 40px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <?php if($article['image']): ?>
                    <div class="featured-image" style="margin: -40px -40px 30px; border-radius: 10px 10px 0 0; overflow: hidden;">
                        <img src="/images/news/<?php echo $article['image']; ?>" alt="<?php echo $article['title']; ?>" style="width: 100%; height: auto; display: block;">
                    </div>
                <?php endif; ?>
                
                <div class="content-area" style="line-height: 1.8;">
                    <?php echo $article['content']; ?>
                </div>
                
                <!-- Social Sharing -->
                <div class="social-share" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;">
                    <h4 style="margin-bottom: 15px;">Share This Article:</h4>
                    <div class="share-buttons" style="display: flex; gap: 10px;">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" rel="noopener noreferrer" class="share-button" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background-color: #3b5998; color: white; border-radius: 50%; text-decoration: none;">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($article['title']); ?>" target="_blank" rel="noopener noreferrer" class="share-button" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background-color: #1da1f2; color: white; border-radius: 50%; text-decoration: none;">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&title=<?php echo urlencode($article['title']); ?>" target="_blank" rel="noopener noreferrer" class="share-button" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background-color: #0077b5; color: white; border-radius: 50%; text-decoration: none;">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="mailto:?subject=<?php echo urlencode($article['title']); ?>&body=<?php echo urlencode('Check out this article: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="share-button" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background-color: #848484; color: white; border-radius: 50%; text-decoration: none;">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="article-sidebar">
                <!-- Related Articles -->
                <div class="sidebar-section" data-aos="fade-up" style="background: white; border-radius: 10px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 30px;">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--color-cream);">Related Articles</h3>
                    
                    <div class="related-articles">
                        <?php if ($related_result && $related_result->num_rows > 0):
                            while ($related = $related_result->fetch_assoc()):
                                $related_image = $related['image'] ? 'images/news/' . $related['image'] : 'images/resources/news-default.jpg';
                        ?>
                            <div class="related-article" style="margin-bottom: 20px; display: flex; gap: 10px;">
                                <div class="related-image" style="flex: 0 0 80px; height: 60px; background-image: url('/<?php echo $related_image; ?>'); background-size: cover; background-position: center; border-radius: 5px;"></div>
                                <div>
                                    <h4 style="font-size: 14px; margin: 0 0 5px 0;"><a href="/immigration-news/<?php echo $related['slug']; ?>" style="color: var(--color-dark); text-decoration: none;"><?php echo $related['title']; ?></a></h4>
                                    <div style="font-size: 12px; color: #666;"><?php echo date('M j, Y', strtotime($related['publish_date'])); ?></div>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                            <p>No related articles found.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="sidebar-section" data-aos="fade-up" data-aos-delay="100" style="background: white; border-radius: 10px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--color-cream);">Quick Links</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 10px;"><a href="/services/express-entry.php" style="color: var(--color-dark); text-decoration: none; display: flex; align-items: center;"><i class="fas fa-chevron-right" style="margin-right: 8px; color: var(--color-burgundy); font-size: 12px;"></i> Express Entry</a></li>
                        <li style="margin-bottom: 10px;"><a href="/services/provincial-nominee.php" style="color: var(--color-dark); text-decoration: none; display: flex; align-items: center;"><i class="fas fa-chevron-right" style="margin-right: 8px; color: var(--color-burgundy); font-size: 12px;"></i> Provincial Nominee Programs</a></li>
                        <li style="margin-bottom: 10px;"><a href="/services/study-permits.php" style="color: var(--color-dark); text-decoration: none; display: flex; align-items: center;"><i class="fas fa-chevron-right" style="margin-right: 8px; color: var(--color-burgundy); font-size: 12px;"></i> Study Permits</a></li>
                        <li style="margin-bottom: 10px;"><a href="/services/work-permits.php" style="color: var(--color-dark); text-decoration: none; display: flex; align-items: center;"><i class="fas fa-chevron-right" style="margin-right: 8px; color: var(--color-burgundy); font-size: 12px;"></i> Work Permits</a></li>
                        <li><a href="/services/family-sponsorship.php" style="color: var(--color-dark); text-decoration: none; display: flex; align-items: center;"><i class="fas fa-chevron-right" style="margin-right: 8px; color: var(--color-burgundy); font-size: 12px;"></i> Family Sponsorship</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="section newsletter-section">
    <div class="container">
        <div class="newsletter-container" data-aos="fade-up" style="max-width: 600px; margin: 0 auto; text-align: center;">
            <h2 class="section-title">Stay Updated</h2>
            <p class="section-subtitle">Subscribe to our newsletter to receive the latest immigration news and updates directly in your inbox.</p>
            
            <form class="newsletter-form" style="margin-top: 30px;">
                <div style="display: flex; gap: 10px;">
                    <input type="email" placeholder="Enter your email address" style="flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 5px;">
                    <button type="submit" class="btn btn-primary">Subscribe</button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include('includes/footer.php'); ?> 