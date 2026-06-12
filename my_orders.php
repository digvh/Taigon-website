<?php
session_start();
require 'db.php';

// Session timeout configuration (30 minutes)
$session_timeout = 30 * 60;

// Check if session has timed out
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $session_timeout) {
        session_unset();
        session_destroy();
        header('Location: userlogin.php?timeout=1');
        exit;
    }
}
$_SESSION['last_activity'] = time();

// Check for valid session (either logged in user or guest tracking)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['tracking_order'])) {
    header('Location: userlogin.php');
    exit;
}

// Guest session timeout (15 minutes)
if (isset($_SESSION['tracking_order']) && !isset($_SESSION['user_id'])) {
    $guest_timeout = 15 * 60;
    if (isset($_SESSION['guest_last_activity'])) {
        if (time() - $_SESSION['guest_last_activity'] > $guest_timeout) {
            session_unset();
            session_destroy();
            header('Location: userlogin.php?timeout=1');
            exit;
        }
    }
    $_SESSION['guest_last_activity'] = time();
}

// Fetch orders based on user type
$orders = [];
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else if (isset($_SESSION['tracking_order'])) {
    $trackingData = $_SESSION['tracking_order'];
    $stmt = $conn->prepare("SELECT * FROM orders WHERE tracking_id = ? AND email = ? ORDER BY order_date DESC");
    $stmt->bind_param('ss', $trackingData['tracking_id'], $trackingData['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    $orders = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Get order items and totals for each order
foreach ($orders as &$order) {
    $stmt = $conn->prepare("SELECT SUM(quantity * price) as total FROM order_items WHERE order_id = ?");
    $stmt->bind_param('i', $order['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $total = $result->fetch_assoc();
    $order['total_amount'] = $total['total'] ?? 0;
    $stmt->close();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ?");
    $stmt->bind_param('i', $order['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc();
    $order['item_count'] = $count['count'] ?? 0;
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Track your orders from Taigon Investments - Real-time order status updates">
    <title>My Orders - Taigon Investments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0ea5e9;
            --primary-dark: #0369a1;
            --primary-light: #38bdf8;
            --primary-glow: rgba(14, 165, 233, 0.25);
            --success: #10b981;
            --success-glow: rgba(16, 185, 129, 0.25);
            --warning: #f59e0b;
            --warning-glow: rgba(245, 158, 11, 0.25);
            --danger: #ef4444;
            --danger-glow: rgba(239, 68, 68, 0.25);
            --info: #8b5cf6;
            --info-glow: rgba(139, 92, 246, 0.25);
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
        .logo-area img { height: 45px; width: auto; border-radius: var(--radius-sm); }
        .logo-text { font-weight: 800; font-size: 1.3rem; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

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
        .btn-track { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; box-shadow: 0 2px 8px var(--primary-glow); }
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

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Live Indicator */
        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--surface);
            border-radius: 2rem;
            box-shadow: var(--shadow-sm);
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--success);
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }

        /* Help Card */
        .help-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            color: white;
        }

        .help-card h3 {
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .help-card p {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .help-card .btn-help {
            background: rgba(255,255,255,0.2);
            padding: 0.6rem 1.2rem;
            border-radius: 2rem;
            color: white;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .help-card .btn-help:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        /* Order Card */
        .order-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid var(--border);
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .order-card.updated {
            animation: cardFlash 0.6s ease;
            border-left: 4px solid var(--primary);
        }

        @keyframes cardFlash {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.01); background: rgba(14,165,233,0.05); }
        }

        /* Order Header */
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            background: var(--surface-muted);
            border-bottom: 1px solid var(--border);
        }

        .order-id {
            font-weight: 700;
            font-size: 1rem;
            color: var(--primary-dark);
        }

        .order-id small {
            font-weight: normal;
            color: var(--text-mid);
            font-size: 0.8rem;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.9rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-Pending { background: #fef3c7; color: #d97706; }
        .status-Processing { background: #dbeafe; color: #2563eb; }
        .status-Shipped { background: #e0e7ff; color: #4f46e5; }
        .status-Delivered { background: #d1fae5; color: #059669; }
        .status-Completed { background: #a7f3d0; color: #047857; }
        .status-Cancelled { background: #fee2e2; color: #dc2626; }

        /* Progress Tracker */
        .progress-tracker {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            max-width: 600px;
            margin: 0 auto;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            background: var(--surface-muted);
            border: 2px solid var(--border);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text-mid);
            transition: all 0.3s;
            margin-bottom: 0.5rem;
        }

        .step-circle.completed {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }

        .step-circle.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        .step-text {
            font-size: 0.7rem;
            text-align: center;
            color: var(--text-mid);
            font-weight: 500;
        }

        .step-text.completed { color: var(--success); }
        .step-text.active { color: var(--primary); font-weight: 600; }

        .progress-line {
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--border);
            z-index: 1;
        }

        .progress-line-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: var(--success);
            transition: width 0.5s ease;
        }

        /* Order Info Grid */
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .info-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: var(--text-light);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        /* Items Table */
        .items-container {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th {
            text-align: left;
            padding: 0.75rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-mid);
            text-transform: uppercase;
            border-bottom: 1px solid var(--border);
        }

        .items-table td {
            padding: 0.75rem 0.5rem;
            font-size: 0.85rem;
            border-bottom: 1px solid var(--border);
            color: var(--text-dark);
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .order-total {
            padding: 1rem 1.5rem;
            background: var(--surface-muted);
            text-align: right;
            font-weight: 700;
            font-size: 1rem;
            color: var(--primary-dark);
        }

        /* No Orders */
        .no-orders {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }

        .no-orders i {
            font-size: 4rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .no-orders h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .no-orders p {
            color: var(--text-mid);
            margin-bottom: 1.5rem;
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

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 3rem 1.5rem 1.5rem;
            margin-top: 3rem;
        }

        /* Notification Toast */
        .notification-toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-xl);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 1100;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--primary);
        }

        .notification-toast.show {
            transform: translateX(0);
        }

        .notification-toast i {
            font-size: 1.2rem;
            color: var(--primary);
        }

        .notification-toast .toast-content {
            flex: 1;
        }

        .notification-toast .toast-title {
            font-weight: 600;
            font-size: 0.85rem;
        }

        .notification-toast .toast-message {
            font-size: 0.75rem;
            color: var(--text-mid);
        }

        /* Responsive */
        @media (max-width: 1100px) {
            .header-container { flex-wrap: wrap; }
            .main-nav { order: 3; width: 100%; justify-content: center; padding-top: 0.5rem; border-top: 1px solid var(--border); margin-top: 0.5rem; }
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
            .progress-steps { flex-wrap: wrap; gap: 1rem; }
            .progress-step { flex: none; width: calc(50% - 0.5rem); }
            .progress-line { display: none; }
            .order-info-grid { grid-template-columns: 1fr; gap: 0.75rem; }
            .items-table { display: block; overflow-x: auto; }
            .notification-toast { left: 1rem; right: 1rem; bottom: 1rem; }
        }

        @media (max-width: 480px) {
            .order-header { flex-direction: column; align-items: flex-start; }
            .progress-step { width: 100%; flex-direction: row; justify-content: flex-start; gap: 1rem; }
            .step-circle { margin-bottom: 0; }
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

<main class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-truck"></i> My Orders</h1>
        <div class="live-indicator">
            <span class="live-dot"></span>
            <span>Live Tracking Active</span>
        </div>
    </div>

    <!-- Help Card -->
    <div class="help-card">
        <div>
            <h3><i class="fas fa-headset"></i> Need help with your order?</h3>
            <p>Contact our support team for assistance with tracking, delivery, or returns</p>
        </div>
        <a href="contact.php" class="btn-help"><i class="fas fa-envelope"></i> Contact Support</a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="no-orders">
            <i class="fas fa-box-open"></i>
            <h3>No orders yet</h3>
            <p>You haven't placed any orders. Start shopping to see your orders here!</p>
            <a href="product.php" class="btn-primary"><i class="fas fa-shopping-cart"></i> Browse Products</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <?php
            $statusClass = 'status-' . $order['order_status'];
            $orderDate = date('M d, Y', strtotime($order['order_date']));
            $orderTime = date('g:i A', strtotime($order['order_date']));
            
            // Calculate progress percentage
            $statusSteps = ['Pending' => 25, 'Processing' => 50, 'Shipped' => 75, 'Delivered' => 100, 'Completed' => 100, 'Cancelled' => 0];
            $progressPercent = $statusSteps[$order['order_status']] ?? 0;
            ?>
            
            <div class="order-card" data-order-id="<?php echo $order['id']; ?>" data-order-status="<?php echo htmlspecialchars($order['order_status']); ?>">
                <div class="order-header">
                    <div class="order-id">
                        Order #<?php echo $order['id']; ?>
                        <small>• Placed on <?php echo $orderDate; ?> at <?php echo $orderTime; ?></small>
                    </div>
                    <div class="status-badge <?php echo $statusClass; ?>" id="status-<?php echo $order['id']; ?>">
                        <i class="fas <?php echo $order['order_status'] == 'Pending' ? 'fa-clock' : ($order['order_status'] == 'Processing' ? 'fa-cog fa-spin' : ($order['order_status'] == 'Shipped' ? 'fa-shipping-fast' : ($order['order_status'] == 'Delivered' ? 'fa-check-circle' : 'fa-times-circle'))); ?>"></i>
                        <?php echo $order['order_status']; ?>
                    </div>
                </div>
                
                <!-- Progress Tracker -->
                <div class="progress-tracker">
                    <div class="progress-steps">
                        <div class="progress-line">
                            <div class="progress-line-fill" style="width: <?php echo $progressPercent; ?>%;"></div>
                        </div>
                        <div class="progress-step">
                            <div class="step-circle <?php echo in_array($order['order_status'], ['Pending','Processing','Shipped','Delivered','Completed']) ? 'completed' : ''; ?>">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="step-text <?php echo in_array($order['order_status'], ['Pending','Processing','Shipped','Delivered','Completed']) ? 'completed' : ''; ?>">Ordered</div>
                        </div>
                        <div class="progress-step">
                            <div class="step-circle <?php echo in_array($order['order_status'], ['Processing','Shipped','Delivered','Completed']) ? 'completed' : ''; ?>">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <div class="step-text <?php echo in_array($order['order_status'], ['Processing','Shipped','Delivered','Completed']) ? 'completed' : ''; ?>">Processing</div>
                        </div>
                        <div class="progress-step">
                            <div class="step-circle <?php echo in_array($order['order_status'], ['Shipped','Delivered','Completed']) ? 'completed' : ''; ?>">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="step-text <?php echo in_array($order['order_status'], ['Shipped','Delivered','Completed']) ? 'completed' : ''; ?>">Shipped</div>
                        </div>
                        <div class="progress-step">
                            <div class="step-circle <?php echo in_array($order['order_status'], ['Delivered','Completed']) ? 'completed' : ''; ?>">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="step-text <?php echo in_array($order['order_status'], ['Delivered','Completed']) ? 'completed' : ''; ?>">Delivered</div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Information -->
                <div class="order-info-grid">
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-qrcode"></i> Tracking ID</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['tracking_id'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-user"></i> Customer</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-map-marker-alt"></i> Delivery Location</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['city'] . ', ' . $order['state']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-phone"></i> Contact</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['phone']); ?></span>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="items-container">
                    <table class="items-table">
                        <thead>
                            <tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr>
                        </thead>
                        <tbody id="items-<?php echo $order['id']; ?>">
                            <tr><td colspan="4" style="text-align: center;">Loading items...</td></tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="order-total" id="total-<?php echo $order['id']; ?>">
                    Total: TShs 0
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<!-- Notification Toast -->
<div id="notificationToast" class="notification-toast">
    <i class="fas fa-truck-fast"></i>
    <div class="toast-content">
        <div class="toast-title" id="toastTitle">Order Update</div>
        <div class="toast-message" id="toastMessage">Your order status has been updated</div>
    </div>
</div>

<footer class="footer">
    <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
        <p style="color: #64748b;">&copy; 2024 Taigon Investments. All rights reserved.</p>
    </div>
</footer>

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

// Load order items for each order
document.addEventListener('DOMContentLoaded', function() {
    const orderCards = document.querySelectorAll('.order-card');
    orderCards.forEach(card => {
        const orderId = card.getAttribute('data-order-id');
        loadOrderItems(orderId);
    });
});

function loadOrderItems(orderId) {
    fetch(`get_order_items.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById(`items-${orderId}`);
                const totalSpan = document.getElementById(`total-${orderId}`);
                let total = 0;
                
                if (tbody) {
                    tbody.innerHTML = '';
                    data.items.forEach(item => {
                        const subtotal = item.price * item.quantity;
                        total += subtotal;
                        const row = `
                            <tr>
                                <td>${escapeHtml(item.product_name)}</td>
                                <td>${item.quantity}</td>
                                <td>TShs ${formatNumber(item.price)}</td>
                                <td>TShs ${formatNumber(subtotal)}</td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                }
                
                if (totalSpan) {
                    totalSpan.innerHTML = `Total: TShs ${formatNumber(total)}`;
                }
            }
        })
        .catch(error => console.error('Error loading items:', error));
}

// Real-time order status checking
let lastCheckTime = Date.now();

function checkOrderUpdates() {
    const orderCards = document.querySelectorAll('.order-card[data-order-id]');
    const orders = Array.from(orderCards).map(card => ({
        id: card.getAttribute('data-order-id'),
        status: card.getAttribute('data-order-status')
    }));
    
    if (orders.length === 0) return;
    
    fetch('check_order_updates.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ orders: orders, last_check: lastCheckTime })
    })
    .then(response => response.json())
    .then(data => {
        if (data.updates && data.updates.length > 0) {
            data.updates.forEach(update => {
                updateOrderUI(update);
            });
        }
        lastCheckTime = Date.now();
    })
    .catch(error => console.error('Error checking updates:', error));
}

function updateOrderUI(update) {
    const card = document.querySelector(`.order-card[data-order-id="${update.order_id}"]`);
    if (!card) return;
    
    const currentStatus = card.getAttribute('data-order-status');
    if (currentStatus !== update.order_status) {
        // Update status badge
        const statusBadge = document.getElementById(`status-${update.order_id}`);
        if (statusBadge) {
            let icon = 'fa-clock';
            if (update.order_status === 'Processing') icon = 'fa-cog fa-spin';
            else if (update.order_status === 'Shipped') icon = 'fa-shipping-fast';
            else if (update.order_status === 'Delivered') icon = 'fa-check-circle';
            else if (update.order_status === 'Cancelled') icon = 'fa-times-circle';
            
            statusBadge.className = `status-badge status-${update.order_status}`;
            statusBadge.innerHTML = `<i class="fas ${icon}"></i> ${update.order_status}`;
        }
        
        // Update progress tracker
        updateProgressTracker(card, update.order_status);
        
        // Add animation
        card.classList.add('updated');
        setTimeout(() => card.classList.remove('updated'), 2000);
        
        // Show notification
        showNotification(update.order_id, update.order_status);
        
        // Update data attribute
        card.setAttribute('data-order-status', update.order_status);
    }
}

function updateProgressTracker(card, status) {
    const steps = card.querySelectorAll('.step-circle');
    const stepTexts = card.querySelectorAll('.step-text');
    const progressFill = card.querySelector('.progress-line-fill');
    
    const statusMap = { 'Pending': 1, 'Processing': 2, 'Shipped': 3, 'Delivered': 4, 'Completed': 4 };
    const currentStep = statusMap[status] || 1;
    const percent = (currentStep / 4) * 100;
    
    if (progressFill) progressFill.style.width = `${percent}%`;
    
    steps.forEach((step, index) => {
        if (index < currentStep) {
            step.classList.add('completed');
            if (stepTexts[index]) stepTexts[index].classList.add('completed');
        } else {
            step.classList.remove('completed');
            if (stepTexts[index]) stepTexts[index].classList.remove('completed');
        }
    });
}

function showNotification(orderId, newStatus) {
    const toast = document.getElementById('notificationToast');
    const title = document.getElementById('toastTitle');
    const message = document.getElementById('toastMessage');
    
    title.innerHTML = `Order #${orderId} Updated`;
    message.innerHTML = `Status changed to: ${newStatus}`;
    
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 5000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNumber(num) {
    return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

// Start real-time checking every 10 seconds
let updateInterval = setInterval(checkOrderUpdates, 10000);

// Stop interval when leaving page
window.addEventListener('beforeunload', () => {
    if (updateInterval) clearInterval(updateInterval);
});
</script>
</body>
</html>