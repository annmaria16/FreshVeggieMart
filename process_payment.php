<?php
require_once 'config.php';
session_start();
$transaction_active = true; // Set a flag

try {
    
    // Validate required parameters
    $required_fields = ['razorpay_payment_id', 'order_id', 'amount'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Get and sanitize parameters
    $razorpay_payment_id = filter_input(INPUT_POST, 'razorpay_payment_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $user_id = $_SESSION['user_id'] ?? null;
    $payment_id = 'PAY' . time() . rand(1000, 9999);
    
    if (!$user_id) {
        throw new Exception("User not authenticated");
    }

    // Begin transaction
    $conn->begin_transaction();

    // Insert payment record
    $stmt = $conn->prepare("
        INSERT INTO payments (
            payment_id, order_id, user_id, amount, 
            payment_method, payment_status, razorpay_payment_id
        ) VALUES (?, ?, ?, ?, 'online', 'success', ?)
    ");

    $stmt->bind_param("sssds", 
        $payment_id,
        $order_id,
        $user_id,
        $amount,
        $razorpay_payment_id
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to insert payment record: " . $stmt->error);
    }

    // Update order status
    $order_stmt = $conn->prepare("
        UPDATE orders 
        SET payment_status = 'completed'
        WHERE order_id = ?
    ");

    if (!$order_stmt->bind_param("s", $order_id) || !$order_stmt->execute()) {
        throw new Exception("Failed to update order status");
    }

    // Clear cart from database
    $clear_cart_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $clear_cart_stmt->bind_param("s", $user_id);
    
    if (!$clear_cart_stmt->execute()) {
        throw new Exception("Failed to clear cart");
    }

    // Clear cart from session
    unset($_SESSION['cart_items']);
    
    // Commit transaction
    $conn->commit();
    $transaction_active = false; // Reset flag

    // Store success data in session
    $_SESSION['payment_success'] = true;
    $_SESSION['order_details'] = array(
        'order_id' => $order_id,
        'payment_id' => $payment_id,
        'total_amount' => $amount,
        'payment_date' => date('Y-m-d H:i:s')
    );
    
    // Redirect to success page
    header("Location: payment_success.php");
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
      $conn->rollback();
    }

    // Rollback transaction on error
    if ($transaction_active) { 
        $conn->rollback();
    }

    error_log("Payment processing failed: " . $e->getMessage());
    $_SESSION['error'] = "Payment processing failed: " . $e->getMessage();
    //header("Location: error.php");
    exit();

} finally {
    // Close all prepared statements
    if (isset($stmt)) $stmt->close();
    if (isset($order_stmt)) $order_stmt->close();
    if (isset($clear_cart_stmt)) $clear_cart_stmt->close();
    if (isset($conn)) $conn->close();
} 
?>