<?php 
session_start(); 
require 'db.php';

// Check if user is logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: userlogin.php');
    exit();
}

// Get the logged-in user ID
$userId = $_SESSION['user_id'];

function generateTrackingId() {
    return 'TGN-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}

function sendSMSNotification($phone, $message) {
    // Integrate with SMS API here
    error_log("SMS to $phone: $message");
    return true;
}

function sendWhatsAppNotification($phone, $message) {
    // Integrate with WhatsApp API here
    error_log("WhatsApp to $phone: $message");
    return true;
}

// Add to cart functionality
if (isset($_GET['action']) && $_GET['action'] == 'add' && isset($_GET['id'])) {
    $productId = (int) $_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Check if product already exists in session cart
        if (!isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => 1,
            ];
            
            // Check if cart item already exists in database
            $checkStmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
            $checkStmt->bind_param('ii', $userId, $productId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $updateStmt = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?");
                $updateStmt->bind_param('ii', $userId, $productId);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                $insertStmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
                $insertStmt->bind_param('ii', $userId, $productId);
                $insertStmt->execute();
                $insertStmt->close();
            }
            $checkStmt->close();
        } else {
            $_SESSION['cart'][$productId]['quantity'] += 1;
            $updateStmt = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?");
            $updateStmt->bind_param('ii', $userId, $productId);
            $updateStmt->execute();
            $updateStmt->close();
        }
        
        // Set success message for toast
        $_SESSION['cart_success'] = "Product added to cart!";
    }
    $stmt->close();
    header('Location: cart.php');
    exit;
}

// Update cart functionality
if (isset($_GET['action']) && $_GET['action'] == 'update' && isset($_GET['id']) && isset($_GET['quantity'])) {
    $productId = (int)$_GET['id'];
    $newQuantity = (int)$_GET['quantity'];
    
    if ($newQuantity <= 0) {
        // Remove if quantity is 0 or negative
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
        }
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param('ii', $userId, $productId);
        $stmt->execute();
        $stmt->close();
    } else {
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] = $newQuantity;
        }
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param('iii', $newQuantity, $userId, $productId);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: cart.php');
    exit;
}

// Remove from cart functionality
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['id'])) {
    $productId = (int) $_GET['id'];
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param('ii', $userId, $productId);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: cart.php');
    exit;
}

// Load cart from database on page load (sync session with database)
if (empty($_SESSION['cart'])) {
    $stmt = $conn->prepare("SELECT product_id, quantity FROM cart WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $productStmt = $conn->prepare("SELECT name, price FROM products WHERE id = ?");
        $productStmt->bind_param('i', $row['product_id']);
        $productStmt->execute();
        $productResult = $productStmt->get_result();
        
        if ($productResult->num_rows > 0) {
            $product = $productResult->fetch_assoc();
            $_SESSION['cart'][$row['product_id']] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $row['quantity']
            ];
        }
        $productStmt->close();
    }
    $stmt->close();
}

// Fetch products from the database for display
$cartProducts = [];
if (!empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($productIds)), ...$productIds);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cartProducts[] = $row;
    }
    $stmt->close();
}

// Locations for delivery
$locations = [
    'Arusha' => 'Arusha',
    'Dar es Salaam' => 'Dar es Salaam',
    'Dodoma' => 'Dodoma',
    'Geita' => 'Geita',
    'Iringa' => 'Iringa',
    'Kagera' => 'Kagera',
    'Kigoma' => 'Kigoma',
    'Kilimanjaro' => 'Kilimanjaro',
    'Lindi' => 'Lindi',
    'Manyara' => 'Manyara',
    'Mara' => 'Mara',
    'Mbeya' => 'Mbeya',
    'Morogoro' => 'Morogoro',
    'Mtwara' => 'Mtwara',
    'Mwanza' => 'Mwanza',
    'Njombe' => 'Njombe',
    'Pemba North' => 'Pemba North',
    'Pemba South' => 'Pemba South',
    'Pwani' => 'Pwani',
    'Rukwa' => 'Rukwa',
    'Ruvuma' => 'Ruvuma',
    'Shinyanga' => 'Shinyanga',
    'Simiyu' => 'Simiyu',
    'Singida' => 'Singida',
    'Tabora' => 'Tabora',
    'Tanga' => 'Tanga',
    'Zanzibar Central/South' => 'Zanzibar Central/South',
    'Zanzibar North' => 'Zanzibar North',
    'Zanzibar Urban/West' => 'Zanzibar Urban/West'
];

// Handling form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
    $state = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $notificationMethod = filter_input(INPUT_POST, 'notification_method', FILTER_SANITIZE_STRING);
    
    $trackingId = generateTrackingId();
    $orderStatus = 'Pending';
    
    try {
        $conn->begin_transaction();
        
        $stmt = $conn->prepare("INSERT INTO orders (tracking_id, user_id, first_name, last_name, email, city, state, phone, address, order_status, notification_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sisssssssss', $trackingId, $userId, $firstName, $lastName, $email, $city, $state, $phone, $address, $orderStatus, $notificationMethod);
        $stmt->execute();
        $orderId = $stmt->insert_id;
        $stmt->close();
        
        $orderTotal = 0;
        foreach ($_SESSION['cart'] as $productId => $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $orderTotal += $subtotal;
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('iiid', $orderId, $productId, $item['quantity'], $item['price']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Clear cart from database
        $clearStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clearStmt->bind_param('i', $userId);
        $clearStmt->execute();
        $clearStmt->close();
        
        $conn->commit();

        $_SESSION['tracking_order'] = [
            'id' => $orderId,
            'tracking_id' => $trackingId,
            'email' => $email
        ];

        // Send Email Notification
        $emailSent = false;
        if ($notificationMethod == 'email' || $notificationMethod == 'both') {
            $to = $email;
            $subject = "Order Confirmation #$orderId - Taigon Investments";
            $message = "
            <html>
            <head><title>Order Confirmation</title></head>
            <body style='font-family: Arial, sans-serif;'>
                <h2>Order Confirmation - Taigon Investments</h2>
                <p>Dear $firstName $lastName,</p>
                <p>Thank you for your order!</p>
                <p><strong>Tracking ID:</strong> $trackingId</p>
                <p><strong>Order Total:</strong> TShs " . number_format($orderTotal, 2) . "</p>
                <p>You can track your order status using your tracking ID on our website.</p>
                <p>For technical support, contact us at +255 740 610 143</p>
            </body>
            </html>
            ";
            $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: no-reply@taigoninvestment.co.tz";
            $emailSent = mail($to, $subject, $message, $headers);
        }

        // Clear session cart
        unset($_SESSION['cart']);

        $_SESSION['order_success'] = [
            'order_id' => $orderId,
            'tracking_id' => $trackingId,
            'order_total' => $orderTotal
        ];

        header('Location: order_success.php');
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error processing your order: " . $e->getMessage();
    }
}

// Get user data for pre-filling form
$userData = [];
$stmt = $conn->prepare("SELECT full_name, email, phone, address FROM users WHERE user_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $userData = $row;
}
$stmt->close();

$nameParts = explode(' ', $userData['full_name'] ?? '', 2);
$firstName = $nameParts[0] ?? '';
$lastName = $nameParts[1] ?? '';

// Calculate cart total
$cartTotal = 0;
foreach ($_SESSION['cart'] ?? [] as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Shopping Cart - Taigon Investments IT Hardware Store">
    <title>Shopping Cart - Taigon Investments</title>
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
            --danger: #ef4444;
            --warning: #f59e0b;
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
            max-width: 1400px;
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
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Cart Grid */
        .cart-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
        }

        /* Cart Items Table */
        .cart-items-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .cart-items-header {
            display: grid;
            grid-template-columns: 3fr 1fr 1.5fr 0.8fr;
            background: var(--surface-muted);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-mid);
        }

        .cart-item {
            display: grid;
            grid-template-columns: 3fr 1fr 1.5fr 0.8fr;
            align-items: center;
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid var(--border);
            transition: background 0.2s;
        }

        .cart-item:hover {
            background: var(--surface-muted);
        }

        .cart-item-product {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cart-item-image {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .cart-item-image img {
            max-width: 80%;
            max-height: 80%;
            object-fit: contain;
        }

        .cart-item-info h3 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .cart-item-category {
            font-size: 0.7rem;
            color: var(--primary);
        }

        .cart-item-price {
            font-weight: 600;
            color: var(--text-dark);
        }

        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .qty-btn {
            width: 30px;
            height: 30px;
            background: var(--surface-muted);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .qty-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .qty-input {
            width: 50px;
            height: 30px;
            text-align: center;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
        }

        .cart-item-subtotal {
            font-weight: 700;
            color: var(--primary-dark);
        }

        .cart-item-remove {
            color: var(--danger);
            background: none;
            border: none;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .cart-item-remove:hover {
            color: #dc2626;
            transform: scale(1.1);
        }

        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
        }

        .empty-cart i {
            font-size: 4rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .empty-cart h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .empty-cart p {
            color: var(--text-mid);
            margin-bottom: 1.5rem;
        }

        .btn-primary {
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

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px var(--primary-glow);
        }

        /* Order Summary */
        .order-summary-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 100px;
        }

        .summary-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 700;
        }

        .summary-content {
            padding: 1.5rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }

        .summary-row.total {
            border-bottom: none;
            padding-top: 1rem;
            margin-top: 0.5rem;
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--primary-dark);
        }

        .checkout-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--success), #059669);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px var(--success-glow);
        }

        .continue-shopping {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
            padding: 0.75rem;
            color: var(--text-mid);
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .continue-shopping:hover {
            color: var(--primary);
        }

        /* Delivery Form */
        .delivery-form-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            margin-top: 2rem;
        }

        .form-header {
            background: var(--surface-muted);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            font-weight: 700;
            color: var(--text-dark);
        }

        .form-body {
            padding: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
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

        .phone-input-group {
            position: relative;
        }

        .phone-prefix {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .phone-input-group input {
            padding-left: 3.5rem;
        }

        /* Notification Options */
        .notification-section {
            background: var(--surface-muted);
            border-radius: var(--radius-lg);
            padding: 1rem;
            margin: 1rem 0;
        }

        .notification-section h4 {
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }

        .notification-options {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .notification-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: 1.5px solid var(--border);
            border-radius: 2rem;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.8rem;
        }

        .notification-option.selected {
            border-color: var(--primary);
            background: rgba(14,165,233,0.1);
        }

        .notification-option input {
            width: auto;
            margin: 0;
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

        /* Responsive */
        @media (max-width: 1100px) {
            .header-container { flex-wrap: wrap; }
            .main-nav { order: 3; width: 100%; justify-content: center; padding-top: 0.5rem; border-top: 1px solid var(--border); margin-top: 0.5rem; }
            .search-wrapper { flex: 1; min-width: 200px; }
            .nav-link span { display: none; }
            .nav-link { padding: 0.6rem; }
            .cart-grid { grid-template-columns: 1fr; }
            .order-summary-card { position: static; margin-top: 2rem; }
        }

        @media (max-width: 900px) {
            .cart-items-header { display: none; }
            .cart-item { grid-template-columns: 1fr; gap: 0.75rem; }
            .cart-item-product { justify-content: flex-start; }
            .cart-item-price, .cart-item-quantity, .cart-item-subtotal { 
                display: flex; 
                justify-content: space-between; 
                align-items: center;
                padding-left: 80px;
            }
            .cart-item-price::before { content: "Price: "; font-weight: 600; }
            .cart-item-quantity::before { content: "Quantity: "; font-weight: 600; }
            .cart-item-subtotal::before { content: "Subtotal: "; font-weight: 600; }
            .cart-item-remove { position: absolute; right: 1rem; top: 1rem; }
            .cart-item { position: relative; }
            .form-row { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .header-container { padding: 0.75rem 1rem; }
            .logo-text { font-size: 1rem; }
            .logo-area img { height: 35px; }
            .btn-auth span { display: none; }
            .btn-auth { padding: 0.6rem; border-radius: 50%; }
        }

        @media (max-width: 480px) {
            .cart-item-image { width: 50px; height: 50px; }
            .cart-item-price, .cart-item-quantity, .cart-item-subtotal { padding-left: 60px; }
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
            <a href="cart.php" class="nav-link active"><i class="fas fa-shopping-cart"></i><span>Cart</span></a>
        </nav>
    </div>
</header>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="index.php">Home</a> <span>/</span>
    <a href="product.php">Products</a> <span>/</span>
    <span>Shopping Cart</span>
</div>

<main class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-shopping-cart"></i> Shopping Cart</h1>
    </div>

    <?php if (isset($_SESSION['cart_success'])): ?>
        <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['cart_success']; unset($_SESSION['cart_success']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cartProducts)) : ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added any IT equipment to your cart yet.</p>
            <a href="product.php" class="btn-primary"><i class="fas fa-shop"></i> Continue Shopping</a>
        </div>
    <?php else : ?>
        <div class="cart-grid">
            <!-- Cart Items -->
            <div>
                <div class="cart-items-card">
                    <div class="cart-items-header">
                        <div>Product</div>
                        <div>Price</div>
                        <div>Quantity</div>
                        <div>Subtotal</div>
                    </div>
                    
                    <?php foreach ($cartProducts as $product) :
                        $productId = $product['id'];
                        $name = htmlspecialchars($product['name']);
                        $price = $product['price'];
                        $quantity = $_SESSION['cart'][$productId]['quantity'];
                        $subTotal = $price * $quantity;
                        $imagePath = !empty($product['image']) && file_exists($product['image']) ? $product['image'] : 'images/placeholder.png';
                    ?>
                    <div class="cart-item" data-id="<?php echo $productId; ?>" data-price="<?php echo $price; ?>">
                        <div class="cart-item-product">
                            <div class="cart-item-image">
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo $name; ?>" onerror="this.src='images/placeholder.png'">
                            </div>
                            <div class="cart-item-info">
                                <h3><?php echo $name; ?></h3>
                                <div class="cart-item-category"><?php echo htmlspecialchars($product['category']); ?></div>
                            </div>
                        </div>
                        <div class="cart-item-price">TShs <?php echo number_format($price, 0); ?></div>
                        <div class="cart-item-quantity">
                            <button class="qty-btn" onclick="updateQuantity(<?php echo $productId; ?>, -1)">-</button>
                            <input type="number" class="qty-input" id="qty-<?php echo $productId; ?>" value="<?php echo $quantity; ?>" min="1" max="<?php echo $product['quantity']; ?>" onchange="updateQuantity(<?php echo $productId; ?>, 0, this.value)">
                            <button class="qty-btn" onclick="updateQuantity(<?php echo $productId; ?>, 1)">+</button>
                        </div>
                        <div class="cart-item-subtotal" id="subtotal-<?php echo $productId; ?>">TShs <?php echo number_format($subTotal, 0); ?></div>
                        <button class="cart-item-remove" onclick="removeItem(<?php echo $productId; ?>)"><i class="fas fa-trash-alt"></i></button>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Continue Shopping Link -->
                <a href="product.php" class="continue-shopping">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
            
            <!-- Order Summary -->
            <div class="order-summary-card">
                <div class="summary-header">
                    <i class="fas fa-receipt"></i> Order Summary
                </div>
                <div class="summary-content">
                    <div class="summary-row">
                        <span>Subtotal (<?php echo count($cartProducts); ?> items)</span>
                        <span id="cartSubtotal">TShs <?php echo number_format($cartTotal, 0); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Fee</span>
                        <span id="deliveryFee">TShs 0</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span id="cartTotal">TShs <?php echo number_format($cartTotal, 0); ?></span>
                    </div>
                    <button class="checkout-btn" id="showCheckoutBtn">
                        <i class="fas fa-arrow-right"></i> Proceed to Checkout
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Delivery Information Form (Hidden until checkout clicked) -->
        <div id="checkoutForm" class="delivery-form-card" style="display: none;">
            <div class="form-header">
                <i class="fas fa-truck"></i> Delivery Information
            </div>
            <div class="form-body">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error" style="background: #fee2e2; color: #991b1b; padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="cart.php" id="checkout-form">
                    <input type="hidden" name="place_order" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($firstName); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($lastName); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <div class="phone-input-group">
                                <span class="phone-prefix">+255</span>
                                <?php 
                                $phone = $userData['phone'] ?? '';
                                if (strpos($phone, '+255') === 0) $phone = substr($phone, 4);
                                ?>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="712345678" pattern="[0-9]{9}" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>City *</label>
                            <select name="city" required>
                                <?php foreach ($locations as $city => $state): ?>
                                <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Region *</label>
                            <select name="state" required>
                                <?php foreach ($locations as $city => $state): ?>
                                <option value="<?php echo htmlspecialchars($state); ?>"><?php echo htmlspecialchars($state); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Delivery Address *</label>
                        <textarea name="address" rows="2" placeholder="Street address, building, apartment" required><?php echo htmlspecialchars($userData['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="notification-section">
                        <h4><i class="fas fa-bell"></i> How would you like to receive order updates?</h4>
                        <div class="notification-options">
                            <label class="notification-option">
                                <input type="radio" name="notification_method" value="email" checked> <i class="fas fa-envelope"></i> Email
                            </label>
                            <label class="notification-option">
                                <input type="radio" name="notification_method" value="sms"> <i class="fas fa-sms"></i> SMS
                            </label>
                            <label class="notification-option">
                                <input type="radio" name="notification_method" value="whatsapp"> <i class="fab fa-whatsapp"></i> WhatsApp
                            </label>
                            <label class="notification-option">
                                <input type="radio" name="notification_method" value="both"> <i class="fas fa-bell-ring"></i> Both
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="checkout-btn" style="background: linear-gradient(135deg, var(--success), #059669); margin-top: 0;">
                        <i class="fas fa-check-circle"></i> Place Order
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>

<footer class="footer">
    <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
        <p style="color: #64748b;">&copy; 2024 Taigon Investments. All rights reserved. | IT Solutions & Hardware Store</p>
    </div>
</footer>

<!-- Toast Notification -->
<div id="toast" class="toast-notification">
    <i class="fas fa-check-circle"></i>
    <span id="toastMessage"></span>
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

// Update quantity
function updateQuantity(productId, change, newValue = null) {
    let quantity;
    if (newValue !== null) {
        quantity = parseInt(newValue);
    } else {
        const currentInput = document.getElementById(`qty-${productId}`);
        quantity = parseInt(currentInput.value) + change;
    }
    
    if (quantity < 1) quantity = 1;
    
    fetch(`cart.php?action=update&id=${productId}&quantity=${quantity}`)
        .then(() => {
            window.location.reload();
        })
        .catch(error => console.error('Error:', error));
}

// Remove item
function removeItem(productId) {
    if (confirm('Remove this item from your cart?')) {
        window.location.href = `cart.php?action=remove&id=${productId}`;
    }
}

// Show checkout form
document.getElementById('showCheckoutBtn')?.addEventListener('click', function() {
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm.style.display === 'none') {
        checkoutForm.style.display = 'block';
        checkoutForm.scrollIntoView({ behavior: 'smooth' });
        this.textContent = 'Hide Checkout';
        this.innerHTML = '<i class="fas fa-arrow-up"></i> Hide Checkout';
    } else {
        checkoutForm.style.display = 'none';
        this.textContent = 'Proceed to Checkout';
        this.innerHTML = '<i class="fas fa-arrow-right"></i> Proceed to Checkout';
    }
});

// Notification option selection
document.querySelectorAll('.notification-option').forEach(option => {
    const radio = option.querySelector('input[type="radio"]');
    radio.addEventListener('change', function() {
        document.querySelectorAll('.notification-option').forEach(opt => opt.classList.remove('selected'));
        if (this.checked) option.classList.add('selected');
    });
});

// Initialize selected notification option
document.querySelectorAll('.notification-option input:checked').forEach(radio => {
    radio.closest('.notification-option').classList.add('selected');
});

// Toast function
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
    setTimeout(() => toast.classList.remove('show'), 3000);
}

// Show success message if exists
<?php if (isset($_SESSION['cart_success'])): ?>
showToast('<?php echo $_SESSION['cart_success']; unset($_SESSION['cart_success']); ?>');
<?php endif; ?>
</script>
</body>
</html>