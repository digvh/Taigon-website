<?php 
session_start(); 
require 'db.php';

// Handle contact form submission
$formSubmitted = false;
$formError = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($message)) {
        $formError = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formError = 'Please enter a valid email address.';
    } else {
        // Save to database
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('sssss', $name, $email, $phone, $subject, $message);
        
        if ($stmt->execute()) {
            $formSuccess = 'Thank you for your message! We will get back to you within 24 hours.';
            $formSubmitted = true;
            
            // Optional: Send email notification
            $to = 'info@taigoninvestment.co.tz';
            $emailSubject = "New Contact Message from $name";
            $emailMessage = "Name: $name\nEmail: $email\nPhone: $phone\nSubject: $subject\n\nMessage:\n$message";
            @mail($to, $emailSubject, $emailMessage, "From: $email");
        } else {
            $formError = 'Something went wrong. Please try again later.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Contact Taigon Investments in Arusha - Get in touch with our team for IT solutions, support, and inquiries">
    <title>Contact Us - Taigon Investments | IT Solutions Tanzania</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0ea5e9;
            --primary-dark: #0369a1;
            --primary-light: #38bdf8;
            --primary-glow: rgba(14, 165, 233, 0.25);
            --accent: #f97316;
            --accent-glow: rgba(249, 115, 22, 0.3);
            --success: #10b981;
            --bg-dark: #0f172a;
            --surface: #ffffff;
            --surface-muted: #f8fafc;
            --border: #e2e8f0;
            --text-dark: #0f172a;
            --text-mid: #475569;
            --text-light: #94a3b8;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: var(--text-dark);
            line-height: 1.5;
            min-height: 100vh;
        }

        /* Header Styles */
        .modern-header {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(20px);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(14,165,233,0.15);
            box-shadow: var(--shadow-sm);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .logo-area { display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0; }
        .logo-area img { height: 45px; width: auto; border-radius: var(--radius-sm); object-fit: contain; }
        .logo-text { font-weight: 800; font-size: 1.3rem; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .main-nav { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
        .nav-link { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1rem; color: var(--text-mid); text-decoration: none; font-weight: 500; font-size: 0.9rem; border-radius: var(--radius-md); transition: all 0.2s; white-space: nowrap; }
        .nav-link:hover, .nav-link.active { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; transform: translateY(-1px); box-shadow: 0 4px 12px var(--primary-glow); }

        .search-wrapper { position: relative; min-width: 280px; }
        .search-wrapper input { width: 100%; padding: 0.6rem 2.5rem 0.6rem 1rem; border: 1.5px solid var(--border); border-radius: 2rem; font-size: 0.85rem; background: white; transition: all 0.2s; }
        .search-wrapper input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
        .search-wrapper button { position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%); background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border: none; color: white; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; transition: all 0.2s; }
        .search-wrapper button:hover { transform: translateY(-50%) scale(1.05); }

        .auth-area { display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0; }
        .btn-auth { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem; border-radius: 2rem; text-decoration: none; font-weight: 600; font-size: 0.85rem; transition: all 0.2s; white-space: nowrap; }
        .btn-login { background: transparent; color: var(--primary-dark); border: 1.5px solid rgba(14,165,233,0.3); }
        .btn-login:hover { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; }
        .btn-track { background: linear-gradient(135deg, var(--accent), #ea580c); color: white; box-shadow: 0 2px 8px var(--accent-glow); }
        .btn-logout { background: transparent; color: #dc2626; border: 1.5px solid rgba(220,38,38,0.3); }
        .btn-logout:hover { background: #dc2626; color: white; }
        .welcome-text { font-size: 0.85rem; color: var(--text-mid); }

        .suggestions { position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: var(--radius-md); box-shadow: var(--shadow-lg); max-height: 300px; overflow-y: auto; z-index: 1000; margin-top: 0.5rem; display: none; }
        .suggestion-item { padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid var(--border); }
        .suggestion-item:hover { background: rgba(14,165,233,0.05); }
        .suggestion-item a { text-decoration: none; color: inherit; display: block; }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* Hero Section */
        .contact-hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: var(--radius-xl);
            padding: 3rem;
            margin-bottom: 3rem;
            text-align: center;
            color: white;
        }

        .contact-hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .contact-hero p {
            font-size: 1.1rem;
            opacity: 0.95;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Contact Grid */
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        /* Contact Info Cards */
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .info-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            border: 1px solid var(--border);
            transition: all 0.3s;
        }

        .info-card:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .info-icon {
            width: 50px;
            height: 50px;
            background: rgba(14,165,233,0.1);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .info-icon i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .info-content h3 {
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .info-content p, .info-content a {
            color: var(--text-mid);
            text-decoration: none;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .info-content a:hover {
            color: var(--primary);
        }

        /* Contact Form */
        .contact-form-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
        }

        .contact-form-card h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-subtitle {
            color: var(--text-mid);
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.85rem;
            color: var(--text-dark);
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.2s;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-submit {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px var(--primary-glow);
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Main Branch Card */
        .branch-featured {
            background: linear-gradient(135deg, var(--surface) 0%, var(--surface-muted) 100%);
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-bottom: 3rem;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
        }

        .branch-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.75rem;
            display: inline-flex;
        }

        .branch-header i {
            font-size: 2rem;
            color: var(--primary);
        }

        .branch-header h2 {
            font-size: 1.5rem;
            color: var(--primary-dark);
        }

        .branch-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .branch-detail-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            transition: all 0.3s;
        }

        .branch-detail-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .branch-detail-item i {
            font-size: 1.5rem;
            color: var(--primary);
            width: 40px;
            text-align: center;
        }

        .branch-detail-item .detail-info h4 {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 0.25rem;
        }

        .branch-detail-item .detail-info p {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        /* Map Section */
        .map-section {
            margin-bottom: 3rem;
        }

        .map-container {
            background: var(--surface);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
        }

        .map-container iframe {
            width: 100%;
            height: 400px;
            border: none;
            display: block;
        }

        /* Business Hours */
        .hours-section {
            background: var(--surface);
            border-radius: var(--radius-xl);
            padding: 2rem;
            text-align: center;
            border: 1px solid var(--border);
        }

        .hours-section h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: var(--primary-dark);
        }

        .hours-grid {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .hours-item {
            text-align: center;
        }

        .hours-item .day {
            font-weight: 600;
            color: var(--text-dark);
        }

        .hours-item .time {
            color: var(--text-mid);
            font-size: 0.85rem;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 3rem 1.5rem 1.5rem;
            margin-top: 3rem;
        }

        /* Social Links */
        .social-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        /* Responsive */
        @media (max-width: 1100px) {
            .header-container { flex-wrap: wrap; }
            .main-nav { order: 3; width: 100%; justify-content: center; padding-top: 0.5rem; border-top: 1px solid var(--border); margin-top: 0.5rem; }
            .search-wrapper { flex: 1; min-width: 200px; }
            .nav-link span { display: none; }
            .nav-link { padding: 0.6rem; }
        }

        @media (max-width: 900px) {
            .contact-grid { grid-template-columns: 1fr; }
            .contact-hero { padding: 2rem; }
            .contact-hero h1 { font-size: 1.8rem; }
            .form-row { grid-template-columns: 1fr; }
            .branch-details { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .header-container { padding: 0.75rem 1rem; }
            .logo-text { font-size: 1rem; }
            .logo-area img { height: 35px; }
            .btn-auth span { display: none; }
            .btn-auth { padding: 0.6rem; border-radius: 50%; }
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="modern-header">
    <div class="header-container">
        <div class="logo-area">
            <img src="image/log.png" alt="Taigon Investments">
            <span class="logo-text">Taigon Investments</span>
        </div>
        
        <div class="search-wrapper">
            <form action="search.php" method="get" onsubmit="return validateSearch()">
                <input type="text" name="query" id="search-input" placeholder="Search products..." autocomplete="off">
                <button type="submit"><i class="fas fa-search"></i></button>
                <div id="suggestions" class="suggestions"></div>
            </form>
        </div>
        
        <div class="auth-area">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="welcome-text">Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="logout.php" class="btn-auth btn-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            <?php else: ?>
                <a href="userlogin.php" class="btn-auth btn-login"><i class="fas fa-sign-in-alt"></i><span>Login</span></a>
            <?php endif; ?>
            <a href="my_orders.php" class="btn-auth btn-track"><i class="fas fa-truck"></i><span>Track</span></a>
        </div>
        
        <nav class="main-nav">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i><span>Home</span></a>
            <a href="product.php" class="nav-link"><i class="fas fa-laptop"></i><span>Products</span></a>
            <a href="about.php" class="nav-link"><i class="fas fa-info-circle"></i><span>About</span></a>
            <a href="contact.php" class="nav-link active"><i class="fas fa-envelope"></i><span>Contact</span></a>
            <a href="cart.php" class="nav-link"><i class="fas fa-shopping-cart"></i><span>Cart</span></a>
        </nav>
    </div>
</header>

<main class="main-content">
    <!-- Hero Section -->
    <div class="contact-hero">
        <h1>Get In Touch</h1>
        <p>Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
    </div>

    <!-- Contact Grid -->
    <div class="contact-grid">
        <!-- Contact Info -->
        <div class="contact-info">
            <div class="info-card">
                <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                <div class="info-content">
                    <h3>Visit Our Headquarters</h3>
                    <p>Arusha, Tanzania<br>Located in the city center</p>
                </div>
            </div>
            <div class="info-card">
                <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                <div class="info-content">
                    <h3>Call Us</h3>
                    <p>+255 740 610 143<br>+255 667 350 570</p>
                </div>
            </div>
            <div class="info-card">
                <div class="info-icon"><i class="fas fa-envelope"></i></div>
                <div class="info-content">
                    <h3>Email Us</h3>
                    <p><a href="mailto:info@taigoninvestment.co.tz">info@taigoninvestment.co.tz</a><br><a href="mailto:sales@taigoninvestment.co.tz">sales@taigoninvestment.co.tz</a></p>
                </div>
            </div>
            <div class="info-card">
                <div class="info-icon"><i class="fab fa-whatsapp"></i></div>
                <div class="info-content">
                    <h3>WhatsApp</h3>
                    <p><a href="https://wa.me/255740610143">+255 740 610 143</a><br>Chat with our support team</p>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="contact-form-card">
            <h2>Send a Message</h2>
            <p class="form-subtitle">Fill out the form below and we'll get back to you within 24 hours</p>
            
            <?php if ($formSuccess): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $formSuccess; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($formError): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $formError; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$formSubmitted || $formError): ?>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Your Name *</label>
                        <input type="text" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject">
                            <option value="General Inquiry">General Inquiry</option>
                            <option value="Product Support">Product Support</option>
                            <option value="Sales">Sales</option>
                            <option value="Technical Support">Technical Support</option>
                            <option value="Installation Service">Installation Service</option>
                            <option value="Partnership">Partnership</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Message *</label>
                    <textarea name="message" rows="5" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Send Message</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Branch Details -->
    <div class="branch-featured">
        <div class="branch-header">
            <i class="fas fa-building"></i>
            <h2>Our Main Branch - Arusha</h2>
        </div>
        <div class="branch-details">
            <div class="branch-detail-item">
                <i class="fas fa-map-marker-alt"></i>
                <div class="detail-info">
                    <h4>Address</h4>
                    <p>Arusha City Center, Arusha, Tanzania</p>
                </div>
            </div>
            <div class="branch-detail-item">
                <i class="fas fa-phone-alt"></i>
                <div class="detail-info">
                    <h4>Phone Numbers</h4>
                    <p>+255 740 610 143 | +255 667 350 570</p>
                </div>
            </div>
            <div class="branch-detail-item">
                <i class="fas fa-envelope"></i>
                <div class="detail-info">
                    <h4>Email Address</h4>
                    <p>info@taigoninvestment.co.tz</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Section -->
    <div class="map-section">
        <div class="map-container">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1019523.437572913!2d35.5!3d-3.5!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x18364b2c7f8b5b3d%3A0x8b9e5e8c8b5b3d!2sArusha%2C%20Tanzania!5e0!3m2!1sen!2s!4v1700000000000!5m2!1sen!2s" 
                allowfullscreen="" 
                loading="lazy">
            </iframe>
        </div>
    </div>

    <!-- Business Hours -->
    <div class="hours-section">
        <h3><i class="fas fa-clock"></i> Business Hours</h3>
        <div class="hours-grid">
            <div class="hours-item">
                <div class="day">Monday - Friday</div>
                <div class="time">8:00 AM - 6:00 PM</div>
            </div>
            <div class="hours-item">
                <div class="day">Saturday</div>
                <div class="time">9:00 AM - 4:00 PM</div>
            </div>
            <div class="hours-item">
                <div class="day">Sunday</div>
                <div class="time">Closed</div>
            </div>
        </div>
        
        <!-- Social Media Links -->
        <div class="social-links">
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
        </div>
    </div>
</main>

<footer class="footer">
    <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
        <p style="color: #64748b;">&copy; 2024 Taigon Investments. All rights reserved. | IT Solutions & Hardware Store</p>
    </div>
</footer>

<script>
function fetchSuggestions() {
    var query = document.getElementById('search-input').value;
    if (query.trim() === '') {
        document.getElementById('suggestions').style.display = 'none';
        return;
    }
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'search.php?query=' + encodeURIComponent(query), true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            var suggestions = JSON.parse(xhr.responseText);
            var container = document.getElementById('suggestions');
            container.innerHTML = '';
            if (suggestions.length > 0) {
                container.style.display = 'block';
                suggestions.forEach(function(suggestion) {
                    var item = document.createElement('div');
                    item.className = 'suggestion-item';
                    item.innerHTML = '<a href="product_details.php?id=' + suggestion.id + '">' + suggestion.name + '</a>';
                    container.appendChild(item);
                });
            } else {
                container.style.display = 'none';
            }
        }
    };
    xhr.send();
}

function validateSearch() {
    var query = document.getElementById('search-input').value;
    if (query.trim() === '') {
        alert('Please enter a search term');
        return false;
    }
    return true;
}

document.getElementById('search-input').addEventListener('input', fetchSuggestions);
document.addEventListener('click', function(e) {
    if (!document.querySelector('.search-wrapper').contains(e.target)) {
        document.getElementById('suggestions').style.display = 'none';
    }
});
</script>
</body>
</html>