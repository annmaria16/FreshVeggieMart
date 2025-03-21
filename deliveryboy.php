<?php
require_once 'config.php';
session_start();

// Check if the delivery boy is logged in, if not then redirect to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'delivery boy') {
    header("Location: login.php");
    exit();
}

// Fetch delivery stats from the database
$today_deliveries = 0;
$completed_deliveries = 0;
$pending_deliveries = 0;

$stats_query = "SELECT 
                COUNT(CASE WHEN status = 'delivered' AND DATE(delivery_date) = CURDATE() THEN 1 END) AS today_deliveries,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) AS completed_deliveries,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending_deliveries
                FROM orders";
$stats_result = $conn->query($stats_query);

if ($stats_result && $stats_result->num_rows > 0) {
    $stats = $stats_result->fetch_assoc();
    $today_deliveries = $stats['today_deliveries'];
    $completed_deliveries = $stats['completed_deliveries'];
    $pending_deliveries = $stats['pending_deliveries'];
}

// Fetch available orders from the database
$available_orders = [];
$orders_query = "SELECT * FROM orders WHERE status = 'pending' ORDER BY delivery_date ASC";
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
    <title>FreshVeggieMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: sans-serif;
        }

        .dashboard {
            width: 100%;
            min-height: 100vh;
            background: #fff;
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

        /* Profile and logout container */
        .dashboard nav .profile-logout-container {
            display: flex;
            align-items: center;
            gap: 20px;
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

        /* Logout link styles */
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
            text-align:center;
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
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .orders-section h2 {
            color: #333;
            margin-bottom: 20px;
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .order-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-5px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .order-id {
            font-weight: bold;
            color: #fac031;
        }

        .order-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            background: #e8f5e9;
            color: #2e7d32;
        }

        .order-details {
            margin-bottom: 15px;
        }

        .order-details p {
            margin: 5px 0;
            color: #666;
        }

        .order-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .btn-accept {
            background: #fac031;
            color: #fff;
        }

        .btn-accept:hover {
            background: #e5ac2c;
        }

        .btn-reject {
            background: #f44336;
            color: #fff;
        }

        .btn-reject:hover {
            background: #d32f2f;
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

            .dashboard nav .profile-logout-container {
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
            <div class="profile-logout-container">
                <div class="profile-info">
                    <div class="welcome-message"><b>
                        Welcome, <?php echo htmlspecialchars($_SESSION['user_id']); ?>!</b>
                        <p>Delivery Partner</p>
                    </div>
                </div>
                <ul>
                    <li><a href="logout.php"><b>Logout</b></a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Stats Section -->
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Today's Deliveries</h3>
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
                            <div class="order-card">
                                <div class="order-header">
                                    <span class="order-id">#<?php echo htmlspecialchars($order['order_id']); ?></span>
                                    <span class="order-status"><?php echo htmlspecialchars($order['status']); ?></span>
                                </div>
                                <div class="order-details">
                                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                    <p><strong>Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                                    <p><strong>Items:</strong> <?php echo htmlspecialchars($order['items']); ?></p>
                                    <p><strong>Total:</strong> ₹<?php echo htmlspecialchars($order['total_amount']); ?></p>
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
        // Add event listeners for accept/reject buttons
        document.querySelectorAll('.btn-accept').forEach(button => {
            button.addEventListener('click', function() {
                const orderCard = this.closest('.order-card');
                const orderId = orderCard.querySelector('.order-id').textContent;
                if(confirm(`Are you sure you want to accept ${orderId}?`)) {
                    orderCard.querySelector('.order-status').textContent = 'Accepted';
                    orderCard.querySelector('.order-status').style.background = '#e8f5e9';
                    this.closest('.order-actions').innerHTML = '<button class="btn btn-accept">Start Delivery</button>';
                }
            });
        });

        document.querySelectorAll('.btn-reject').forEach(button => {
            button.addEventListener('click', function() {
                const orderCard = this.closest('.order-card');
                const orderId = orderCard.querySelector('.order-id').textContent;
                if(confirm(`Are you sure you want to reject ${orderId}?`)) {
                    orderCard.remove();
                }
            });
        });
    </script>
</body>
</html>