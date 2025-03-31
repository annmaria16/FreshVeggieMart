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
$rating = $_POST['rating'] ?? null;
$comments = $_POST['comments'] ?? '';

if (!$order_id || !$rating) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit();
}

// Check if the order belongs to the user and is in delivered status
$check_query = "SELECT id FROM orders WHERE order_id = ? AND user_id = ? AND status = 'delivered' AND feedback_given = 0";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ss", $order_id, $_SESSION['user_id']);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Feedback cannot be submitted']);
    exit();
}

// Insert feedback
$insert_query = "INSERT INTO order_feedback (order_id, user_id, rating, comments) VALUES (?, ?, ?, ?)";
$insert_stmt = $conn->prepare($insert_query);
$insert_stmt->bind_param("ssis", $order_id, $_SESSION['user_id'], $rating, $comments);

// Update order to mark feedback as given
$update_query = "UPDATE orders SET feedback_given = 1 WHERE order_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("s", $order_id);

$conn->begin_transaction();

try {
    if (!$insert_stmt->execute()) {
        throw new Exception("Failed to insert feedback");
    }

    if (!$update_stmt->execute()) {
        throw new Exception("Failed to update order feedback status");
    }

    $conn->commit();
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$check_stmt->close();
$insert_stmt->close();
$update_stmt->close();
$conn->close();
?>