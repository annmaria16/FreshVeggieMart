<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['order_id'])) {
    die("No order ID provided.");
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Add debugging at the top
error_reporting(E_ALL);
ini_set('display_errors', 1);

// After getting order_id and user_id, add debug output
echo "Debug Info:<br>";
echo "Order ID: " . htmlspecialchars($order_id) . "<br>";
echo "User ID: " . htmlspecialchars($user_id) . "<br>";

// Modify the first query section with debug info
$stmt = $conn->prepare("
    SELECT * FROM orders 
    WHERE order_id = ? AND user_id = ?
");
$stmt->bind_param("ss", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Query failed: " . $conn->error);
}

$order = $result->fetch_assoc();

if (!$order) {
    echo "Debug: No order found with these parameters<br>";
    
    // Check if order exists at all
    $check_stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
    $check_stmt->bind_param("s", $order_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $found_order = $check_result->fetch_assoc();
        echo "Order exists but with different user_id: " . $found_order['user_id'] . "<br>";
    } else {
        echo "No order found with this order_id in the database<br>";
    }
    
    // Check the orders table structure
    $table_check = $conn->query("DESCRIBE orders");
    echo "Orders table structure:<br>";
    while ($row = $table_check->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
    
    die("Order not found or unauthorized access.");
}

// Then fetch the order items separately
$stmt = $conn->prepare("
    SELECT oi.*, p.name, p.net_weight, p.unit 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("s", $order_id);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - FreshVeggieMart</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .success-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .success-icon {
            color: #34d399;
            font-size: 48px;
            margin-bottom: 20px;
        }

        .order-details {
            margin-top: 30px;
            border-top: 2px solid #e5e7eb;
            padding-top: 20px;
        }

        .order-items {
            list-style: none;
            padding: 0;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .delivery-details {
            margin-top: 30px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
        }

        .total-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .grand-total {
            font-size: 1.2em;
            font-weight: bold;
            color: #34d399;
        }

        .continue-shopping {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 24px;
            background: #34d399;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s;
        }

        .continue-shopping:hover {
            background: #2cb67d;
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="success-header">
            <i class="fas fa-check-circle success-icon"></i>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your order. Your order has been successfully placed.</p>
            <p>Order ID: <strong><?php echo htmlspecialchars($order_id); ?></strong></p>
        </div>

        <div class="order-details">
            <h2>Order Items</h2>
            <ul class="order-items">
                <?php foreach ($order_items as $item): ?>
                    <li class="order-item">
                        <div>
                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                            <p><?php echo htmlspecialchars($item['quantity']); ?> × 
                               <?php echo htmlspecialchars($item['net_weight'] . ' ' . $item['unit']); ?></p>
                        </div>
                        <div>
                            ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="delivery-details">
            <h2>Delivery Details</h2>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['delivery_name']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
            <p><strong>City:</strong> <?php echo htmlspecialchars($order['delivery_city']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['delivery_phone']); ?></p>
        </div>

        <div class="total-section">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>₹<?php echo number_format($order['total_amount'] - $order['delivery_fee'], 2); ?></span>
            </div>
            <div class="total-row">
                <span>Delivery Fee:</span>
                <span>₹<?php echo number_format($order['delivery_fee'], 2); ?></span>
            </div>
            <div class="total-row grand-total">
                <span>Total Amount:</span>
                <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>

        <a href="index.php" class="continue-shopping">
            <i class="fas fa-shopping-cart"></i> Continue Shopping
        </a>
    </div>
</body>
</html>