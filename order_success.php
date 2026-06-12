<?php
session_start();
require 'db.php';

// Check if order was just placed
if (!isset($_SESSION['order_success'])) {
    header('Location: index.php');
    exit();
}

$orderData = $_SESSION['order_success'];
$orderId = $orderData['order_id'];
$trackingId = $orderData['tracking_id'];
$orderTotal = $orderData['order_total'];

// Fetch order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param('i', $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Clear the session data
unset($_SESSION['order_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Taigon Investments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .success-container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 24px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #d1fae5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .success-icon i { font-size: 2.5rem; color: #10b981; }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        .tracking-id {
            background: #f1f5f9;
            padding: 0.75rem;
            border-radius: 12px;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 1.1rem;
        }
        .btn { display: inline-block; padding: 0.8rem 1.5rem; background: #0ea5e9; color: white; text-decoration: none; border-radius: 2rem; margin-top: 1rem; }
        .btn-outline { background: transparent; border: 2px solid #0ea5e9; color: #0ea5e9; margin-left: 0.5rem; }
    </style>
</head>
<body>
<div class="success-container">
    <div class="success-icon"><i class="fas fa-check-circle"></i></div>
    <h1>Order Placed Successfully!</h1>
    <p>Thank you for shopping with Taigon Investments</p>
    
    <div class="tracking-id">
        <strong>Tracking ID:</strong> <?php echo htmlspecialchars($trackingId); ?>
    </div>
    
    <p style="color: #64748b; font-size: 0.85rem;">We've sent a confirmation to your email. Use your tracking ID to monitor your order status.</p>
    
    <div>
        <a href="my_orders.php" class="btn"><i class="fas fa-truck"></i> Track Order</a>
        <a href="product.php" class="btn btn-outline"><i class="fas fa-shop"></i> Continue Shopping</a>
    </div>
</div>
</body>
</html>