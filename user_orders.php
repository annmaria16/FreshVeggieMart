<?php
require_once 'config.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's orders with order items and product details
$orders_query = "
    SELECT 
        o.id, 
        o.order_id, 
        o.total_amount, 
        o.status, 
        o.created_at, 
        o.delivery_date,
        o.payment_method,
        o.payment_status,
        o.feedback_given
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
";

$stmt = $conn->prepare($orders_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - FreshVeggieMart</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .orders-container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .order-card {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            margin-bottom: 15px;
            padding: 15px;
            position: relative;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .order-header .order-id {
            font-weight: bold;
            color: #4CAF50;
        }

        .order-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9em;
            text-transform: capitalize;
        }

        .order-status.pending {
            background-color: #FFC107;
            color: white;
        }

        .order-status.confirmed {
            background-color: #2196F3;
            color: white;
        }

        .order-status.processing {
            background-color: #9C27B0;
            color: white;
        }

        .order-status.out_for_delivery {
            background-color: #FF9800;
            color: white;
        }

        .order-status.delivered {
            background-color: #4CAF50;
            color: white;
        }

        .order-status.cancelled {
            background-color: #F44336;
            color: white;
        }

        .order-details {
            display: flex;
            justify-content: space-between;
        }

        .order-details-left {
            flex: 1;
        }

        .order-details-right {
            text-align: right;
        }

        .no-orders {
            text-align: center;
            color: #888;
            padding: 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }

        .order-item-details {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin-right: 10px;
        }

        .order-item-left {
            display: flex;
            flex-direction: column;
        }

        .order-item-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .order-item-name {
            font-weight: bold;
            color: #333;
        }

        .order-item-weight {
            color: #666;
            font-size: 0.9em;
        }

        .order-item-quantity, .order-item-subtotal {
            color: #4CAF50;
        }

        .order-item-image img {
            max-width: 80px;
            max-height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }

        .order-reorder, .cancel-btn, .feedback-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .cancel-btn {
            background-color: #F44336;
        }

        .feedback-btn {
            background-color: #2196F3;
        }

        .order-actions {
            display: flex;
            gap: 10px;
        }

        .feedback-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .feedback-modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
        }

        .feedback-rating {
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
            flex-direction: row-reverse;
        }

        .feedback-rating input {
            display: none;
        }

        .feedback-rating label {
            font-size: 30px;
            color: #ddd;
            cursor: pointer;
            margin: 0 5px;
            transition: color 0.3s ease;
        }

        .feedback-rating label:hover,
        .feedback-rating input:checked ~ label {
            color: #fac031;
        }

        .feedback-rating label:hover ~ label {
            color: #fac031;
        }

        .feedback-comments {
            width: 100%;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }

        .submit-feedback-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            padding: 10px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .logo img {
            height: 105px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logout-btn {
            text-decoration: none;
            color: #333;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            border-radius: 4px;
            transition: color 0.3s;
        }

        .logout-btn:hover {
            color: #fac031;
        }

        .logout-btn i {
            font-size: 20px;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
        }

        .action-link {
            display: inline-block;
            padding: 12px 25px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .action-link:hover {
            background-color: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .action-link.secondary {
            background-color: #fac031;
        }

        .action-link.secondary:hover {
            background-color: #e5ad2c;
        }

        .button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .new-button {
            background: #fac031;
            color: white;
        }

        .new-button:hover {
            background: #e5a92a;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">
            <a href="index.php"><img src="image/logo8.png" alt="FreshVeggieMart Logo"></a>
        </div>
        <div class="nav-right">
            <a href="logout.php" class="logout-btn">
                <h3>Logout</h3>
            </a>
        </div>
    </nav>

    <div class="orders-container">
        <h1 style="text-align: center; color: #4CAF50;">My Orders</h1>
        
        <?php if ($orders_result->num_rows > 0): ?>
            <?php while ($order = $orders_result->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-header">
                        <span class="order-id">Order #<?php echo htmlspecialchars($order['order_id']); ?></span>
                        <span class="order-status <?php echo htmlspecialchars(strtolower($order['status'])); ?>">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </span>
                    </div>
                    
                    <div class="order-details">
                        <div class="order-details-left">
                            <p>Ordered on: <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></p>
                            <p>Estimated Delivery: <?php echo date('d M Y, h:i A', strtotime($order['created_at'] . ' +8 hours')); ?>
                        </div>
                        <div class="order-details-right">
                            <p>Payment Method: <?php 
                                echo $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online Payment';
                            ?></p>
                            <p>Payment Status: <?php echo htmlspecialchars(strtoupper($order['payment_status'])); ?></p>
                            <p class="order-total">Total: ₹<?php echo number_format($order['total_amount'], 2); ?></p>
                        </div>
                    </div>

                    <div class="order-items">
                        <div class="order-items-header">Order Items:</div>
                        <?php 
                        // Fetch order items for this specific order
                        $order_items_query = "
                            SELECT 
                                oi.quantity, 
                                p.name, 
                                p.price,
                                oi.cut_type,
                                p.image_path,
                                p.net_weight,
                                p.unit
                            FROM order_items oi
                            JOIN products p ON oi.product_id = p.id
                            WHERE oi.order_id = ?
                        ";
                        $items_stmt = $conn->prepare($order_items_query);
                        $items_stmt->bind_param("s", $order['order_id']);
                        $items_stmt->execute();
                        $order_items_result = $items_stmt->get_result();

                        while ($item = $order_items_result->fetch_assoc()): 
                        ?>
                            <div class="order-item">
                                <div class="order-item-details">
                                    <div class="order-item-left">
                                        <span class="order-item-name">
                                            <?php echo htmlspecialchars($item['name']); ?> 
                                            <?php if (!empty($item['cut_type'])): ?>
                                                (<?php echo htmlspecialchars($item['cut_type']); ?>)
                                            <?php endif; ?>
                                        </span>
                                        <span class="order-item-weight">
                                            <?php 
                                            if (!empty($item['net_weight'])) {
                                                echo htmlspecialchars($item['net_weight'] . ' ' . $item['unit']);
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="order-item-right">
                                        <span class="order-item-quantity">
                                            <?php echo $item['quantity']; ?> x ₹<?php echo number_format($item['price'], 2); ?>
                                        </span>
                                        <span class="order-item-subtotal">
                                            ₹<?php echo number_format($item['quantity'] * $item['price'], 2); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php if (!empty($item['image_path'])): ?>
                                    <div class="order-item-image">
                                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <div class="order-actions">
                        <?php if ($order['status'] === 'pending'): ?>
                            <button class="cancel-btn" onclick="cancelOrder('<?php echo $order['order_id']; ?>')">
                                <i class="fas fa-times"></i> Cancel Order
                            </button>
                        <?php endif; ?>

                        <?php if ($order['status'] === 'delivered' && $order['feedback_given'] == 0): ?>
                            <button class="feedback-btn" onclick="openFeedbackModal('<?php echo $order['order_id']; ?>')">
                                <i class="fas fa-comment"></i> Give Feedback
                            </button>
                        <?php endif; ?>

                        <!-- Reorder button now available for ALL order states -->
                        <button class="order-reorder" onclick="reorderItems('<?php echo $order['order_id']; ?>')">
                            <i class="fas fa-redo"></i> Reorder
                        </button>

                        <?php if ($order['status'] === 'delivered' || $order['status'] == 'confirmed'): ?>
                            <a href="generate_receipt.php?order_id=<?php echo htmlspecialchars($order['order_id']); ?>" class="button new-button">
                                <i class="fas fa-box"></i>
                                Receipt
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-orders">
                <p>You haven't placed any orders yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="action-buttons">
        <a href="index.php" class="action-link">Back to Home</a>
        <?php if ($orders_result->num_rows === 0): ?>
            <a href="index.php" class="action-link secondary">Start Shopping</a>
        <?php endif; ?>
    </div>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="feedback-modal">
        <div class="feedback-modal-content">
            <span class="close-modal" onclick="closeFeedbackModal()">&times;</span>
            <h2>Provide Feedback</h2>
            <div class="feedback-rating">
                <input type="radio" name="rating" id="star5" value="5">
                <label for="star5">★</label>
                <input type="radio" name="rating" id="star4" value="4">
                <label for="star4">★</label>
                <input type="radio" name="rating" id="star3" value="3">
                <label for="star3">★</label>
                <input type="radio" name="rating" id="star2" value="2">
                <label for="star2">★</label>
                <input type="radio" name="rating" id="star1" value="1">
                <label for="star1">★</label>
            </div>
            <textarea class="feedback-comments" id="feedbackComments" placeholder="Write your feedback here (optional)"></textarea>
            <button class="submit-feedback-btn" onclick="submitFeedback()">Submit Feedback</button>
        </div>
    </div>

    <script>
        let currentOrderId = null;

        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order? There will be NO REFUND.')) {
                fetch('cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_id=' + encodeURIComponent(orderId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Order cancelled successfully.');
                        location.reload();
                    } else {
                        alert('Failed to cancel order: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the order.');
                });
            }
        }

        function reorderItems(orderId) {
            fetch('reorder.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + encodeURIComponent(orderId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Items added to cart successfully!');
                    window.location.href = 'add_to_cart.php';
                } else {
                    alert('Failed to reorder: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while reordering.');
            });
        }

        function openFeedbackModal(orderId) {
            currentOrderId = orderId;
            document.getElementById('feedbackModal').style.display = 'block';
        }

        function closeFeedbackModal() {
            document.getElementById('feedbackModal').style.display = 'none';
            resetFeedbackForm();
        }

        function submitFeedback() {
            const rating = document.querySelector('input[name="rating"]:checked');
            const comments = document.getElementById('feedbackComments').value.trim();

            if (!rating) {
                alert('Please select a rating.');
                return;
            }

            fetch('submit_feedback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + encodeURIComponent(currentOrderId) + 
                      '&rating=' + encodeURIComponent(rating.value) + 
                      '&comments=' + encodeURIComponent(comments)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Thank you for your feedback!');
                    location.reload();
                } else {
                    alert('Failed to submit feedback: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting feedback.');
            });
        }

        function resetFeedbackForm() {
            const ratings = document.querySelectorAll('input[name="rating"]');
            ratings.forEach(rating => rating.checked = false);
            document.getElementById('feedbackComments').value = '';
            currentOrderId = null;
        }

        // Close modal if clicked outside
        window.onclick = function(event) {
            const modal = document.getElementById('feedbackModal');
            if (event.target == modal) {
                closeFeedbackModal();
            }
        }
    </script>
</body>
</html>