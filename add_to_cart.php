<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle the add to cart action from product listing pages
if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $user_id = $_SESSION['user_id'];
    
    try {
        // Check if the product exists and has stock
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Product not found");
        }
        
        $product = $result->fetch_assoc();
        if ($product['stock'] <= 0) {
            throw new Exception("Sorry, this product is out of stock");
        }
        
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
        } else {
            // Add new product to cart
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
            $stmt->bind_param("si", $user_id, $product_id);
            $stmt->execute();
        }
        
        // Redirect back to the referring page or to cart page
        if (isset($_SERVER['HTTP_REFERER']) && 
            (strpos($_SERVER['HTTP_REFERER'], 'add_to_cart.php') !== false || 
             strpos($_SERVER['HTTP_REFERER'], 'Gallary') !== false)) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            header('Location: add_to_cart.php');
        }
        exit;
    } catch (Exception $e) {
        // Handle errors by showing a message
        $_SESSION['cart_error'] = $e->getMessage();
        header('Location: index.php');
        exit;
    }
}

try {
    // Verify products table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'products'");
    if ($check_table->num_rows == 0) {
        throw new Exception("Products table does not exist");
    }

    // Fetch cart items with product details
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT c.id, c.quantity, p.name, p.price as unit_price, p.image_path as image, 
               (c.quantity * p.price) as subtotal,
               p.stock
        FROM cart c 
        INNER JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_items = $result->fetch_all(MYSQLI_ASSOC);

    // Calculate total
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['subtotal'];
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshVeggieMart - Cart</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        /* Your existing CSS styles remain the same */
        .cart-container { width: 100%; min-height: 100vh; padding: 70px 0; }
        .cart-wrapper { width: 90%; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .cart-header { text-align: center; margin-bottom: 40px; }
        .cart-header h1 { font-size: 55px; margin-bottom: 10px; }
        .cart-header h1 span { color: #fac031; font-family: mv boli; }
        .cart-items { background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); overflow: hidden; }
        .cart-item { display: flex; align-items: center; padding: 20px; border-bottom: 1px solid #eee; }
        .cart-item:last-child { border-bottom: none; }
        .item-image { width: 100px; height: 100px; border-radius: 8px; overflow: hidden; margin-right: 20px; }
        .item-image img { width: 100%; height: 100%; object-fit: cover; }
        .item-details { flex: 1; }
        .item-name { font-size: 18px; font-weight: 500; margin-bottom: 5px; }
        .item-price { color: #fac031; font-weight: 600; font-size: 16px; }
        .item-quantity { display: flex; align-items: center; margin: 10px 0; }
        .quantity-btn { background: #fac031; color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 16px; }
        .quantity-input { width: 50px; height: 30px; text-align: center; margin: 0 10px; border: 1px solid #ddd; border-radius: 4px; }
        .item-subtotal { font-weight: 500; color: #333; }
        .remove-item { color: #ff4444; cursor: pointer; margin-left: 20px; font-size: 20px; }
        .cart-summary { margin-top: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; padding: 10px 0; border-bottom: 1px solid #eee; }
        .summary-row:last-child { border-bottom: none; font-weight: 600; font-size: 18px; }
        .checkout-btn { display: block; width: 100%; padding: 15px; background: #fac031; color: white; text-align: center; text-decoration: none; border-radius: 8px; margin-top: 20px; font-size: 18px; font-weight: 500; transition: 0.3s; }
        .checkout-btn:hover { background: #e5ac2c; }
        .empty-cart { text-align: center; padding: 50px 20px; }
        .empty-cart i { font-size: 60px; color: #ddd; margin-bottom: 20px; }
        .empty-cart p { font-size: 18px; color: #666; margin-bottom: 20px; }
        .continue-shopping { display: inline-block; padding: 12px 25px; background: #fac031; color: white; text-decoration: none; border-radius: 5px; transition: 0.3s; }
        .continue-shopping:hover { background: #e5ac2c; }
        .continue-shopping-container {margin-top: 30px; text-align: center;}
        .continue-shopping-btn { display: inline-flex;align-items: center; gap: 10px;padding: 12px 25px;background-color: #4a9c5c;color: white;text-decoration: none;
          border-radius: 5px;transition: 0.3s;font-weight: 500; box-shadow: 0 2px 4px rgba(0,0,0,0.1);}
        .continue-shopping-btn:hover {background-color: #3c8a4e;transform: translateY(-2px);box-shadow: 0 4px 6px rgba(0,0,0,0.15);}
        .continue-shopping-btn i { font-size: 14px;}
        .stock-warning { color: #ff4444; font-size: 14px; margin-top: 5px; }
        .cart-item { transition: opacity 0.3s, background-color 0.5s;}
        .cart-item.updating { background-color: rgba(250, 192, 49, 0.1);}
        .cart-item.fade-out { opacity: 0;}
        .quantity-btn, .quantity-input { transition: all 0.2s;}
        .quantity-btn:active { transform: scale(0.9); }
    
    @keyframes update-highlight {
        0% { background-color: transparent; }
        50% { background-color: rgba(250, 192, 49, 0.2); }
        100% { background-color: transparent; }
    }
    .item-subtotal.updated { animation: update-highlight 1s ease;}
    </style>
</head>
<body>
    <section>
        <nav>
            <div class="logo">
                <a href="index.php"><img src="image/logo8.png" alt="Logo"></a>
            </div>
        </nav>

        <div class="cart-container"><br><br>
            <div class="cart-wrapper">
                <div class="cart-header">
                    <h1>Your <span>Cart</span></h1>
                </div>

                <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Your cart is empty</p>
                    <a href="index.php" class="continue-shopping">Continue Shopping</a>
                </div>
                <?php else: ?>
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item" data-id="<?php echo htmlspecialchars($item['id']); ?>">
                        <div class="item-image">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </div>
                        <div class="item-details">
                            <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <div class="item-price">₹<?php echo number_format($item['unit_price'], 2); ?></div>
                            <div class="item-quantity">
                                <button class="quantity-btn minus">-</button>
                                <input type="number" class="quantity-input" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" max="<?php echo $item['stock']; ?>">
                                <button class="quantity-btn plus">+</button>
                            </div>
                            <?php if ($item['quantity'] > $item['stock']): ?>
                                <div class="stock-warning">Only <?php echo $item['stock']; ?> items available</div>
                            <?php endif; ?>
                            <div class="item-subtotal">Subtotal: ₹<?php echo number_format($item['subtotal'], 2); ?></div>
                        </div>
                        <i class="fas fa-trash remove-item"></i>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Fee</span>
                        <span>₹50.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Total</span>
                        <span>₹<?php echo number_format($total + 50, 2); ?></span>
                    </div>
                    <a href="checkout.php" class="checkout-btn">
                        Proceed to Checkout
                    </a>
                </div>
                
<div class="continue-shopping-container">
    <a href="index.php" class="continue-shopping-btn">
        <i class="fas fa-arrow-left"></i> Continue Shopping
    </a>
</div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <script>
   document.addEventListener('DOMContentLoaded', function() {
    // Quantity buttons
    document.querySelectorAll('.quantity-btn').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.quantity-input');
            const max = parseInt(input.getAttribute('max'));
            let value = parseInt(input.value);
            
            if (this.classList.contains('plus')) {
                value = Math.min(value + 1, max);
            } else {
                value = Math.max(value - 1, 1);
            }
            
            input.value = value;
            
            const cartItem = this.closest('.cart-item');
            const cartId = cartItem.dataset.id;
            const unitPrice = parseFloat(cartItem.querySelector('.item-price').textContent.replace('₹', ''));
            
            // Show immediate feedback
            const subtotalElement = cartItem.querySelector('.item-subtotal');
            subtotalElement.textContent = 'Subtotal: ₹' + (unitPrice * value).toFixed(2);
            
            // Update server
            updateCartItem(cartId, value);
        });
    });

    // Manual quantity input
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const max = parseInt(this.getAttribute('max'));
            let value = parseInt(this.value);
            
            // Ensure value is within bounds
            value = Math.max(1, Math.min(value, max));
            this.value = value;
            
            const cartItem = this.closest('.cart-item');
            const cartId = cartItem.dataset.id;
            const unitPrice = parseFloat(cartItem.querySelector('.item-price').textContent.replace('₹', ''));
            
            // Show immediate feedback
            const subtotalElement = cartItem.querySelector('.item-subtotal');
            subtotalElement.textContent = 'Subtotal: ₹' + (unitPrice * value).toFixed(2);
            
            // Update server
            updateCartItem(cartId, value);
        });
    });

    // Remove item buttons
    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to remove this item?')) {
                const cartItem = this.closest('.cart-item');
                removeCartItem(cartItem.dataset.id);
                
                // Immediately hide the item
                cartItem.style.display = 'none';
                
                // Update totals
                updateTotals();
            }
        });
    });

    // Update cart item on server
    function updateCartItem(id, quantity) {
        fetch('update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                updateTotals();
            } else {
                alert(data.message || 'Error updating cart');
                location.reload(); // Fallback
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating cart');
            location.reload(); // Fallback
        });
    }

    // Remove cart item
    function removeCartItem(id) {
        fetch('remove_cart_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') {
                alert(data.message || 'Error removing item');
                location.reload(); // Fallback
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error removing item');
            location.reload(); // Fallback
        });
    }
    
    // Calculate and update totals
    function updateTotals() {
        let subtotal = 0;
        document.querySelectorAll('.cart-item').forEach(item => {
            if (item.style.display !== 'none') {
                const itemSubtotal = parseFloat(item.querySelector('.item-subtotal').textContent
                    .replace('Subtotal: ₹', '').trim());
                subtotal += itemSubtotal;
            }
        });
        
        const summaryRows = document.querySelectorAll('.summary-row');
        if (summaryRows.length >= 3) {
            summaryRows[0].querySelector('span:last-child').textContent = '₹' + subtotal.toFixed(2);
            const deliveryFee = 50;
            summaryRows[2].querySelector('span:last-child').textContent = '₹' + (subtotal + deliveryFee).toFixed(2);
        }
        
        // Check if cart is empty
        if (document.querySelectorAll('.cart-item').length === 0 || 
            document.querySelectorAll('.cart-item:not([style*="display: none"])').length === 0) {
            window.location.reload(); // Show empty cart message by reloading
        }
    }
});
    </script>
</body>
</html>