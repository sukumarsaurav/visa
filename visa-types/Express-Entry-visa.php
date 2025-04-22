<?php
$page_title = "Express Entry | Visafy Immigration";
include('../includes/header.php');
?>

<!-- Page Header -->
<section class="page-header" style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('../images/visas/express-entry-header.jpg'); background-size: cover; background-position: center; padding: 100px 0; color: var(--color-light); text-align: center; position: relative; overflow: hidden; background-color: var(--color-burgundy);">
    <!-- Decorative Circles -->
    <div style="position: absolute; width: 300px; height: 300px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.1); top: -100px; left: -100px;"></div>
    <div style="position: absolute; width: 200px; height: 200px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.1); bottom: -50px; right: 10%; animation: pulse 4s infinite alternate;"></div>
    <div style="position: absolute; width: 100px; height: 100px; border-radius: 50%; background-color: rgba(255, 255, 255, 0.1); top: 30%; right: 20%; animation: pulse 3s infinite alternate;"></div>
    
    <div class="container">
        <h1 data-aos="fade-up">Express Entry Immigration</h1>
        <nav aria-label="breadcrumb" data-aos="fade-up" data-aos-delay="100">
            <ol class="breadcrumb" style="display: flex; justify-content: center; list-style: none; padding: 0; margin: 20px 0 0 0;">
                <li class="breadcrumb-item"><a href="../index.php" style="color: var(--color-cream);">Home</a></li>
                <li class="breadcrumb-item"><a href="../visas.php" style="color: var(--color-cream);">Visa Services</a></li>
                <li class="breadcrumb-item active" aria-current="page" style="color: var(--color-light);">Express Entry</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Introduction Section -->
<section class="section intro-section" style="position: relative; overflow: hidden; background-color: #f9f9f9;">
    <!-- Decorative Circles -->
    <div style="position: absolute; width: 400px; height: 400px; border-radius: 50%; background-color: rgba(163, 29, 29, 0.05); top: -200px; right: -200px; animation: pulse 8s infinite alternate;"></div>
    <div style="position: absolute; width: 200px; height: 200px; border-radius: 50%; background-color: rgba(163, 29, 29, 0.05); bottom: -100px; left: 10%; animation: pulse 6s infinite alternate;"></div>
    
    <div class="container">
        <div class="row" style="display: flex; flex-wrap: wrap; gap: 30px;">
            <div class="col-content" data-aos="fade-up" style="flex: 2; min-width: 60%;">
                <h2 class="section-title">Canada's Premier Immigration Program</h2>
                <p data-aos="fade-up" data-aos-delay="100">Express Entry is Canada's flagship immigration system that manages applications for permanent residence under three federal economic programs: the Federal Skilled Worker Program, the Federal Skilled Trades Program, and the Canadian Experience Class.</p>
                
                <p data-aos="fade-up" data-aos-delay="200">This points-based system evaluates candidates based on factors such as age, education, work experience, and language proficiency. Those with the highest scores are invited to apply for permanent residence through regular draws.</p>
                
                <p data-aos="fade-up" data-aos-delay="300">Express Entry offers one of the fastest pathways to permanent residence in Canada, with processing times typically ranging from 6 to 8 months. For skilled workers looking to build a new life in Canada, Express Entry represents an excellent opportunity to achieve Canadian permanent residence.</p>
            </div>
            
            <div class="col-sidebar" data-aos="fade-up" data-aos-delay="400" style="flex: 1; min-width: 30%;">
                <div style="background-color: var(--color-cream); padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 20px;">Why Choose Visafy?</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                            <i class="fas fa-check-circle" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                            <span>Personalized Express Entry strategy</span>
                        </li>
                        <li style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                            <i class="fas fa-check-circle" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                            <span>CRS score optimization</span>
                        </li>
                        <li style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                            <i class="fas fa-check-circle" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                            <span>Document verification and preparation</span>
                        </li>
                        <li style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                            <i class="fas fa-check-circle" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                            <span>Post-ITA application support</span>
                        </li>
                        <li style="display: flex; align-items: flex-start;">
                            <i class="fas fa-check-circle" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                            <span>Post-landing services and guidance</span>
                        </li>
                    </ul>
                    <a href="../Book-Consultation.php?service=express-entry" class="btn btn-primary" style="width: 100%; margin-top: 20px;">Book Consultation</a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
@media (max-width: 768px) {
    .intro-section .row {
        flex-direction: column;
    }
    .intro-section .col-content,
    .intro-section .col-sidebar {
        min-width: 100% !important;
    }
}
</style>

<!-- Eligibility Section -->
<section class="section eligibility-section" style="background-color: var(--color-cream);">
    <div class="container">
        <h2 class="section-title text-center" data-aos="fade-up" style="text-align: center;">Express Entry Eligibility</h2>
        <p class="section-subtitle text-center" data-aos="fade-up" data-aos-delay="100" style="text-align: center; max-width: 800px; margin: 0 auto 40px;">
            Each program within Express Entry has its own eligibility requirements. Below are the key criteria for each program:
        </p>
        
        <div class="eligibility-tabs" style="margin-top: 40px;">
            <!-- Tab Navigation -->
            <ul class="nav-tabs" style="display: flex; list-style: none; padding: 0; margin-bottom: 30px; border-bottom: 1px solid #ddd; overflow-x: auto;" data-aos="fade-up" data-aos-delay="200">
                <li style="margin-right: 5px;">
                    <a href="#fswp" class="tab-link active" style="display: block; padding: 10px 20px; background-color: var(--color-burgundy); color: white; text-decoration: none; border-radius: 5px 5px 0 0;">Federal Skilled Worker</a>
                </li>
                <li style="margin-right: 5px;">
                    <a href="#fstp" class="tab-link" style="display: block; padding: 10px 20px; background-color: #f5f5f5; color: var(--color-dark); text-decoration: none; border-radius: 5px 5px 0 0;">Federal Skilled Trades</a>
                </li>
                <li>
                    <a href="#cec" class="tab-link" style="display: block; padding: 10px 20px; background-color: #f5f5f5; color: var(--color-dark); text-decoration: none; border-radius: 5px 5px 0 0;">Canadian Experience Class</a>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content" data-aos="fade-up" data-aos-delay="300">
                <!-- Federal Skilled Worker Program -->
                <div id="fswp" class="tab-pane active" style="background-color: white; padding: 30px; border-radius: 0 5px 5px 5px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 20px;">Federal Skilled Worker Program (FSWP)</h3>
                    <p>To qualify for the FSWP, you need:</p>
                    <ul style="margin: 15px 0; padding-left: 20px;">
                        <li style="margin-bottom: 10px;"><strong>Skilled work experience:</strong> At least 1 year of continuous full-time (or equivalent part-time) skilled work experience in the past 10 years</li>
                        <li style="margin-bottom: 10px;"><strong>Language skills:</strong> CLB 7 in English or French for all language abilities</li>
                        <li style="margin-bottom: 10px;"><strong>Education:</strong> Canadian secondary or post-secondary certificate, diploma or degree, or an Educational Credential Assessment (ECA) for education obtained outside Canada</li>
                        <li style="margin-bottom: 10px;"><strong>Score at least 67 out of 100 points</strong> on the FSWP grid which assesses six factors: language skills, education, work experience, age, arranged employment in Canada, and adaptability</li>
                        <li style="margin-bottom: 10px;"><strong>Proof of funds:</strong> Show you have enough money to settle in Canada (unless you have a valid job offer)</li>
                    </ul>
                </div>
                
                <!-- Federal Skilled Trades Program -->
                <div id="fstp" class="tab-pane" style="background-color: white; padding: 30px; border-radius: 0 5px 5px 5px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: none;">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 20px;">Federal Skilled Trades Program (FSTP)</h3>
                    <p>To qualify for the FSTP, you need:</p>
                    <ul style="margin: 15px 0; padding-left: 20px;">
                        <li style="margin-bottom: 10px;"><strong>Skilled trades experience:</strong> At least 2 years of full-time work experience (or equivalent part-time) in a skilled trade within the 5 years before applying</li>
                        <li style="margin-bottom: 10px;"><strong>Language skills:</strong> CLB 5 for speaking and listening, and CLB 4 for reading and writing</li>
                        <li style="margin-bottom: 10px;"><strong>Qualification requirements:</strong> Either a valid job offer of at least 1 year OR a certificate of qualification in your skilled trade issued by a Canadian provincial, territorial or federal authority</li>
                        <li style="margin-bottom: 10px;"><strong>Eligible occupation:</strong> Your job must be in a skilled trade in one of the designated groups</li>
                        <li style="margin-bottom: 10px;"><strong>Proof of funds:</strong> Show you have enough money to settle in Canada (unless you have a valid job offer)</li>
                    </ul>
                </div>
                
                <!-- Canadian Experience Class -->
                <div id="cec" class="tab-pane" style="background-color: white; padding: 30px; border-radius: 0 5px 5px 5px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: none;">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 20px;">Canadian Experience Class (CEC)</h3>
                    <p>To qualify for the CEC, you need:</p>
                    <ul style="margin: 15px 0; padding-left: 20px;">
                        <li style="margin-bottom: 10px;"><strong>Canadian work experience:</strong> At least 1 year of skilled, professional or technical work experience in Canada within 3 years of applying</li>
                        <li style="margin-bottom: 10px;"><strong>Language skills:</strong> CLB 7 for NOC 0 or A jobs, or CLB 5 for NOC B jobs</li>
                        <li style="margin-bottom: 10px;"><strong>Work experience must be:</strong> In Canada with proper authorization, paid, full-time (or equivalent in part-time), in one or more NOC 0, A, or B occupations</li>
                        <li style="margin-bottom: 10px;"><strong>Not planning to live in Quebec</strong> (Quebec has its own immigration programs)</li>
                    </ul>
                    <p>Note: There is no education requirement for the CEC, but education points can boost your CRS score.</p>
                </div>
            </div>
        </div>
        
        <div class="eligibility-notes" style="margin-top: 30px; text-align: center;" data-aos="fade-up" data-aos-delay="400">
            <p>Meeting these minimum requirements makes you eligible to enter the Express Entry pool, but does not guarantee an invitation to apply for permanent residence.</p>
            <a href="../assessment-tools.php#crs" class="btn btn-secondary" style="margin-top: 15px;">Calculate Your CRS Score</a>
        </div>
    </div>
</section>

<!-- Required Documents Section -->
<section class="section documents-section">
    <div class="container">
        <h2 class="section-title text-center" data-aos="fade-up" style="text-align: center;">Required Documents</h2>
        <p class="section-subtitle text-center" data-aos="fade-up" data-aos-delay="100" style="text-align: center; max-width: 800px; margin: 0 auto 40px;">
            Express Entry has a two-step application process. First, you create a profile to enter the pool, and if invited, you submit a complete application for permanent residence.
        </p>
        
        <div class="documents-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;" data-aos="fade-up" data-aos-delay="200">
            <!-- Express Entry Profile Documents -->
            <div class="document-card" style="background-color: var(--color-cream); padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <h3 style="color: var(--color-burgundy); margin-bottom: 20px; display: flex; align-items: center;">
                    <i class="fas fa-file-alt" style="margin-right: 10px;"></i>
                    Creating Your Express Entry Profile
                </h3>
                <p style="margin-bottom: 15px;">To create an Express Entry profile, you'll need:</p>
                <ul style="list-style: none; padding: 0;">
                    <li style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                        <i class="fas fa-check" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                        <span>Valid passport or travel document</span>
                    </li>
                    <li style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                        <i class="fas fa-check" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                        <span>Language test results (IELTS, CELPIP, or TEF)</span>
                    </li>
                    <li style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                        <i class="fas fa-check" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                        <span>Educational Credential Assessment (for foreign education)</span>
                    </li>
                    <li style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                        <i class="fas fa-check" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                        <span>Proof of work experience (reference letters, employment contracts)</span>
                    </li>
                    <li style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                        <i class="fas fa-check" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                        <span>Provincial Nomination certificate (if applicable)</span>
                    </li>
                    <li style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                        <i class="fas fa-check" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                        <span>Job offer letter from Canadian employer (if applicable)</span>
                    </li>
                </ul>
            </div>
            
            <!-- Permanent Residence Application Documents -->
            <div class="document-card" style="background-color: var(--color-cream); padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <h3 style="color: var(--color-burgundy); margin-bottom: 20px; display: flex; align-items: center;">
                    <i class="fas fa-file-alt" style="margin-right: 10px;"></i>
                    After Receiving an ITA
                </h3>
                <p style="margin-bottom: 15px;">After receiving an Invitation to Apply (ITA), you'll need additional documents:</p>
                <ul style="list-style: none; padding: 0;">
                    <li style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                        <i class="fas fa-check" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                        <span>Police certificates from all countries where you've lived for 6+ months since age 18</span>
                    </li>
                    <li style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                        <i class="fas fa-check" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                        <span>Medical examination results</span>
                    </li>
                    <li style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                        <i class="fas fa-check" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                        <span>Birth certificates for you and any accompanying family members</span>
                    </li>
                    <li style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                        <i class="fas fa-check" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                        <span>Marriage certificate (if applicable)</span>
                    </li>
                    <li style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                        <i class="fas fa-check" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                        <span>Proof of funds (bank statements, investment certificates)</span>
                    </li>
                    <li style="display: flex; align-items: flex-start; margin-bottom: 15px;">
                        <i class="fas fa-check" style="color: var(--color-burgundy); margin-right: 10px; margin-top: 3px;"></i>
                        <span>Digital photos meeting specifications</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="document-notes" style="margin-top: 30px; text-align: center;" data-aos="fade-up" data-aos-delay="300">
            <p>Document requirements may vary based on your specific situation. Our immigration experts can provide a personalized document checklist.</p>
            <a href="../Book-Consultation.php?service=express-entry" class="btn btn-secondary" style="margin-top: 15px;">Get Document Checklist</a>
        </div>
    </div>
</section>

<!-- Processing Time Section -->
<section class="section processing-section" style="background-color: var(--color-cream);">
    <div class="container">
        <h2 class="section-title text-center" data-aos="fade-up" style="text-align: center;">Processing Time</h2>
        <p class="section-subtitle text-center" data-aos="fade-up" data-aos-delay="100" style="text-align: center; max-width: 800px; margin: 0 auto 40px;">
            Express Entry is designed to process applications quickly, but the overall timeline depends on several factors.
        </p>
        
        <div class="timeline" data-aos="fade-up" data-aos-delay="200" style="max-width: 800px; margin: 0 auto;">
            <!-- Timeline Step 1 -->
            <div class="timeline-step" style="display: flex; margin-bottom: 30px;">
                <div class="timeline-icon" style="min-width: 50px; height: 50px; background-color: var(--color-burgundy); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                    <span>1</span>
                </div>
                <div class="timeline-content" style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); flex-grow: 1;">
                    <h4 style="color: var(--color-burgundy); margin-bottom: 10px;">Create Express Entry Profile</h4>
                    <p><strong>Duration:</strong> 1-2 weeks to gather documents and submit your profile</p>
                    <p>Your profile will remain in the pool for up to 12 months.</p>
                </div>
            </div>
            
            <!-- Timeline Step 2 -->
            <div class="timeline-step" style="display: flex; margin-bottom: 30px;">
                <div class="timeline-icon" style="min-width: 50px; height: 50px; background-color: var(--color-burgundy); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                    <span>2</span>
                </div>
                <div class="timeline-content" style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); flex-grow: 1;">
                    <h4 style="color: var(--color-burgundy); margin-bottom: 10px;">Receive Invitation to Apply (ITA)</h4>
                    <p><strong>Duration:</strong> Varies based on CRS score and draw frequency</p>
                    <p>Express Entry draws typically occur every 2 weeks, but this can vary. The higher your CRS score, the sooner you may receive an ITA.</p>
                </div>
            </div>
            
            <!-- Timeline Step 3 -->
            <div class="timeline-step" style="display: flex; margin-bottom: 30px;">
                <div class="timeline-icon" style="min-width: 50px; height: 50px; background-color: var(--color-burgundy); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                    <span>3</span>
                </div>
                <div class="timeline-content" style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); flex-grow: 1;">
                    <h4 style="color: var(--color-burgundy); margin-bottom: 10px;">Submit Complete Application</h4>
                    <p><strong>Duration:</strong> 60 days to gather all required documents and submit your application after receiving an ITA</p>
                    <p>This includes time to complete medical exams and obtain police certificates.</p>
                </div>
            </div>
            
            <!-- Timeline Step 4 -->
            <div class="timeline-step" style="display: flex;">
                <div class="timeline-icon" style="min-width: 50px; height: 50px; background-color: var(--color-burgundy); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20px;">
                    <span>4</span>
                </div>
                <div class="timeline-content" style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); flex-grow: 1;">
                    <h4 style="color: var(--color-burgundy); margin-bottom: 10px;">Application Processing</h4>
                    <p><strong>Duration:</strong> 6 months or less for 80% of applications</p>
                    <p>Processing begins once IRCC receives a complete application. If approved, you'll receive a Confirmation of Permanent Residence (COPR) and permanent resident visa (if applicable).</p>
                </div>
            </div>
        </div>
        
        <div class="processing-notes" style="margin-top: 30px; text-align: center;" data-aos="fade-up" data-aos-delay="300">
            <p>The total processing time from creating a profile to receiving permanent residence can range from 6 months to over a year, depending on your CRS score, document preparation time, and IRCC processing volumes.</p>
            <a href="../Book-Consultation.php?service=express-entry" class="btn btn-secondary" style="margin-top: 15px;">Get Processing Time Estimate</a>
        </div>
    </div>
</section>

<!-- Success Stories Section -->
<section class="section success-stories-section">
    <div class="container">
        <h2 class="section-title text-center" data-aos="fade-up" style="text-align: center;">Success Stories</h2>
        <p class="section-subtitle text-center" data-aos="fade-up" data-aos-delay="100" style="text-align: center; max-width: 800px; margin: 0 auto 40px;">
            Read about our clients who successfully immigrated to Canada through the Express Entry system.
        </p>
        
        <div class="stories-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;" data-aos="fade-up" data-aos-delay="200">
            <!-- Success Story 1 -->
            <div class="story-card" style="background-color: var(--color-cream); padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="story-header" style="display: flex; align-items: center; margin-bottom: 20px;">
                    <img src="../images/testimonials/person1.jpg" alt="Rajiv Patel" class="story-avatar" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; margin-right: 15px;">
                    <div>
                        <h4 class="story-client" style="color: var(--color-burgundy); margin-bottom: 5px;">Rajiv Patel</h4>
                        <p class="story-visa-type" style="font-size: 0.9rem; color: #666;">Federal Skilled Worker</p>
                    </div>
                </div>
                <div class="story-content">
                    <p>"As a software engineer from India, I was looking to advance my career in Canada. Visafy helped me optimize my CRS score. My application was approved in just 5 months, and I now work for a tech company in Toronto."</p>
                    <p style="margin-top: 15px;"><strong>CRS Score:</strong> 478 | <strong>Processing Time:</strong> 5 months</p>
                </div>
            </div>
            
            <!-- Success Story 2 -->
            <div class="story-card" style="background-color: var(--color-cream); padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="story-header" style="display: flex; align-items: center; margin-bottom: 20px;">
                    <img src="../images/testimonials/person2.jpg" alt="Maria Rodriguez" class="story-avatar" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; margin-right: 15px;">
                    <div>
                        <h4 class="story-client" style="color: var(--color-burgundy); margin-bottom: 5px;">Maria Rodriguez</h4>
                        <p class="story-visa-type" style="font-size: 0.9rem; color: #666;">Canadian Experience Class</p>
                    </div>
                </div>
                <div class="story-content">
                    <p>"After completing my master's degree in Canada, Visafy helped me leverage my Canadian education and work experience for CEC. I received my permanent residence just 7 months after entering the Express Entry pool."</p>
                    <p style="margin-top: 15px;"><strong>CRS Score:</strong> 502 | <strong>Processing Time:</strong> 4 months</p>
                </div>
            </div>
            
            <!-- Success Story 3 -->
            <div class="story-card" style="background-color: var(--color-cream); padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="story-header" style="display: flex; align-items: center; margin-bottom: 20px;">
                    <img src="../images/testimonials/person3.jpg" alt="Ahmed Hassan" class="story-avatar" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; margin-right: 15px;">
                    <div>
                        <h4 class="story-client" style="color: var(--color-burgundy); margin-bottom: 5px;">Ahmed Hassan</h4>
                        <p class="story-visa-type" style="font-size: 0.9rem; color: #666;">Federal Skilled Trades</p>
                    </div>
                </div>
                <div class="story-content">
                    <p>"As an electrician with 10 years of experience, Visafy helped me get my qualifications assessed. I'm now working in Vancouver and have brought my family to Canada as well."</p>
                    <p style="margin-top: 15px;"><strong>CRS Score:</strong> 386 | <strong>Processing Time:</strong> 6 months</p>
                </div>
            </div>
        </div>
        
        <div class="success-notes" style="margin-top: 30px; text-align: center;" data-aos="fade-up" data-aos-delay="300">
            <p>These are just a few examples of our successful Express Entry clients. Each case is unique, and results may vary based on individual circumstances.</p>
            <a href="../Book-Consultation.php?service=express-entry" class="btn btn-secondary" style="margin-top: 15px;">Start Your Success Story</a>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="section faq-section" style="background-color: var(--color-cream);">
    <div class="container">
        <h2 class="section-title text-center" data-aos="fade-up" style="text-align: center;">Frequently Asked Questions</h2>
        <p class="section-subtitle text-center" data-aos="fade-up" data-aos-delay="100" style="text-align: center; max-width: 800px; margin: 0 auto 40px;">
            Get answers to common questions about the Express Entry immigration process.
        </p>
        
        <div class="accordion" data-aos="fade-up" data-aos-delay="200" style="max-width: 800px; margin: 0 auto;">
            <!-- FAQ Item 1 -->
            <div class="accordion-item" style="margin-bottom: 15px; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="accordion-header" style="background-color: white; padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="color: var(--color-burgundy); margin: 0; font-size: 1.1rem;">What is the minimum CRS score needed to receive an ITA?</h3>
                    <i class="fas fa-chevron-down" style="color: var(--color-burgundy);"></i>
                </div>
                <div class="accordion-content" style="background-color: white; padding: 0 20px; max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out;">
                    <div style="padding: 20px 0;">
                        <p>There is no fixed minimum CRS score, as cutoff scores vary with each draw. Recent draws have ranged from 450 to 500 points for Federal Skilled Worker draws. Program-specific draws (like CEC or PNP) may have different score requirements.</p>
                        <p>To maximize your chances, aim for a CRS score above 470. Our team can help identify strategies to improve your score through language tests, additional education, or provincial nominations.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 2 -->
            <div class="accordion-item" style="margin-bottom: 15px; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="accordion-header" style="background-color: white; padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="color: var(--color-burgundy); margin: 0; font-size: 1.1rem;">Can I include my family in my Express Entry application?</h3>
                    <i class="fas fa-chevron-down" style="color: var(--color-burgundy);"></i>
                </div>
                <div class="accordion-content" style="background-color: white; padding: 0 20px; max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out;">
                    <div style="padding: 20px 0;">
                        <p>Yes, you can include your spouse or common-law partner and dependent children in your Express Entry application. They will receive permanent residence along with you if your application is approved.</p>
                        <p>Dependent children must be under 22 years of age and not married or in a common-law relationship (with some exceptions for children who depend on their parents due to physical or mental conditions).</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 3 -->
            <div class="accordion-item" style="margin-bottom: 15px; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="accordion-header" style="background-color: white; padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="color: var(--color-burgundy); margin: 0; font-size: 1.1rem;">How much does it cost to apply through Express Entry?</h3>
                    <i class="fas fa-chevron-down" style="color: var(--color-burgundy);"></i>
                </div>
                <div class="accordion-content" style="background-color: white; padding: 0 20px; max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out;">
                    <div style="padding: 20px 0;">
                        <p>The government fees for Express Entry include:</p>
                        <ul>
                            <li>Application fee: $850 CAD per adult applicant</li>
                            <li>Right of Permanent Residence Fee: $515 CAD per adult</li>
                            <li>Dependent child fee: $230 CAD per child</li>
                        </ul>
                        <p>Additional costs include medical exams (approximately $200-300 CAD per person), police certificates (varies by country), language tests ($200-300 CAD), Educational Credential Assessment ($200-300 CAD), and biometrics ($85 CAD per person).</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 4 -->
            <div class="accordion-item" style="margin-bottom: 15px; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="accordion-header" style="background-color: white; padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="color: var(--color-burgundy); margin: 0; font-size: 1.1rem;">Do I need a job offer to apply through Express Entry?</h3>
                    <i class="fas fa-chevron-down" style="color: var(--color-burgundy);"></i>
                </div>
                <div class="accordion-content" style="background-color: white; padding: 0 20px; max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out;">
                    <div style="padding: 20px 0;">
                        <p>No, a job offer is not required for Express Entry. However, having a valid job offer from a Canadian employer can significantly boost your CRS score (50-200 points depending on the skill level).</p>
                        <p>A job offer must generally be supported by a Labour Market Impact Assessment (LMIA), though there are some LMIA-exempt categories for certain workers.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 5 -->
            <div class="accordion-item" style="margin-bottom: 15px; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <div class="accordion-header" style="background-color: white; padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="color: var(--color-burgundy); margin: 0; font-size: 1.1rem;">What if my circumstances change after I create my profile?</h3>
                    <i class="fas fa-chevron-down" style="color: var(--color-burgundy);"></i>
                </div>
                <div class="accordion-content" style="background-color: white; padding: 0 20px; max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out;">
                    <div style="padding: 20px 0;">
                        <p>If your circumstances change (new language test results, additional education, marriage/divorce, birth of a child, new job, etc.), you must update your Express Entry profile. Changes could increase or decrease your CRS score and affect your chances of receiving an ITA.</p>
                        <p>Failure to update your profile with significant changes could result in your application being refused for misrepresentation.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="faq-notes" style="margin-top: 30px; text-align: center;" data-aos="fade-up" data-aos-delay="300">
            <p>Have more questions about Express Entry? Our immigration experts are here to help.</p>
            <a href="../Book-Consultation.php?service=express-entry" class="btn btn-secondary" style="margin-top: 15px;">Ask Our Experts</a>
        </div>
    </div>
</section>

<!-- Related Resources Section -->
<section class="section resources-section">
    <div class="container">
        <h2 class="section-title text-center" data-aos="fade-up" style="text-align: center;">Related Resources</h2>
        <p class="section-subtitle text-center" data-aos="fade-up" data-aos-delay="100" style="text-align: center; max-width: 800px; margin: 0 auto 40px;">
            Explore these additional resources to help with your Express Entry journey.
        </p>
        
        <div class="resources-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;" data-aos="fade-up" data-aos-delay="200">
            <!-- Resource 1 -->
            <div class="resource-card" style="background-color: var(--color-cream); padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column;">
                <div style="flex-grow: 1;">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 15px; display: flex; align-items: center;">
                        <i class="fas fa-calculator" style="margin-right: 10px;"></i>
                        CRS Calculator
                    </h3>
                    <p>Calculate your potential Comprehensive Ranking System (CRS) score to estimate your chances in the Express Entry pool.</p>
                </div>
                <a href="../assessment-tools.php#crs" class="btn btn-secondary" style="margin-top: 20px;">Use Calculator</a>
            </div>
            
            <!-- Resource 2 -->
            <div class="resource-card" style="background-color: var(--color-cream); padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column;">
                <div style="flex-grow: 1;">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 15px; display: flex; align-items: center;">
                        <i class="fas fa-clipboard-check" style="margin-right: 10px;"></i>
                        Eligibility Assessment
                    </h3>
                    <p>Determine if you qualify for Express Entry and which program would be best for your situation with our assessment tool.</p>
                </div>
                <a href="../assessment-tools.php#eligibility" class="btn btn-secondary" style="margin-top: 20px;">Check Eligibility</a>
            </div>
            
            <!-- Resource 3 -->
            <div class="resource-card" style="background-color: var(--color-cream); padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column;">
                <div style="flex-grow: 1;">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 15px; display: flex; align-items: center;">
                        <i class="fas fa-landmark" style="margin-right: 10px;"></i>
                        Provincial Nominee Programs
                    </h3>
                    <p>Learn about Provincial Nominee Programs (PNPs) that can significantly boost your CRS score and improve your Express Entry chances.</p>
                </div>
                <a href="Provincial-Nominee.php" class="btn btn-secondary" style="margin-top: 20px;">Learn More</a>
            </div>
            
            <!-- Resource 4 -->
            <div class="resource-card" style="background-color: var(--color-cream); padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column;">
                <div style="flex-grow: 1;">
                    <h3 style="color: var(--color-burgundy); margin-bottom: 15px; display: flex; align-items: center;">
                        <i class="fas fa-newspaper" style="margin-right: 10px;"></i>
                        Latest Draw Results
                    </h3>
                    <p>Stay updated with the latest Express Entry draw results, including cutoff scores and number of invitations issued.</p>
                </div>
                <a href="Immigration-News.php" class="btn btn-secondary" style="margin-top: 20px;">View Results</a>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section cta-section" style="background-image: linear-gradient(rgba(109, 35, 35, 0.9), rgba(109, 35, 35, 0.9)), url('../images/cta-background.jpg'); background-size: cover; background-position: center; padding: 80px 0; color: var(--color-light); text-align: center; margin-top: 50px;">
    <div class="container">
        <h2 data-aos="fade-up" style="font-size: 2.2rem; margin-bottom: 20px;">Ready to Start Your Express Entry Journey?</h2>
        <p data-aos="fade-up" data-aos-delay="100" style="font-size: 1.1rem; margin-bottom: 30px; max-width: 700px; margin-left: auto; margin-right: auto;">Let our experts guide you through the Express Entry process, from profile creation to landing in Canada.</p>
        <a href="../Book-Consultation.php?service=express-entry" class="btn btn-primary" data-aos="fade-up" data-aos-delay="200" style="background-color: var(--color-cream); color: var(--color-burgundy);">Book a Consultation</a>
    </div>
</section>

<!-- JavaScript for Accordion -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Accordion functionality
    const accordionHeaders = document.querySelectorAll('.accordion-header');
    
    accordionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const icon = this.querySelector('i');
            
            // Toggle content
            if (content.style.maxHeight) {
                content.style.maxHeight = null;
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                content.style.maxHeight = content.scrollHeight + "px";
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        });
    });
});
</script>

<?php include('../includes/footer.php'); ?>
