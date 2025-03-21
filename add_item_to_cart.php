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
        'message' => 'You must be logged in to add items to cart'
    ]);
    exit;
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Check if product_id is set
if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Product ID is required'
    ]);
    exit;
}

$product_id = $_POST['product_id'];
$user_id = $_SESSION['user_id'];
$cut_type = isset($_POST['cut_type']) ? $_POST['cut_type'] : null;

// Get product info from database
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Product not found'
    ]);
    exit;
}

$product = $result->fetch_assoc();

// Check if product is in stock
if ($product['stock'] <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Product is out of stock'
    ]);
    exit;
}

// Removed the mandatory cut type check

try {
    // Check if the product is already in the cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("si", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Product already in cart, update quantity
        $cart_item = $result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + 1;

        // Check if new quantity exceeds stock
        if ($new_quantity > $product['stock']) {
            throw new Exception("Cannot add more of this product due to stock limitations");
        }

        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
        $stmt->execute();
        $cart_id = $cart_item['id'];
    } else {
        // Add new product to cart
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $stmt->bind_param("si", $user_id, $product_id);
        $stmt->execute();
        $cart_id = $conn->insert_id;

        // Add selected cut type to the cart_cut_types table (only if provided)
        if ($product['category'] === 'regular' && !empty($cut_type)) {
            $stmt = $conn->prepare("INSERT INTO cart_cut_types (cart_id, cut_type_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $cart_id, $cut_type);
            $stmt->execute();
        }
    }

    // Get updated cart count
    $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $count_stmt->bind_param("s", $user_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $cart_count = $count_result->fetch_assoc()['count'];

    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Item added to cart successfully',
        'cart_count' => $cart_count
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
?>