<?php
require_once 'config.php';
session_start();
// Check if the user is logged in, if not then redirect him to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}
// Get the username from session
$username = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

// Initialize cart session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Function to get cut type options
function getCutTypeOptions() {
    global $conn;
    $options = '';
    $cut_types = $conn->query("SELECT id, name FROM cut_types ORDER BY name");
    while ($type = $cut_types->fetch_assoc()) {
        $options .= '<option value="' . $type['id'] . '">' . htmlspecialchars($type['name']) . '</option>';
    }
    return $options;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshVeggieMart</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #c4d7aa;
            padding: 5px 20px;
            position: relative;
        }

        nav ul {
            display: flex;
            align-items: center;
            margin-top: -10px;
            padding: 0;
            list-style: none;
        }

        nav .icon-group {
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
        }

        nav .icon-group i {
            font-size: 20px;
            cursor: pointer;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            margin-top: -10px;
        }

        .welcome-message {
            font-size: 19px;
            color: #34d399;
            padding: 5px 10px;
            border-radius: 4px;
            text-align:center;
            margin-top: -10px;
            white-space: nowrap;
        }


.icon > div:first-of-type {
    display: flex;
    gap: 20px;
}

        /* Dropdown menu */
.dropdown-menu {
    position: absolute;
    top: 90px;
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
    padding: 10px 20px;
    font-size: 16px;
    display: flex;
    align-items: center; /* Center items vertically */
    gap: 10px; /* Add space between icon and text */
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
    font-size: 18px; 
}

.cut-type-select {
    width: 80%;
    padding: 8px;
    margin-bottom: 10px;
    margin-left: auto;
    margin-right: auto;
    margin-top: -25px;  
    display: block;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
}
.add-to-cart-form {
    display: flex;
    flex-direction: column;
    border: 3px;
}

/* Notification styles */
.notification {
    position: fixed;
    top: 80px;
    right: 20px;
    background-color: #4CAF50;
    color: white;
    padding: 15px 20px;
    border-radius: 5px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    z-index: 1001;
    max-width: 300px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transform: translateX(400px);
    transition: transform 0.3s ease-in-out;
}

.notification.show {
    transform: translateX(0);
}

.notification.error {
    background-color: #f44336;
}

.notification i {
    margin-right: 10px;
    font-size: 18px;
}

.notification .close-btn {
    cursor: pointer;
    margin-left: 10px;
    font-size: 16px;
}

/* Spinner styles for loading */
.loading-spinner {
    display: none;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 8px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.menu_btn {
    position: relative;
}

.menu_btn.loading .loading-spinner {
    display: inline-block;
}

/* Cart count badge */
.cart-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: #f44336;
    color: white;
    font-size: 12px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.fa-cart-shopping {
    position: relative;
}

/* New search styles */
.search-wrapper {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 300px;
    display: none;
    z-index: 1000;
    top: 75%;
    margin-top: -5px;
}

.search-container {
    width: 100%;
    position: relative;
    background-color: white;
    border-radius: 25px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.search-bar {
    width: 100%;
    padding: 6px 40px 6px 15px;
    border: 2px solid #c4d7aa;
    border-radius: 25px;
    font-size: 14px;
    background-color: white;
    color: #333;
    transition: all 0.3s ease;
}

.search-bar:focus {
    outline: none;
    border-color: #a5c77f;
    box-shadow: 0 0 8px rgba(196, 215, 170, 0.4);
}

.search-button {
    cursor: pointer;
    color: #4a5568;
    transition: color 0.3s ease;
}

.search-button:hover {
    color: #2d3748;
}

/* Adjust the animation for search container */
.search-wrapper.show {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translate(-50%, -30px);
    }
    to {
        opacity: 1;
        transform: translate(-50%, 0);
    }
}

/* Adjust logo position if needed */
.logo {
    margin-top: -10px;
}
    </style>
</head><br>
<body>
    <section id="Home">
<nav>
    <div class="logo">
        <a href="#Home"><img src="image/logo8.png" alt="Logo"></a> 
    </div>
    
    <!-- Add search wrapper here -->
    <div class="search-wrapper">
        <div class="search-container">
            <input type="text" class="search-bar" placeholder="Search Veggies Here...">
        </div>
    </div>
    
    <ul>
        <li><a href="#Home"><h1>Home</h1></a></li>
        <li><a href="#Menu"><h1>Veggies</h1></a></li>
        <li><a href="#Gallary"><h1>Veggie Kit</h1></a></li>
        <li><a href="cuttypes.html"><h1>Cut Types</h1></a></li>
        <li><a href="#About"><h1>About</h1></a></li>
        <li><a href="#footer"><h1>Contact</h1></a></li>
    </ul>
    
    <div class="sidebar">
    <div class="icon">
    <?php if(isset($_SESSION['user_id'])): ?>
        <div class="welcome-message"><b>
            Welcome, <?php echo htmlspecialchars($_SESSION['user_id']); ?>!</b>
        </div>
    <?php endif; ?>&nbsp;&nbsp;&nbsp;
        <i class="fa-solid fa-magnifying-glass search-button"></i>
        <a href="add_to_cart.php" class="cart-icon-link">
    <i class="fa-solid fa-cart-shopping">
        <?php
        // Get the actual count of items in the cart from the database
        $cart_count_query = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
        $cart_count_query->bind_param("s", $_SESSION['user_id']);
        $cart_count_query->execute();
        $cart_count_result = $cart_count_query->get_result();
        $cart_count = $cart_count_result->fetch_assoc()['count'];
        
        // Display the badge if there are items in the cart
        if($cart_count > 0): 
        ?>
        <span class="cart-badge"><?php echo $cart_count; ?></span>
        <?php endif; ?>
    </i>
     </a>&nbsp;&nbsp;
        <i class="fa-solid fa-user" id="profile-icon"></i>
    </div>
    <div class="nav-icons">
        <div class="dropdown-menu" id="dropdown-menu">
            <a href="profile.php"><i class="fa-solid fa-address-card"></i></i><h4>Profile</h4></a>
            <a href="user_orders.php"><i class="fa-solid fa-carrot"></i><h4>Orders</h4></a>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><h4>Logout</h4></a>
        </div>
    </div>
</div>
</nav>

<!-- Notification container -->
<div id="notification-container"></div>

<?php if (isset($_SESSION['cart_error'])): ?>
    <div class="cart-error-message" style="background-color: #ffebee; color: #d32f2f; padding: 10px; margin: 10px auto; max-width: 600px; border-radius: 5px; text-align: center;">
        <?php 
        echo htmlspecialchars($_SESSION['cart_error']); 
        unset($_SESSION['cart_error']);
        ?>
    </div>
<?php endif; ?>

        <div class="main">
            <div class="men_text">
                <h1>Get Fresh<span>Veggies</span><br>in an Easy Way</h1>
            </div>
        
            <div class="main_image">
                <img src="image/intro1.jpg" class="active">
                <img src="image/intro2.jpg">
                <img src="image/intro3.jpg">
            </div>
        </div>
        <div class="main_btn">
            <a href="#Menu" class="main_btn_link">
               <h3>Order Now</h3>
                <i class="fa-solid fa-angle-right"></i>
            </a>
        </div>
    </section>

    <!--Menu-->

    <div class="menu" id="Menu">
        <br><br><br>
        <h1>Today's<span>Veggies</span></h1>

        <div class="menu_box">
            <?php
            // Modified query to exclude veggie kits
            $products = $conn->query("SELECT * FROM products WHERE category != 'veggie_kit' ORDER BY name");
            while ($product = $products->fetch_assoc()) {
                $stock_status = $product['stock'] > 0 ? 'In Stock' : 'Out of Stock';
                $add_to_cart_button = $product['stock'] > 0 ? 
                    '<form class="add-to-cart-form" data-product-id="' . $product['id'] . '" data-product-category="' . $product['category'] . '">' .
                    ($product['category'] === 'regular' ? 
                        '<select name="cut_type" class="cut-type-select">' .
                        '<option value="">Select Cut Type</option>' .
                        getCutTypeOptions() .
                        '</select>' : '') .
                    '<button type="submit" class="menu_btn">
                        <span class="loading-spinner"></span>
                        Add to Cart
                    </button>' .
                    '</form>' : 
                    '<span class="out-of-stock">Out of Stock</span>';
            ?>
            <div class="menu_card">
                <div class="menu_image">
                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>">
                </div>
                <div class="menu_info">
                <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                <p>
                Net Weight: <?php echo htmlspecialchars($product['net_weight']); ?>
                </p>
                <h3>MRP: ₹<?php echo htmlspecialchars($product['price']); ?></h3>
                    <br><br>
                    <?php echo $add_to_cart_button; ?>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>


 <!--Veggie Kit-->
<div class="gallary" id="Gallary">
    <br><br><br>
    <h1>Veggie<span>Kit</span></h1>
    <br>
    <div class="gallary_image_box">
        <?php
        // Get veggie kit products from database
        $veggie_kits = $conn->query("SELECT * FROM products WHERE category = 'veggie_kit' ORDER BY name");
        while ($kit = $veggie_kits->fetch_assoc()) {
            $stock_status = $kit['stock'] > 0 ? 'In Stock' : 'Out of Stock';
            $add_to_cart_button = $kit['stock'] > 0 ? 
                '<button class="gallary_btn add-kit-to-cart" data-product-id="' . $kit['id'] . '">
                    <span class="loading-spinner"></span>
                    Add to Cart
                </button>' : 
                '<span class="out-of-stock">Out of Stock</span>';
        ?>
        <div class="gallary_image">
            <img src="<?php echo htmlspecialchars($kit['image_path']); ?>">
            <h3><?php echo htmlspecialchars($kit['name']); ?></h3>
            <p><?php echo htmlspecialchars($kit['description']); ?></p>
            <div class="price">MRP: ₹<?php echo htmlspecialchars($kit['price']); ?></div>
            <?php echo $add_to_cart_button; ?>
        </div>
        <?php } ?>
    </div>
</div>


<!--About-->
    
<div class="about" id="About"><br>
    <div class="about_main">

        <div class="image">
            <img src="image/abou.png">
        </div>

        <div class="about_text">
            <h1><span>About</span>Us</h1>
            <h3>Why Choose us?</h3>
            <p>
                At FreshVeggieMart, where fresh meets convenience, your go-to place for fresh and tasty vegetables! 
                We bring the garden to your doorstep with a wide selection of hand-picked, farm-fresh veggies. At FreshVeggieMart,
                we're all about making your meals healthier and more delicious with the finest vegetables.<br><br>
                Skip the long supermarket lines and enjoy the vibrant colors, flavors, and aromas of nature's best, all without
                 leaving your home. Whether you're a seasoned chef or just starting your healthy eating journey, FreshVeggieMart 
                 makes it easy and enjoyable to savor the goodness of fresh produce every day. Let's bring the farm to your table!
            </p>
        </div>

    </div>

    <div class="about_btn">
        <a href="#Menu" class="main_btn_link">
            <h3>Order Now</h3>
            <i class="fa-solid fa-angle-right"></i>
        </a>
    </div>

</div>
    <!--Team-->

    <div class="team"><br><br><br>
        <h1>Veggie<span>Experts</span></h1>

        <div class="team_box">
            <div class="profile">
                <img src="image/team2.webp">

                <div class="info">
                    <h2 class="name">Freshness Gurus</h2>
                    <p class="bio">Our Freshness Gurus make sure every vegetable you get is as fresh as if you picked it yourself.
                    </p>

                </div>

            </div>

            <div class="profile">
                <img src="image/team4wash.avif">

                <div class="info">
                    <h2 class="name">Ozone Specialists</h2>
                    <p class="bio">Our Ozone Washing Specialists use ozone technology to preserve vegetable freshness and nutrients.</p>

                </div>

            </div>

            <div class="profile">
                <img src="image/team3del.jpg">

                <div class="info">
                    <h2 class="name">Green Crew </h2>
                    <p class="bio">Our Green Logistics Crew delivers veggies sustainably, reducing our carbon footprint while ensuring freshness.</p>

                </div>

            </div>

            <div class="profile">
                <img src="image/team1.jpg">

                <div class="info">
                    <h2 class="name">Harvest Team</h2>
                    <p class="bio">Our Harvest Coordinators handpick peak-quality vegetables for the freshest produce.</p>

                </div>

            </div>

        </div>

    </div>
        <!--Footer-->

        <footer id="footer">
        <div class="footer_main">
            <div class="footer_tag">
                <h2>Contact</h2>
                <p>+94 12 3456 789</p>
                <p>+94 25 5568456</p>
                <p>freshveggie123@gmail.com</p>
            </div>

            <div class="footer_tag">
                <h2>Our Service</h2>
                <p>Ozone Wash</p>
                <p>Easy Payments</p>
                <p>Eco-Friendly Packaging </p>
                <p>Fresh Vegetable Delivery </p>
            </div>
        </div>

        <div>
            <p><center>| Kottayam |</center></p><br>
        </div>

        <div>
            <p><center>|<a href="#Home"> Home </a>|
                <a href="#About"> About </a>|
                <a href="#Menu"> Veggies </a>|
                <a href="#Gallary"> Veggie Kit </a>|</center>
            </p>
        </div>

<script>
    // Animation code
    let currentIndex = 0;
    const images = document.querySelectorAll('.main_image img');
    const totalImages = images.length;

    function showNextImage() {
        images[currentIndex].classList.remove('active');
        currentIndex = (currentIndex + 1) % totalImages;
        images[currentIndex].classList.add('active');
    }

    setInterval(showNextImage, 2000);  // Change image every 2 seconds

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

    // Function to show notification
    function showNotification(message, type = 'success') {
        const notificationContainer = document.getElementById('notification-container');
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'notification ' + type;
        
        // Icon based on notification type
        let icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        notification.innerHTML = `
            <i class="fas ${icon}"></i>
            <span>${message}</span>
            <span class="close-btn">&times;</span>
        `;
        
        notificationContainer.appendChild(notification);
        
        // Show notification with animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Close button functionality
        notification.querySelector('.close-btn').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
        
        // Auto-close after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 3000);
    }

    // Update cart badge count
    function updateCartBadge(count) {
    const cartIcon = document.querySelector('.fa-cart-shopping');
    let badge = cartIcon.querySelector('.cart-badge');
    
    // Always show the badge, even if count is 0
    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'cart-badge';
            cartIcon.appendChild(badge);
        }
        badge.textContent = count;
    } else if (count === 0) {
        // If count is explicitly 0, remove the badge
        if (badge) {
            badge.remove();
        }
    }
}

    // AJAX function to add items to cart
    function addToCart(productId, cutType = null) {
        // Show debugging information
        console.log('Adding to cart - Product ID:', productId, 'Cut Type:', cutType);
        
        const formData = new FormData();
        formData.append('product_id', productId);
        
        if (cutType) {
            formData.append('cut_type', cutType);
        }
        
        return fetch('add_item_to_cart.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin' // Important for session cookies
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.status === 'success') {
                showNotification(data.message);
                updateCartBadge(data.cart_count);
                return data;
            } else {
                showNotification(data.message || 'Error adding item to cart', 'error');
                throw new Error(data.message || 'Error adding item to cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred. Please try again.', 'error');
            throw error;
        });
    }

    // Event delegation for regular veggie add to cart forms
document.addEventListener('submit', function(event) {
    if (event.target.classList.contains('add-to-cart-form')) {
        event.preventDefault();
        
        const form = event.target;
        const productId = form.getAttribute('data-product-id');
        const productCategory = form.getAttribute('data-product-category');
        const submitButton = form.querySelector('button[type="submit"]');
        
        // Add loading state
        submitButton.classList.add('loading');
        
        let cutType = null;
        if (productCategory === 'regular') {
            const cutTypeSelect = form.querySelector('select[name="cut_type"]');
            cutType = cutTypeSelect.value;
            
        }
        
        // Debug information
        console.log('Form submitted - Product ID:', productId, 'Category:', productCategory, 'Cut Type:', cutType);
        
        addToCart(productId, cutType)
            .catch(error => console.error('Add to cart error:', error))
            .finally(() => {
                submitButton.classList.remove('loading');
            });
    }
});

    // Event listeners for veggie kit buttons
    document.querySelectorAll('.add-kit-to-cart').forEach(button => {
        button.addEventListener('click', function(event) {
            const productId = this.getAttribute('data-product-id');
            
            // Debug information
            console.log('Kit button clicked - Product ID:', productId);
            
            this.classList.add('loading');
            
            addToCart(productId)
                .catch(error => console.error('Add kit to cart error:', error))
                .finally(() => {
                    this.classList.remove('loading');
                });
        });
    });

    // Clear the existing event listeners for search button and re-add them
    document.querySelector('.search-button').removeEventListener('click', function(){});

    // Enhanced search button click handler
    document.querySelector('.search-button').addEventListener('click', function(event) {
        event.stopPropagation();
        const searchWrapper = document.querySelector('.search-wrapper');
        
        if (!searchWrapper.classList.contains('show')) {
            searchWrapper.classList.add('show');
            document.querySelector('.search-bar').focus();
        } else {
            searchWrapper.classList.remove('show');
            document.querySelector('.search-bar').value = '';
            performSearch('');
        }
    });

    // Make sure the search bar input event is working
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, setting up search');
        const searchBar = document.querySelector('.search-bar');
        
        if (searchBar) {
            searchBar.addEventListener('input', function() {
                console.log('Search input:', this.value);
                performSearch(this.value);
            });
        } else {
            console.error('Search bar element not found');
        }
    });

    // Document click handler to close search when clicking outside
    document.addEventListener('click', function(event) {
        const searchWrapper = document.querySelector('.search-wrapper');
        const searchButton = document.querySelector('.search-button');
        
        if (!searchWrapper.contains(event.target) && event.target !== searchButton) {
            searchWrapper.classList.remove('show');
            document.querySelector('.search-bar').value = '';
            performSearch('');
        }
    });

    // Function to calculate similarity between two strings
    function calculateSimilarity(str1, str2) {
        str1 = str1.toLowerCase();
        str2 = str2.toLowerCase();
        
        // If strings are equal, return 1
        if (str1 === str2) return 1;
        
        // If one string contains the other, return 0.8
        if (str1.includes(str2) || str2.includes(str1)) return 0.8;
        
        // Calculate Levenshtein distance
        const matrix = [];
        
        for (let i = 0; i <= str1.length; i++) {
            matrix[i] = [i];
        }
        
        for (let j = 0; j <= str2.length; j++) {
            matrix[0][j] = j;
        }
        
        for (let i = 1; i <= str1.length; i++) {
            for (let j = 1; j <= str2.length; j++) {
                if (str1[i-1] === str2[j-1]) {
                    matrix[i][j] = matrix[i-1][j-1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i-1][j-1] + 1,  // substitution
                        matrix[i][j-1] + 1,    // insertion
                        matrix[i-1][j] + 1     // deletion
                    );
                }
            }
        }
        
        // Convert distance to similarity score (0 to 1)
        const maxLength = Math.max(str1.length, str2.length);
        const similarity = 1 - (matrix[str1.length][str2.length] / maxLength);
        
        return similarity;
    }

    // Updated performSearch function with fuzzy matching
    function performSearch(query) {
        console.log('Performing search for:', query);
        query = query.toLowerCase().trim();
        
        const menuCards = document.querySelectorAll('.menu_card');
        const galleryCards = document.querySelectorAll('.gallary_image');
        
        let visibleCount = 0;
        const similarityThreshold = 0.4; // Adjust this value to control matching sensitivity
        
        // Function to check word similarity
        function hasSimularWord(text, searchQuery) {
            const words = text.split(' ');
            const queryWords = searchQuery.split(' ');
            
            for (const word of words) {
                for (const queryWord of queryWords) {
                    if (queryWord.length > 2 && calculateSimilarity(word, queryWord) > similarityThreshold) {
                        return true;
                    }
                }
            }
            return false;
        }
        
        // Filter regular products
        menuCards.forEach(card => {
            const productName = card.querySelector('h2').textContent.toLowerCase();
            
            if (query === '' || 
                productName.includes(query) || 
                hasSimularWord(productName, query)) {
                
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Filter veggie kits
        galleryCards.forEach(card => {
            const kitName = card.querySelector('h3').textContent.toLowerCase();
            
            if (query === '' || 
                kitName.includes(query) || 
                hasSimularWord(kitName, query)) {
                
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Handle no results message
        let noResultsMessage = document.getElementById('no-results-message');
        if (!noResultsMessage) {
            noResultsMessage = document.createElement('div');
            noResultsMessage.id = 'no-results-message';
            noResultsMessage.style.textAlign = 'center';
            noResultsMessage.style.padding = '20px';
            noResultsMessage.style.fontSize = '18px';
            noResultsMessage.style.color = '#888';
            
            const menuSection = document.getElementById('Menu');
            menuSection.parentNode.insertBefore(noResultsMessage, menuSection.nextSibling);
        }
        
        if (visibleCount === 0 && query !== '') {
            noResultsMessage.innerHTML = `
                <p>No products found matching "<strong>${query}</strong>"</p>
                <p>Try searching with a different keyword</p>
            `;
            noResultsMessage.style.display = 'block';
        } else {
            noResultsMessage.style.display = 'none';
        }
    }
</script>

<p><br><center>&copy; 2025 FreshVeggieMart. All rights reserved.</center><br><br></p>
    </footer> 
</body>
</html>