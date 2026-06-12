<?php
session_start();
require 'db.php';

// Check if user is logged in and is a courier
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'courier') {
    header('Location: userlogin.php');
    exit();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $conn->real_escape_string($_POST['order_status']);
    $courierId = $_SESSION['user_id'];
    
    // Validate status - couriers can only set these statuses
    $validStatuses = ['Processing', 'Shipped', 'Delivered', 'Cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        $_SESSION['error_message'] = 'Invalid order status';
        header('Location: courier_dashboard.php');
        exit();
    }

    // Update status - courier can update any order's status
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    $stmt->bind_param('si', $newStatus, $orderId);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Order #$orderId status updated to $newStatus";
    } else {
        $_SESSION['error_message'] = "Failed to update order status";
    }
    $stmt->close();
    header('Location: courier_dashboard.php');
    exit();
}

// Fetch ALL orders (not just assigned ones)
$orders_query = "
    SELECT 
        o.id as order_id,
        o.first_name,
        o.last_name,
        o.order_date,
        o.order_status,
        o.phone,
        o.courier_id,
        SUM(oi.quantity * oi.price) AS total_price
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id, o.first_name, o.last_name, o.order_date, o.order_status, o.phone, o.courier_id
    ORDER BY o.order_date DESC
";

$stmt = $conn->prepare($orders_query);
$stmt->execute();
$order_result = $stmt->get_result();
$orders = $order_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courier Dashboard - Pharmacy System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .alert {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .table-container {
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 20px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--light);
            font-weight: 600;
        }

        tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #cce5ff;
            color: #004085;
        }

        .status-shipped {
            background: #e2e3e5;
            color: #383d41;
        }

        .status-delivered {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s;
        }

        .btn-primary {
            background: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .status-form {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .status-form select {
            width: 100%;
            padding: 8px;
            margin-bottom: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .status-form button {
            padding: 8px 12px;
            margin-right: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .assigned-to-me {
            background-color: rgba(46, 204, 113, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Courier Dashboard - All Orders</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="logout.php" class="action-btn btn-primary">Logout</a>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success_message']); 
            unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error_message']); 
            unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>

        <div class="table-container">
            <h2>All Orders</h2>
            <?php if (count($orders) > 0): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Assigned To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr class="<?php echo ($order['courier_id'] == $_SESSION['user_id']) ? 'assigned-to-me' : ''; ?>">
                                <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                <td><?php echo htmlspecialchars($order['phone']); ?></td>
                                <td>
                                    <span class="status status-<?php echo strtolower($order['order_status']); ?>">
                                        <?php echo htmlspecialchars($order['order_status']); ?>
                                    </span>
                                </td>
                                <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                <td>
                                    <?php echo ($order['courier_id'] == $_SESSION['user_id']) ? 'You' : 'Not assigned'; ?>
                                </td>
                                <td>
                                    <button onclick="toggleStatusForm(<?php echo $order['order_id']; ?>)" 
                                            class="action-btn btn-primary">
                                        Update Status
                                    </button>
                                    
                                    <div id="status-form-<?php echo $order['order_id']; ?>" class="status-form">
                                        <form method="POST" action="">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            
                                            <select name="order_status" required>
                                                <option value="Processing" <?= $order['order_status'] == 'Processing' ? 'selected' : '' ?>>Processing</option>
                                                <option value="Shipped" <?= $order['order_status'] == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                                <option value="Delivered" <?= $order['order_status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                                <option value="Cancelled" <?= $order['order_status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                            
                                            <button type="submit" name="update_status" class="action-btn btn-primary">Update</button>
                                            <button type="button" onclick="toggleStatusForm(<?php echo $order['order_id']; ?>)" class="action-btn" style="background: var(--gray);">Cancel</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No orders found in the system.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleStatusForm(orderId) {
            const form = document.getElementById('status-form-' + orderId);
            form.style.display = form.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>