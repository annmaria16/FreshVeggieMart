<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: user_orders.php");
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Fetch order details with payment information
$order_query = "SELECT o.*, p.payment_id, p.payment_method, p.payment_status, u.username, u.email, u.phone
                FROM orders o
                LEFT JOIN payments p ON o.order_id = p.order_id
                LEFT JOIN users u ON o.user_id = u.user_id
                WHERE o.order_id = ? AND o.user_id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ss", $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    header("Location: user_orders.php");
    exit();
}

// Fetch order items
$items_query = "SELECT oi.*, p.name as product_name, p.price, p.image_path 
                       FROM order_items oi 
                       JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("s", $order_id);
$stmt->execute();
$items = $stmt->get_result();

// Calculate totals
$subtotal = 0;
$items_array = array();

while ($item = $items->fetch_assoc()) {
    $items_array[] = $item;
    $item_total = $item['price'] * $item['quantity'];
    $subtotal += $item_total;
}

// Get delivery fee
$delivery_fee = isset($order['delivery_fee']) ? $order['delivery_fee'] : 50.00;

// Calculate total amount
$total_amount = $subtotal + $delivery_fee;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Receipt - #<?php echo htmlspecialchars($order_id); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .hero {
            background-color: #f3f4f6;
            padding: 15px 0;
            margin-bottom: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo img {
            max-height: 60px;
        }

        .header h1 {
            color: #34d399;
            margin: 0;
            font-size: 28px;
        }

        .header-actions {
            display: flex;
            align-items: center;
        }

        .logout-btn {
            background-color: #fac031;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            margin-left: 20px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #e5a92a;
            transform: translateY(-2px);
        }

        .receipt-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }

        .store-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #34d399;
        }

        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .info-group {
            flex: 1;
            margin: 0 10px;
        }

        .info-label {
            font-weight: bold;
            color: #666;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .totals {
            float: right;
            width: 300px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .total-row.final {
            font-weight: bold;
            font-size: 1.1em;
            border-top: 2px solid #ddd;
            margin-top: 10px;
            padding-top: 10px;
        }

        .shipping-address {
            margin-bottom: 30px;
        }

        .print-button {
            background-color: #34d399;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .print-button:hover {
            background-color: #059669;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .status-completed, .status-success {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            color: #666;
        }

        .product-image {
            width: 100px;
            height: 100px;
            border-radius: 4px;
            overflow: hidden;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 5px;
            background-color: #fff;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* Print-specific styles for images */
        @media print {
            .product-image {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            
            .product-image img {
                max-width: 100%;
                height: auto;
            }
        }

        /* Ensure table cells with images have enough width */
        th:first-child,
        td:first-child {
            width: 120px;
            min-width: 120px;
        }
    </style>
</head>
<body>
    <!-- Hero Section with Header -->
    <div class="hero no-print">
        <header class="header">
            <div class="logo">
                <a href="index.php"><img src="image/logo8.png" alt="FreshVeggieMart Logo"></a> 
            </div>
            <h1>Order Receipt</h1>
            <div class="header-actions">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>
    </div>

    <div class="receipt-container">
        <button onclick="window.print();" class="print-button no-print">
            <i class="fas fa-print"></i> Print Receipt
        </button>

        <div class="receipt-header">
            <div class="store-name">FreshVeggieMart</div>
            <div>Order Receipt</div>
        </div>

        <div class="receipt-info">
            <div class="info-group">
                <div class="info-label">Order Details</div>
                <div>Order ID: #<?php echo htmlspecialchars($order_id); ?></div>
                <div>Date: <?php echo date('d M Y', strtotime($order['created_at'])); ?></div>
                <div>Payment Method: <?php echo ucfirst(htmlspecialchars($order['payment_method'] ?? 'N/A')); ?></div>
                <div>Payment Status: 
                    <span class="status-badge status-<?php echo strtolower($order['payment_status'] ?? 'pending'); ?>">
                        <?php echo ucfirst($order['payment_status'] ?? 'Pending'); ?>
                    </span>
                </div>
            </div>
            <div class="info-group">
                <div class="info-label">Customer Details</div>
                <div>Name: <?php echo htmlspecialchars($order['username'] ?? 'N/A'); ?></div>
                <div>Email: <?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></div>
                <div>Phone: <?php echo htmlspecialchars($order['delivery_phone'] ?? $order['phone'] ?? 'N/A'); ?></div>
            </div>
        </div>

        <?php if (!empty($order['delivery_address'])): ?>
        <div class="shipping-address">
            <div class="info-label">Delivery Address</div>
            <div><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></div>
            <?php if (!empty($order['delivery_city']) && !empty($order['delivery_state']) && !empty($order['delivery_pincode'])): ?>
            <div>
                <?php echo htmlspecialchars($order['delivery_city']); ?>, 
                <?php echo htmlspecialchars($order['delivery_state']); ?> - 
                <?php echo htmlspecialchars($order['delivery_pincode']); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Product Image</th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items_array as $item): ?>
                <tr>
                    <td>
                        <div class="product-image">
                            <?php 
                            $image_path = $item['image_path'];
                            if (!empty($image_path)) {
                                // Ensure the path is correctly formatted
                                $image_path = ltrim($image_path, '/');
                                if (!file_exists($image_path)) {
                                    $image_path = "uploads/products/" . basename($image_path);
                                }
                            } else {
                                $image_path = "uploads/products/default.jpg";
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                onerror="this.src='uploads/products/default.jpg'"
                                style="width: 100px; height: 100px; object-fit: cover;">
                        </div>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($item['product_name']); ?>
                        <?php if (!empty($item['cut_type'])): ?>
                            <br><small>(Cut type: <?php echo ucfirst($item['cut_type']); ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>₹<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="total-row">
                <span>Delivery Fee:</span>
                <span>₹<?php echo number_format($delivery_fee, 2); ?></span>
            </div>
            <div class="total-row final">
                <span>Total Amount:</span>
                <span>₹<?php echo number_format($total_amount, 2); ?></span>
            </div>
        </div>

        <div style="clear: both;"></div>

        <div class="footer">
            <p>Thank you for shopping with FreshVeggieMart!</p>
            <p>For any queries, please contact our customer support.</p>
            <p>This is a computer-generated receipt and does not require a signature.</p>
        </div>
    </div>

    <script>
        // Automatically open print dialog when page loads
        window.onload = function() {
            // Small delay to ensure styles are properly loaded
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html> 