<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if cart_id and action are submitted
if (isset($_POST['cart_id']) && isset($_POST['action'])) {
    $cart_id = intval($_POST['cart_id']);
    $action = $_POST['action'];
    $user_id = $_SESSION['user_id'];
    
    try {
        // Verify that the cart item belongs to the current user
        $stmt = $conn->prepare("SELECT c.quantity, p.stock, p.price, p.cutting_price, p.category 
                               FROM cart c 
                               JOIN products p ON c.product_id = p.id 
                               WHERE c.id = ? AND c.user_id = ?");
        $stmt->bind_param("is", $cart_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Cart item not found");
        }
        
        $cart_item = $result->fetch_assoc();
        $current_quantity = $cart_item['quantity'];
        $max_stock = $cart_item['stock'];
        $unit_price = $cart_item['price'];
        
        // Handle the action
        switch ($action) {
            case 'increase':
                if ($current_quantity < $max_stock) {
                    $new_quantity = $current_quantity + 1;
                    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    $stmt->bind_param("ii", $new_quantity, $cart_id);
                    $stmt->execute();
                } else {
                    throw new Exception("Cannot add more due to stock limitations");
                }
                break;
                
            case 'decrease':
                if ($current_quantity > 1) {
                    $new_quantity = $current_quantity - 1;
                    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                    $stmt->bind_param("ii", $new_quantity, $cart_id);
                    $stmt->execute();
                }
                break;
                
            case 'remove':
                $stmt = $conn->prepare("DELETE FROM cart WHERE id = ?");
                $stmt->bind_param("i", $cart_id);
                $stmt->execute();
                
                // Also delete any associated cut types
                $stmt = $conn->prepare("DELETE FROM cart_cut_types WHERE cart_id = ?");
                $stmt->bind_param("i", $cart_id);
                $stmt->execute();
                break;
                
            default:
                throw new Exception("Invalid action");
        }
        
        // Get updated cart data for the response
        $stmt = $conn->prepare("
            SELECT c.id, c.quantity, p.price as unit_price, p.stock, p.cutting_price, 
                   (c.quantity * p.price) as subtotal,
                   GROUP_CONCAT(ct.name SEPARATOR ', ') as cut_types
            FROM cart c 
            INNER JOIN products p ON c.product_id = p.id 
            LEFT JOIN cart_cut_types cct ON c.id = cct.cart_id
            LEFT JOIN cut_types ct ON cct.cut_type_id = ct.id
            WHERE c.user_id = ?
            GROUP BY c.id
        ");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart_items = $result->fetch_all(MYSQLI_ASSOC);
        
        // Calculate totals
        $cart_subtotal = 0;
        $cutting_total = 0;
        $delivery_charge = 0;
        
        foreach ($cart_items as $item) {
            $cart_subtotal += $item['subtotal'];
            if ($item['cut_types']) {
                $cutting_total += $item['cutting_price'];
            }
        }
        
        // Get delivery charge from first item if exists
        if (!empty($cart_items)) {
            $stmt = $conn->prepare("SELECT delivery_charge FROM products WHERE id = (SELECT product_id FROM cart WHERE id = ?)");
            $stmt->bind_param("i", $cart_items[0]['id']);
            $stmt->execute();
            $delivery_result = $stmt->get_result();
            if ($delivery_result->num_rows > 0) {
                $delivery_data = $delivery_result->fetch_assoc();
                $delivery_charge = $delivery_data['delivery_charge'];
            }
        }
        
        $cart_total = $cart_subtotal + $cutting_total + $delivery_charge;
        
        // Prepare response data
        $response = [
            'success' => true,
            'action' => $action
        ];
        
        if ($action !== 'remove') {
            // For increase/decrease, include item-specific data
            $response['quantity'] = $new_quantity;
            $response['subtotal'] = $new_quantity * $unit_price;
            $response['stock'] = $max_stock;
        }
        
        // Include cart totals for all actions
        $response['cart_subtotal'] = $cart_subtotal;
        $response['cutting_total'] = $cutting_total;
        $response['delivery_charge'] = $delivery_charge;
        $response['cart_total'] = $cart_total;
        
        header('Content-Type: application/json');
        echo json_encode($response);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
}
?>