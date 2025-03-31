<?php
require_once 'config.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please login first']);
    exit;
}

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    // Prepare and execute the delete query
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("is", $id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        // Return success response
        echo json_encode(['status' => 'success']);
    } else {
        // Return error response if deletion fails
        echo json_encode(['status' => 'error', 'message' => 'Failed to remove item']);
    }
} else {
    // Return error response for invalid request method
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>