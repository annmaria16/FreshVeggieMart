<?php
require_once 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_POST['order_id'] ?? null;

if (!$order_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid order ID']);
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // First, clear existing cart items for this user
    $clear_cart_query = "DELETE FROM cart WHERE user_id = ?";
    $clear_stmt = $conn->prepare($clear_cart_query);
    $clear_stmt->bind_param("s", $user_id);
    $clear_stmt->execute();

    // Get order items from the previous order
    $order_items_query = "
        SELECT 
            oi.product_id, 
            oi.quantity,
            p.stock,
            oi.cut_type
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ";
    $items_stmt = $conn->prepare($order_items_query);
    $items_stmt->bind_param("s", $order_id);
    $items_stmt->execute();
    $order_items_result = $items_stmt->get_result();

    $added_items = 0;
    while ($item = $order_items_result->fetch_assoc()) {
        // Check if product is in stock
        if ($item['stock'] > 0) {
            $add_to_cart_query = "
                INSERT INTO cart (user_id, product_id, quantity, cut_type) 
                VALUES (?, ?, ?, ?)
            ";
            $add_stmt = $conn->prepare($add_to_cart_query);
            $add_stmt->bind_param("ssis", 
                $user_id, 
                $item['product_id'], 
                $item['quantity'], 
                $item['cut_type']
            );
            $add_stmt->execute();
            $added_items++;
        }
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => "$added_items items added to cart",
        'added_items' => $added_items
    ]);

} catch (Exception $e) {
    // Rollback transaction in case of error
    $conn->rollback();
    
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to reorder: ' . $e->getMessage()
    ]);
}
exit();
?>
