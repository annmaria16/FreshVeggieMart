<?php
session_start();
require_once 'config.php';

// Verify if payment was successful
if (!isset($_SESSION['payment_success']) || !isset($_SESSION['order_details'])) {
    header("Location: index.php");
    exit();
}

$order_details = $_SESSION['order_details'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - FreshVeggieMart</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body {
            background-color: #f3f4f6;
            font-family: 'Arial', sans-serif;
        }
        
        .success-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .success-animation {
            animation: scaleUp 0.5s ease-in-out;
        }

        @keyframes scaleUp {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .success-icon {
            color: #34d399;
            font-size: 80px;
            margin-bottom: 30px;
            animation: bounce 1s ease-in-out;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-30px); }
            60% { transform: translateY(-15px); }
        }

        .success-title {
            color: #34d399;
            font-size: 32px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .order-details {
            margin: 30px 0;
            padding: 25px;
            background: #f8fafc;
            border-radius: 12px;
            border: 2px dashed #e2e8f0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .buttons {
            margin-top: 40px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .primary-button {
            background: #34d399;
            color: white;
        }

        .primary-button:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .secondary-button {
            background: #e5e7eb;
            color: #374151;
        }

        .secondary-button:hover {
            background: #d1d5db;
            transform: translateY(-2px);
        }

        .new-button {
            background: #fac031;
            color: white;
        }

        .new-button:hover {
            background: #e5a92a;
            transform: translateY(-2px);
        }

        .thank-you-message {
            color: #4b5563;
            margin: 20px 0;
            font-size: 1.1em;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="success-container success-animation">
        <i class="fas fa-check-circle success-icon"></i>
        <h1 class="success-title">Payment Successful!</h1>
        <p class="thank-you-message">Thank you for shopping with FreshVeggieMart. Your order has been confirmed and is being processed.</p>
        
        <div class="order-details">
            <div class="detail-row">
                <strong>Order ID:</strong>
                <span><?php echo htmlspecialchars($order_details['order_id']); ?></span>
            </div>
            <div class="detail-row">
                <strong>Payment ID:</strong>
                <span><?php echo htmlspecialchars($order_details['payment_id']); ?></span>
            </div>
            <div class="detail-row">
                <strong>Amount Paid:</strong>
                <span>₹<?php echo number_format($order_details['total_amount'], 2); ?></span>
            </div>
            <div class="detail-row">
                <strong>Date:</strong>
                <span><?php echo date('d M Y, h:i A', strtotime($order_details['payment_date'])); ?></span>
            </div>
        </div>
        
        <div class="buttons">
            <a href="user_orders.php" class="button primary-button">
                <i class="fa-solid fa-carrot"></i>
                View Orders
            </a>
            <a href="generate_receipt.php?order_id=<?php echo htmlspecialchars($order_details['order_id']); ?>" class="button new-button">
                <i class="fas fa-box"></i>
                Receipt
            </a>
            <a href="index.php" class="button secondary-button">
                <i class="fas fa-shopping-cart"></i>
                Continue Shopping
            </a>
            
        </div>
    </div>

    <script>
        // Optional: Add confetti effect
        function createConfetti() {
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.animationDelay = Math.random() * 3 + 's';
                document.body.appendChild(confetti);
            }
        }

        // Call confetti on page load
        window.onload = createConfetti;
    </script>
</body>
</html>

<?php
// Clear the success session data
unset($_SESSION['payment_success']);
unset($_SESSION['order_details']);
?>