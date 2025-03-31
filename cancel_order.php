<?php
require_once 'config.php';
session_start();

// Check if the user is logged in
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

// Check if the order belongs to the user and is in pending status
$check_query = "SELECT id FROM orders WHERE order_id = ? AND user_id = ? AND status = 'pending'";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ss", $order_id, $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Order cannot be cancelled']);
    exit();
}

// Update order status to cancelled
$update_query = "UPDATE orders SET status = 'cancelled' WHERE order_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("s", $order_id);

if ($update_stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}

$check_stmt->close();
$update_stmt->close();
$conn->close();
?>