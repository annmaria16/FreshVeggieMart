<?php
require_once 'config.php';
session_start();

// Check if the delivery boy is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'delivery boy') {
    header("Location: login.php");
    exit();
}

$delivery_boy_id = $conn->real_escape_string($_SESSION['user_id']);

// Get today's deliveries
$today_deliveries = 0;
$today_orders_query = "SELECT COUNT(*) as count 
                       FROM payments 
                       WHERE DATE(payment_date) = CURDATE()";
$stmt = $conn->prepare($today_orders_query);
$stmt->execute();
$today_orders_result = $stmt->get_result();
if ($today_orders_result && $today_orders_result->num_rows > 0) {
    $today_deliveries = $today_orders_result->fetch_assoc()['count'];
}

// Fetch completed deliveries
$completed_deliveries = 0;
$completed_query = "SELECT COUNT(*) as count 
                   FROM payments p
                   JOIN orders o ON p.order_id = o.order_id 
                   WHERE o.assigned_delivery_boy = ? 
                   AND o.status = 'delivered'";
$stmt = $conn->prepare($completed_query);
$stmt->bind_param("s", $_SESSION['user_id']);
$stmt->execute();
$completed_result = $stmt->get_result();
if ($completed_result && $completed_result->num_rows > 0) {
    $completed_deliveries = $completed_result->fetch_assoc()['count'];
}

// Get pending deliveries
$pending_deliveries = 0;
$pending_query = "SELECT COUNT(*) as count 
                 FROM payments p
                 JOIN orders o ON p.order_id = o.order_id
                 WHERE o.assigned_delivery_boy = ? 
                 AND o.status IN ('pending', 'processing', 'confirmed', 'out_for_delivery')";
$stmt = $conn->prepare($pending_query);
$stmt->bind_param("s", $_SESSION['user_id']);
$stmt->execute();
$pending_result = $stmt->get_result();
if ($pending_result && $pending_result->num_rows > 0) {
    $pending_deliveries = $pending_result->fetch_assoc()['count'];
}

// Fetch available orders from the database
$available_orders = [];
$orders_query = "SELECT o.*, 
                 u.username as customer_name, 
                 p.payment_method,
                 p.payment_status,
                 p.amount as payment_amount,
                 p.payment_id,
                 p.razorpay_payment_id,
                 GROUP_CONCAT(CONCAT(oi.quantity, 'x ', pr.name) SEPARATOR ', ') as items
                 FROM orders o
                 JOIN payments p ON o.order_id = p.order_id
                 LEFT JOIN users u ON o.user_id = u.user_id
                 LEFT JOIN order_items oi ON o.order_id = oi.order_id
                 LEFT JOIN products pr ON oi.product_id = pr.id
                 WHERE o.status = 'pending' 
                 AND (o.assigned_delivery_boy IS NULL OR o.assigned_delivery_boy = '')
                 AND (
                     (p.payment_method = 'cod' AND p.payment_status = 'pending')
                     OR 
                     (p.payment_method = 'online' AND p.payment_status = 'success')
                 )
                 GROUP BY o.order_id
                 ORDER BY o.delivery_date ASC";
$orders_result = $conn->query($orders_query);

if ($orders_result && $orders_result->num_rows > 0) {
    while ($row = $orders_result->fetch_assoc()) {
        $available_orders[] = $row;
    }
}

// Fetch accepted orders for this delivery boy, including delivered orders
$accepted_orders_query = "SELECT o.*, u.username as customer_name, 
                 GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as items
                 FROM orders o 
                 LEFT JOIN users u ON o.user_id = u.user_id
                 LEFT JOIN order_items oi ON o.order_id = oi.order_id
                 LEFT JOIN products p ON oi.product_id = p.id
                 WHERE o.assigned_delivery_boy = '$delivery_boy_id' 
                 AND o.status IN ('processing', 'out_for_delivery', 'delivered')
                 GROUP BY o.order_id
                 ORDER BY o.created_at DESC";
$accepted_orders_result = $conn->query($accepted_orders_query);

$accepted_orders = [];
if ($accepted_orders_result && $accepted_orders_result->num_rows > 0) {
    while ($row = $accepted_orders_result->fetch_assoc()) {
        $accepted_orders[] = $row;
    }
}

// Handle OTP verification
if (isset($_POST['verify_otp'])) {
    $order_id = $conn->real_escape_string($_POST['order_id']);
    $entered_otp = $conn->real_escape_string($_POST['otp']);

    // Verify OTP
    $verify_query = "SELECT * FROM orders 
                     WHERE order_id = '$order_id' 
                     AND otp = '$entered_otp' 
                     AND assigned_delivery_boy = '$delivery_boy_id'";
    $verify_result = $conn->query($verify_query);

    if ($verify_result && $verify_result->num_rows > 0) {
        // Update order status to delivered
        $update_query = "UPDATE orders 
                         SET status = 'delivered', 
                             delivery_date = NOW() 
                         WHERE order_id = '$order_id'";
        if ($conn->query($update_query)) {
            $success_message = "Order successfully delivered!";
            
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $error_message = "Error updating order status.";
        }
    } else {
        $error_message = "Invalid OTP. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Boy - Accepted Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: sans-serif;
        }

        body {
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .dashboard {
            width: 100%;
            min-height: 100vh;
            background: #f4f4f4;
        }

        /* Navigation styles */
        .dashboard nav {
            width: 100%;
            height: 130px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 50px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .dashboard nav .logo img {
            width: 240px;
        }

        /* Profile and navigation container */
        .dashboard nav .profile-nav-container {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .dashboard nav .profile-info {
            text-align: right;
        }

        .dashboard nav .profile-info h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        .dashboard nav .profile-info p {
            margin: 0;
            font-size: 14px;
            color: #666;
        }

        /* Navigation links */
        .dashboard nav ul {
            list-style: none;
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 0;
            padding: 0;
        }

        .dashboard nav ul li {
            display: inline-block;
        }

        .dashboard nav ul li a {
            text-decoration: none;
            color: #333;
            font-weight: bold;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .dashboard nav ul li a:hover {
            background-color: #fac031;
            color: #fff;
        }

        .welcome-message {
            font-size: 19px;
            color: #34d399;
            padding: 5px 10px;
            border-radius: 4px;
            text-align: center;
            margin-top: -10px;
            white-space: nowrap;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard nav {
                padding: 0 20px;
                height: 100px;
            }

            .dashboard nav .logo img {
                width: 180px;
            }

            .dashboard nav .profile-nav-container {
                gap: 10px;
            }

            .dashboard nav ul li a {
                padding: 6px 10px;
                font-size: 14px;
            }

            .dashboard nav .profile-info h3 {
                font-size: 16px;
            }

            .dashboard nav .profile-info p {
                font-size: 12px;
            }
        }

        .main-content {
            padding: 30px 50px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 24px;
            color: #fac031;
            font-weight: bold;
        }

        .orders-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .order-card {
            position: relative;
            background: white;
            border-radius: 12px;
            border: 2px solid #e0e0e0;
            padding: 25px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .order-card:hover {
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            transform: translateY(-5px);
        }

        .order-card.delivered {
            background: #f9f9f9;
            border-color: #d0d0d0;
            opacity: 0.8;
        }

        .delivered-badge {
            position: absolute;
            top: -15px;
            right: -50px;
            background: #dc3545;
            color: white;
            padding: 10px 60px;
            text-transform: uppercase;
            font-weight: bold;
            transform: rotate(45deg);
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            z-index: 10;
        }

        .order-details {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .order-details p {
            margin: 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-details p strong {
            color: #555;
            min-width: 120px;
            display: inline-block;
        }

        .order-card.delivered .order-details p {
            color: #777;
            text-decoration: line-through;
        }

        .otp-form {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .otp-form input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .btn-verify {
            background-color: #34d399;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-verify:hover {
            background-color: #34d399;
        }

        .order-card.delivered .btn-verify,
        .order-card.delivered .otp-form {
            display: none;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }

            .orders-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
</head>
<body>
    <div class="dashboard">
        <!-- Navigation -->
        <nav>
            <div class="logo">
                <a href="deliveryboy.php">
                    <img src="image/logo8.png" width="240px" height="100px">
                </a>
            </div>
            <div class="profile-nav-container">
                <div class="profile-info">
                    <div class="welcome-message"><b>
                        Welcome, <?php echo htmlspecialchars($_SESSION['user_id']); ?>!</b>
                        <p>Delivery Partner</p>
                    </div>
                </div>
                <ul>
                    <li><a href="deliveryboy.php"><b>Available Orders</b></a></li>
                    <li><a href="logout.php"><b>Logout</b></a></li>
                </ul>
            </div>
        </nav>
        <div class="main-content">
            <!-- Stats Section -->
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Today's Orders</h3>
                    <div class="number"><?php echo $today_deliveries; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Completed Deliveries</h3>
                    <div class="number"><?php echo $completed_deliveries; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Pending Deliveries</h3>
                    <div class="number"><?php echo $pending_deliveries; ?></div>
                </div>
            </div>
            <div class="orders-section">
                <h2>Accepted Orders</h2>
                <?php if (!empty($error_message)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                <div class="orders-grid">
                    <?php if (!empty($accepted_orders)): ?>
                        <?php foreach ($accepted_orders as $order): ?>
                            <div class="order-card <?php echo ($order['status'] == 'delivered' ? 'delivered' : ''); ?>">
                                <?php if ($order['status'] == 'delivered'): ?>
                                    <div class="delivered-badge">Delivered</div>
                                <?php endif; ?>
                                <div class="order-details">
                                    <p><strong><i class="fas fa-receipt"></i> Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></p>
                                    <p><strong><i class="fas fa-user"></i> Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                    <p><strong><i class="fas fa-map-marker-alt"></i> Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                                    <p><strong><i class="fas fa-shopping-basket"></i> Items:</strong> <?php echo htmlspecialchars($order['items']); ?></p>
                                    <p><strong><i class="fas fa-money-bill-wave"></i> Total:</strong> ₹<?php echo htmlspecialchars($order['total_amount']); ?></p>
                                    <p><strong><i class="fas fa-check-circle"></i> Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
                                    <p><strong>Payment Status:</strong> 
                                        <span class="payment-status <?php echo $order['payment_method'] === 'online' && $order['payment_status'] === 'success' ? 'paid' : ''; ?>">
                                            <?php 
                                            if ($order['payment_method'] === 'online' && $order['payment_status'] === 'completed') {
                                                echo 'PAID';
                                            } else if ($order['payment_method'] === 'cod') {
                                                echo 'Cash on Delivery';
                                            }
                                            ?>
                                        </span>
                                    </p>
                                    
                                    <?php if ($order['status'] != 'delivered'): ?>
                                        <form method="POST" class="otp-form">
                                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                            <input type="text" name="otp" placeholder="Enter OTP to Complete Delivery" required>
                                            <button type="submit" name="verify_otp" class="btn-verify">Verify OTP</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No accepted orders at the moment.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>