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
            padding: 10px 20px;
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

/* Flex styling for centering content */
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

    </style>
</head><br>
<body>
    <section id="Home">
<nav>
    <div class="logo">
        <a href="#Home"><img src="image/logo8.png" alt="Logo"></a> 
    </div>
    <ul>
        <li><a href="#Home"><h1>Home</h1></a></li>
        <li><a href="#Menu"><h1>Veggies</h1></a></li>
        <li><a href="#Gallary"><h1>Veggie Kit</h1></a></li>
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
        <a href="add_to_cart.php"><i class="fa-solid fa-cart-shopping"></i></a>&nbsp;&nbsp;
        <i class="fa-solid fa-user" id="profile-icon"></i>
    </div>
    <div class="nav-icons">
        <div class="search-container">
            <input type="text" class="search-bar" placeholder="Search Products Here">
        </div>
        <div class="dropdown-menu" id="dropdown-menu">
            <a href="profile.php"><i class="fa-solid fa-address-card"></i></i><h4>Profile</h4></a>
            <a href="#orders"><i class="fa-solid fa-carrot"></i><h4>Orders</h4></a>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><h4>Logout</h4></a>
        </div>
    </div>
</div>
</nav>

<?php if (isset($_SESSION['cart_error'])): ?>
    <div class="cart-error-message" style="background-color: #ffebee; color: #d32f2f; padding: 10px; margin: 10px auto; max-width: 600px; border-radius: 5px; text-align: center;">
        <?php 
        echo htmlspecialchars($_SESSION['cart_error']); 
        unset($_SESSION['cart_error']); // Clear the message after displaying it
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
                    '<a href="add_to_cart.php?product_id=' . $product['id'] . '" class="menu_btn">Add to Cart</a>' : 
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
                '<a href="add_to_cart.php?product_id=' . $kit['id'] . '" class="gallary_btn">Add to Cart</a>' : 
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
    //animation//
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
</script>
<script src="cut-types.js">
    // Get DOM elements
const cutTypesTrigger = document.getElementById('cutTypesTrigger');
const gallery = document.querySelector('.gallery');

// Initial state
let isGalleryVisible = false;

// Function to toggle gallery visibility
function toggleGallery(event) {
    event.preventDefault();
    
    if (!isGalleryVisible) {
        // Show gallery
        gallery.style.display = 'grid';
        gallery.style.opacity = '0';
        // Use setTimeout to create a smooth fade-in effect
        setTimeout(() => {
            gallery.style.opacity = '1';
            gallery.style.transition = 'opacity 0.3s ease-in-out';
        }, 10);
    } else {
        // Hide gallery
        gallery.style.opacity = '0';
        setTimeout(() => {
            gallery.style.display = 'none';
        }, 300); // Match the transition duration
    }
    
    isGalleryVisible = !isGalleryVisible;
}

// Function to close gallery when clicking outside
function closeGalleryOnClickOutside(event) {
    if (isGalleryVisible && 
        !gallery.contains(event.target) && 
        !cutTypesTrigger.contains(event.target)) {
        toggleGallery(event);
    }
}

// Event listeners
cutTypesTrigger.addEventListener('click', toggleGallery);
document.addEventListener('click', closeGalleryOnClickOutside);

// Initialize gallery state
gallery.style.display = 'none';
gallery.style.opacity = '0';


</script>
<p><br><center>&copy; 2025 FreshVeggieMart. All rights reserved.</center><br><br></p>
    </footer> 
</body>
</html>