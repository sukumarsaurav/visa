<?php
$page_title = "About Us | Visafy Immigration";
include('includes/header.php');
?>

<!-- Page Header -->
<section class="page-header" style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('images/about-header.jpg'); background-size: cover; background-position: center; padding: 100px 0; color: var(--color-light); text-align: center;background-color: var(--color-burgundy);">
    <div style="position: absolute; width: 300px; height: 300px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.1); top: -100px; left: -100px;"></div>
    <div style="position: absolute; width: 200px; height: 200px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.1); bottom: -50px; right: 10%; animation: pulse 4s infinite alternate;"></div>
    <div style="position: absolute; width: 100px; height: 100px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.1); top: 30%; right: 20%; animation: pulse 3s infinite alternate;"></div>
    <div class="container">
        <h1 data-aos="fade-up">About Us</h1>
        <nav aria-label="breadcrumb" data-aos="fade-up" data-aos-delay="100">
            <ol class="breadcrumb" style="display: flex; justify-content: center; list-style: none; padding: 0; margin: 20px 0 0 0;">
                <li class="breadcrumb-item"><a href="index.php" style="color: var(--color-cream);">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page" style="color: var(--color-light);">About Us</li>
            </ol>
        </nav>
    </div>
</section>

<!-- About Overview Section -->
<section class="section about-overview" style="background-color: var(--color-cream);">
    <div class="container">
        <div class="about-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 50px; align-items: center;">
            <div class="about-content" data-aos="fade-right">
                <h2 style="color: var(--color-burgundy); font-size: 2.5rem; margin-bottom: 20px;">Your Trusted Partner in Canadian Immigration</h2>
                <p style="margin-bottom: 20px;">At Visafy Immigration, we understand that immigrating to Canada is a life-changing decision. Our mission is to make this journey as smooth and successful as possible for our clients.</p>
                <p style="margin-bottom: 20px;">With years of experience and a team of licensed professionals, we provide comprehensive immigration services tailored to your unique needs and circumstances.</p>
                <div class="stats-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 30px;">
                    <div class="stat-item" style="text-align: center;">
                        <h3 style="color: var(--color-burgundy); font-size: 2.5rem; margin-bottom: 10px;">1000+</h3>
                        <p>Successful Cases</p>
                    </div>
                    <div class="stat-item" style="text-align: center;">
                        <h3 style="color: var(--color-burgundy); font-size: 2.5rem; margin-bottom: 10px;">95%</h3>
                        <p>Success Rate</p>
                    </div>
                    <div class="stat-item" style="text-align: center;">
                        <h3 style="color: var(--color-burgundy); font-size: 2.5rem; margin-bottom: 10px;">10+</h3>
                        <p>Years Experience</p>
                    </div>
                </div>
            </div>
            <div class="about-image" data-aos="fade-left">
                <img src="assets/images/about-main.jpg" alt="Visafy Immigration Team" style="width: 100%; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
            </div>
        </div>
    </div>
</section>

<!-- Our Values Section -->
<section class="section our-values" style="background-color: var(--color-light);">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 50px;">
            <h2 class="section-title" data-aos="fade-up">Our Values</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">The principles that guide us in serving our clients</p>
        </div>

        <div class="values-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
            <div class="value-card" data-aos="fade-up" style="text-align: center; padding: 30px; background-color: var(--color-cream); border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="value-icon" style="font-size: 3rem; color: var(--color-burgundy); margin-bottom: 20px;">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3 style="color: var(--color-burgundy); margin-bottom: 15px;">Integrity</h3>
                <p>We maintain the highest standards of honesty and ethical conduct in all our dealings.</p>
            </div>

            <div class="value-card" data-aos="fade-up" data-aos-delay="100" style="text-align: center; padding: 30px; background-color: var(--color-cream); border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="value-icon" style="font-size: 3rem; color: var(--color-burgundy); margin-bottom: 20px;">
                    <i class="fas fa-star"></i>
                </div>
                <h3 style="color: var(--color-burgundy); margin-bottom: 15px;">Excellence</h3>
                <p>We strive for excellence in every aspect of our service delivery.</p>
            </div>

            <div class="value-card" data-aos="fade-up" data-aos-delay="200" style="text-align: center; padding: 30px; background-color: var(--color-cream); border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="value-icon" style="font-size: 3rem; color: var(--color-burgundy); margin-bottom: 20px;">
                    <i class="fas fa-heart"></i>
                </div>
                <h3 style="color: var(--color-burgundy); margin-bottom: 15px;">Commitment</h3>
                <p>We are committed to helping our clients achieve their immigration goals.</p>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="section team" style="background-color: var(--color-cream);">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 50px;">
            <h2 class="section-title" data-aos="fade-up">Our Team</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Meet our experienced immigration professionals</p>
        </div>

        <div class="team-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
            <div class="team-card" data-aos="fade-up" style="text-align: center; background-color: var(--color-light); border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div style="height: 250px; background-image: url('images/team-1.jpg'); background-size: cover; background-position: center;"></div>
                <div style="padding: 20px;">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 5px;">John Smith</h3>
                    <p style="color: var(--color-burgundy); font-size: 0.9rem; margin-bottom: 15px;">Senior Immigration Consultant</p>
                    <p style="font-size: 0.9rem;">ICCRC Licensed | 15+ years experience</p>
                </div>
            </div>

            <div class="team-card" data-aos="fade-up" data-aos-delay="100" style="text-align: center; background-color: var(--color-light); border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div style="height: 250px; background-image: url('images/team-2.jpg'); background-size: cover; background-position: center;"></div>
                <div style="padding: 20px;">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 5px;">Sarah Johnson</h3>
                    <p style="color: var(--color-burgundy); font-size: 0.9rem; margin-bottom: 15px;">Immigration Specialist</p>
                    <p style="font-size: 0.9rem;">ICCRC Licensed | 8+ years experience</p>
                </div>
            </div>

            <div class="team-card" data-aos="fade-up" data-aos-delay="200" style="text-align: center; background-color: var(--color-light); border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div style="height: 250px; background-image: url('images/team-3.jpg'); background-size: cover; background-position: center;"></div>
                <div style="padding: 20px;">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 5px;">Michael Chen</h3>
                    <p style="color: var(--color-burgundy); font-size: 0.9rem; margin-bottom: 15px;">Student Visa Specialist</p>
                    <p style="font-size: 0.9rem;">ICCRC Licensed | 5+ years experience</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="section cta-section" style="background-image: linear-gradient(rgba(31, 55, 153, 0.9), rgba(109, 35, 35, 0.9)), url('images/cta.png'); background-size: cover; background-position: center; padding: 80px 0; color: var(--color-light); text-align: center;">
    <div class="container">
        <h2 data-aos="fade-up" style="font-size: 2.2rem; margin-bottom: 20px;">Start Your Immigration Journey Today</h2>
        <p data-aos="fade-up" data-aos-delay="100" style="font-size: 1.1rem; margin-bottom: 30px; max-width: 700px; margin-left: auto; margin-right: auto;">Let our experienced team guide you through the immigration process.</p>
        <a href="contact.php" class="btn btn-primary" data-aos="fade-up" data-aos-delay="200" style="background-color: var(--color-cream); color: var(--color-burgundy);">Contact Us Now</a>
    </div>
</section>

<?php include('includes/footer.php'); ?>
