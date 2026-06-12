<?php 
session_start(); 
require 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Learn about Taigon Investments - Your trusted IT solutions partner in Tanzania since 2015">
    <title>About Us - Taigon Investments | IT Solutions Tanzania</title>
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
        .about-hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: var(--radius-xl);
            padding: 3rem;
            margin-bottom: 3rem;
            text-align: center;
            color: white;
        }

        .about-hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .about-hero p {
            font-size: 1.1rem;
            opacity: 0.95;
            max-width: 700px;
            margin: 0 auto;
        }

        /* Stats Section */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 0.75rem;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
        }

        .stat-card .label {
            color: var(--text-mid);
            font-size: 0.9rem;
        }

        /* About Content */
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .about-text h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .about-text p {
            color: var(--text-mid);
            margin-bottom: 1rem;
            line-height: 1.7;
        }

        .about-image {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .about-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            min-height: 300px;
        }

        /* Mission Vision */
        .mission-vision {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .mv-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 2rem;
            text-align: center;
            border: 1px solid var(--border);
            transition: all 0.3s;
        }

        .mv-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-light);
        }

        .mv-card i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .mv-card h3 {
            font-size: 1.3rem;
            margin-bottom: 0.75rem;
        }

        .mv-card p {
            color: var(--text-mid);
        }

        /* Services Grid */
        .services-section {
            margin-bottom: 3rem;
        }

        .section-title {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .service-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid var(--border);
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .service-card i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .service-card h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .service-card p {
            color: var(--text-mid);
            font-size: 0.85rem;
        }

        /* Branch Section - Single Branch */
        .branch-section {
            margin-bottom: 3rem;
        }

        .branch-card-single {
            background: linear-gradient(135deg, var(--surface) 0%, var(--surface-muted) 100%);
            border-radius: var(--radius-xl);
            padding: 2rem;
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

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: var(--radius-xl);
            padding: 3rem;
            text-align: center;
            color: white;
        }

        .cta-section h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .cta-section p {
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        .btn-cta {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            color: var(--primary-dark);
            padding: 0.8rem 2rem;
            border-radius: 2rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 3rem 1.5rem 1.5rem;
            margin-top: 3rem;
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
            .about-content { grid-template-columns: 1fr; }
            .mission-vision { grid-template-columns: 1fr; }
            .about-hero { padding: 2rem; }
            .about-hero h1 { font-size: 1.8rem; }
            .branch-details { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .header-container { padding: 0.75rem 1rem; }
            .logo-text { font-size: 1rem; }
            .logo-area img { height: 35px; }
            .btn-auth span { display: none; }
            .btn-auth { padding: 0.6rem; border-radius: 50%; }
            .stats-section { grid-template-columns: repeat(2, 1fr); }
            .stat-card .number { font-size: 1.5rem; }
        }

        @media (max-width: 480px) {
            .stats-section { grid-template-columns: 1fr; }
            .services-grid { grid-template-columns: 1fr; }
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
            <a href="about.php" class="nav-link active"><i class="fas fa-info-circle"></i><span>About</span></a>
            <a href="contact.php" class="nav-link"><i class="fas fa-envelope"></i><span>Contact</span></a>
            <a href="cart.php" class="nav-link"><i class="fas fa-shopping-cart"></i><span>Cart</span></a>
        </nav>
    </div>
</header>

<main class="main-content">
    <!-- Hero Section -->
    <div class="about-hero">
        <h1>About Taigon Investments</h1>
        <p>Your trusted partner for IT solutions, computer hardware, CCTV installation, and network services in Tanzania since 2015</p>
    </div>

    <!-- Stats Section -->
    <div class="stats-section">
        <div class="stat-card">
            <i class="fas fa-calendar-alt"></i>
            <div class="number">10+</div>
            <div class="label">Years of Experience</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <div class="number">5,000+</div>
            <div class="label">Happy Customers</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-box"></i>
            <div class="number">10,000+</div>
            <div class="label">Products Sold</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-headset"></i>
            <div class="number">24/7</div>
            <div class="label">Support Available</div>
        </div>
    </div>

    <!-- About Content -->
    <div class="about-content">
        <div class="about-text">
            <h2>Who We Are</h2>
            <p>Taigon Investments is a premier IT solutions provider headquartered in Arusha, Tanzania. Founded in 2015, we have grown to become one of the most trusted names in the Tanzanian IT industry, serving thousands of satisfied customers across the country.</p>
            <p>Our team of certified technicians and IT professionals is dedicated to delivering cutting-edge technology solutions that meet the unique needs of businesses and individuals alike. From small home networks to enterprise-level infrastructure, we have the expertise to handle projects of any scale.</p>
            <p>We pride ourselves on offering high-quality products at competitive prices, backed by exceptional customer service and technical support. Our commitment to excellence has earned us a reputation for reliability and trustworthiness in the Tanzanian market.</p>
        </div>
        <div class="about-image">
            <img src="images/about-office.jpg" alt="Taigon Investments Office" onerror="this.src='images/placeholder-about.jpg'">
        </div>
    </div>

    <!-- Mission & Vision -->
    <div class="mission-vision">
        <div class="mv-card">
            <i class="fas fa-bullseye"></i>
            <h3>Our Mission</h3>
            <p>To bridge the technology gap in Tanzania by providing high-quality IT products and services that empower businesses and individuals to achieve their full potential in the digital age.</p>
        </div>
        <div class="mv-card">
            <i class="fas fa-eye"></i>
            <h3>Our Vision</h3>
            <p>To become Tanzania's most trusted and innovative IT solutions provider, recognized for excellence in service, quality, and customer satisfaction.</p>
        </div>
    </div>

    <!-- Our Services -->
    <div class="services-section">
        <h2 class="section-title">What We Offer</h2>
        <div class="services-grid">
            <div class="service-card">
                <i class="fas fa-network-wired"></i>
                <h3>Network Installation</h3>
                <p>Professional network setup, structured cabling, router configuration, and network security solutions</p>
            </div>
            <div class="service-card">
                <i class="fas fa-video"></i>
                <h3>CCTV Systems</h3>
                <p>High-definition surveillance cameras with remote access and 24/7 recording capabilities</p>
            </div>
            <div class="service-card">
                <i class="fas fa-laptop"></i>
                <h3>Computer Sales</h3>
                <p>New and refurbished computers from top brands including Dell, HP, Lenovo, and Apple</p>
            </div>
            <div class="service-card">
                <i class="fas fa-print"></i>
                <h3>Printer Services</h3>
                <p>Printer sales, consumables, repair, and maintenance for all major brands</p>
            </div>
            <div class="service-card">
                <i class="fas fa-microchip"></i>
                <h3>IT Spare Parts</h3>
                <p>Genuine computer components including processors, RAM, hard drives, and motherboards</p>
            </div>
            <div class="service-card">
                <i class="fas fa-tools"></i>
                <h3>Device Repair</h3>
                <p>Expert diagnosis and repair for computers, printers, and other electronic devices</p>
            </div>
        </div>
    </div>

    <!-- Our Branch - Arusha (Single Branch) -->
    <div class="branch-section">
        <div class="branch-card-single">
            <div class="branch-header">
                <i class="fas fa-building"></i>
                <h2>Our Headquarters - Arusha</h2>
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
                <div class="branch-detail-item">
                    <i class="fas fa-clock"></i>
                    <div class="detail-info">
                        <h4>Business Hours</h4>
                        <p>Mon-Fri: 8AM - 6PM | Sat: 9AM - 4PM</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="cta-section">
        <h2>Ready to work with us?</h2>
        <p>Get in touch with our team for all your IT needs</p>
        <a href="contact.php" class="btn-cta"><i class="fas fa-envelope"></i> Contact Us Today</a>
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

// Animate stats on scroll
const observerOptions = { threshold: 0.5, rootMargin: '0px' };
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const numbers = entry.target.querySelectorAll('.number');
            numbers.forEach(num => {
                const final = parseInt(num.textContent);
                let current = 0;
                const increment = final / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= final) {
                        num.textContent = final;
                        clearInterval(timer);
                    } else {
                        num.textContent = Math.floor(current);
                    }
                }, 20);
            });
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

const statsSection = document.querySelector('.stats-section');
if (statsSection) observer.observe(statsSection);
</script>
</body>
</html>