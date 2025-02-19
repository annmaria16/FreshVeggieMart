<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Not logged in']));
}

if (!isset($_POST['id']) || !isset($_POST['quantity'])) {
    die(json_encode(['status' => 'error', 'message' => 'Missing parameters']));
}

$cart_id = (int)$_POST['id'];
$quantity = (int)$_POST['quantity'];

if ($quantity < 1) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid quantity']));
}

try {
    // Check stock availability
    $stmt = $conn->prepare("
        SELECT p.stock 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.id = ? AND c.user_id = ?
    ");
    $stmt->bind_param("is", $cart_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die(json_encode(['status' => 'error', 'message' => 'Item not found']));
    }
    
    $stock = $result->fetch_assoc()['stock'];
    
    if ($quantity > $stock) {
        die(json_encode(['status' => 'error', 'message' => 'Not enough stock']));
    }
    
    // Update quantity
    $stmt = $conn->prepare("
        UPDATE cart 
        SET quantity = ? 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("iis", $quantity, $cart_id, $_SESSION['user_id']);
    $stmt->execute();
    
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}