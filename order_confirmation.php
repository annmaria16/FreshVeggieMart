<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$order_id = $_GET['order_id'];

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, oi.*, p.name 
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.order_id = ? AND o.user_id = ?
");
$stmt->bind_param("ss", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($order_items)) {
    die("Order not found.");
}

$order = $order_items[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Order Confirmation</h1>
    <p>Thank you for your order! Your order ID is: <?php echo $order_id; ?></p>
    <h2>Order Details</h2>
    <ul>
        <?php foreach ($order_items as $item): ?>
        <li>
            <?php echo $item['name']; ?> - 
            <?php echo $item['quantity']; ?> × ₹<?php echo number_format($item['price'], 2); ?>
            <?php if ($item['cut_type'] !== 'none'): ?>
                (Cut Type: <?php echo $item['cut_type']; ?> + ₹30)
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <p>Total: ₹<?php echo number_format($order['total_amount'], 2); ?></p>
</body>
</html>