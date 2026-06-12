<?php
session_start();
require 'db.php';

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

$product = null;
$error = null;

if (isset($_GET['id'])) {
    $productId = (int)$_GET['id'];
    
    // Fetch product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $product['image_path'] = getProductImagePath($product['image'] ?? '', $productId);
    } else {
        $error = "Product not found.";
    }
    $stmt->close();
} else {
    $error = "No product specified.";
}

// Fetch related products (same category, excluding current product)
$relatedProducts = [];
if ($product) {
    $stmt = $conn->prepare("SELECT id, name, price, image, quantity FROM products WHERE category = ? AND id != ? AND status = 'active' LIMIT 4");
    $stmt->bind_param('si', $product['category'], $product['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['image_path'] = getProductImagePath($row['image'] ?? '', $row['id']);
        $relatedProducts[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="<?php echo htmlspecialchars($product['name'] ?? 'Product Details'); ?> - Shop at Taigon Investments for quality IT products">
    <title><?php echo htmlspecialchars($product['name'] ?? 'Product Not Found'); ?> - Taigon Investments</title>
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
            --success-glow: rgba(16, 185, 129, 0.25);
            --warning: #f59e0b;
            --danger: #ef4444;
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

        /* Breadcrumb */
        .breadcrumb {
            max-width: 1200px;
            margin: 1.5rem auto 0;
            padding: 0 1.5rem;
        }

        .breadcrumb a {
            color: var(--text-mid);
            text-decoration: none;
            font-size: 0.85rem;
        }

        .breadcrumb a:hover {
            color: var(--primary);
        }

        .breadcrumb span {
            color: var(--text-light);
            font-size: 0.85rem;
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* Product Detail Container */
        .product-detail-container {
            background: var(--surface);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            margin-bottom: 3rem;
        }

        .product-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }

        /* Product Image Section */
        .product-image-section {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            padding: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-right: 1px solid var(--border);
        }

        .product-image-section img {
            width: 100%;
            max-width: 400px;
            height: auto;
            object-fit: contain;
            transition: transform 0.3s;
        }

        .product-image-section img:hover {
            transform: scale(1.02);
        }

        /* Product Info Section */
        .product-info-section {
            padding: 2rem;
        }

        .product-category {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(14,165,233,0.1);
            color: var(--primary-dark);
            padding: 0.3rem 1rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .product-info-section h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text-dark);
        }

        .product-price {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-dark);
            margin: 1rem 0;
        }

        .product-description {
            color: var(--text-mid);
            line-height: 1.7;
            margin-bottom: 1.5rem;
            padding: 1rem 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .product-meta {
            margin-bottom: 1.5rem;
        }

        .meta-row {
            display: flex;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
        }

        .meta-label {
            width: 120px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .meta-value {
            color: var(--text-mid);
        }

        .stock-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.3rem 1rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .stock-in {
            background: #d1fae5;
            color: #065f46;
        }

        .stock-low {
            background: #fed7aa;
            color: #9a3412;
        }

        .stock-out {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Quantity Selector */
        .quantity-section {
            margin: 1.5rem 0;
        }

        .quantity-section label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .quantity-btn {
            width: 40px;
            height: 40px;
            background: var(--surface-muted);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .quantity-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .quantity-input {
            width: 80px;
            height: 40px;
            text-align: center;
            font-size: 1rem;
            font-weight: 600;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
        }

        .quantity-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .btn-add-cart {
            flex: 1;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 1rem;
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

        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px var(--primary-glow);
        }

        .btn-add-cart:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-buy-now {
            flex: 1;
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            border: none;
            padding: 1rem;
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

        .btn-buy-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px var(--success-glow);
        }

        .btn-buy-now:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Service Info */
        .service-info {
            background: var(--surface-muted);
            border-radius: var(--radius-lg);
            padding: 1rem;
            margin-top: 1rem;
        }

        .service-info p {
            font-size: 0.85rem;
            color: var(--text-mid);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .service-info p:last-child {
            margin-bottom: 0;
        }

        .service-info i {
            color: var(--primary);
            width: 20px;
        }

        /* Related Products Section */
        .related-section {
            margin-top: 3rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
        }

        .related-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid var(--border);
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-light);
        }

        .related-image {
            height: 150px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .related-image img {
            max-width: 80%;
            max-height: 120px;
            object-fit: contain;
        }

        .related-info {
            padding: 1rem;
        }

        .related-info h4 {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            line-height: 1.4;
            min-height: 2.4rem;
        }

        .related-price {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .related-stock {
            font-size: 0.7rem;
            margin-top: 0.25rem;
        }

        /* Error Page */
        .error-container {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
        }

        .error-container i {
            font-size: 4rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .error-container h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .error-container p {
            color: var(--text-mid);
            margin-bottom: 1.5rem;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 2rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px var(--primary-glow);
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 3rem 1.5rem 1.5rem;
            margin-top: 3rem;
        }

        /* Toast Notification */
        .toast-notification {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--surface);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-xl);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 1100;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--success);
        }

        .toast-notification.show {
            transform: translateX(0);
        }

        .toast-notification i {
            font-size: 1.2rem;
            color: var(--success);
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
            .product-detail-grid { grid-template-columns: 1fr; }
            .product-image-section { border-right: none; border-bottom: 1px solid var(--border); }
            .product-info-section h1 { font-size: 1.5rem; }
            .product-price { font-size: 1.5rem; }
        }

        @media (max-width: 768px) {
            .header-container { padding: 0.75rem 1rem; }
            .logo-text { font-size: 1rem; }
            .logo-area img { height: 35px; }
            .btn-auth span { display: none; }
            .btn-auth { padding: 0.6rem; border-radius: 50%; }
            .action-buttons { flex-direction: column; }
            .related-grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
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
            <a href="contact.php" class="nav-link"><i class="fas fa-envelope"></i><span>Contact</span></a>
            <a href="cart.php" class="nav-link"><i class="fas fa-shopping-cart"></i><span>Cart</span></a>
        </nav>
    </div>
</header>

<?php if ($error): ?>
    <main class="main-content">
        <div class="error-container">
            <i class="fas fa-exclamation-triangle"></i>
            <h2>Product Not Found</h2>
            <p><?php echo htmlspecialchars($error); ?></p>
            <a href="product.php" class="btn-back"><i class="fas fa-arrow-left"></i> Browse Products</a>
        </div>
    </main>
<?php elseif ($product): 
    $imagePath = $product['image_path'];
    $stockClass = $product['quantity'] > 10 ? 'stock-in' : ($product['quantity'] > 0 ? 'stock-low' : 'stock-out');
    $stockText = $product['quantity'] > 10 ? 'In Stock' : ($product['quantity'] > 0 ? "Only {$product['quantity']} left" : 'Out of Stock');
    $stockIcon = $product['quantity'] > 0 ? 'fa-check-circle' : 'fa-times-circle';
?>
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="index.php">Home</a> <span>/</span>
        <a href="product.php">Products</a> <span>/</span>
        <span><?php echo htmlspecialchars($product['name']); ?></span>
    </div>

    <main class="main-content">
        <!-- Product Detail -->
        <div class="product-detail-container">
            <div class="product-detail-grid">
                <div class="product-image-section">
                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='images/placeholder.png'">
                </div>
                <div class="product-info-section">
                    <div class="product-category">
                        <i class="fas fa-folder"></i> <?php echo htmlspecialchars($product['category']); ?>
                    </div>
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    <div class="product-price">TShs <?php echo number_format($product['price'], 0); ?></div>
                    
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available for this product.')); ?>
                    </div>
                    
                    <div class="product-meta">
                        <div class="meta-row">
                            <div class="meta-label">Availability:</div>
                            <div class="meta-value">
                                <span class="stock-status <?php echo $stockClass; ?>">
                                    <i class="fas <?php echo $stockIcon; ?>"></i> <?php echo $stockText; ?>
                                </span>
                            </div>
                        </div>
                        <?php if (!empty($product['brand'])): ?>
                        <div class="meta-row">
                            <div class="meta-label">Brand:</div>
                            <div class="meta-value"><?php echo htmlspecialchars($product['brand']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($product['model'])): ?>
                        <div class="meta-row">
                            <div class="meta-label">Model:</div>
                            <div class="meta-value"><?php echo htmlspecialchars($product['model']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($product['warranty_months'])): ?>
                        <div class="meta-row">
                            <div class="meta-label">Warranty:</div>
                            <div class="meta-value"><?php echo $product['warranty_months']; ?> months</div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($product['quantity'] > 0): ?>
                    <div class="quantity-section">
                        <label>Quantity:</label>
                        <div class="quantity-selector">
                            <button class="quantity-btn" id="decreaseQty">-</button>
                            <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="<?php echo $product['quantity']; ?>">
                            <button class="quantity-btn" id="increaseQty">+</button>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button class="btn-add-cart" id="addToCartBtn" data-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                            <button class="btn-buy-now" id="buyNowBtn" data-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-bolt"></i> Buy Now
                            </button>
                        <?php else: ?>
                            <a href="userlogin.php?product_id=<?php echo $product['id']; ?>" class="btn-add-cart">
                                <i class="fas fa-sign-in-alt"></i> Login to Purchase
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="action-buttons">
                        <button class="btn-add-cart" disabled>
                            <i class="fas fa-times-circle"></i> Out of Stock
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="service-info">
                        <p><i class="fas fa-truck"></i> Free delivery on orders over TShs 500,000</p>
                        <p><i class="fas fa-shield-alt"></i> 1 year warranty on all products</p>
                        <p><i class="fas fa-headset"></i> 24/7 technical support available</p>
                        <p><i class="fas fa-undo-alt"></i> 7-day return policy for defective items</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
        <div class="related-section">
            <h2 class="section-title">You Might Also Like</h2>
            <div class="related-grid">
                <?php foreach ($relatedProducts as $related): 
                    $relImage = $related['image_path'];
                ?>
                <a href="product_details.php?id=<?php echo $related['id']; ?>" class="related-card">
                    <div class="related-image">
                        <img src="<?php echo htmlspecialchars($relImage); ?>" alt="<?php echo htmlspecialchars($related['name']); ?>" onerror="this.src='images/placeholder.png'">
                    </div>
                    <div class="related-info">
                        <h4><?php echo htmlspecialchars($related['name']); ?></h4>
                        <div class="related-price">TShs <?php echo number_format($related['price'], 0); ?></div>
                        <div class="related-stock">
                            <span class="<?php echo $related['quantity'] > 0 ? 'stock-in' : 'stock-out'; ?>">
                                <i class="fas <?php echo $related['quantity'] > 0 ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                <?php echo $related['quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                            </span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>
<?php endif; ?>

<footer class="footer">
    <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
        <p style="color: #64748b;">&copy; 2024 Taigon Investments. All rights reserved. | IT Solutions & Hardware Store</p>
    </div>
</footer>

<!-- Toast Notification -->
<div id="toast" class="toast-notification">
    <i class="fas fa-check-circle"></i>
    <span id="toastMessage">Product added to cart!</span>
</div>

<script>
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

// Quantity selector
const quantityInput = document.getElementById('quantity');
const decreaseBtn = document.getElementById('decreaseQty');
const increaseBtn = document.getElementById('increaseQty');

if (quantityInput) {
    const maxStock = parseInt(quantityInput.getAttribute('max')) || 99;
    
    function updateQuantity() {
        let value = parseInt(quantityInput.value);
        if (isNaN(value)) value = 1;
        if (value < 1) value = 1;
        if (value > maxStock) value = maxStock;
        quantityInput.value = value;
    }
    
    if (decreaseBtn) {
        decreaseBtn.addEventListener('click', () => {
            let value = parseInt(quantityInput.value);
            if (value > 1) {
                quantityInput.value = value - 1;
            }
        });
    }
    
    if (increaseBtn) {
        increaseBtn.addEventListener('click', () => {
            let value = parseInt(quantityInput.value);
            if (value < maxStock) {
                quantityInput.value = value + 1;
            }
        });
    }
    
    quantityInput.addEventListener('change', updateQuantity);
}

// Add to cart functionality
const addToCartBtn = document.getElementById('addToCartBtn');
if (addToCartBtn) {
    addToCartBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const productId = this.dataset.id;
        const quantity = quantityInput ? quantityInput.value : 1;
        
        fetch(`add_to_cart_ajax.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${productId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Product added to cart successfully!', 'success');
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) cartCount.textContent = data.cart_count;
            } else {
                showToast(data.message || 'Error adding product to cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error adding product to cart', 'error');
        });
    });
}

// Buy now functionality
const buyNowBtn = document.getElementById('buyNowBtn');
if (buyNowBtn) {
    buyNowBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const productId = this.dataset.id;
        const quantity = quantityInput ? quantityInput.value : 1;
        
        fetch(`add_to_cart_ajax.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${productId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'cart.php';
            } else {
                showToast(data.message || 'Error adding product to cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error processing request', 'error');
        });
    });
}

// Toast notification
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    const icon = toast.querySelector('i');
    
    toastMessage.textContent = message;
    
    if (type === 'success') {
        icon.className = 'fas fa-check-circle';
        toast.style.borderLeftColor = '#10b981';
    } else {
        icon.className = 'fas fa-exclamation-circle';
        toast.style.borderLeftColor = '#ef4444';
    }
    
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}
</script>
</body>
</html>