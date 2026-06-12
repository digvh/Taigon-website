<?php
session_start();
require 'db.php';

// Handle AJAX suggestions
if (isset($_GET['query']) && !isset($_GET['full'])) {
    $query = $_GET['query'];
    // FIXED: Use prepared statement for suggestions
    $stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE name LIKE ? AND status = 'active' LIMIT 10");
    $searchTerm = "%$query%";
    $stmt->bind_param('s', $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row;
    }
    $stmt->close();
    echo json_encode($suggestions);
    exit;
}

// Handle full search results - FIXED with prepared statement
$searchQuery = isset($_GET['query']) ? trim($_GET['query']) : '';
$searchResults = [];

if (!empty($searchQuery)) {
    $stmt = $conn->prepare("SELECT id, name, price, image, description FROM products WHERE (name LIKE ? OR description LIKE ?) AND status = 'active' ORDER BY name ASC");
    $searchTerm = "%$searchQuery%";
    $stmt->bind_param('ss', $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $searchResults[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results for "<?php echo htmlspecialchars($searchQuery); ?>" - Taigon Investments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: #0f172a;
            line-height: 1.5;
            min-height: 100vh;
        }
        .modern-header {
            background: rgba(255,255,255,0.98);
            backdrop-filter: blur(20px);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(14,165,233,0.15);
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
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
        .logo-area img { height: 45px; width: auto; border-radius: 8px; }
        .logo-text { font-weight: 800; font-size: 1.3rem; background: linear-gradient(135deg, #0ea5e9, #0369a1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .main-nav { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
        .nav-link { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1rem; color: #475569; text-decoration: none; font-weight: 500; font-size: 0.9rem; border-radius: 12px; transition: all 0.2s; white-space: nowrap; }
        .nav-link:hover, .nav-link.active { background: linear-gradient(135deg, #0ea5e9, #0369a1); color: white; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(14,165,233,0.25); }
        .search-wrapper { position: relative; min-width: 280px; }
        .search-wrapper input { width: 100%; padding: 0.6rem 2.5rem 0.6rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 2rem; font-size: 0.85rem; background: white; }
        .search-wrapper input:focus { outline: none; border-color: #0ea5e9; box-shadow: 0 0 0 3px rgba(14,165,233,0.25); }
        .search-wrapper button { position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%); background: linear-gradient(135deg, #0ea5e9, #0369a1); border: none; color: white; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; }
        .auth-area { display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0; }
        .btn-auth { display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem; border-radius: 2rem; text-decoration: none; font-weight: 600; font-size: 0.85rem; transition: all 0.2s; white-space: nowrap; }
        .btn-login { background: transparent; color: #0369a1; border: 1.5px solid rgba(14,165,233,0.3); }
        .btn-login:hover { background: linear-gradient(135deg, #0ea5e9, #0369a1); color: white; }
        .btn-track { background: linear-gradient(135deg, #f97316, #ea580c); color: white; }
        .btn-logout { background: transparent; color: #dc2626; border: 1.5px solid rgba(220,38,38,0.3); }
        .btn-logout:hover { background: #dc2626; color: white; }
        .welcome-text { font-size: 0.85rem; color: #475569; }
        .suggestions { position: absolute; top: 100%; left: 0; right: 0; background: white; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); max-height: 300px; overflow-y: auto; z-index: 1000; margin-top: 0.5rem; display: none; }
        .suggestion-item { padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid #e2e8f0; }
        .suggestion-item:hover { background: rgba(14,165,233,0.05); }
        .suggestion-item a { text-decoration: none; color: inherit; display: block; }
        .main-content { max-width: 1200px; margin: 2rem auto; padding: 0 1.5rem; }
        .search-header { margin-bottom: 2rem; }
        .search-header h1 { font-size: 1.5rem; color: #0f172a; }
        .search-header p { color: #475569; margin-top: 0.5rem; }
        .results-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
        .product-card { background: white; border-radius: 16px; overflow: hidden; transition: all 0.3s; border: 1px solid #e2e8f0; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .product-image { height: 200px; background: linear-gradient(135deg, #f0f9ff, #e0f2fe); display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .product-image img { width: 80%; height: 80%; object-fit: contain; }
        .product-info { padding: 1rem; }
        .product-info h3 { font-size: 0.95rem; font-weight: 600; margin-bottom: 0.5rem; line-height: 1.4; }
        .product-price { font-size: 1.2rem; font-weight: 700; color: #0369a1; margin: 0.5rem 0; }
        .product-actions { display: flex; gap: 0.5rem; }
        .btn-cart { flex: 1; background: linear-gradient(135deg, #0ea5e9, #0369a1); color: white; border: none; padding: 0.6rem; border-radius: 12px; font-weight: 600; font-size: 0.8rem; cursor: pointer; text-decoration: none; text-align: center; display: inline-block; }
        .btn-view { width: 38px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; display: flex; align-items: center; justify-content: center; text-decoration: none; color: #475569; transition: all 0.3s; }
        .btn-view:hover { background: #0ea5e9; color: white; }
        .no-results { text-align: center; padding: 3rem; background: white; border-radius: 16px; }
        .no-results i { font-size: 3rem; color: #94a3b8; margin-bottom: 1rem; }
        .no-results h3 { font-size: 1.2rem; margin-bottom: 0.5rem; }
        .no-results p { color: #475569; margin-bottom: 1.5rem; }
        .footer { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; padding: 3rem 1.5rem 1.5rem; margin-top: 3rem; }
        @media (max-width: 1100px) {
            .header-container { flex-wrap: wrap; }
            .main-nav { order: 3; width: 100%; justify-content: center; padding-top: 0.5rem; border-top: 1px solid #e2e8f0; margin-top: 0.5rem; }
            .search-wrapper { flex: 1; min-width: 200px; }
            .nav-link span { display: none; }
            .nav-link { padding: 0.6rem; }
        }
        @media (max-width: 768px) {
            .header-container { padding: 0.75rem 1rem; }
            .logo-text { font-size: 1rem; }
            .logo-area img { height: 35px; }
            .btn-auth span { display: none; }
            .btn-auth { padding: 0.6rem; border-radius: 50%; }
            .results-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<header class="modern-header">
    <div class="header-container">
        <div class="logo-area">
            <img src="image/log.png" alt="Taigon Investments">
            <span class="logo-text">Taigon Investments</span>
        </div>
        <div class="search-wrapper">
            <form action="search.php" method="get" onsubmit="return validateSearch()">
                <input type="text" name="query" id="search-input" placeholder="Search products..." autocomplete="off" value="<?php echo htmlspecialchars($searchQuery); ?>">
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
<main class="main-content">
    <div class="search-header">
        <h1>Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</h1>
        <p>Found <?php echo count($searchResults); ?> product(s)</p>
    </div>
    <?php if (empty($searchResults)): ?>
        <div class="no-results">
            <i class="fas fa-search"></i>
            <h3>No products found</h3>
            <p>We couldn't find any products matching "<?php echo htmlspecialchars($searchQuery); ?>"</p>
            <a href="product.php" class="btn-cart" style="display: inline-block; width: auto; padding: 0.8rem 2rem;">Browse All Products</a>
        </div>
    <?php else: ?>
        <div class="results-grid">
            <?php foreach ($searchResults as $product): 
                $imagePath = (!empty($product['image']) && file_exists($product['image'])) ? $product['image'] : 'images/placeholder.png';
            ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
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
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<footer class="footer">
    <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
        <p style="color: #64748b;">&copy; 2024 Taigon Investments. All rights reserved.</p>
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