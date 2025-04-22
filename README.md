# CANEXT Consultancy Website

A comprehensive website for a Canadian immigration consultancy specializing in visa services, including study permits, work permits, and express entry applications.

## Features

- **Responsive Design**: Mobile-friendly layout that works on all devices
- **Visa Information Pages**: Detailed information about various Canadian visa types
- **Assessment Tools**: Eligibility calculator, CRS score calculator, and more
- **Booking System**: Online consultation scheduling
- **Contact Forms**: Easy ways for clients to get in touch
- **Blog/News Section**: Latest immigration updates and resources
- **Custom Design**: Built with custom CSS (no frameworks) using the specified color scheme
- **AOS Animations**: Smooth, elegant animations on scroll
- **Database Integration**: MySQL backend for storing client inquiries and more

## Technology Stack

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Animations**: AOS (Animate On Scroll) library
- **Icons**: Font Awesome
- **Sliders**: Swiper.js

## Color Scheme

- Primary Light: #FEF9E1 (cream/beige)
- Secondary Light: #E5D0AC (light tan/gold)
- Primary Accent: #A31D1D (deep red)
- Secondary Accent: #6D2323 (burgundy)

## Installation Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/easyborders-website.git
   cd easyborders-website
   ```

2. **Set up a local server environment**
   - Install XAMPP, WAMP, MAMP, or any other local server stack that supports PHP and MySQL
   - Place the project files in your server's web directory (e.g., htdocs for XAMPP)

3. **Create the database**
   - Open your MySQL administration tool (e.g., phpMyAdmin)
   - Create a new database named `immigration_db`
   - Import the `immigration_db.sql` file to set up the tables and sample data

4. **Configure database connection**
   - Open `php/db_config.php`
   - Update the database connection credentials if needed:
     ```php
     $servername = "localhost";
     $username = "your_mysql_username";
     $password = "your_mysql_password";
     $dbname = "immigration_db";
     ```

5. **Start your server and access the website**
   - Navigate to `http://localhost/easyborders-website` in your web browser (URL may vary depending on your server configuration)

## Project Structure

```
/
├── css/                    # CSS stylesheets
│   ├── styles.css          # Main stylesheet
│   └── animations.css      # Custom animations
├── js/                     # JavaScript files
│   └── main.js             # Main JavaScript functionality
├── php/                    # PHP scripts
│   ├── db_config.php       # Database configuration
│   └── process_contact.php # Contact form processing
├── includes/               # PHP include files
│   ├── header.php          # Site header
│   └── footer.php          # Site footer
├── images/                 # Image assets
├── uploads/                # User uploads directory
├── fonts/                  # Custom fonts (if any)
├── immigration_db.sql      # Database schema and sample data
├── index.php               # Homepage
├── contact.php             # Contact page
└── README.md               # This file
```

## Customization

- **Logo**: Replace `images/logo.png` with your own logo
- **Colors**: Edit the CSS variables in `css/styles.css` to match your brand colors
- **Content**: Update text content in PHP files as needed
- **Images**: Replace placeholder images in the `images` directory

## Contributors

- Your Name

## License

This project is licensed under the MIT License - see the LICENSE file for details. 
