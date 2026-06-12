<?php 
session_start(); 
require 'db.php';

// Function to get proper image path
function getProductImagePath($image, $productId = null) {
    if (empty($image) || $image === 'default-product.jpg') {
        if ($productId) {
            $possiblePaths = [
                'images/products/product_' . $productId . '.jpg',
                'images/products/product_' . $productId . '.png',
                'images/products/' . $productId . '.jpg',
                'image/products/product_' . $productId . '.jpg',
                'image/products/' . $productId . '.jpg'
            ];
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        return 'images/placeholder.png';
    }
    
    if (file_exists($image)) return $image;
    
    $pathsToCheck = [
        'images/products/' . basename($image),
        'images/' . basename($image),
        'image/products/' . basename($image),
        'uploads/products/' . basename($image)
    ];
    
    foreach ($pathsToCheck as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    if ($productId) {
        $idPaths = [
            'images/products/product_' . $productId . '.jpg',
            'images/products/' . $productId . '.jpg'
        ];
        foreach ($idPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
    }
    
    return 'images/placeholder.png';
}

// Function to get category icon based on category name
function getCategoryIcon($categoryName) {
    $icons = [
        'Laptops - Dell' => 'fab fa-dell',
        'Laptops - HP' => 'fab fa-hp',
        'Laptops - Lenovo' => 'fab fa-lenovo',
        'Laptops - Apple MacBook' => 'fab fa-apple',
        'Desktop Computers' => 'fas fa-desktop',
        'Printers & Scanners' => 'fas fa-print',
        'CCTV Cameras' => 'fas fa-video',
        'Network Equipment' => 'fas fa-network-wired',
        'Computer Spare Parts' => 'fas fa-microchip',
        'IT Accessories' => 'fas fa-mouse'
    ];
    
    return $icons[$categoryName] ?? 'fas fa-folder';
}

// Function to get category color based on name
function getCategoryColor($categoryName) {
    $colors = [
        'Laptops - Dell' => 'linear-gradient(135deg, #0078D6, #0066B3)',
        'Laptops - HP' => 'linear-gradient(135deg, #0096D6, #0078B3)',
        'Laptops - Lenovo' => 'linear-gradient(135deg, #E2231A, #C01B13)',
        'Laptops - Apple MacBook' => 'linear-gradient(135deg, #555555, #333333)',
        'Desktop Computers' => 'linear-gradient(135deg, #0ea5e9, #0369a1)',
        'Printers & Scanners' => 'linear-gradient(135deg, #f97316, #ea580c)',
        'CCTV Cameras' => 'linear-gradient(135deg, #10b981, #059669)',
        'Network Equipment' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)',
        'Computer Spare Parts' => 'linear-gradient(135deg, #ef4444, #dc2626)',
        'IT Accessories' => 'linear-gradient(135deg, #f59e0b, #d97706)'
    ];
    
    return $colors[$categoryName] ?? 'linear-gradient(135deg, var(--primary), var(--primary-dark))';
}

// Get logo colors for balanced theme
$logoColors = [
    'primary' => '#0ea5e9',      // Blue from logo
    'primary-dark' => '#0369a1',  // Darker blue
    'accent' => '#f97316',        // Orange accent
    'success' => '#10b981',       // Green
    'warning' => '#f59e0b',       // Yellow/Orange
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Taigon Investments - Your trusted partner for IT solutions, computer hardware, CCTV installation, and network services in Tanzania">
    <title>Taigon Investments - IT Solutions & Hardware Store Tanzania</title>
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
            --bg-dark: #0f172a;
            --bg-mid: #1e293b;
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
        .logo-area img { height: 45px; width: auto; border-radius: var(--radius-sm); }
        .logo-text { 
            font-weight: 800; 
            font-size: 1.3rem; 
            background: linear-gradient(135deg, var(--primary), var(--primary-dark), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .main-nav { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
        .nav-link { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1rem; color: var(--text-mid); text-decoration: none; font-weight: 500; font-size: 0.9rem; border-radius: var(--radius-md); transition: all 0.2s; white-space: nowrap; }
        .nav-link:hover, .nav-link.active { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; transform: translateY(-1px); box-shadow: 0 4px 12px var(--primary-glow); }

        .search-wrapper { position: relative; min-width: 280px; }
        .search-wrapper input { width: 100%; padding: 0.6rem 2.5rem 0.6rem 1rem; border: 1.5px solid var(--border); border-radius: 2rem; font-size: 0.85rem; background: white; }
        .search-wrapper input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
        .search-wrapper button { position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%); background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border: none; color: white; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; }

        .auth-area { display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0; }
        .btn-auth { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem; border-radius: 2rem; text-decoration: none; font-weight: 600; font-size: 0.85rem; transition: all 0.2s; white-space: nowrap; }
        .btn-login { background: transparent; color: var(--primary-dark); border: 1.5px solid rgba(14,165,233,0.3); }
        .btn-login:hover { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; }
        .btn-track { background: linear-gradient(135deg, var(--accent), #ea580c); color: white; box-shadow: 0 2px 8px rgba(249,115,22,0.3); }
        .btn-logout { background: transparent; color: #dc2626; border: 1.5px solid rgba(220,38,38,0.3); }
        .btn-logout:hover { background: #dc2626; color: white; }
        .welcome-text { font-size: 0.85rem; color: var(--text-mid); }

        .suggestions { position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: var(--radius-md); box-shadow: var(--shadow-lg); max-height: 300px; overflow-y: auto; z-index: 1000; margin-top: 0.5rem; display: none; }
        .suggestion-item { padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid var(--border); }
        .suggestion-item:hover { background: rgba(14,165,233,0.05); }
        .suggestion-item a { text-decoration: none; color: inherit; display: block; }

        /* Hero Section - Balanced Colors */
        .hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            position: relative;
            overflow: hidden;
            padding: 4rem 1.5rem;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(14,165,233,0.15) 0%, transparent 50%);
            pointer-events: none;
        }

        .hero::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(249,115,22,0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.2rem;
            color: #94a3b8;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 2rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-decoration: none;
            border-radius: 2rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px var(--primary-glow);
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 2rem;
            background: transparent;
            color: white;
            text-decoration: none;
            border-radius: 2rem;
            font-weight: 600;
            border: 2px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
        }

        .btn-outline:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        /* Stats Section */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: -2rem auto 2rem;
            padding: 0 1.5rem;
            position: relative;
            z-index: 2;
        }

        .stat-card {
            background: var(--surface);
            padding: 1.5rem;
            text-align: center;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            transition: transform 0.3s;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .stat-card:hover { 
            transform: translateY(-5px); 
            border-bottom-color: var(--primary);
        }

        .stat-card i { font-size: 2rem; color: var(--primary); margin-bottom: 0.5rem; }
        .stat-card h3 { font-size: 2rem; font-weight: 800; color: var(--text-dark); }
        .stat-card p { color: var(--text-mid); font-size: 0.9rem; }

        /* Section Styles */
        .section {
            max-width: 1200px;
            margin: 4rem auto;
            padding: 0 1.5rem;
        }

        .section-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .section-header h2 {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .section-header p {
            color: var(--text-mid);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .service-card {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius-lg);
            text-align: center;
            transition: all 0.3s;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transform: scaleX(0);
            transition: transform 0.3s;
        }

        .service-card:hover::before {
            transform: scaleX(1);
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: transparent;
        }

        .service-card i {
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .service-card h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .service-card p {
            color: var(--text-mid);
            font-size: 0.9rem;
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid var(--border);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }

        .product-image {
            height: 200px;
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-image img {
            width: 80%;
            height: 80%;
            object-fit: contain;
            transition: transform 0.3s;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-info {
            padding: 1rem;
        }

        .product-info h3 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            line-height: 1.4;
            min-height: 2.8rem;
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0.5rem 0;
        }

        .product-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-cart {
            flex: 1;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.6rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .btn-view {
            width: 38px;
            background: var(--surface-muted);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--text-mid);
            transition: all 0.3s;
        }

        .btn-view:hover {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        /* Categories Grid - FIXED ICONS */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1.5rem;
        }

        .category-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid var(--border);
            text-decoration: none;
            color: inherit;
            display: block;
            position: relative;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }

        .category-image {
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .category-image i {
            font-size: 4rem;
            transition: all 0.3s;
        }

        .category-card:hover .category-image i {
            transform: scale(1.1) rotate(5deg);
        }

        /* Brand-specific icon colors */
        .category-card[data-category*="Dell"] .category-image i { color: #0078D6; }
        .category-card[data-category*="HP"] .category-image i { color: #0096D6; }
        .category-card[data-category*="Lenovo"] .category-image i { color: #E2231A; }
        .category-card[data-category*="Apple"] .category-image i { color: #555555; }
        .category-card .category-image i { color: var(--primary); }

        .category-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(14,165,233,0.9), rgba(3,105,161,0.9));
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .category-card:hover .category-overlay {
            opacity: 1;
        }

        .category-overlay span {
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .category-info {
            padding: 1rem;
            text-align: center;
        }

        .category-info h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .category-info p {
            font-size: 0.75rem;
            color: var(--text-mid);
        }

        /* CTA Banner */
        .cta-banner {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark), var(--accent));
            border-radius: var(--radius-xl);
            padding: 3rem 2rem;
            text-align: center;
            margin: 4rem auto;
            max-width: 1200px;
            position: relative;
            overflow: hidden;
        }

        .cta-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .cta-banner h2 {
            color: white;
            font-size: 1.8rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .cta-banner p {
            color: rgba(255,255,255,0.9);
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .btn-light {
            background: white;
            color: var(--primary-dark);
            padding: 0.8rem 2rem;
            border-radius: 2rem;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            position: relative;
            z-index: 1;
        }

        .btn-light:hover {
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
            .hero h1 { font-size: 2rem; }
        }

        @media (max-width: 768px) {
            .header-container { padding: 0.75rem 1rem; }
            .logo-text { font-size: 1rem; }
            .logo-area img { height: 35px; }
            .btn-auth span { display: none; }
            .btn-auth { padding: 0.6rem; border-radius: 50%; }
            .hero { padding: 2rem 1rem; }
            .hero h1 { font-size: 1.5rem; }
            .hero p { font-size: 0.9rem; }
            .stats { grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: -3rem; }
            .stat-card { padding: 1rem; }
            .stat-card h3 { font-size: 1.5rem; }
            .section-header h2 { font-size: 1.5rem; }
            .categories-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); }
        }

        @media (max-width: 480px) {
            .stats { grid-template-columns: 1fr; }
            .hero-buttons { flex-direction: column; align-items: stretch; }
            .btn-primary, .btn-outline { justify-content: center; }
            .products-grid { grid-template-columns: 1fr; }
            .categories-grid { grid-template-columns: 1fr; }
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modern-header, .hero, .stats, .section, .cta-banner {
            animation: fadeInUp 0.7s ease both;
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="modern-header">
    <div class="header-container">
        <div class="logo-area">
            <img src="image/log.png" alt="Taigon Investments" onerror="this.src='images/placeholder-logo.png'">
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
            <a href="index.php" class="nav-link active"><i class="fas fa-home"></i><span>Home</span></a>
            <a href="product.php" class="nav-link"><i class="fas fa-laptop"></i><span>Products</span></a>
            <a href="about.php" class="nav-link"><i class="fas fa-info-circle"></i><span>About</span></a>
            <a href="contact.php" class="nav-link"><i class="fas fa-envelope"></i><span>Contact</span></a>
            <a href="cart.php" class="nav-link"><i class="fas fa-shopping-cart"></i><span>Cart</span></a>
        </nav>
    </div>
</header>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1>Premium IT Solutions & Hardware</h1>
        <p>Your trusted partner for quality computers, CCTV systems, networking equipment, and professional IT services in Tanzania</p>
        <div class="hero-buttons">
            <a href="product.php" class="btn-primary"><i class="fas fa-shopping-cart"></i> Shop Now</a>
            <a href="about.php" class="btn-outline"><i class="fas fa-info-circle"></i> Learn More</a>
        </div>
    </div>
</section>

<!-- Stats Section -->
<div class="stats">
    <div class="stat-card">
        <i class="fas fa-building"></i>
        <h3>4+</h3>
        <p>Branch Locations</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-users"></i>
        <h3>1000+</h3>
        <p>Happy Customers</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-box"></i>
        <h3>5000+</h3>
        <p>Products Sold</p>
    </div>
    <div class="stat-card">
        <i class="fas fa-headset"></i>
        <h3>24/7</h3>
        <p>Technical Support</p>
    </div>
</div>

<!-- Services Section -->
<section class="section">
    <div class="section-header">
        <h2>Our Services</h2>
        <p>Comprehensive IT solutions tailored to your needs</p>
    </div>
    <div class="services-grid">
        <div class="service-card"><i class="fas fa-network-wired"></i><h3>Network Installation</h3><p>Professional network setup, cabling, and configuration</p></div>
        <div class="service-card"><i class="fas fa-video"></i><h3>CCTV Systems</h3><p>High-definition surveillance with remote access</p></div>
        <div class="service-card"><i class="fas fa-laptop"></i><h3>Computer Sales</h3><p>New & refurbished computers from top brands</p></div>
        <div class="service-card"><i class="fas fa-print"></i><h3>Printer Services</h3><p>Sales, consumables, repair & maintenance</p></div>
        <div class="service-card"><i class="fas fa-microchip"></i><h3>IT Spare Parts</h3><p>Genuine computer components</p></div>
        <div class="service-card"><i class="fas fa-tools"></i><h3>Device Repair</h3><p>Expert diagnosis and repair services</p></div>
    </div>
</section>

<!-- Featured Products -->
<section class="section">
    <div class="section-header">
        <h2>Featured Products</h2>
        <p>Shop our most popular IT equipment</p>
    </div>
    <div class="products-grid">
        <?php
        $stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE status = 'active' ORDER BY id DESC LIMIT 8");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($product = $result->fetch_assoc()):
            $imagePath = getProductImagePath($product['image'] ?? '', $product['id']);
        ?>
        <div class="product-card">
            <div class="product-image">
                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='images/placeholder.png'">
            </div>
            <div class="product-info">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <div class="product-price">TShs <?php echo number_format($product['price'], 0); ?></div>
                <div class="product-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="cart.php?action=add&id=<?php echo $product['id']; ?>" class="btn-cart"><i class="fas fa-cart-plus"></i> Add to Cart</a>
                    <?php else: ?>
                        <a href="userlogin.php?product_id=<?php echo $product['id']; ?>" class="btn-cart"><i class="fas fa-sign-in-alt"></i> Login to Buy</a>
                    <?php endif; ?>
                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn-view"><i class="fas fa-eye"></i></a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        <?php $stmt->close(); ?>
    </div>
</section>

<!-- CTA Banner -->
<div class="cta-banner">
    <h2>Need Technical Support or Installation?</h2>
    <p>Our certified technicians are ready to help with all your IT needs</p>
    <a href="contact.php" class="btn-light"><i class="fas fa-phone-alt"></i> Contact Us Today</a>
</div>

<!-- Shop by Category Section - FIXED ICONS -->
<section class="section">
    <div class="section-header">
        <h2>Shop by Category</h2>
        <p>Browse our extensive range of IT products</p>
    </div>
    <div class="categories-grid">
        <?php
        $catStmt = $conn->prepare("SELECT categories_name FROM categore WHERE status = 'active' LIMIT 8");
        $catStmt->execute();
        $catResult = $catStmt->get_result();
        
        while ($cat = $catResult->fetch_assoc()):
            $catName = $cat['categories_name'];
            $icon = getCategoryIcon($catName);
            $gradientColor = getCategoryColor($catName);
            
            // Count products in this category
            $countStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM products WHERE category = ? AND status = 'active'");
            $countStmt->bind_param('s', $catName);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $productCount = $countResult->fetch_assoc()['cnt'];
            $countStmt->close();
        ?>
        <a href="product.php?category=<?php echo urlencode($catName); ?>" class="category-card" data-category="<?php echo htmlspecialchars($catName); ?>">
            <div class="category-image" style="background: linear-gradient(135deg, #f8fafc, #f1f5f9);">
                <i class="<?php echo $icon; ?>"></i>
                <div class="category-overlay">
                    <span><i class="fas fa-arrow-right"></i> Shop Now</span>
                </div>
            </div>
            <div class="category-info">
                <h3><?php echo htmlspecialchars($catName); ?></h3>
                <p><?php echo $productCount; ?> products available</p>
            </div>
        </a>
        <?php endwhile; ?>
        <?php $catStmt->close(); ?>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div style="max-width: 1200px; margin: 0 auto;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            <div>
                <h3 style="color: #0ea5e9; margin-bottom: 1rem;">Quick Links</h3>
                <ul style="list-style: none;">
                    <li style="margin-bottom: 0.5rem;"><a href="index.php" style="color: #94a3b8; text-decoration: none;">Home</a></li>
                    <li style="margin-bottom: 0.5rem;"><a href="product.php" style="color: #94a3b8; text-decoration: none;">Products</a></li>
                    <li style="margin-bottom: 0.5rem;"><a href="about.php" style="color: #94a3b8; text-decoration: none;">About Us</a></li>
                    <li style="margin-bottom: 0.5rem;"><a href="contact.php" style="color: #94a3b8; text-decoration: none;">Contact</a></li>
                </ul>
            </div>
            <div>
                <h3 style="color: #0ea5e9; margin-bottom: 1rem;">Top Categories</h3>
                <ul style="list-style: none;">
                    <li style="margin-bottom: 0.5rem;"><a href="product.php?category=Laptops%20-%20Dell" style="color: #94a3b8; text-decoration: none;"><i class="fab fa-dell"></i> Dell Laptops</a></li>
                    <li style="margin-bottom: 0.5rem;"><a href="product.php?category=Laptops%20-%20HP" style="color: #94a3b8; text-decoration: none;"><i class="fab fa-hp"></i> HP Laptops</a></li>
                    <li style="margin-bottom: 0.5rem;"><a href="product.php?category=Laptops%20-%20Lenovo" style="color: #94a3b8; text-decoration: none;"><i class="fab fa-lenovo"></i> Lenovo Laptops</a></li>
                    <li style="margin-bottom: 0.5rem;"><a href="product.php?category=Printers%20%26%20Scanners" style="color: #94a3b8; text-decoration: none;"><i class="fas fa-print"></i> Printers</a></li>
                </ul>
            </div>
            <div>
                <h3 style="color: #0ea5e9; margin-bottom: 1rem;">Contact Us</h3>
                <p style="color: #94a3b8; margin-bottom: 0.5rem;"><i class="fas fa-map-marker-alt"></i> Arusha, Tanzania</p>
                <p style="color: #94a3b8; margin-bottom: 0.5rem;"><i class="fas fa-phone"></i> +255 740 610 143</p>
                <p style="color: #94a3b8; margin-bottom: 0.5rem;"><i class="fas fa-envelope"></i> info@taigoninvestment.co.tz</p>
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <a href="#" style="color: #94a3b8; transition: color 0.3s;"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" style="color: #94a3b8; transition: color 0.3s;"><i class="fab fa-twitter"></i></a>
                    <a href="#" style="color: #94a3b8; transition: color 0.3s;"><i class="fab fa-instagram"></i></a>
                    <a href="#" style="color: #94a3b8; transition: color 0.3s;"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        <div style="text-align: center; padding-top: 1.5rem; border-top: 1px solid #334155; color: #64748b; font-size: 0.8rem;">
            &copy; <?php echo date('Y'); ?> Taigon Investments. All rights reserved. | IT Solutions & Hardware Store
        </div>
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

// Add hover effect for category cards
document.querySelectorAll('.category-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        const icon = this.querySelector('.category-image i');
        if (icon) {
            icon.style.transform = 'scale(1.1) rotate(5deg)';
        }
    });
    card.addEventListener('mouseleave', function() {
        const icon = this.querySelector('.category-image i');
        if (icon) {
            icon.style.transform = 'scale(1) rotate(0deg)';
        }
    });
});
</script>
</body>
</html>