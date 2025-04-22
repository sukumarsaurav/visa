<?php
$page_title = "Contact Us | Visafy Immigration";
include('includes/header.php');
?>

<!-- Page Header -->
<section class="page-header" style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('images/contact-header.jpg'); background-size: cover; background-position: center; padding: 100px 0; color: var(--color-light); text-align: center;background-color: var(--color-burgundy);">
<div style="position: absolute; width: 300px; height: 300px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.1); top: -100px; left: -100px;"></div>
    <div style="position: absolute; width: 200px; height: 200px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.1); bottom: -50px; right: 10%; animation: pulse 4s infinite alternate;"></div>
    <div style="position: absolute; width: 100px; height: 100px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.1); top: 30%; right: 20%; animation: pulse 3s infinite alternate;"></div>
    <div class="container">
        <h1 data-aos="fade-up">Contact Us</h1>
        <nav aria-label="breadcrumb" data-aos="fade-up" data-aos-delay="100">
            <ol class="breadcrumb" style="display: flex; justify-content: center; list-style: none; padding: 0; margin: 20px 0 0 0;">
                <li class="breadcrumb-item"><a href="index.php" style="color: var(--color-cream);">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page" style="color: var(--color-light);">Contact</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Contact Information Section -->
<section class="section contact-info-section" style="background-color: var(--color-cream);">
    <div class="container">
        <div class="info-cards-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 50px;">
            <!-- Office Address Card -->
            <div class="info-card" data-aos="fade-up" style="background-color: var(--color-light); border-radius: 10px; padding: 30px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="info-icon" style="font-size: 3rem; color: var(--color-burgundy); margin-bottom: 20px;">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h3 style="color: var(--color-burgundy); margin-bottom: 15px;">Our Office</h3>
                <p>2233 Argentina Rd</p>
                <p>Mississauga ON L5N 2X7</p>
                <p>Canada</p>
            </div>
            
            <!-- Phone Card -->
            <div class="info-card" data-aos="fade-up" data-aos-delay="100" style="background-color: var(--color-light); border-radius: 10px; padding: 30px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="info-icon" style="font-size: 3rem; color: var(--color-burgundy); margin-bottom: 20px;">
                    <i class="fas fa-phone-alt"></i>
                </div>
                <h3 style="color: var(--color-burgundy); margin-bottom: 15px;">Phone</h3>
                <p>Main: +1 (647) 226-7436</p>
                <p>Toll-free: 1-800-123-4567</p>
                <p>Fax: +1 (647) 226-7437</p>
            </div>
            
            <!-- Email Card -->
            <div class="info-card" data-aos="fade-up" data-aos-delay="200" style="background-color: var(--color-light); border-radius: 10px; padding: 30px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="info-icon" style="font-size: 3rem; color: var(--color-burgundy); margin-bottom: 20px;">
                    <i class="fas fa-envelope"></i>
                </div>
                <h3 style="color: var(--color-burgundy); margin-bottom: 15px;">Email</h3>
                <p>General: info@visafy.com</p>
                <p>Support: support@visafy.com</p>
                <p>Careers: careers@visafy.com</p>
            </div>
            
            <!-- Hours Card -->
            <div class="info-card" data-aos="fade-up" data-aos-delay="300" style="background-color: var(--color-light); border-radius: 10px; padding: 30px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="info-icon" style="font-size: 3rem; color: var(--color-burgundy); margin-bottom: 20px;">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 style="color: var(--color-burgundy); margin-bottom: 15px;">Office Hours</h3>
                <p>Monday - Friday: 9am - 5pm</p>
                <p>Saturday: 10am - 2pm</p>
                <p>Sunday: Closed</p>
            </div>
        </div>
    </div>
</section>

<!-- Map and Form Section -->
<section class="section contact-form-section">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 50px;">
            <h2 class="section-title" data-aos="fade-up">Get In Touch</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Have questions about your immigration journey? Send us a message and we'll get back to you as soon as possible.</p>
        </div>
        
        <?php
        // Display success or error messages if redirected from form submission
        if (isset($_GET['status']) && isset($_GET['message'])) {
            $status = $_GET['status'];
            $message = $_GET['message'];
            
            if ($status === 'success') {
                echo '<div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 30px; text-align: center;">' . htmlspecialchars($message) . '</div>';
            } else {
                echo '<div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 30px; text-align: center;">' . htmlspecialchars($message) . '</div>';
            }
        }
        ?>
        
        <div class="contact-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 50px;">
            <!-- Contact Form -->
            <div class="contact-form-container" data-aos="fade-up">
                <form id="contact-form" class="contact-form" action="php/process_contact.php" method="POST">
                    <div class="form-group">
                        <label for="name">Your Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="service">Service of Interest</label>
                        <select id="service" name="service">
                            <option value="">Select a service</option>
                            <option value="study">Study Permit</option>
                            <option value="work">Work Permit</option>
                            <option value="express-entry">Express Entry</option>
                            <option value="provincial-nominee">Provincial Nominee</option>
                            <option value="family">Family Sponsorship</option>
                            <option value="visitor">Visitor Visa</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Your Message *</label>
                        <textarea id="message" name="message" rows="5" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <p style="font-size: 0.9rem; color: #666;">Fields marked with an asterisk (*) are required</p>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
            
            <!-- Google Map -->
            <div class="map-container" data-aos="fade-up" data-aos-delay="100">
                <div style="border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <!-- Replace with your actual Google Maps embed code -->
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2889.8848212076276!2d-79.75753582417056!3d43.59019305508704!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x882b6a73ae42d81f%3A0xf7ce21138dab90ed!2s2233%20Argentina%20Rd%2C%20Mississauga%2C%20ON%20L5N%202X7%2C%20Canada!5e0!3m2!1sen!2sus!4v1633527141748!5m2!1sen!2sus" width="100%" height="100%" style="border:0; height: 450px;" allowfullscreen="" loading="lazy"></iframe>
                </div>
                
                <div style="margin-top: 30px;">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 15px;">Additional Information</h3>
                    <p>Our office is conveniently located in Mississauga, with easy access to public transportation. Street parking is also available.</p>
                    <p>If you're coming for a consultation, please arrive 10 minutes before your scheduled appointment time.</p>
                </div>
                
                <div style="margin-top: 30px;">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 15px;">Connect With Us</h3>
                    <div class="social-links" style="display: flex; gap: 15px;">
                        <a href="#" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background-color: var(--color-burgundy); color: var(--color-light); transition: all 0.3s ease;">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background-color: var(--color-burgundy); color: var(--color-light); transition: all 0.3s ease;">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background-color: var(--color-burgundy); color: var(--color-light); transition: all 0.3s ease;">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background-color: var(--color-burgundy); color: var(--color-light); transition: all 0.3s ease;">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="section faq-section" style="background-color: var(--color-cream);">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 50px;">
            <h2 class="section-title" data-aos="fade-up">Frequently Asked Questions</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Find answers to common questions about our services and the consultation process</p>
        </div>
        
        <div class="faq-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            <!-- FAQ Column 1 -->
            <div class="faq-column" data-aos="fade-up">
                <div class="faq-item" style="margin-bottom: 30px;">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 15px;">How do I schedule a consultation?</h3>
                    <p>You can schedule a consultation by filling out our contact form, calling our office directly, or using our online booking system. We offer both in-person and virtual consultations for your convenience.</p>
                </div>
                
                <div class="faq-item" style="margin-bottom: 30px;">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 15px;">What documents should I bring to my consultation?</h3>
                    <p>For your initial consultation, please bring your passport, educational credentials, work experience documents, language test results (if available), and any previous immigration applications or correspondence.</p>
                </div>
            </div>
            
            <!-- FAQ Column 2 -->
            <div class="faq-column" data-aos="fade-up" data-aos-delay="100">
                <div class="faq-item" style="margin-bottom: 30px;">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 15px;">How much do your services cost?</h3>
                    <p>Our service fees vary depending on the type of application and level of assistance required. We provide transparent pricing during the initial consultation based on your specific situation and immigration goals.</p>
                </div>
                
                <div class="faq-item" style="margin-bottom: 30px;">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 15px;">How long does the immigration process take?</h3>
                    <p>Processing times vary by immigration program and individual circumstances. During your consultation, we'll provide estimated timeframes based on current processing standards and your specific situation.</p>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;" data-aos="fade-up">
            <p>Have more questions? Don't hesitate to contact us or check our <a href="resources.php#faq" style="color: var(--color-burgundy); font-weight: 600;">complete FAQ section</a>.</p>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="section cta-section" style="background-image: linear-gradient(rgba(31, 55, 153, 0.9), rgba(109, 35, 35, 0.9)), url('images/cta.png'); background-size: cover; background-position: center; padding: 80px 0; color: var(--color-light); text-align: center;">
    <div class="container">
        <h2 data-aos="fade-up" style="font-size: 2.2rem; margin-bottom: 20px;">Ready to Start Your Canadian Journey?</h2>
        <p data-aos="fade-up" data-aos-delay="100" style="font-size: 1.1rem; margin-bottom: 30px; max-width: 700px; margin-left: auto; margin-right: auto;">Book a consultation with one of our immigration experts today and take the first step toward your new life in Canada.</p>
        <a href="#" class="btn btn-primary" data-aos="fade-up" data-aos-delay="200" style="background-color: var(--color-cream); color: var(--color-burgundy);">Book a Consultation</a>
    </div>
</section>

<?php include('includes/footer.php'); ?> 