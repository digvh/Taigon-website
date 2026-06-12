<?php
session_start();

// Database connection
include('db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header('Location: admin_dashboard.php');
    exit();
}
// Handle cancel action from dashboard
if (isset($_GET['action']) && $_GET['action'] == 'cancel') {
    $stmt = $conn->prepare("UPDATE orders SET order_status = 'Cancelled' WHERE id = ?");
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['success_message'] = "Order #$order_id has been cancelled";
    header("Location: view_order.php?order_id=$order_id");
    exit();
}

$order_id = (int)$_GET['order_id'];

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $conn->real_escape_string($_POST['order_status']);
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    $stmt->bind_param('si', $new_status, $order_id);
    
    if ($stmt->execute()) {
        $success_message = "Order status updated successfully!";
    } else {
        $error_message = "Failed to update order status.";
    }
    $stmt->close();
}

// Fetch order details
$order_query = "
    SELECT 
        orders.*,
        SUM(order_items.quantity * order_items.price) AS total_amount,
        COUNT(order_items.id) AS total_items
    FROM orders 
    LEFT JOIN order_items ON orders.id = order_items.order_id 
    WHERE orders.id = ?
    GROUP BY orders.id
";

$stmt = $conn->prepare($order_query);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    header('Location: admin_dashboard.php?error=Order not found');
    exit();
}

$order = $order_result->fetch_assoc();
$stmt->close();

// Fetch order items
$items_query = "
    SELECT 
        order_items.*,
        products.name as product_name,
        products.image as product_image
    FROM order_items 
    LEFT JOIN products ON order_items.product_id = products.id 
    WHERE order_items.order_id = ?
    ORDER BY order_items.id
";

$stmt = $conn->prepare($items_query);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> Details | Pharmacy Admin</title>
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #1abc9c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gray: #95a5a6;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .header {
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.8rem;
            color: var(--dark);
        }

        .back-btn {
            background: var(--gray);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: background 0.3s;
        }

        .back-btn:hover {
            background: #7f8c8d;
        }

        .back-btn svg {
            margin-right: 0.5rem;
        }

        .order-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .card {
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }

        .card h2 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: var(--dark);
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }

        .order-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .info-group h3 {
            font-size: 0.9rem;
            color: var(--gray);
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .info-group p {
            font-size: 1rem;
            font-weight: 500;
        }

        .status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #cce5ff;
            color: #004085;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .items-table th,
        .items-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .items-table th {
            background: var(--light);
            font-weight: 600;
            color: var(--dark);
        }

        .items-table tr:hover {
            background: rgba(52, 152, 219, 0.05);
        }

        .product-info {
            display: flex;
            align-items: center;
        }

        .product-image {
            width: 50px;
            height: 50px;
            border-radius: 4px;
            margin-right: 1rem;
            object-fit: cover;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-success {
            background: var(--secondary);
            color: var(--white);
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .summary-row.total {
            font-weight: 700;
            font-size: 1.1rem;
            border-bottom: 2px solid var(--primary);
            color: var(--primary);
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .order-grid {
                grid-template-columns: 1fr;
            }

            .order-info {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order #<?php echo $order_id; ?> Details</h1>
            <a href="admin_dashboard.php" class="back-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                </svg>
                Back to Dashboard
            </a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="order-grid">
            <!-- Order Details -->
            <div class="card">
                <h2>Order Information</h2>
                <div class="order-info">
                    <div class="info-group">
                        <h3>Order ID</h3>
                        <p>#<?php echo htmlspecialchars($order['id']); ?></p>
                    </div>
                    <div class="info-group">
                        <h3>Order Date</h3>
                        <p><?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?></p>
                    </div>
                    <div class="info-group">
                        <h3>Customer Name</h3>
                        <p><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                    </div>
                    <div class="info-group">
                        <h3>Email</h3>
                        <p><?php echo htmlspecialchars($order['email']); ?></p>
                    </div>
                    <div class="info-group">
                        <h3>Phone</h3>
                        <p><?php echo htmlspecialchars($order['phone']); ?></p>
                    </div>
                    <div class="info-group">
                        <h3>Status</h3>
                        <p>
                            <span class="status status-<?php echo strtolower($order['order_status']); ?>">
                                <?php echo htmlspecialchars($order['order_status']); ?>
                            </span>
                        </p>
                    </div>
                </div>

                <?php if (!empty($order['address'])): ?>
                <div style="margin-top: 1.5rem;">
                    <h3 style="color: var(--gray); font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">Delivery Address</h3>
                    <p><?php echo htmlspecialchars($order['address']); ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($order['notes'])): ?>
                <div style="margin-top: 1.5rem;">
                    <h3 style="color: var(--gray); font-size: 0.9rem; text-transform: uppercase; margin-bottom: 0.5rem;">Order Notes</h3>
                    <p><?php echo htmlspecialchars($order['notes']); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Order Management -->
            <div class="card">
                <h2>Order Management</h2>
                
               <form method="POST" action="">
    <div class="form-group">
        <label for="order_status">Update Status</label>
        <select name="order_status" id="order_status" class="form-control" required>
            <option value="Pending" <?= $order['order_status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Processing" <?= $order['order_status'] == 'Processing' ? 'selected' : '' ?>>Processing</option>
            <option value="Shipped" <?= $order['order_status'] == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
            <option value="Delivered" <?= $order['order_status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
            <option value="Completed" <?= $order['order_status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
            <option value="Cancelled" <?= $order['order_status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
    </div>
    
    <div class="form-group">
        <label for="notes">Status Notes</label>
        <textarea id="notes" name="notes" class="form-control" rows="3" 
                  placeholder="Add any notes about this status change..."></textarea>
    </div>
    
    <div class="form-group">
        <label>
            <input type="checkbox" name="send_notification" value="1" checked>
            Send email notification to customer
        </label>
    </div>
    
    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
</form>

                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #eee;">
                    <h3 style="color: var(--gray); font-size: 0.9rem; text-transform: uppercase; margin-bottom: 1rem;">Order Summary</h3>
                    
                    <div class="summary-row">
                        <span>Total Items:</span>
                        <span><?php echo $order['total_items']; ?></span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total Amount:</span>
                        <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>

                <div class="actions">
                    <a href="print_order.php?order_id=<?php echo $order_id; ?>" class="btn btn-success" target="_blank">
                        Print Order
                    </a>
                    <?php if ($order['order_status'] != 'Cancelled' && $order['order_status'] != 'Completed'): ?>
                        <a href="admin_dashboard.php?action=cancel&order_id=<?php echo $order_id; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to cancel this order?')">
                            Cancel Order
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="card">
            <h2>Order Items</h2>
            
            <?php if ($items_result->num_rows > 0): ?>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $items_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <?php if (!empty($item['product_image']) && file_exists('images/' . $item['product_image'])): ?>
                                            <img src="images/<?php echo htmlspecialchars($item['product_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                 class="product-image">
                                        <?php else: ?>
                                            <div class="product-image" style="background: var(--light); display: flex; align-items: center; justify-content: center; color: var(--gray);">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                                    <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1zm3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4h-3.5zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V5z"/>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['product_name'] ?: 'Product #' . $item['product_id']); ?></strong>
                                            <div style="font-size: 0.8rem; color: var(--gray);">
                                                Product ID: <?php echo htmlspecialchars($item['product_id']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td><strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: var(--gray); padding: 2rem;">No items found for this order.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>