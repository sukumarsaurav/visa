<?php
$page_title = "Our Services | CANEXT Immigration";
include('includes/header.php');
?>

<!-- Page Header -->
<section class="page-header" style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('images/services-header.jpg'); background-size: cover; background-position: center; padding: 100px 0; color: var(--color-light); text-align: center;background-color: var(--color-burgundy);">
    <div style="position: absolute; width: 300px; height: 300px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.1); top: -100px; left: -100px;"></div>
    <div style="position: absolute; width: 200px; height: 200px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.1); bottom: -50px; right: 10%; animation: pulse 4s infinite alternate;"></div>
    <div style="position: absolute; width: 100px; height: 100px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.1); top: 30%; right: 20%; animation: pulse 3s infinite alternate;"></div>
    <div class="container">
        <h1 data-aos="fade-up">Our Immigration Services</h1>
        <nav aria-label="breadcrumb" data-aos="fade-up" data-aos-delay="100">
            <ol class="breadcrumb" style="display: flex; justify-content: center; list-style: none; padding: 0; margin: 20px 0 0 0;">
                <li class="breadcrumb-item"><a href="index.php" style="color: var(--color-cream);">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page" style="color: var(--color-light);">Services</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Services Overview Section -->
<section class="section services-overview" style="background-color: var(--color-cream);">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 50px;">
            <h2 class="section-title" data-aos="fade-up">Comprehensive Immigration Solutions</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">We offer a wide range of immigration services tailored to your unique needs and circumstances</p>
        </div>

        <div class="services-grid">
            <!-- Study Permit -->
            <div class="service-card" data-aos="fade-up" data-aos-delay="200">
                <div class="service-image" style="background-image: url('images/study-visa.png')"></div>
                <div class="service-content">
                    <h3 class="service-title">Study Permits</h3>
                    <p class="service-description">Pursue your educational dreams at top Canadian institutions with our expert guidance on study permit applications.</p>
                    <a href="visa-types/Study-Permit.php" class="btn btn-secondary">Learn More</a>
                </div>
            </div>
            
            <!-- Work Permit -->
            <div class="service-card" data-aos="fade-up" data-aos-delay="300">
                <div class="service-image" style="background-image: url('images/work-visa.png')"></div>
                <div class="service-content">
                    <h3 class="service-title">Work Permits</h3>
                    <p class="service-description">Advance your career in Canada with our specialized assistance for work permit applications and employer connections.</p>
                    <a href="visa-types/Work-Permit.php" class="btn btn-secondary">Learn More</a>
                </div>
            </div>
            
            <!-- Express Entry -->
            <div class="service-card" data-aos="fade-up" data-aos-delay="400">
                <div class="service-image" style="background-image: url('images/express-entry.png')"></div>
                <div class="service-content">
                    <h3 class="service-title">Express Entry</h3>
                    <p class="service-description">Fast-track your permanent residency through Canada's Express Entry system with our proven strategies.</p>
                    <a href="visa-types/Express-Entry-visa.php" class="btn btn-secondary">Learn More</a>
                </div>
            </div>
            
            <!-- Provincial Nominee -->
            <div class="service-card" data-aos="fade-up" data-aos-delay="500">
                <div class="service-image" style="background-image: url('images/pro.png')"></div>
                <div class="service-content">
                    <h3 class="service-title">Provincial Nominee</h3>
                    <p class="service-description">Leverage provincial immigration programs tailored to specific regions' needs and boost your chances of permanent residence.</p>
                    <a href="visa-types/Provincial-Nominee.php" class="btn btn-secondary">Learn More</a>
                </div>
            </div>
            
            <!-- Family Sponsorship -->
            <div class="service-card" data-aos="fade-up" data-aos-delay="600">
                <div class="service-image" style="background-image: url('images/family-sponsership.png')"></div>
                <div class="service-content">
                    <h3 class="service-title">Family Sponsorship</h3>
                    <p class="service-description">Reunite with your loved ones by sponsoring family members for Canadian permanent residence with our expert guidance.</p>
                    <a href="visa-types/Family-Sponsorship.php" class="btn btn-secondary">Learn More</a>
                </div>
            </div>
            
            <!-- Visitor Visa -->
            <div class="service-card" data-aos="fade-up" data-aos-delay="700">
                <div class="service-image" style="background-image: url('images/visitor-visa.png')"></div>
                <div class="service-content">
                    <h3 class="service-title">Visitor Visa</h3>
                    <p class="service-description">Visit Canada for tourism, business meetings, or family visits with our comprehensive visitor visa application support.</p>
                    <a href="visa-types/Visitor-Visa.php" class="btn btn-secondary">Learn More</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="section why-choose-us" style="background-color: var(--color-light);">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 50px;">
            <h2 class="section-title" data-aos="fade-up">Why Choose Our Services</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Experience the difference with our professional immigration services</p>
        </div>

        <div class="features-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
            <div class="feature-card" data-aos="fade-up" style="text-align: center; padding: 30px; background-color: var(--color-cream); border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="feature-icon" style="font-size: 3rem; color: var(--color-burgundy); margin-bottom: 20px;">
                    <i class="fas fa-certificate"></i>
                </div>
                <h3 style="color: var(--color-burgundy); margin-bottom: 15px;">Licensed Professionals</h3>
                <p>Our team consists of licensed immigration consultants registered with ICCRC.</p>
            </div>

            <div class="feature-card" data-aos="fade-up" data-aos-delay="100" style="text-align: center; padding: 30px; background-color: var(--color-cream); border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="feature-icon" style="font-size: 3rem; color: var(--color-burgundy); margin-bottom: 20px;">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 style="color: var(--color-burgundy); margin-bottom: 15px;">Fast Processing</h3>
                <p>We ensure timely submission and efficient processing of your applications.</p>
            </div>

            <div class="feature-card" data-aos="fade-up" data-aos-delay="200" style="text-align: center; padding: 30px; background-color: var(--color-cream); border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="feature-icon" style="font-size: 3rem; color: var(--color-burgundy); margin-bottom: 20px;">
                    <i class="fas fa-users"></i>
                </div>
                <h3 style="color: var(--color-burgundy); margin-bottom: 15px;">Personalized Support</h3>
                <p>Get dedicated support and guidance throughout your immigration journey.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="section cta-section" style="background-image: linear-gradient(rgba(31, 55, 153, 0.9), rgba(109, 35, 35, 0.9)), url('images/cta.png'); background-size: cover; background-position: center; padding: 80px 0; color: var(--color-light); text-align: center;">
    <div class="container">
        <h2 data-aos="fade-up" style="font-size: 2.2rem; margin-bottom: 20px;">Ready to Start Your Immigration Journey?</h2>
        <p data-aos="fade-up" data-aos-delay="100" style="font-size: 1.1rem; margin-bottom: 30px; max-width: 700px; margin-left: auto; margin-right: auto;">Contact us today for a consultation and let us help you achieve your Canadian dreams.</p>
        <a href="contact.php" class="btn btn-primary" data-aos="fade-up" data-aos-delay="200" style="background-color: var(--color-cream); color: var(--color-burgundy);">Get Started Now</a>
    </div>
</section>

<?php include('includes/footer.php'); ?>
