<?php
require_once 'config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user details from database
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get cart total and items
$stmt = $conn->prepare("
    SELECT c.*, p.name, p.price, p.net_weight, p.unit,
           (p.price * c.quantity) as subtotal
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$cart_total = array_sum(array_column($cart_items, 'subtotal'));
$delivery_fee = 50; // Fixed delivery fee
$total_amount = $cart_total + $delivery_fee;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $delivery_name = trim($_POST['delivery_name']);
    $delivery_address = trim($_POST['delivery_address']);
    $delivery_district = trim($_POST['delivery_district']);
    $delivery_city = trim($_POST['delivery_city']); // Add city
    $delivery_state = trim($_POST['delivery_state']);
    $delivery_pincode = trim($_POST['delivery_pincode']);
    $delivery_phone = trim($_POST['delivery_phone']);
    $payment_method = $_POST['payment_method'];
    $payment_upi = $_POST['payment_upi'] ?? '';
    
    $errors = [];
    
    // Validation
    if (empty($delivery_name)) $errors[] = "Name is required";
    if (empty($delivery_address)) $errors[] = "Address is required";
    if (empty($delivery_district)) $errors[] = "District is required";
    if (empty($delivery_city)) $errors[] = "City is required";
    if (empty($delivery_state)) $errors[] = "State is required";
    if (!preg_match("/^[0-9]{6}$/", $delivery_pincode)) $errors[] = "Invalid PIN code";
    if (!preg_match("/^[0-9]{10}$/", $delivery_phone)) $errors[] = "Invalid phone number";
    if ($payment_method === 'online' && empty($payment_upi)) $errors[] = "Please select a payment method";
    
    if (empty($errors)) {
        // Generate unique order ID
        $order_id = 'ORD' . time() . rand(100, 999);
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert into orders table
            $stmt = $conn->prepare("
                INSERT INTO orders (
                    order_id, user_id, total_amount, delivery_fee,
                    delivery_name, delivery_address, delivery_district,
                    delivery_city, delivery_state, delivery_pincode, 
                    delivery_phone, payment_method, payment_upi, status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    'pending'
                )
            ");
            
            $stmt->bind_param(
                "ssddssssssss",
                $order_id, $user_id, $total_amount, $delivery_fee,
                $delivery_name, $delivery_address, $delivery_district,
                $delivery_city, $delivery_state, $delivery_pincode,
                $delivery_phone, $payment_method, $payment_upi
            );
            $stmt->execute();
            
            // Move items from cart to order_items
            // Move items from cart to order_items
foreach ($cart_items as $item) {
    $stmt = $conn->prepare("
        INSERT INTO order_items (
            order_id, product_id, quantity, price
        ) VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "siid",
        $order_id, $item['product_id'], 
        $item['quantity'], $item['price']
    );
    $stmt->execute();
}

// Clear user's cart
$stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();

// Always commit the transaction to ensure the order is saved in the database
// regardless of payment method
$conn->commit();

if ($payment_method === 'cod') {
    // Begin a new transaction for payment
    $conn->begin_transaction();
    
    try {
        // Generate unique payment ID for COD
        $payment_id = 'COD' . time() . rand(100, 999);
        
        // Insert payment record for COD
        $stmt = $conn->prepare("
            INSERT INTO payments (
                payment_id, order_id, user_id, amount, 
                payment_method, status
            ) VALUES (
                ?, ?, ?, ?, 
                'cod', 'pending'
            )
        ");
        
        $stmt->bind_param(
            "sssd",
            $payment_id, $order_id, $user_id, $total_amount
        );
        $stmt->execute();
        
        // Commit payment transaction
        $conn->commit();
        
        // Log successful order and payment creation
        error_log("Order $order_id created successfully with COD payment (Payment ID: $payment_id)");
        
        header("Location: order_confirmation.php?order_id=" . $order_id);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = "Error recording payment: " . $e->getMessage();
        error_log("Payment error for order $order_id: " . $e->getMessage());
    }
} else {
    // For online payment, store order details in session
    $_SESSION['razorpay_order_id'] = $order_id;
    $_SESSION['razorpay_amount'] = $total_amount * 100; // Amount in paise
    $_SESSION['razorpay_name'] = $delivery_name;
    $_SESSION['razorpay_email'] = $user['email'] ?? '';
    $_SESSION['razorpay_contact'] = $delivery_phone;
    
}
       } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error processing your order. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - FreshVeggieMart</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #34d399;
            --secondary-color: #2cb67d;
            --border-color: #e5e7eb;
            --background-color: #f9fafb;
            --text-color: #1f2937;
            --error-color: #dc2626;
            --success-color: #059669;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
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
            align-items: center;
            font-size:18px;
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

        .checkout-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 30px;
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 30px;
}
        .main-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .sidebar {
            background: white;
            border-radius: 12px;
            padding: 30px;
            height: fit-content;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1, h2 {
            color: var(--text-color);
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
.section h2 {
    font-size: 1.5rem;
    color: #1f2937;
    font-weight: 600;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--primary-color);
    margin-bottom: 24px;
    position: relative;
}

.sidebar h2 {
    font-size: 1.5rem;
    color: #1f2937;
    font-weight: 600;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--primary-color);
    margin-bottom: 24px;
}

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        input[type="text"],
        input[type="tel"],
        input[type="email"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.2);
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .payment-methods {
            display: grid;
            gap: 15px;
        }

        .payment-option {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-option:hover {
            border-color: var(--primary-color);
            background-color: #f0fdf4;
        }

        .payment-option.selected {
            border-color: var(--primary-color);
            background-color: #f0fdf4;
        }

        .payment-option label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            cursor: pointer;
        }

        .payment-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .upi-options {
            display: none;
            margin-top: 15px;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }

        .cart-summary {
            margin-bottom: 30px;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .cart-item-details {
            flex: 1;
        }

        .cart-item-price {
            text-align: right;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-weight: 500;
        }

        .grand-total {
            font-size: 1.2em;
            font-weight: 600;
            color: var(--primary-color);
            border-top: 2px solid var(--border-color);
            margin-top: 10px;
            padding-top: 10px;
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 15px 30px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .submit-btn:hover {
            background-color: var(--secondary-color);
        }

        .error-message {
            color: var(--error-color);
            background-color: #fee2e2;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .pincode-input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 1024px) {
            .checkout-container {
                grid-template-columns: 1fr;
                padding: 20px;
            }
        }

        @media (max-width: 1024px) {
            .sidebar {
                grid-template-columns: 1fr;
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .row {
                grid-template-columns: 1fr;
            }
        }

        .back-to-cart {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: white;
    color: var(--primary-color);
    border: 2px solid var(--primary-color);
    border-radius: 6px;
    padding: 12px 24px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 20px;
    text-decoration: none;
}

.back-to-cart:hover {
    background-color: var(--primary-color);
    color: white;
}

#razorpay-response {
    margin-top: 20px;
    padding: 15px;
    border-radius: 6px;
    background-color: #f0fdf4;
    display: none;
}

.success-message {
    color: var(--success-color);
    background-color: #d1fae5;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}
    </style>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
<nav>
    <div class="logo">
        <a href="add_to_cart.php"><img src="image/logo8.png" alt="FreshVeggieMart Logo"></a> 
    </div>
    <ul>
        <li><a href="index.php#Home">Home</a></li>
        <li><a href="index.php#Menu">Veggies</a></li>
        <li><a href="index.php#Gallary">Veggie Kit</a></li>
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

    <div class="checkout-container">
        <div class="main-content">
            <h1>Checkout</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form id="checkout-form" method="POST" action="">
                <div class="section">
                    <h2>Delivery Details</h2>
                    <div class="form-group">
                        <label for="delivery_name">Full Name</label>
                        <input type="text" id="delivery_name" name="delivery_name" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="delivery_address">Address</label>
                        <textarea id="delivery_address" name="delivery_address" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="form-group">
                            <label for="delivery_pincode">PIN Code</label>
                            <div class="pincode-input-group">
                                <input type="text" id="delivery_pincode" name="delivery_pincode" value="<?php echo htmlspecialchars($user['pincode'] ?? ''); ?>" required>
                                <div class="spinner" id="pincode-spinner"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="delivery_district">District</label>
                            <input type="text" id="delivery_district" name="delivery_district" value="<?php echo htmlspecialchars($user['district'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="form-group">
                            <label for="delivery_city">City</label>
                            <input type="text" id="delivery_city" name="delivery_city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="delivery_state">State</label>
                            <input type="text" id="delivery_state" name="delivery_state" value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="delivery_phone">Phone Number</label>
                        <input type="tel" id="delivery_phone" name="delivery_phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="section">
                    <h2>Payment Method</h2>
                    <div class="payment-methods">
                        <div class="payment-option" data-method="cod">
                            <input type="radio" id="cod" name="payment_method" value="cod" checked>
                            <label for="cod">
                                <i class="fas fa-money-bill-wave"></i>
                                Cash on Delivery
                            </label>
                        </div>
                        
                        <div class="payment-option" data-method="online">
                            <input type="radio" id="online" name="payment_method" value="online">
                            <label for="online">
                                <i class="fas fa-mobile-alt"></i>
                                Online Payment (UPI)
                            </label>
                        </div>
                        
                        <div class="upi-options" id="upi-options">
                            <div class="payment-option">
                                <input type="radio" id="upi-gpay" name="payment_upi" value="gpay">
                                <label for="upi-gpay">
                                    <img src="image/gpay.png" alt="Google Pay" class="payment-logo">
                                    Google Pay
                                </label>
                            </div>
                            
                            <div class="payment-option">
                                <input type="radio" id="upi-phonepe" name="payment_upi" value="phonepe">
                                <label for="upi-phonepe">
                                    <img src="image/phonepe.jpg" alt="PhonePe" class="payment-logo">
                                    PhonePe
                                </label>
                            </div>
                            
                            <div class="payment-option">
                                <input type="radio" id="upi-paytm" name="payment_upi" value="paytm">
                                <label for="upi-paytm">
                                    <img src="image/paytm.png" alt="Paytm" class="payment-logo">
                                    Paytm
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn" id="place-order-btn">Place Order</button>
                <a href="add_to_cart.php" class="back-to-cart"><i class="fas fa-arrow-left"></i> Back to Cart</a>
            </form>
            
            <div id="razorpay-response"></div>
        </div>
        
        <div class="sidebar">
            <h2>Order Summary</h2>
            <div class="cart-summary">
                <?php if (empty($cart_items)): ?>
                    <p>Your cart is empty</p>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="cart-item-details">
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <p><?php echo htmlspecialchars($item['quantity']); ?> x <?php echo htmlspecialchars($item['net_weight']); ?> <?php echo htmlspecialchars($item['unit']); ?></p>
                            </div>
                            <div class="cart-item-price">
                                <p>₹<?php echo number_format($item['subtotal'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>₹<?php echo number_format($cart_total, 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Delivery Fee</span>
                    <span>₹<?php echo number_format($delivery_fee, 2); ?></span>
                </div>
                <div class="total-row grand-total">
                    <span>Total</span>
                    <span>₹<?php echo number_format($total_amount, 2); ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
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

        // Auto-fill user details and handle pincode lookup
        document.addEventListener('DOMContentLoaded', function() {
            // Handle payment method selection
            const paymentOptions = document.querySelectorAll('.payment-option');
            const upiOptions = document.getElementById('upi-options');
            
            paymentOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const method = this.getAttribute('data-method');
                    
                    // Clear previous selections
                    paymentOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    // Select the radio button
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    
                    // Show/hide UPI options
                    if (method === 'online') {
                        upiOptions.style.display = 'grid';
                    } else {
                        upiOptions.style.display = 'none';
                    }
                });
            });
            
            // Handle pincode lookup
            const pincodeInput = document.getElementById('delivery_pincode');
            const districtInput = document.getElementById('delivery_district');
            const stateInput = document.getElementById('delivery_state');
            const spinner = document.getElementById('pincode-spinner');
            
            pincodeInput.addEventListener('input', function() {
                const pincode = this.value.trim();
                
                if (pincode.length === 6 && /^\d+$/.test(pincode)) {
                    // Show spinner
                    spinner.style.display = 'block';
                    
                    // Call API to get location details
                    fetch(`https://api.postalpincode.in/pincode/${pincode}`)
                        .then(response => response.json())
                        .then(data => {
                            spinner.style.display = 'none';
                            
                            if (data[0].Status === 'Success') {
                                const postOffice = data[0].PostOffice[0];
                                districtInput.value = postOffice.District;
                                stateInput.value = postOffice.State;
                            } else {
                                // Handle invalid pincode
                                districtInput.value = '';
                                stateInput.value = '';
                                alert('Invalid PIN Code. Please check and try again.');
                            }
                        })
                        .catch(error => {
                            spinner.style.display = 'none';
                            console.error('Error fetching pincode data:', error);
                        });
                }
            });
            // Handle Razorpay payment
    const options = {
    key: "rzp_test_4GCxMOoqwqydp6", // Using the provided key ID
    amount: <?php echo isset($_SESSION['razorpay_amount']) ? $_SESSION['razorpay_amount'] : ($total_amount * 100); ?>,
    currency: "INR",
    name: "FreshVeggieMart",
    description: "Order Payment",
    order_id: "<?php echo isset($_SESSION['razorpay_order_id']) ? $_SESSION['razorpay_order_id'] : ''; ?>",
    handler: function (response) {
        document.getElementById('razorpay-response').style.display = 'block';
        document.getElementById('razorpay-response').innerHTML = 
            '<div class="success-message">Payment successful! Processing your order...</div>';
        
        // Send payment information to server
        fetch('process_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: '<?php echo isset($_SESSION['razorpay_order_id']) ? $_SESSION['razorpay_order_id'] : ''; ?>',
                payment_id: response.razorpay_payment_id,
                payment_signature: response.razorpay_signature,
                amount: <?php echo isset($_SESSION['razorpay_amount']) ? $_SESSION['razorpay_amount'] / 100 : $total_amount; ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to order confirmation page
                window.location.href = "order_confirmation.php?order_id=<?php echo isset($_SESSION['razorpay_order_id']) ? $_SESSION['razorpay_order_id'] : ''; ?>&payment_id=" + response.razorpay_payment_id;
            } else {
                document.getElementById('razorpay-response').innerHTML = 
                    '<div class="error-message">Error processing payment: ' + data.message + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('razorpay-response').innerHTML = 
                '<div class="error-message">Error processing payment. Please contact support.</div>';
            console.error('Payment processing error:', error);
        });
    },
    prefill: {
        name: "<?php echo isset($_SESSION['razorpay_name']) ? $_SESSION['razorpay_name'] : htmlspecialchars($user['name'] ?? ''); ?>",
        email: "<?php echo isset($_SESSION['razorpay_email']) ? $_SESSION['razorpay_email'] : htmlspecialchars($user['email'] ?? ''); ?>",
        contact: "<?php echo isset($_SESSION['razorpay_contact']) ? $_SESSION['razorpay_contact'] : htmlspecialchars($user['phone'] ?? ''); ?>"
    },
    theme: {
        color: "#34d399"
    }
};
            const rzp = new Razorpay(options);
            
            // Open Razorpay payment form if online payment is selected on form submit
            document.getElementById('checkout-form').addEventListener('submit', function(e) {
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
                
                if (paymentMethod === 'online') {
                    e.preventDefault();
                    rzp.open();
                }
            });
        });
    </script>
</body>
</html>