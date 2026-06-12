<?php 
session_start(); 
require 'db.php';

// Handle category from URL parameter (for direct links from footer/category pages)
$urlCategory = isset($_GET['category']) ? urldecode($_GET['category']) : null;

// Function to get proper image path
function getProductImagePath($image, $productId = null) {
    // If image is empty or default, try to find by ID
    if (empty($image) || $image === 'default-product.jpg') {
        if ($productId) {
            $possiblePaths = [
                'images/products/product_' . $productId . '.jpg',
                'images/products/product_' . $productId . '.png',
                'images/products/' . $productId . '.jpg',
                'images/products/' . $productId . '.png',
                'image/products/product_' . $productId . '.jpg',
                'image/products/' . $productId . '.jpg',
                'uploads/products/' . $productId . '.jpg'
            ];
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        return 'images/placeholder.png';
    }
    
    // Check if the image path exists directly
    if (file_exists($image)) {
        return $image;
    }
    
    // Try common directory patterns
    $pathsToCheck = [
        'images/products/' . basename($image),
        'images/' . basename($image),
        'image/products/' . basename($image),
        'image/' . basename($image),
        'uploads/products/' . basename($image),
        'uploads/' . basename($image)
    ];
    
    foreach ($pathsToCheck as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    // Try ID-based pattern as fallback
    if ($productId) {
        $idPaths = [
            'images/products/product_' . $productId . '.jpg',
            'images/products/product_' . $productId . '.png',
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Shop premium IT products at Taigon Investments - Laptops, Desktops, Printers, CCTV, Networking Equipment and more">
    <title>Products - Taigon Investments | IT Hardware Store Tanzania</title>
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
        .logo-text { font-weight: 800; font-size: 1.3rem; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }

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

        /* Main Layout */
        .main-content {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            display: flex;
            gap: 2rem;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            flex-shrink: 0;
        }

        .sidebar-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .sidebar-header {
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            font-weight: 600;
        }

        .sidebar-header h3 {
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .category-list {
            list-style: none;
            padding: 0.5rem 0;
        }

        .category-list li a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            color: var(--text-mid);
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .category-list li a:hover {
            background: rgba(14,165,233,0.08);
            color: var(--primary);
            padding-left: 1.5rem;
        }

        .category-list li a i {
            width: 20px;
            color: var(--primary);
        }

        .category-count {
            margin-left: auto;
            font-size: 0.7rem;
            background: var(--surface-muted);
            padding: 0.2rem 0.5rem;
            border-radius: 1rem;
            color: var(--text-light);
        }

        /* Products Area */
        .products-area {
            flex: 1;
            min-width: 0;
        }

        /* Filter Bar */
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem 1.25rem;
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .results-count {
            font-size: 0.85rem;
            color: var(--text-mid);
        }

        .results-count strong {
            color: var(--primary-dark);
            font-size: 1rem;
        }

        .sort-select {
            padding: 0.5rem 1rem;
            border: 1.5px solid var(--border);
            border-radius: 2rem;
            font-size: 0.85rem;
            background: white;
            cursor: pointer;
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
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-light);
        }

        .product-badge {
            position: absolute;
            top: 0.75rem;
            left: 0.75rem;
            background: var(--accent);
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            z-index: 2;
        }

        .product-image {
            height: 200px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 1rem;
        }

        .product-image img {
            max-width: 85%;
            max-height: 85%;
            object-fit: contain;
            transition: transform 0.3s;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-info {
            padding: 1rem 1rem 1.25rem;
        }

        .product-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 2.5rem;
        }

        .product-category {
            font-size: 0.7rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0.5rem 0;
        }

        .product-stock {
            font-size: 0.7rem;
            margin-bottom: 0.75rem;
        }

        .stock-in {
            color: var(--success);
        }

        .stock-low {
            color: var(--accent);
        }

        .stock-out {
            color: #dc2626;
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
            padding: 0.6rem 0.8rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--primary-glow);
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
            transition: all 0.2s;
        }

        .btn-view:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 0.75rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            text-decoration: none;
            color: var(--text-mid);
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .pagination a:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination .active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-color: transparent;
        }

        /* Loading State */
        .loading {
            text-align: center;
            padding: 3rem;
        }

        .loading i {
            font-size: 2rem;
            color: var(--primary);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
        }

        .no-results i {
            font-size: 4rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .no-results h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .no-results p {
            color: var(--text-mid);
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 3rem 1.5rem 1.5rem;
            margin-top: 3rem;
        }

        /* Mobile Filter Toggle */
        .filter-toggle {
            display: none;
            width: 100%;
            padding: 0.75rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 1rem;
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
            .main-content { flex-direction: column; }
            .sidebar { width: 100%; }
            .filter-toggle { display: block; }
            .sidebar { display: none; }
            .sidebar.show { display: block; }
        }

        @media (max-width: 768px) {
            .header-container { padding: 0.75rem 1rem; }
            .logo-text { font-size: 1rem; }
            .logo-area img { height: 35px; }
            .btn-auth span { display: none; }
            .btn-auth { padding: 0.6rem; border-radius: 50%; }
            .products-grid { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; }
        }

        @media (max-width: 480px) {
            .products-grid { grid-template-columns: 1fr; }
            .filter-bar { flex-direction: column; align-items: stretch; }
        }

        /* ============ ENHANCED VISUALS, 3D & ANIMATIONS ============ */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes floatY { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        @keyframes gradientShift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        @keyframes glowPulse { 0%,100% { box-shadow: 0 0 20px var(--primary-glow); } 50% { box-shadow: 0 0 40px var(--primary-glow); } }
        @keyframes popIn { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        @keyframes bgDrift { 0% { transform: translate(0,0); } 50% { transform: translate(-2%, 2%); } 100% { transform: translate(0,0); } }

        body { background-size: 200% 200%; animation: gradientShift 18s ease infinite; }

        .modern-header { animation: fadeInUp 0.6s ease; }
        .logo-area img { transition: transform 0.4s ease; }
        .logo-area:hover img { transform: perspective(400px) rotateY(12deg) scale(1.08); }
        .nav-link:hover { transform: translateY(-2px) perspective(400px) rotateX(6deg) scale(1.04); }

        .main-content { animation: fadeInUp 0.7s ease both; }

        /* Sidebar 3D */
        .sidebar-card {
            transition: transform 0.35s cubic-bezier(.2,.8,.2,1), box-shadow 0.35s;
        }
        .sidebar-card:hover {
            transform: translateY(-4px) perspective(700px) rotateX(2deg);
            box-shadow: var(--shadow-xl), 0 0 25px var(--primary-glow);
        }
        .category-list li a { transition: all 0.25s ease; }
        .category-list li a:hover i { transform: scale(1.2) rotateY(180deg); transition: transform 0.4s ease; }

        .filter-bar { transition: box-shadow 0.3s; }
        .filter-bar:hover { box-shadow: var(--shadow-md), 0 0 20px var(--primary-glow); }
        .sort-select { transition: border-color 0.25s, box-shadow 0.25s; }
        .sort-select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }

        /* Product cards 3D + shimmer */
        .products-grid { perspective: 1400px; }
        .product-card {
            transform-style: preserve-3d;
            transition: transform 0.45s cubic-bezier(.2,.8,.2,1), box-shadow 0.45s, border-color 0.3s;
            animation: popIn 0.5s ease backwards;
        }
        .product-card:hover {
            transform: translateY(-10px) rotateX(6deg) rotateY(-4deg) scale(1.025);
            box-shadow: var(--shadow-xl), 0 0 35px var(--primary-glow);
            border-color: var(--primary);
            z-index: 2;
        }
        .product-image { position: relative; overflow: hidden; }
        .product-image::before {
            content: '';
            position: absolute;
            top: 0; left: -150%;
            width: 80%; height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255,255,255,0.4), transparent);
            transform: skewX(-20deg);
            transition: left 0.7s ease;
        }
        .product-card:hover .product-image::before { left: 150%; }
        .product-card:hover .product-image img { transform: scale(1.1) rotateY(-6deg); }
        .product-badge { animation: floatY 2.5s ease-in-out infinite; }
        .btn-cart:hover { transform: translateY(-2px) scale(1.04); animation: glowPulse 1.6s ease-in-out infinite; }
        .btn-view:hover { transform: rotate(8deg) scale(1.1); }

        /* Pagination */
        .pagination a:hover, .pagination .active { transform: translateY(-2px) scale(1.05); }
        .pagination a { transition: all 0.25s ease; }

        .footer { animation: fadeInUp 0.8s ease both; }
        .footer a { transition: color 0.25s, transform 0.25s; display: inline-block; }
        .footer a:hover { color: var(--primary-light) !important; transform: translateX(4px) scale(1.05); }

        html { scroll-behavior: smooth; }
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
            <a href="product.php" class="nav-link active"><i class="fas fa-laptop"></i><span>Products</span></a>
            <a href="about.php" class="nav-link"><i class="fas fa-info-circle"></i><span>About</span></a>
            <a href="contact.php" class="nav-link"><i class="fas fa-envelope"></i><span>Contact</span></a>
            <a href="cart.php" class="nav-link"><i class="fas fa-shopping-cart"></i><span>Cart</span></a>
        </nav>
    </div>
</header>

<main class="main-content">
    <!-- Sidebar Filters -->
    <aside class="sidebar" id="sidebar">
        <!-- Categories -->
        <div class="sidebar-card">
            <div class="sidebar-header">
                <h3><i class="fas fa-folder-tree"></i> Categories</h3>
            </div>
            <ul class="category-list" id="categoryList">
                <li><a href="#" data-category="all" class="active-cat"><i class="fas fa-th-large"></i> All Products <span class="category-count" id="totalCount">-</span></a></li>
                <?php
                $catStmt = $conn->prepare("SELECT categories_name FROM categore WHERE status = 'active' ORDER BY categories_name");
                $catStmt->execute();
                $catResult = $catStmt->get_result();
                while ($cat = $catResult->fetch_assoc()):
                    $catName = $cat['categories_name'];
                    // Get count for this category
                    $countStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM products WHERE category = ? AND status = 'active'");
                    $countStmt->bind_param('s', $catName);
                    $countStmt->execute();
                    $countResult = $countStmt->get_result();
                    $count = $countResult->fetch_assoc()['cnt'];
                    $countStmt->close();
                ?>
                <li><a href="#" data-category="<?php echo htmlspecialchars($catName); ?>">
                    <i class="fas <?php 
                        echo strpos($catName, 'Laptop') !== false ? 'fa-laptop' : 
                             (strpos($catName, 'Printer') !== false ? 'fa-print' :
                             (strpos($catName, 'CCTV') !== false ? 'fa-video' :
                             (strpos($catName, 'Network') !== false ? 'fa-network-wired' :
                             (strpos($catName, 'Spare') !== false ? 'fa-microchip' :
                             (strpos($catName, 'Accessories') !== false ? 'fa-mouse' : 'fa-folder'))))); 
                    ?>"></i>
                    <?php echo htmlspecialchars($catName); ?>
                    <span class="category-count"><?php echo $count; ?></span>
                </a></li>
                <?php endwhile; ?>
            </ul>
        </div>

        <!-- Filter by Price -->
        <div class="sidebar-card">
            <div class="sidebar-header">
                <h3><i class="fas fa-tag"></i> Price Range</h3>
            </div>
            <div style="padding: 1rem 1.25rem;">
                <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                    <input type="number" id="minPrice" placeholder="Min" class="sort-select" style="flex: 1;">
                    <input type="number" id="maxPrice" placeholder="Max" class="sort-select" style="flex: 1;">
                </div>
                <button id="applyPriceBtn" class="btn-cart" style="width: 100%; background: var(--primary);">Apply Filter</button>
                <button id="clearFiltersBtn" class="btn-cart" style="width: 100%; margin-top: 0.5rem; background: var(--text-light);">Clear All Filters</button>
            </div>
        </div>
    </aside>

    <!-- Products Area -->
    <div class="products-area">
        <!-- Mobile Filter Toggle -->
        <button class="filter-toggle" id="filterToggleBtn">
            <i class="fas fa-filter"></i> Filter & Categories
        </button>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="results-count">
                <i class="fas fa-box"></i> Showing <strong id="showingCount">0</strong> of <strong id="totalProductsCount">0</strong> products
            </div>
            <div>
                <select id="sortSelect" class="sort-select">
                    <option value="newest">Latest First</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    <option value="name_asc">Name: A to Z</option>
                </select>
            </div>
        </div>

        <!-- Products Grid -->
        <div id="productsGrid" class="products-grid">
            <div class="loading">
                <i class="fas fa-spinner"></i>
                <p>Loading products...</p>
            </div>
        </div>

        <!-- Pagination -->
        <div id="pagination" class="pagination"></div>
    </div>
</main>

<footer class="footer">
    <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
        <p style="color: #64748b;">&copy; 2024 Taigon Investments. All rights reserved. | IT Solutions & Hardware Store</p>
    </div>
</footer>

<script>
// Global variables
let currentPage = 1;
let currentCategory = 'all';
let currentSort = 'newest';
let currentMinPrice = '';
let currentMaxPrice = '';
const productsPerPage = 12;
const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

// Handle URL category parameter
<?php if ($urlCategory): ?>
currentCategory = <?php echo json_encode($urlCategory); ?>;
<?php endif; ?>

// Search suggestions
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

// Load products with filters
function loadProducts() {
    const params = new URLSearchParams({
        page: currentPage,
        category: currentCategory,
        sort: currentSort,
        min_price: currentMinPrice,
        max_price: currentMaxPrice,
        per_page: productsPerPage
    });
    
    fetch(`get_products_api.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderProducts(data.products);
                updatePagination(data.total, data.current_page, data.total_pages);
                document.getElementById('showingCount').textContent = data.products.length;
                document.getElementById('totalProductsCount').textContent = data.total;
                if (document.getElementById('totalCount')) {
                    document.getElementById('totalCount').textContent = data.total;
                }
            } else {
                document.getElementById('productsGrid').innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>No products found</h3>
                        <p>Try adjusting your filters or browse other categories</p>
                    </div>
                `;
                document.getElementById('pagination').innerHTML = '';
            }
        })
        .catch(error => {
            console.error('Error loading products:', error);
            document.getElementById('productsGrid').innerHTML = `
                <div class="no-results">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Error loading products</h3>
                    <p>Please try again later</p>
                </div>
            `;
        });
}

function renderProducts(products) {
    const grid = document.getElementById('productsGrid');
    
    if (!products || products.length === 0) {
        grid.innerHTML = `
            <div class="no-results">
                <i class="fas fa-box-open"></i>
                <h3>No products found</h3>
                <p>Try adjusting your filters or browse other categories</p>
            </div>
        `;
        return;
    }
    
    let productsHtml = '';
    
    for (let i = 0; i < products.length; i++) {
        const product = products[i];
        const stockClass = product.quantity > 10 ? 'stock-in' : (product.quantity > 0 ? 'stock-low' : 'stock-out');
        const stockText = product.quantity > 10 ? 'In Stock' : (product.quantity > 0 ? `Only ${product.quantity} left` : 'Out of Stock');
        const badge = product.quantity > 0 && product.quantity <= 5 ? '<span class="product-badge">Low Stock</span>' : '';
        
        let cartButton = '';
        if (product.quantity > 0) {
            if (isLoggedIn) {
                cartButton = `<a href="cart.php?action=add&id=${product.id}" class="btn-cart"><i class="fas fa-cart-plus"></i> Add to Cart</a>`;
            } else {
                cartButton = `<a href="userlogin.php?product_id=${product.id}" class="btn-cart"><i class="fas fa-sign-in-alt"></i> Login to Buy</a>`;
            }
        } else {
            cartButton = `<button class="btn-cart" disabled style="opacity:0.5; cursor:not-allowed;"><i class="fas fa-times-circle"></i> Out of Stock</button>`;
        }
        
        productsHtml += `
            <div class="product-card">
                ${badge}
                <div class="product-image">
                    <img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.name)}" loading="lazy" onerror="this.src='images/placeholder.png'">
                </div>
                <div class="product-info">
                    <div class="product-category">
                        <i class="fas fa-folder"></i> ${escapeHtml(product.category || 'Uncategorized')}
                    </div>
                    <h3 class="product-title">${escapeHtml(product.name)}</h3>
                    <div class="product-price">TShs ${formatNumber(product.price)}</div>
                    <div class="product-stock">
                        <span class="${stockClass}"><i class="fas ${product.quantity > 0 ? 'fa-check-circle' : 'fa-times-circle'}"></i> ${stockText}</span>
                    </div>
                    <div class="product-actions">
                        ${cartButton}
                        <a href="product_details.php?id=${product.id}" class="btn-view"><i class="fas fa-eye"></i></a>
                    </div>
                </div>
            </div>
        `;
    }
    
    grid.innerHTML = productsHtml;
}

function updatePagination(total, currentPageNum, totalPages) {
    const pagination = document.getElementById('pagination');
    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Previous button
    if (currentPageNum > 1) {
        html += `<a href="#" data-page="${currentPageNum - 1}"><i class="fas fa-chevron-left"></i></a>`;
    }
    
    // Page numbers
    let startPage = Math.max(1, currentPageNum - 2);
    let endPage = Math.min(totalPages, currentPageNum + 2);
    
    if (startPage > 1) {
        html += `<a href="#" data-page="1">1</a>`;
        if (startPage > 2) html += `<span>...</span>`;
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<a href="#" data-page="${i}" class="${i === currentPageNum ? 'active' : ''}">${i}</a>`;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += `<span>...</span>`;
        html += `<a href="#" data-page="${totalPages}">${totalPages}</a>`;
    }
    
    // Next button
    if (currentPageNum < totalPages) {
        html += `<a href="#" data-page="${currentPageNum + 1}"><i class="fas fa-chevron-right"></i></a>`;
    }
    
    pagination.innerHTML = html;
    
    // Add click handlers
    pagination.querySelectorAll('a[data-page]').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            currentPage = parseInt(link.dataset.page);
            loadProducts();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
}

// Category selection
document.querySelectorAll('.category-list a').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();
        document.querySelectorAll('.category-list a').forEach(a => a.classList.remove('active-cat'));
        link.classList.add('active-cat');
        currentCategory = link.dataset.category;
        currentPage = 1;
        loadProducts();
        
        // Update URL without reload
        const url = new URL(window.location.href);
        if (currentCategory !== 'all') {
            url.searchParams.set('category', currentCategory);
        } else {
            url.searchParams.delete('category');
        }
        window.history.pushState({}, '', url);
        
        // Close sidebar on mobile
        if (window.innerWidth <= 900) {
            document.getElementById('sidebar').classList.remove('show');
        }
    });
});

// Sort change
document.getElementById('sortSelect').addEventListener('change', (e) => {
    currentSort = e.target.value;
    currentPage = 1;
    loadProducts();
});

// Price filter
document.getElementById('applyPriceBtn').addEventListener('click', () => {
    currentMinPrice = document.getElementById('minPrice').value;
    currentMaxPrice = document.getElementById('maxPrice').value;
    currentPage = 1;
    loadProducts();
});

document.getElementById('clearFiltersBtn').addEventListener('click', () => {
    document.getElementById('minPrice').value = '';
    document.getElementById('maxPrice').value = '';
    currentMinPrice = '';
    currentMaxPrice = '';
    currentCategory = 'all';
    currentSort = 'newest';
    currentPage = 1;
    document.getElementById('sortSelect').value = 'newest';
    document.querySelectorAll('.category-list a').forEach(a => a.classList.remove('active-cat'));
    document.querySelector('.category-list a[data-category="all"]').classList.add('active-cat');
    
    // Clear URL parameter
    const url = new URL(window.location.href);
    url.searchParams.delete('category');
    window.history.pushState({}, '', url);
    
    loadProducts();
});

// Mobile filter toggle
document.getElementById('filterToggleBtn').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('show');
});

// Helper functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNumber(num) {
    return parseFloat(num).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

// Set active category from URL on page load
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($urlCategory): ?>
    const categoryLinks = document.querySelectorAll('.category-list a');
    for (let i = 0; i < categoryLinks.length; i++) {
        if (categoryLinks[i].dataset.category === <?php echo json_encode($urlCategory); ?>) {
            categoryLinks[i].click();
            break;
        }
    }
    <?php else: ?>
    loadProducts();
    <?php endif; ?>
});
</script>
</body>
</html>