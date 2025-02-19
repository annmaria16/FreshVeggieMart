<?php
require_once 'config.php';
session_start();
// Check if the delivery boy is logged in, if not then redirect him to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'delivery_boy') {
    header("Location: login.php");
    exit();
}
?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard - FreshVeggieMart</title>
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

        .dashboard nav .profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .dashboard nav .profile img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
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
                <img src="image/logo8.png" alt="FreshVeggieMart Logo">
            </div>
            <div class="profile">
                <img src="/api/placeholder/50/50" alt="Profile Picture">
                <div class="profile-info">
                    <h3>John Doe</h3>
                    <p>Delivery Partner</p>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Stats Section -->
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Today's Deliveries</h3>
                    <div class="number">12</div>
                </div>
                <div class="stat-card">
                    <h3>Completed Orders</h3>
                    <div class="number">8</div>
                </div>
                <div class="stat-card">
                    <h3>Pending Orders</h3>
                    <div class="number">4</div>
                </div>
            </div>

            <!-- Orders Section -->
            <div class="orders-section">
                <h2>Available Orders</h2>
                <div class="orders-grid">
                    <!-- Order Card 1 -->
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">#ORD001</span>
                            <span class="order-status">New Order</span>
                        </div>
                        <div class="order-details">
                            <p><strong>Customer:</strong> Alice Johnson</p>
                            <p><strong>Address:</strong> 123 Main St, City</p>
                            <p><strong>Items:</strong> 5 items</p>
                            <p><strong>Total:</strong> ₹450</p>
                        </div>
                        <div class="order-actions">
                            <button class="btn btn-accept">Accept Order</button>
                            <button class="btn btn-reject">Reject</button>
                        </div>
                    </div>

                    <!-- Order Card 2 -->
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">#ORD002</span>
                            <span class="order-status">New Order</span>
                        </div>
                        <div class="order-details">
                            <p><strong>Customer:</strong> Bob Smith</p>
                            <p><strong>Address:</strong> 456 Park Ave, City</p>
                            <p><strong>Items:</strong> 3 items</p>
                            <p><strong>Total:</strong> ₹280</p>
                        </div>
                        <div class="order-actions">
                            <button class="btn btn-accept">Accept Order</button>
                            <button class="btn btn-reject">Reject</button>
                        </div>
                    </div>

                    <!-- Order Card 3 -->
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">#ORD003</span>
                            <span class="order-status">New Order</span>
                        </div>
                        <div class="order-details">
                            <p><strong>Customer:</strong> Charlie Brown</p>
                            <p><strong>Address:</strong> 789 Oak Rd, City</p>
                            <p><strong>Items:</strong> 7 items</p>
                            <p><strong>Total:</strong> ₹650</p>
                        </div>
                        <div class="order-actions">
                            <button class="btn btn-accept">Accept Order</button>
                            <button class="btn btn-reject">Reject</button>
                        </div>
                    </div>
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