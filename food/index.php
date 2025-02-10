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
        <i class="fa-solid fa-cart-shopping"></i>&nbsp;&nbsp;
        <i class="fa-solid fa-user" id="profile-icon"></i>
    </div>
    <div class="nav-icons">
        <div class="search-container">
            <input type="text" class="search-bar" placeholder="Search Products Here">
        </div>
        <div class="dropdown-menu" id="dropdown-menu">
            <a href="#orders"><i class="fa-solid fa-carrot"></i><h4>Orders</h4></a>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><h4>Logout</h4></a>
        </div>
    </div>
</div>
</nav>

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
            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/c.jpg">
                </div>
                <div class="menu_info">
                    <h2>Carrot</h2>
                    <p>
                        Net wt: 250 g
                    </p>
                    <h3>MRP: ₹40</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 
            
            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/potato.jpg">
                </div>
                <div class="menu_info">
                    <h2>Potato</h2>
                    <p>
                       Net wt: 250 g
                    </p>
                    <h3>MRP: ₹25</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/tomato.jpg">
                </div>
                <div class="menu_info">
                    <h2>Tomato</h2>
                    <p>
                        Net wt: 250 g
                    </p>
                    <h3>MRP: ₹25</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/chill.jpg">
                </div>
                <div class="menu_info">
                    <h2>Chilly</h2>
                    <p>
                        Net wt: 250 g
                    </p>
                    <h3>MRP: ₹18</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/peas.jpg">
                </div>
                <div class="menu_info">
                    <h2>Yardlong Beans</h2>
                    <p>
                        Net wt: 250 g
                    </p>
                    <h3>MRP: ₹20</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/beans.jpg">
                </div>
                <div class="menu_info">
                    <h2>Beans</h2>
                    <p>
                       Net wt: 250 g
                    </p>
                    <h3>MRP: ₹22</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/beet.webp">
                </div>
                <div class="menu_info">
                    <h2>Beetroot</h2>
                    <p>
                        Net wt: 250 g
                    </p>
                    <h3>MRP: ₹28</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/cabbage.webp">
                </div>       
                <div class="menu_info">
                    <h2>Cabbage</h2>
                    <p>
                        Net wt: 250 g
                    </p>
                    <h3>MRP: ₹26</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/red cabb.webp">
                </div>
                <div class="menu_info">
                    <h2>Red Cabbage</h2>
                    <p>
                        Net wt: 250 g
                    </p>
                    <h3>MRP: ₹30</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/velli.webp">
                </div>
                <div class="menu_info">
                    <h2>Cucumber</h2>
                    <p>
                       Net wt: 250 g
                    </p>
                    <h3>MRP: ₹19</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/cucumber.jpg">
                </div>
                <div class="menu_info">
                    <h2>Cucumber</h2>
                    <p>
                       Net wt: 250 g
                    <h3>MRP: ₹20</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 


            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/drumstick.jpg">
                </div>
                <div class="menu_info">
                    <h2>Drumstick</h2>
                    <p>
                       Net wt: 250 g
                    <h3>MRP: ₹20</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/Raddish.webp">
                </div>
                <div class="menu_info">
                    <h2>Raddish</h2>
                    <p>
                       Net wt: 250 g
                    <h3>MRP: ₹12</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/ivygourd.webp">
                </div>
                <div class="menu_info">
                    <h2>Ivy Gourd</h2>
                    <p>
                       Net wt: 250 g
                    <h3>MRP: ₹16</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/pumpkin.webp">
                </div>
                <div class="menu_info">
                    <h2>Pumpkin</h2>
                    <p>
                       Net wt: 250 g
                    <h3>MRP: ₹22</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div>

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/bittergourd.webp">
                </div>
                <div class="menu_info">
                    <h2>Bitter Gourd</h2>
                    <p>
                       Net wt: 250 g
                    <h3>MRP: ₹24</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div>
            
            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/red_cheera.jpg">
                </div>
                <div class="menu_info">
                    <h2>Amaranthus</h2>
                    <p>
                       Net wt: 250 g
                    <h3>MRP: ₹28</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/green_cheera.jpg">
                </div>
                <div class="menu_info">
                    <h2>Amaranthus</h2>
                    <p>
                       Net wt: 250 g
                    <h3>MRP: ₹23</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/cauliflower.jpg">
                </div>
                <div class="menu_info">
                    <h2>Cauliflower</h2>
                    <p>
                       Net wt: 250 g
                    <h3>MRP: ₹40</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/mashroom.webp">
                </div>
                <div class="menu_info">
                    <h2>Mashroom</h2>
                    <p>
                       Net wt: 250 g
                    <h3>MRP: ₹30</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div>

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/Brinjal.jpg">
                </div>
                <div class="menu_info">
                    <h2>Brinjal</h2>
                    <p>
                       Net wt: 250 g
                    <h3>MRP: ₹30</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/chinesepot.webp">
                </div>
                <div class="menu_info">
                    <h2>Chinese Potato</h2>
                    <p>
                       Net wt: 250 g
                    <h3>MRP: ₹24</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/ladyfinger.webp">
                </div>
                <div class="menu_info">
                    <h2>Lady Finger</h2>
                    <p>
                       Net wt: 250 g
                    <h3>MRP: ₹25</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/lemon.jpg">
                </div>
                <div class="menu_info">
                    <h2>Lemon</h2>
                    <p>
                       Net wt: 250 g
                    <h3>MRP: ₹50</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 
            
            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/onion.jpeg">
                </div>
                <div class="menu_info">
                    <h2>Onion</h2>
                    <p>
                        Net wt: 250 g
                    </p>
                    <h3>MRP: ₹10</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/smalloni.webp">
                </div>
                <div class="menu_info">
                    <h2>Small Onion</h2>
                    <p>
                       Net wt: 100 g
                    <h3>MRP: ₹18</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/garlic.jpg">
                </div>
                <div class="menu_info">
                    <h2>Garlic</h2>
                    <p>
                       Net wt: 250 g
                    <h3>MRP: ₹100</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/curry.webp">
                </div>
                <div class="menu_info">
                    <h2>Curry Leaves</h2>
                    <p>
                       Net wt: 100 g
                    <h3>MRP: ₹10</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div>

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/mint.jpg">
                </div>
                <div class="menu_info">
                    <h2>Mint Leaves</h2>
                    <p>
                       Net wt: 100 g
                    <h3>MRP: ₹15</h3>
                    <br><br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div> 

            <div class="menu_card">
                <div class="menu_image">
                    <img src="image/coriander.jpg">
                </div>
                <div class="menu_info">
                    <h2>Coriander Leaves</h2>
                    <p>Net wt: 100 g</p>
                    <h3>MRP: ₹16</h3>
                    <br>
                    <a href="#" class="menu_btn">Add to Cart</a>
                </div>
            </div>
        </div>
    </div>


 <!--Veggie Kit-->
   <div class="gallary" id="Gallary">
    <br><br><br>
       <h1>Veggie<span>Kit</span></h1>
      <br>
       <div class="gallary_image_box">
           <div class="gallary_image">
               <img src="image/instant_beetroot.jpg">
   
               <h3>Instant Beetroot Thoran Kit</h3>
               <p>
                   Beetroot thoran kit includes beetroot ( 300 gm ) , grated coconut ( 100 gm ) ,
                    peeled shallot ( 7 pieces ) , chilly ( 5 pieces ) and curry leaves
               </p>
               <a href="#" class="gallary_btn">Add to Cart</a>
           </div>
   
           <div class="gallary_image">
               <img src="image/carrot_thoran.jpg">
   
               <h3>Carrot Thoran Kit</h3>
               <p>
                   Carrot thoran kit includes carrot ( 300 gm ) , grated coconut ( 100 gm ) , peeled shallot ( 7 pieces ) , 
                   chilly ( 5 pieces ) and curry leaves
               </p>
               <a href="#" class="gallary_btn">Add to Cart</a>
           </div>
   
           <div class="gallary_image">
               <img src="image/cheera_thoran.jpg">
   
               <h3>Instant Cheera Thoran Kit</h3>
               <p>
                   Cheera thoran kit includes cheera ( 300gm ) , grated coconut ( 100 gm ) ,
                    peeled shallot ( 6 - 7 pieces ) , chilly ( 4 - 5 pieces )  and Curry leaves
               </p>
               <a href="#" class="gallary_btn">Add to Cart</a>
           </div>
   
        <div class="gallary_image">
            <img src="image/instant_beans_thoran.jpg">

            <h3>Instant Beans Thoran Kit </h3>
            <p>
                Beans thoran kit includes beans ( 300 gm ) , grated coconut ( 100 gm ) , peeled shallot ( 7 pieces ) ,
                 chilly ( 5 pieces ) and curry leaves
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/instant_payar Thoran.jpg">

            <h3>Instant Payar Thoran Kit </h3>
            <p>
                Payar thoran kit includes payar ( 300 gm ) , grated coconut ( 100 gm ) , peeled shallot ( 7 pieces ) ,
                 chilly ( 5 pieces ) and curry leaves
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/instant_cabbage.jpg">

            <h3>Instant Cabbage Thoran Kit </h3>
            <p>
                Cabbage thoran kit includes cabbage ( 300 gm ) , grated coconut ( 100 gm ) , peeled shallot ( 7 pieces ) ,
                chilly ( 5 pieces ) and curry leaves
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/instant_kovakka_kit.jpg">

            <h3>Instant Kovakka Thoran Kit </h3>
            <p>
                Kovakka thoran kit includes kovakka ( 300 gm ) , grated coconut ( 100 gm ) , peeled shallot ( 7 pieces ) ,
                 chilly ( 5 pieces ) and curry leaves 
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/instant_muringayila_kit.jpg">

            <h3>Instant Muringayila Thoran Kit </h3>
            <p>
                Muringayila thoran kit includes muringayila ( 300 gm ) , grated coconut ( 100 gm ) , peeled shallot ( 7 pieces ) , 
                chilly ( 5 pieces ) and curry leaves
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/instant_pavakka_thoran_kit.jpg">

            <h3>Instant Pavakka Thoran Kit</h3>
            <p>
                Pavakka thoran kit includes pavakka ( 300 gm ) , grated coconut ( 100 gm ) , peeled shallot ( 7 pieces ) ,
                 chilly ( 5 pieces ) , ginger ( 2 small pieces ) and curry leaves
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>


        <div class="gallary_image">
            <img src="image/instant_vendakka_thoran_kit.jpg">

            <h3>Instant Vendakka Thoran Kit</h3>
            <p>
                Vendakka thoran kit includes vendakka ( 300 gm ) , grated coconut ( 100 gm ) , peeled shallot ( 7 pieces ) ,
                 chilly ( 5 pieces )  and curry leaves
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/instant_mathanga_erissery_kit.jpg">

            <h3>Instant Mathanga Erissery Kit </h3>
            <p>
                Mathanga erissery kit includes mathanga ( 300 gm ) , grated coconut ( 100 gm ) , chilly ( 5 pieces ) and curry leaves
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/instant_vellarikka_pachadi_kit.jpg">

            <h3>Instant Vellarikka Pachadi Kit </h3>
            <p>
                Vellarikka pachadi kit includes vellarikka ( 300 gm ) , grated coconut ( 100 gm ) , chilly ( 5 pieces ) 
                and curry leaves 
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/sambar_kit.jpg">

            <h3>Sambar Kit</h3>
            <p>
                Safe and Fresh vegetables in cut format ready to make Sambar.(OZONE WASHED)
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/avial_kit.jpg">

            <h3>Avial Kit</h3>
            <p>
                Aviyal vegetables in cut format ready to make Aviyal which are Ozone Washed to remove any external pesticide residue .
                 Only Premium Vegetable Cuts.(OZONE WASHED)
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/violet_cabbeage_kit.jpeg">

            <h3>Instant Violet Cabbage Thoran Kit</h3>
            <p>
                Violet  cabbage thoran kit includes cabbage ( 300 gm ) , grated coconut ( 80 gm ) , 
                peeled shallot ( 7 pieces ) , chilly ( 5 pieces ) and curry leaves
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/beans&carrot_thoran.jpeg">

            <h3>Instant Carrot Beans Thoran Kit</h3>
            <p>
                Instant Carrot& Beans Thoran includes Carrot(150gm), Beans(150gm), Grated Coconut(50gm), Shallot, 
                Chilly, Curry leaves(50gm)
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/cauliflower_kit.jpeg">

            <h3>Instant Cauliflower Roast Kit</h3>
            <p>
                Instant Cauliflower Roast Kit includes Cauliflower(300gm), onion(100gm), Tomato, Chilly, Ginger,
                 Garlic, Curry leaves(100gm) 
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/sadhya_kit.jpg">

            <h3>Sadhya Kit</h3>
            <p>
                10 items best for 4-5 persons<br>
               ഈ ഓണം അടുക്കളയിൽ അല്ല , വീട്ടുകാരോടൊപ്പം !!
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/instant_salad_kit.jpeg">

            <h3>Instant Salad Kit</h3>
            <p>
                Instant Salad Kit includes Cucumber, Carrot, Onion, Lemon, Chilly.(OZONE WASHED)
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/instant_kaya_achinga_mezhukkupuratti_kit.jpeg">

            <h3>Instant Kaya-Achinga<br> Mezhukkupuratti Kit</h3>
            <p>
                Instant Kaya-Achinga Mezhukkupuratti includes Payar, Kaya(320gm), Shallot, Chilly, Curry leaves(80gm)   
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/instant_koottucurry_kit.jpeg">

            <h3>Instant Koottucurry Kit</h3>
            <p>
                Koottucurry Kit Includes Chena Cuts , Kaya Cuts, Grated Coconut , Curry Leaves  
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/instant_pineapple_pachadi_kit.jpeg">

            <h3>Instant Pineapple Pachadi Kit</h3>
            <p>
                Instant Pineapple Pachadi Kit includes Pineapple Cuts, Grapes ,Grated Coconut ,Green Chilly ,Curry Leaves 
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/instant_kalan_kit.jpeg">

            <h3>Instant Kalan Kit</h3>
            <p>
                Kalan Kit includes Chena Cuts(400gm), Kaya Cuts(180gm), Grated Coconut(100gm),Curry Leaves ,Green Chilly(20gm)
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/pavakka_theeyal.jpeg">

            <h3>Instant Pavakka Theeyal Kit</h3>
            <p>
                Instant Pavakka Theeyal includes Pavakka(280gm), Grated Coconut(100gm), Shallot, Curry Leaves(20gm)
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/peeled_small_onion.jpeg">

            <h3>Peeled Small Onion </h3>
            <p>
                Baby onions are so charming in different dishes. Yet, peeling them is a pain in the neck/fanny.
                 However, you don't want to waste an entire day peeling the skins from the onions.(OZONE WASHED)
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/cleaned_ginger.jpeg">

            <h3>Cleaned Ginger </h3>
            <p>
                 Studies demonstrate that supplementing with ginger can help increase the movement of food through 
                 your stomach, improve indigestion, decrease bloating, and reduce intestinal cramping.(OZONE WASHED)
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

        <div class="gallary_image">
            <img src="image/peeled_garlic.jpeg">

            <h3>Peeled Garlic</h3>
            <p>
                Several studies show that garlic may help decrease inflammation and boost
                 immune function, which may be due to its content of antioxidants and sulfur-containing compounds like allicin.(OZONE WASHED)
            </p>
            <a href="#" class="gallary_btn">Add to Cart</a>
        </div>

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
                we’re all about making your meals healthier and more delicious with the finest vegetables.<br><br>
                Skip the long supermarket lines and enjoy the vibrant colors, flavors, and aromas of nature’s best, all without
                 leaving your home. Whether you're a seasoned chef or just starting your healthy eating journey, FreshVeggieMart 
                 makes it easy and enjoyable to savor the goodness of fresh produce every day. Let’s bring the farm to your table!
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

