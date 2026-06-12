<?php
session_start();

// Database connection
include('db.php');

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

// Handle order status update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];
    
    $validStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Completed', 'Cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('si', $newStatus, $orderId);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Status updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    $stmt->close();
    exit;
}

// Fetch statistics
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN order_status = 'Completed' THEN 1 ELSE 0 END) as completed_orders,
    SUM(CASE WHEN order_status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN order_status = 'Processing' THEN 1 ELSE 0 END) as processing_orders,
    SUM(CASE WHEN order_status = 'Shipped' THEN 1 ELSE 0 END) as shipped_orders,
    SUM(CASE WHEN order_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_orders
    FROM orders";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get total products
$products_query = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
$products_result = $conn->query($products_query);
$total_products = $products_result->fetch_assoc()['total'];

// Get total customers
$customers_query = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
$customers_result = $conn->query($customers_query);
$total_customers = $customers_result->fetch_assoc()['total'];

// Get low stock products
$low_stock_query = "SELECT id, name, quantity, price FROM products WHERE quantity < 10 ORDER BY quantity ASC LIMIT 5";
$low_stock_result = $conn->query($low_stock_query);

// Get recent orders
$orders_query = "
    SELECT 
        o.id, o.first_name, o.last_name, o.email, o.order_date, o.order_status,
        SUM(oi.quantity * oi.price) as total_amount
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.order_date DESC
    LIMIT 10
";
$orders_result = $conn->query($orders_query);

// Get recent products
$recent_products_query = "SELECT id, name, price, quantity, category FROM products ORDER BY id DESC LIMIT 5";
$recent_products_result = $conn->query($recent_products_query);

// Get monthly revenue
$revenue_query = "SELECT 
    DATE_FORMAT(order_date, '%Y-%m') as month,
    SUM(oi.quantity * oi.price) as revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.order_status IN ('Completed', 'Delivered')
    GROUP BY DATE_FORMAT(order_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6";
$revenue_result = $conn->query($revenue_query);
$monthly_revenue = [];
while ($row = $revenue_result->fetch_assoc()) {
    $monthly_revenue[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Admin Dashboard - Taigon Investments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #0ea5e9;
            --primary-dark: #0369a1;
            --primary-light: #38bdf8;
            --primary-glow: rgba(14, 165, 233, 0.25);
            --accent: #f97316;
            --success: #10b981;
            --success-glow: rgba(16, 185, 129, 0.25);
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #8b5cf6;
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
            background: #f1f5f9;
            color: var(--text-dark);
            line-height: 1.5;
            min-height: 100vh;
        }

        /* Admin Container */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 100;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }

        .sidebar-header h2 {
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sidebar-header h2 i {
            color: var(--primary);
        }

        .sidebar-menu {
            padding: 0 1rem;
        }

        .sidebar-menu h3 {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 1px;
            padding: 0.75rem 0.5rem;
            margin-top: 0.5rem;
        }

        .sidebar-menu ul {
            list-style: none;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .sidebar-menu li a:hover, .sidebar-menu li a.active {
            background: rgba(14,165,233,0.2);
            color: white;
        }

        .sidebar-menu li a i {
            width: 20px;
            font-size: 1rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 1.5rem;
        }

        /* Top Bar */
        .top-bar {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .page-title h1 {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .page-title p {
            font-size: 0.8rem;
            color: var(--text-mid);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-name {
            font-weight: 500;
        }

        .logout-btn {
            background: var(--danger);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            border: 1px solid var(--border);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .stat-header h3 {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-mid);
            text-transform: uppercase;
        }

        .stat-header i {
            font-size: 1.5rem;
            opacity: 0.5;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--text-light);
            margin-top: 0.25rem;
        }

        /* Chart and Quick Stats Row */
        .row-2cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        /* Cards */
        .card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .card-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: var(--surface-muted);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th, .data-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .data-table th {
            background: var(--surface-muted);
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--text-mid);
        }

        .data-table tr:hover {
            background: rgba(14,165,233,0.03);
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .status-Pending { background: #fef3c7; color: #d97706; }
        .status-Processing { background: #dbeafe; color: #2563eb; }
        .status-Shipped { background: #e0e7ff; color: #4f46e5; }
        .status-Delivered { background: #d1fae5; color: #059669; }
        .status-Completed { background: #a7f3d0; color: #047857; }
        .status-Cancelled { background: #fee2e2; color: #dc2626; }

        /* Action Buttons */
        .action-btn {
            padding: 0.3rem 0.6rem;
            border-radius: var(--radius-sm);
            font-size: 0.7rem;
            text-decoration: none;
            margin-right: 0.3rem;
            cursor: pointer;
        }

        .btn-view { background: var(--primary); color: white; }
        .btn-edit { background: var(--warning); color: white; }
        .btn-process { background: var(--info); color: white; }
        .btn-ship { background: var(--success); color: white; }
        .btn-cancel { background: var(--danger); color: white; }

        /* Low Stock Warning */
        .stock-low { color: var(--danger); font-weight: 600; }
        .stock-medium { color: var(--warning); font-weight: 600; }
        .stock-high { color: var(--success); font-weight: 600; }

        /* Quick Actions Dropdown */
        .quick-actions {
            display: inline-flex;
            gap: 0.3rem;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 200;
            background: var(--primary);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: var(--shadow-md);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.mobile-open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .mobile-menu-toggle { display: flex; align-items: center; justify-content: center; }
            .row-2cols { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .main-content { padding: 1rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
            .top-bar { flex-direction: column; gap: 0.5rem; text-align: center; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .data-table th, .data-table td { padding: 0.5rem; font-size: 0.75rem; }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-chart-line"></i> Taigon Admin</h2>
        </div>
        <div class="sidebar-menu">
            <h3>Main</h3>
            <ul>
                <li><a href="#" class="active" data-section="dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            </ul>
            <h3>Management</h3>
            <ul>
                <li><a href="#" data-section="orders"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="#" data-section="products"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="#" data-section="inventory"><i class="fas fa-warehouse"></i> Inventory</a></li>
            </ul>
            <h3>Account</h3>
            <ul>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <li><a href="index.php"><i class="fas fa-store"></i> View Store</a></li>
            </ul>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_full_name']); ?></p>
            </div>
            <div class="user-info">
                <span class="user-name"><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboardSection">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Total Orders</h3>
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
                    <div class="stat-label">All time orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Completed</h3>
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['completed_orders']); ?></div>
                    <div class="stat-label">Successfully delivered</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Pending</h3>
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($stats['pending_orders']); ?></div>
                    <div class="stat-label">Awaiting processing</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Products</h3>
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_products); ?></div>
                    <div class="stat-label">Active products</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Customers</h3>
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_customers); ?></div>
                    <div class="stat-label">Registered users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-header">
                        <h3>Low Stock</h3>
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-value"><?php echo $low_stock_result->num_rows; ?></div>
                    <div class="stat-label">Need restocking</div>
                </div>
            </div>

            <!-- Chart and Quick Stats -->
            <div class="row-2cols">
                <div class="card">
                    <div class="card-header">
                        <span><i class="fas fa-chart-line"></i> Monthly Revenue</span>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="200"></canvas>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span><i class="fas fa-chart-pie"></i> Order Status Distribution</span>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-clock"></i> Recent Orders</span>
                    <a href="#" onclick="showSection('orders')" style="color: var(--primary); text-decoration: none; font-size: 0.8rem;">View All →</a>
                </div>
                <div class="card-body table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr><th>Order ID</th><th>Customer</th><th>Date</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php while($order = $orders_result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                <td>TShs <?php echo number_format($order['total_amount'], 0); ?></td>
                                <td><span class="status-badge status-<?php echo $order['order_status']; ?>"><?php echo $order['order_status']; ?></span></td>
                                <td>
                                    <a href="view_order.php?order_id=<?php echo $order['id']; ?>" class="action-btn btn-view"><i class="fas fa-eye"></i></a>
                                    <select onchange="updateOrderStatus(<?php echo $order['id']; ?>, this.value)" class="status-select" style="padding: 0.2rem 0.4rem; font-size: 0.7rem; border-radius: 4px;">
                                        <option value="Pending" <?php echo $order['order_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Processing" <?php echo $order['order_status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="Shipped" <?php echo $order['order_status'] == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="Delivered" <?php echo $order['order_status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="Completed" <?php echo $order['order_status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="Cancelled" <?php echo $order['order_status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Orders Section (Hidden by default) -->
        <div id="ordersSection" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-shopping-cart"></i> All Orders</span>
                    <button onclick="showSection('dashboard')" style="background: none; border: none; color: var(--primary); cursor: pointer;">← Back to Dashboard</button>
                </div>
                <div class="card-body table-responsive">
                    <table class="data-table" id="allOrdersTable">
                        <thead>
                            <tr><th>Order ID</th><th>Customer</th><th>Email</th><th>Date</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="allOrdersBody">
                            <tr><td colspan="7" style="text-align: center;">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Products Section (Hidden by default) -->
        <div id="productsSection" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-box"></i> Products</span>
                    <button onclick="showSection('dashboard')" style="background: none; border: none; color: var(--primary); cursor: pointer;">← Back to Dashboard</button>
                </div>
                <div class="card-body table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr><th>ID</th><th>Product Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            $allProducts = $conn->query("SELECT id, name, category, price, quantity FROM products ORDER BY id DESC LIMIT 20");
                            while($product = $allProducts->fetch_assoc()): 
                                $stockClass = $product['quantity'] < 5 ? 'stock-low' : ($product['quantity'] < 15 ? 'stock-medium' : 'stock-high');
                            ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td>TShs <?php echo number_format($product['price'], 0); ?></td>
                                <td><span class="<?php echo $stockClass; ?>"><?php echo $product['quantity']; ?> units</span></td>
                                <td><a href="edit_product.php?id=<?php echo $product['id']; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i> Edit</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Inventory Section (Hidden by default) -->
        <div id="inventorySection" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-warehouse"></i> Low Stock Inventory</span>
                    <button onclick="showSection('dashboard')" style="background: none; border: none; color: var(--primary); cursor: pointer;">← Back to Dashboard</button>
                </div>
                <div class="card-body table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr><th>Product ID</th><th>Product Name</th><th>Current Stock</th><th>Price</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            $lowStockAll = $conn->query("SELECT id, name, quantity, price FROM products WHERE quantity < 15 ORDER BY quantity ASC");
                            while($item = $lowStockAll->fetch_assoc()): 
                                $statusClass = $item['quantity'] == 0 ? 'stock-low' : 'stock-medium';
                                $statusText = $item['quantity'] == 0 ? 'Out of Stock' : 'Low Stock';
                            ?>
                            <tr>
                                <td><?php echo $item['id']; ?></td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><span class="<?php echo $statusClass; ?>"><?php echo $item['quantity']; ?> units</span></td>
                                <td>TShs <?php echo number_format($item['price'], 0); ?></td>
                                <td><span class="status-badge status-Pending"><?php echo $statusText; ?></span></td>
                                <td><a href="edit_product.php?id=<?php echo $item['id']; ?>" class="action-btn btn-edit"><i class="fas fa-plus"></i> Restock</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Mobile menu toggle
        const mobileToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        
        mobileToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('mobile-open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 1024) {
                if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });

        // Section navigation
        function showSection(sectionName) {
            document.getElementById('dashboardSection').style.display = 'none';
            document.getElementById('ordersSection').style.display = 'none';
            document.getElementById('productsSection').style.display = 'none';
            document.getElementById('inventorySection').style.display = 'none';
            
            document.getElementById(sectionName + 'Section').style.display = 'block';
            
            // Update active state in sidebar
            document.querySelectorAll('.sidebar-menu a').forEach(a => a.classList.remove('active'));
            
            if (sectionName === 'dashboard') {
                document.querySelector('.sidebar-menu a[data-section="dashboard"]')?.classList.add('active');
            } else if (sectionName === 'orders') {
                document.querySelector('.sidebar-menu a[data-section="orders"]')?.classList.add('active');
                loadAllOrders();
            } else if (sectionName === 'products') {
                document.querySelector('.sidebar-menu a[data-section="products"]')?.classList.add('active');
            } else if (sectionName === 'inventory') {
                document.querySelector('.sidebar-menu a[data-section="inventory"]')?.classList.add('active');
            }
            
            // Close mobile sidebar after navigation
            if (window.innerWidth <= 1024) {
                sidebar.classList.remove('mobile-open');
            }
        }

        // Load all orders via AJAX
        function loadAllOrders() {
            fetch('admin_get_orders.php')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('allOrdersBody');
                    if (data.orders && data.orders.length > 0) {
                        tbody.innerHTML = data.orders.map(order => `
                            <tr>
                                <td>#${order.id}</td>
                                <td>${escapeHtml(order.first_name)} ${escapeHtml(order.last_name)}</td>
                                <td>${escapeHtml(order.email)}</td>
                                <td>${order.order_date}</td>
                                <td>TShs ${formatNumber(order.total_amount)}</td>
                                <td><span class="status-badge status-${order.order_status}">${order.order_status}</span></td>
                                <td>
                                    <a href="view_order.php?order_id=${order.id}" class="action-btn btn-view"><i class="fas fa-eye"></i></a>
                                    <select onchange="updateOrderStatus(${order.id}, this.value)" class="status-select" style="padding: 0.2rem 0.4rem; font-size: 0.7rem; border-radius: 4px;">
                                        <option value="Pending" ${order.order_status == 'Pending' ? 'selected' : ''}>Pending</option>
                                        <option value="Processing" ${order.order_status == 'Processing' ? 'selected' : ''}>Processing</option>
                                        <option value="Shipped" ${order.order_status == 'Shipped' ? 'selected' : ''}>Shipped</option>
                                        <option value="Delivered" ${order.order_status == 'Delivered' ? 'selected' : ''}>Delivered</option>
                                        <option value="Completed" ${order.order_status == 'Completed' ? 'selected' : ''}>Completed</option>
                                        <option value="Cancelled" ${order.order_status == 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                                    </select>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No orders found</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error loading orders:', error);
                    document.getElementById('allOrdersBody').innerHTML = '<tr><td colspan="7" style="text-align: center;">Error loading orders</td></tr>';
                });
        }

        // Update order status via AJAX
        function updateOrderStatus(orderId, newStatus) {
            if (!confirm(`Change order #${orderId} status to ${newStatus}?`)) return;
            
            fetch('admin_dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update_status&order_id=${orderId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating order status');
            });
        }

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

        // Charts
        <?php if (!empty($monthly_revenue)): ?>
        const revenueCtx = document.getElementById('revenueChart')?.getContext('2d');
        if (revenueCtx) {
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($monthly_revenue, 'month')); ?>,
                    datasets: [{
                        label: 'Revenue (TShs)',
                        data: <?php echo json_encode(array_column($monthly_revenue, 'revenue')); ?>,
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { position: 'top' } }
                }
            });
        }
        <?php endif; ?>

        const statusCtx = document.getElementById('statusChart')?.getContext('2d');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'],
                    datasets: [{
                        data: [
                            <?php echo $stats['pending_orders']; ?>,
                            <?php echo $stats['processing_orders']; ?>,
                            <?php echo $stats['shipped_orders']; ?>,
                            <?php echo $stats['completed_orders']; ?>,
                            <?php echo $stats['cancelled_orders']; ?>
                        ],
                        backgroundColor: ['#f59e0b', '#3b82f6', '#8b5cf6', '#10b981', '#ef4444']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        // Sidebar navigation
        document.querySelectorAll('.sidebar-menu a[data-section]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                showSection(link.dataset.section);
            });
        });
    </script>
</body>
</html>