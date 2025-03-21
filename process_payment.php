<?php
require_once 'config.php';
session_start();

// Debug incoming data
error_log('POST data: ' . file_get_contents('php://input'));

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

// Check if we received valid JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('Invalid JSON received: ' . file_get_contents('php://input'));
    echo json_encode(['success' => false, 'message' => 'Invalid data format']);
    exit;
}

// Debug the parsed data
error_log('Parsed payment data: ' . print_r($data, true));

// Validate individual fields with detailed error messages
$errors = [];

if (empty($data['order_id'])) {
    $errors[] = 'Order ID is missing';
    error_log('Missing order_id in payment request');
}

if (empty($data['payment_id'])) {
    $errors[] = 'Payment ID is missing';
    error_log('Missing payment_id in payment request');
}

if (empty($data['payment_signature'])) {
    $errors[] = 'Payment signature is missing';
    error_log('Missing payment_signature in payment request');
}

if (!isset($data['amount']) || $data['amount'] <= 0) {
    $errors[] = 'Invalid payment amount';
    error_log('Missing or invalid amount in payment request');
}

// If any validation errors, return them
if (!empty($errors)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Missing payment information: ' . implode(', ', $errors),
        'received_data' => $data
    ]);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Extract variables from data
    $payment_id = $data['payment_id'];
    $order_id = $data['order_id'];
    $amount = $data['amount'];
    $payment_signature = $data['payment_signature'];
    
    // Verify Razorpay signature
    $key_secret = "YOUR_KEY_SECRET"; // Replace with your actual Razorpay key secret
    $generated_signature = hash_hmac('sha256', $payment_id . '|' . $order_id, $key_secret);
    
    if ($generated_signature !== $payment_signature) {
        throw new Exception("Invalid payment signature");
    }
    
    // Check if user_id is in session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User not logged in");
    }
    
    $user_id = $_SESSION['user_id'];
    $response_data = json_encode($data);
    
    // Insert payment record
    $insert_payment = $conn->prepare("
        INSERT INTO payments (
            payment_id, order_id, user_id, amount, 
            payment_method, payment_gateway, payment_reference,
            transaction_id, status, response_data
        ) VALUES (
            ?, ?, ?, ?, 
            'online', 'razorpay', ?,
            ?, 'completed', ?
        )
    ");
    
    $insert_payment->bind_param(
        "sssdsss",
        $payment_id, $order_id, $user_id, $amount,
        $payment_signature, $payment_id, $response_data
    );
    
    $insert_payment->execute();
    
    // Update order status and payment status
    $update_order = $conn->prepare("
        UPDATE orders 
        SET status = 'confirmed', 
            payment_status = 'completed' 
        WHERE order_id = ?
    ");
    $update_order->bind_param("s", $order_id);
    $update_order->execute();
    
    // Commit the transaction
    $conn->commit();
    
    // Clear session variables
    unset($_SESSION['razorpay_order_id']);
    unset($_SESSION['razorpay_amount']);
    unset($_SESSION['razorpay_name']);
    unset($_SESSION['razorpay_email']);
    unset($_SESSION['razorpay_contact']);
    
    error_log("Payment $payment_id for order $order_id processed successfully");
    echo json_encode(['success' => true, 'message' => 'Payment processed successfully']);
    
} catch (Exception $e) {
    // Roll back the transaction
    $conn->rollback();
    error_log("Payment processing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>