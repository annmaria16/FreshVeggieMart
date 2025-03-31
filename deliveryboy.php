<?php
require_once 'config.php';
session_start();

// Check if the delivery boy is logged in, if not then redirect to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'delivery boy') {
    header("Location: login.php");
    exit();
}


// Handle order acceptance via AJAX
if (isset($_POST['action']) && isset($_POST['order_id'])) {
    $order_id = $conn->real_escape_string($_POST['order_id']);
    $delivery_boy_id = $conn->real_escape_string($_SESSION['user_id']);

    // Update order status, assign delivery boy, and save OTP
    $update_query = "UPDATE orders 
                     SET status = 'processing', 
                         assigned_delivery_boy = '$delivery_boy_id',
                         otp = '$otp'
                     WHERE order_id = '$order_id' 
                     AND status = 'pending' 
                     AND assigned_delivery_boy IS NULL";
    
    $result = $conn->query($update_query);

    if ($result && $conn->affected_rows > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Order accepted successfully',
            'otp' => $otp
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order already taken or not available']);
    }
    exit();
}

// Fetch delivery stats from the database
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshVeggieMart - Delivery Partner</title>
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

        /* Main content */
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

        /* Orders section */
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

        .order-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .btn {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-weight: bold;
        }

        .btn-accept {
            background-color: #34d399;
            color: white;
        }

        .btn-accept:hover {
            background-color: #34d399;
        }

        .btn-reject {
            background-color: #fac031;
            color: white;
        }

        .btn-reject:hover {
            background-color: #fac031;
        }

        .payment-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .payment-status.paid {
            background-color: #10b981;
            color: white;
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

            .main-content {
                padding: 20px;
            }

            .stats-container {
                grid-template-columns: 1fr;
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
                    <li><a href="delivery_accepted_orders.php"><b>Accepted Orders</b></a></li>
                    <li><a href="logout.php"><b>Logout</b></a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
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

            <!-- Orders Section -->
            <div class="orders-section">
                <h2>Available Orders</h2>
                <div class="orders-grid">
                    <?php if (!empty($available_orders)): ?>
                        <?php foreach ($available_orders as $order): ?>
                            <div class="order-card" data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>">
                                <div class="order-details">
                                    <p><strong><i class="fas fa-receipt"></i> Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></p>
                                    <p><strong><i class="fas fa-user"></i> Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                    <p><strong><i class="fas fa-map-marker-alt"></i> Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                                    <p><strong><i class="fas fa-shopping-basket"></i> Items:</strong> <?php echo htmlspecialchars($order['items']); ?></p>
                                    <p><strong><i class="fas fa-money-bill-wave"></i> Total:</strong> ₹<?php echo htmlspecialchars($order['total_amount']); ?></p>
                                    <p><strong>Payment Status:</strong> 
                                        <span class="payment-status <?php echo $order['payment_method'] === 'online' && $order['payment_status'] === 'success' ? 'paid' : ''; ?>">
                                            <?php 
                                            if ($order['payment_method'] === 'online' && $order['payment_status'] === 'success') {
                                                echo 'PAID';
                                            } else if ($order['payment_method'] === 'cod') {
                                                echo 'Cash on Delivery';
                                            }
                                            ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="order-actions">
                                    <button class="btn btn-accept">Accept Order</button>
                                    <button class="btn btn-reject">Reject</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No available orders at the moment.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        // JavaScript for order handling remains the same as in the previous version
        document.querySelectorAll('.btn-accept').forEach(button => {
    button.addEventListener('click', function() {
        const orderCard = this.closest('.order-card');
        const orderId = orderCard.getAttribute('data-order-id');

        if(confirm(`Are you sure you want to accept order ${orderId}?`)) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=accept_order&order_id=${orderId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the order card
                    orderCard.remove();
                    
                    // Update pending deliveries stat
                    const pendingDeliveriesEl = document.querySelector('.stats-container .stat-card:last-child .number');
                    let currentPending = parseInt(pendingDeliveriesEl.textContent);
                    pendingDeliveriesEl.textContent = currentPending + 1;

                    // Redirect to accepted orders page
                    window.location.href = 'delivery_accepted_orders.php';

                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while accepting the order.');
            });
        }
    });
});

        document.querySelectorAll('.btn-reject').forEach(button => {
            button.addEventListener('click', function() {
                const orderCard = this.closest('.order-card');
                const orderId = orderCard.getAttribute('data-order-id');

                if(confirm(`Are you sure you want to reject order ${orderId}?`)) {
                    orderCard.remove();
                }
            });
        });
    </script>
</body>
</html>