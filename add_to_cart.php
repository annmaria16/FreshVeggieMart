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
    $cut_type_ids = isset($_POST['cut_types']) ? $_POST['cut_types'] : [];

    try {
        // Check if the product exists and has stock
        $stmt = $conn->prepare("SELECT stock, cutting_price, delivery_charge, category FROM products WHERE id = ?");
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
            $cart_id = $stmt->insert_id;

            // Add selected cut types to the cart_cut_types table (only for regular products)
            if ($product['category'] === 'regular' && !empty($cut_type_ids)) {
                foreach ($cut_type_ids as $cut_type_id) {
                    if (!empty($cut_type_id)) { // Only add if a cut type was selected
                        $stmt = $conn->prepare("INSERT INTO cart_cut_types (cart_id, cut_type_id) VALUES (?, ?)");
                        $stmt->bind_param("ii", $cart_id, $cut_type_id);
                        $stmt->execute();
                    }
                }
            }
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

    // Fetch cart items with product details and cut types
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT c.id, c.quantity, p.name, p.price as unit_price, p.image_path as image, 
               (c.quantity * p.price) as subtotal, p.stock, p.cutting_price, p.delivery_charge,
               GROUP_CONCAT(ct.name SEPARATOR ', ') as cut_types
        FROM cart c 
        INNER JOIN products p ON c.product_id = p.id 
        LEFT JOIN cart_cut_types cct ON c.id = cct.cart_id
        LEFT JOIN cut_types ct ON cct.cut_type_id = ct.id
        WHERE c.user_id = ?
        GROUP BY c.id
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
        if ($item['cut_types']) {
            $total += $item['cutting_price']; // Add cutting price if cut types are selected
        }
    }
    $total += isset($cart_items[0]) ? $cart_items[0]['delivery_charge'] : 0; // Add delivery charge if cart has items
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - FreshVeggieMart</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: white;
            padding: 15px 5%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 30px;
            margin: 0;
            padding: 0;
        }

        nav ul li a {
           text-decoration: none;
           color: #333;
           font-weight: 600;
           font-size: 20px; 
           transition: color 0.3s;
        }
     

        nav ul li a:hover {
            color:  #fac031;
        }

        .logo img {
            height: 105px;
        }

       .sidebar {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .icon {
              display: flex;
              align-items: center;
              margin-top: 5px;
        }

        .icon > div {
              display: flex;
              align-items: center;
              gap: 22px; 
        }
        .welcome-message {
    font-size: 18px; 
    color: #34d399;
    font-weight: 600;
    text-align: right;
    margin-bottom: 8px;
}
        .fa-solid {
    color: #333;
    font-size: 18px;
    cursor: pointer;
    transition: color 0.3s;
}

        .fa-solid:hover {
            color: #fac031; 
        }
    

        .search-container {
            position: relative;
        }
    
        .search-bar {
            position: absolute;
            top: 30px;
            right: 0;
            width: 0;
            padding: 0;
            border: none;
            border-radius: 4px;
            outline: none;
            transition: width 0.3s, padding 0.3s;
            opacity: 0;
        }
    
        .search-bar.active {
            width: 200px;
            padding: 8px;
            border: 1px solid #ddd;
            opacity: 1;
        } 
    
       
        .dropdown-menu {
            position: absolute;
            top: 95px;
            right: 0px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            width: 200px;
            display: none;
            flex-direction: column;
            z-index: 1000;
            padding: 10px 0;
        }
    
        .dropdown-menu a {
            text-decoration: none;
            color: black;
            padding: 12px 22px;
            font-size: 17px;
            display: flex;
            align-items: center; 
            gap: 10px; 
            border-bottom: 1px solid #ddd;
            transition: background-color 0.2s;
        }
    
        .dropdown-menu a:last-child {
            border-bottom: none;
        }
    
        .dropdown-menu a:hover {
            background-color: #f0f0f0;
        }
    
        .dropdown-menu i {
            font-size: 20px; 
        } 
    
        /* .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
    
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            position: relative;
        }
    
        .close-modal {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
        } */
        
        .cart-container { 
            width: 100%; 
            min-height: 100vh; 
            padding: 30px 0; 
            background-color: #f9f9f9;
        }
        
        .cart-wrapper { 
            width: 90%; 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        
        .cart-header { 
            text-align: center; 
            margin-bottom: 40px; 
        }
        
        .cart-header h1 { 
            font-size: 55px; 
            margin-bottom: 10px; 
            color: #333;
        }
        
        .cart-header h1 span { 
            color: #fac031; 
            font-family: mv boli; 
        }
        
        .cart-items { 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); 
            overflow: hidden; 
            margin-bottom: 25px;
        }
        
        .cart-item { 
            display: flex; 
            align-items: center; 
            padding: 20px; 
            border-bottom: 1px solid #eee; 
            transition: background-color 0.3s;
        }
        
        .cart-item:hover {
            background-color: #f8f8f8;
        }
        
        .cart-item:last-child { 
            border-bottom: none; 
        }
        
        .item-image { 
            width: 100px; 
            height: 100px; 
            border-radius: 8px; 
            overflow: hidden; 
            margin-right: 20px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .item-image img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }
        
        .item-details { 
            flex: 1; 
        }
        
        .item-name { 
            font-size: 18px; 
            font-weight: bold; 
            margin-bottom: 5px; 
            color: #333;
        }
        
        .item-cut-type { 
            font-size: 15px; 
            color: #666; 
            margin-bottom: 5px; 
            font-style: italic;
        }
        
        .item-price { 
            font-size: 16px; 
            color: #333; 
        }
        
        .item-quantity { 
            display: flex; 
            align-items: center; 
            margin: 0 20px; 
        }
        
        .quantity-btn { 
            background: #f5f5f5; 
            border: none; 
            width: 30px; 
            height: 30px; 
            border-radius: 50%; 
            cursor: pointer; 
            transition: background-color 0.3s;
            font-weight: bold;
        }
        
        .quantity-btn:hover {
            background-color: #e0e0e0;
        }
        
        .quantity-input { 
            width: 40px; 
            text-align: center; 
            margin: 0 10px; 
            border: 1px solid #ddd; 
            padding: 5px; 
            border-radius: 4px; 
        }
        
        .item-subtotal { 
            font-size: 18px; 
            font-weight: bold; 
            margin-right: 20px; 
            min-width: 80px; 
            text-align: right; 
            color: #fac031;
        }
        
        .item-remove { 
            color: #e74c3c; 
            cursor: pointer; 
            font-size: 18px; 
            background: none;
            border: none;
        }
        
        .item-remove:hover {
            color: #c0392b;
        }
        
        .cart-summary { 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); 
            padding: 25px; 
            margin-top: 20px; 
        }
        
        .summary-row { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 15px; 
            font-size: 16px;
        }
        
        .summary-row.total { 
            font-size: 22px; 
            font-weight: bold; 
            border-top: 1px solid #eee; 
            padding-top: 15px; 
            margin-top: 15px; 
            color: #333;
        }
        
        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
        }
        
        .back-btn {
            background: #fff;
            color: #fac031;
            border: 2px solid #fac031;
            padding: 12px 25px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        
        .back-btn i {
            margin-right: 8px;
        }
        
        .back-btn:hover {
            background: #faf0d2;
        }
        
        .checkout-btn { 
            background: #fac031; 
            color: #fff; 
            border: none; 
            padding: 12px 30px; 
            border-radius: 50px; 
            font-size: 16px; 
            font-weight: bold;
            cursor: pointer; 
            transition: all 0.3s;
            box-shadow: 0 4px 8px rgba(250, 192, 49, 0.3);
        }
        
        .checkout-btn:hover {
            background: #e8b32e;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(250, 192, 49, 0.4);
        }
        
        .empty-cart { 
            text-align: center; 
            padding: 50px 0; 
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .empty-cart i { 
            font-size: 70px; 
            color: #ddd; 
            margin-bottom: 20px; 
        }
        
        .empty-cart p { 
            font-size: 20px; 
            color: #888; 
            margin-bottom: 30px; 
        }
        
        .continue-shopping { 
            color: #fff; 
            background: #fac031;
            text-decoration: none; 
            font-weight: bold; 
            padding: 12px 25px;
            border-radius: 50px;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .continue-shopping:hover {
            background: #e8b32e;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(250, 192, 49, 0.4);
        }
    </style>
</head>
<body>
<nav>
    <div class="logo">
        <a href="index.php"><img src="image/logo8.png" alt="FreshVeggieMart Logo"></a> 
    </div>
    <ul>
        <li><a href="index.php#Home">Home</a></li>
        <li><a href="index.php#Menu">Veggies</a></li>
        <li><a href="index.php#Gallary">Veggie Kit</a></li>
        <li><a href="cuttypes.html">Cut Types</a></li>
        <li><a href="index.php#About">About</a></li>
        <li><a href="index.php#footer">Contact</a></li>
    </ul>
    <div class="sidebar">
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="welcome-message">
                Welcome, <?php echo htmlspecialchars($_SESSION['user_id']); ?>!
            </div>
        <?php endif; ?>
        <div class="icon">
            <div>
                <i class="fa-solid fa-magnifying-glass search-button"></i>
                <a href="add_to_cart.php"><i class="fa-solid fa-cart-shopping"></i></a>
                <i class="fa-solid fa-user" id="profile-icon"></i>
            </div>
        </div>
        <div class="nav-icons">
            <div class="search-container">
                <input type="text" class="search-bar" placeholder="Search Products Here">
            </div>
            <div class="dropdown-menu" id="dropdown-menu">
                <a href="profile.php"><i class="fa-solid fa-address-card"></i><h4>Profile</h4></a>
                <a href="#orders"><i class="fa-solid fa-carrot"></i><h4>Orders</h4></a>
                <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><h4>Logout</h4></a>
            </div>
        </div>
    </div>
</nav>

    <!-- Cart Section -->
    <div class="cart-container">
        <div class="cart-wrapper">
            <div class="cart-header">
                <h1>Your <span>Cart</span></h1>
                <p>Review your items and proceed to checkout</p>
            </div>

            <?php if (!empty($cart_items)): ?>
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="item-image">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </div>
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <?php if (!empty($item['cut_types'])): ?>
                                    <div class="item-cut-type">Cut type: <?php echo htmlspecialchars($item['cut_types']); ?></div>
                                <?php endif; ?>
                                <div class="item-price">₹<?php echo number_format($item['unit_price'], 2); ?> per unit</div>
                            </div>
                            <div class="item-quantity">
                                <form method="post" action="update_cart.php">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="action" value="decrease" class="quantity-btn" <?php echo ($item['quantity'] <= 1) ? 'disabled' : ''; ?>>-</button>
                                    <input type="text" class="quantity-input" value="<?php echo $item['quantity']; ?>" readonly>
                                    <button type="submit" name="action" value="increase" class="quantity-btn" <?php echo ($item['quantity'] >= $item['stock']) ? 'disabled' : ''; ?>>+</button>
                                </form>
                            </div>
                            <div class="item-subtotal">₹<?php echo number_format($item['subtotal'], 2); ?></div>
                            <form method="post" action="update_cart.php" class="item-remove-form">
                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="action" value="remove" class="item-remove"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format(array_sum(array_column($cart_items, 'subtotal')), 2); ?></span>
                    </div>
                    <?php if (isset($cart_items[0]['delivery_charge']) && $cart_items[0]['delivery_charge'] > 0): ?>
                        <div class="summary-row">
                            <span>Delivery Charge</span>
                            <span>₹<?php echo number_format($cart_items[0]['delivery_charge'], 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php 
                    $cutting_total = 0;
                    foreach ($cart_items as $item) {
                        if ($item['cut_types']) {
                            $cutting_total += $item['cutting_price'];
                        }
                    }
                    if ($cutting_total > 0): 
                    ?>
                        <div class="summary-row">
                            <span>Cutting Charges</span>
                            <span>₹<?php echo number_format($cutting_total, 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="button-group">
                        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Shopping</a>
                        <a href="checkout.php" class="checkout-btn">Proceed to Checkout <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Your cart is empty</p>
                    <a href="index.php" class="continue-shopping">Continue Shopping <i class="fas fa-arrow-right"></i></a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for notifications -->
    <div id="notificationModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div id="modalMessage"></div>
        </div>
    </div>

    <script>
    // Show notification if there's an error
    <?php if (isset($_SESSION['cart_error'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('notificationModal');
            const modalMessage = document.getElementById('modalMessage');
            const closeModal = document.querySelector('.close-modal');
            
            modalMessage.innerHTML = "<?php echo $_SESSION['cart_error']; ?>";
            modal.style.display = "block";
            
            closeModal.addEventListener('click', function() {
                modal.style.display = "none";
            });
            
            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            });
            
            <?php unset($_SESSION['cart_error']); ?>
        });
    <?php endif; ?>
    
    // Dropdown menu functionality for profile icon
    document.addEventListener('DOMContentLoaded', function() {
        const profileIcon = document.getElementById("profile-icon");
        const dropdownMenu = document.getElementById("dropdown-menu");
        
        // Toggle dropdown menu visibility
        profileIcon.addEventListener("click", (e) => {
            e.stopPropagation(); // Prevent click from propagating to window
            dropdownMenu.style.display =
                dropdownMenu.style.display === "flex" ? "none" : "flex";
        });
        
        // Close dropdown when clicking outside the menu
        window.addEventListener("click", () => {
            dropdownMenu.style.display = "none";
        });
        
        // Prevent closing dropdown when clicking inside the menu
        dropdownMenu.addEventListener("click", (e) => {
            e.stopPropagation();
        });
        
        // Toggle the search bar on icon click
        document.querySelector('.search-button').addEventListener('click', function(event) {
            event.stopPropagation(); // Prevent event from propagating to the document
            const searchBar = document.querySelector('.search-bar');
            searchBar.classList.toggle('active');
            if (searchBar.classList.contains('active')) {
                searchBar.focus(); // Focus on the search bar when it becomes active
            }
        });
        
        // Close the search bar when clicking outside
        document.addEventListener('click', function(event) {
            const searchContainer = document.querySelector('.search-container');
            const searchBar = document.querySelector('.search-bar');
            
            if (!searchContainer.contains(event.target)) {
                searchBar.classList.remove('active');
            }
        });
    });
document.addEventListener('DOMContentLoaded', function() {
    // Get all quantity buttons
    const quantityButtons = document.querySelectorAll('.quantity-btn');
    
    // Add event listeners to each button
    quantityButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get the form and form data
            const form = this.closest('form');
            const formData = new FormData();
            
            // Manually add the required parameters
            formData.append('cart_id', form.querySelector('input[name="cart_id"]').value);
            formData.append('action', this.value); // 'increase' or 'decrease'
            
            // Send AJAX request
            fetch('update_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update quantity
                    const quantityInput = form.querySelector('.quantity-input');
                    quantityInput.value = data.quantity;
                    
                    // Update item subtotal
                    const cartItem = form.closest('.cart-item');
                    const subtotalElement = cartItem.querySelector('.item-subtotal');
                    subtotalElement.textContent = '₹' + data.subtotal.toFixed(2);
                    
                    // Update cart summary
                    document.querySelector('.summary-row:first-child span:last-child').textContent = 
                        '₹' + data.cart_subtotal.toFixed(2);
                    
                    document.querySelector('.summary-row.total span:last-child').textContent = 
                        '₹' + data.cart_total.toFixed(2);
                    
                    // Update button states based on new quantity and stock
                    const decreaseBtn = form.querySelector('button[value="decrease"]');
                    const increaseBtn = form.querySelector('button[value="increase"]');
                    
                    decreaseBtn.disabled = data.quantity <= 1;
                    increaseBtn.disabled = data.quantity >= data.stock;
                    
                    // If there are cutting charges, update them too
                    const cuttingChargesRow = document.querySelector('.summary-row:nth-child(3)');
                    if (cuttingChargesRow && data.cutting_total !== undefined) {
                        cuttingChargesRow.querySelector('span:last-child').textContent = 
                            '₹' + data.cutting_total.toFixed(2);
                    }
                } else {
                    // Show error message
                    showNotification(data.message || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.');
            });
        });
    });
    
    // Handle remove button clicks
    const removeButtons = document.querySelectorAll('.item-remove');
    
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const form = this.closest('.item-remove-form');
            const formData = new FormData();
            
            // Manually add the required parameters
            formData.append('cart_id', form.querySelector('input[name="cart_id"]').value);
            formData.append('action', 'remove');
            
            const cartItem = this.closest('.cart-item');
            
            fetch('update_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the item from the DOM
                    cartItem.remove();
                    
                    // Update cart summary
                    document.querySelector('.summary-row:first-child span:last-child').textContent = 
                        '₹' + data.cart_subtotal.toFixed(2);
                    
                    document.querySelector('.summary-row.total span:last-child').textContent = 
                        '₹' + data.cart_total.toFixed(2);
                    
                    // If there are cutting charges, update them too
                    const cuttingChargesRow = document.querySelector('.summary-row:nth-child(3)');
                    if (cuttingChargesRow && data.cutting_total !== undefined) {
                        cuttingChargesRow.querySelector('span:last-child').textContent = 
                            '₹' + data.cutting_total.toFixed(2);
                    }
                    
                    // If cart is now empty, replace with empty cart message
                    if (document.querySelectorAll('.cart-item').length === 0) {
                        const cartItems = document.querySelector('.cart-items');
                        const cartSummary = document.querySelector('.cart-summary');
                        
                        // Create empty cart message
                        const emptyCart = document.createElement('div');
                        emptyCart.className = 'empty-cart';
                        emptyCart.innerHTML = `
                            <i class="fas fa-shopping-cart"></i>
                            <p>Your cart is empty</p>
                            <a href="index.php" class="continue-shopping">Continue Shopping <i class="fas fa-arrow-right"></i></a>
                        `;
                        
                        // Replace content
                        cartItems.parentNode.replaceChild(emptyCart, cartItems);
                        if (cartSummary) {
                            cartSummary.remove();
                        }
                    }
                } else {
                    // Show error message
                    showNotification(data.message || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.');
            });
        });
    });
    
    // Function to show notification
    function showNotification(message) {
        const modal = document.getElementById('notificationModal');
        const modalMessage = document.getElementById('modalMessage');
        
        modalMessage.innerHTML = message;
        modal.style.display = "block";
    }
});

// Function to handle adding products to cart via AJAX
const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
addToCartButtons.forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Get product ID from data attribute or form
        const productId = this.getAttribute('data-product-id');
        let cutType = null;
        
        // If there's a cut type selector, get the selected value
        const cutTypeSelect = document.querySelector('select[name="cut_type"]');
        if (cutTypeSelect) {
            cutType = cutTypeSelect.value;
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('product_id', productId);
        if (cutType) {
            formData.append('cut_type', cutType);
        }
        
        // Send AJAX request
        fetch('add_item_to_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Show success message using the existing notification system
                showNotification(data.message);
                
                // Update cart count in UI if you have a cart counter
                const cartCounter = document.querySelector('.cart-counter');
                if (cartCounter) {
                    cartCounter.textContent = data.cart_count;
                }
            } else {
                // Show error message
                showNotification(data.message || 'An error occurred');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred. Please try again.');
        });
    });
});
</script>
</body>
</html>