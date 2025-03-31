<?php
// Start session
session_start();
require_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'User not logged in'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get updated cart count
$count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
$count_stmt->bind_param("s", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$cart_count = $count_result->fetch_assoc()['count'];

// Return success response
echo json_encode([
    'status' => 'success',
    'cart_count' => $cart_count
]);
?>