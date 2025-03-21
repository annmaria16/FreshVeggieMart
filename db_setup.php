<?php
require_once 'config.php';

// Create initial connection without database
$conn = mysqli_connect($servername, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS veggies";
if (mysqli_query($conn, $sql)) {
    echo "Database 'veggies' created successfully<br>";
} else {
    die("Error creating database: " . mysqli_error($conn));
}

// Select the veggie database
mysqli_select_db($conn, "veggies");

// Create users table with updated structure
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(15),
    address TEXT,
    city VARCHAR(50),
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin', 'delivery boy') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    id_proof VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'users' created successfully<br>";
} else {
    die("Error creating users table: " . mysqli_error($conn));
}

// Create products table
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    image_path VARCHAR(255),
    unit ENUM('g', 'kg') DEFAULT 'kg',
    description TEXT,
    category VARCHAR(50) DEFAULT 'regular',
    cutting_price DECIMAL(10,2) DEFAULT 30.00, -- Added cutting price
    delivery_charge DECIMAL(10,2) DEFAULT 50.00, -- Added delivery charge
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'products' created successfully<br>";
} else {
    die("Error creating products table: " . mysqli_error($conn));
}
// description
$check_columns = [
    'description' => 'TEXT',
    'category' => 'VARCHAR(50) DEFAULT "regular"'
];

foreach ($check_columns as $column => $definition) {
    $check_query = "SHOW COLUMNS FROM products LIKE '$column'";
    $result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($result) == 0) {
        $alter_query = "ALTER TABLE products ADD COLUMN $column $definition";
        if (mysqli_query($conn, $alter_query)) {
            echo "Added $column column to products table<br>";
        } else {
            echo "Error adding $column column: " . mysqli_error($conn) . "<br>";
        }
    }
}


    if (mysqli_num_rows($result) == 0) {
        // Insert new product - removed 'image' column from the INSERT query
        $insert_query = "INSERT INTO products (name, description, price, stock, image_path, unit, category) 
                         VALUES (
                            '" . mysqli_real_escape_string($conn, $kit['name']) . "',
                            '" . mysqli_real_escape_string($conn, $kit['description']) . "',
                            " . (float)$kit['price'] . ",
                            " . (int)$kit['stock'] . ",
                            '" . mysqli_real_escape_string($conn, $kit['image_path']) . "',
                            '" . mysqli_real_escape_string($conn, $kit['unit']) . "',
                            '" . mysqli_real_escape_string($conn, $kit['category']) . "'
                         )";
        
        if (mysqli_query($conn, $insert_query)) {
            echo "Added " . htmlspecialchars($kit['name']) . " to products table<br>";
        } else {
            echo "Error adding " . htmlspecialchars($kit['name']) . ": " . mysqli_error($conn) . "<br>";
        }
    }
    // Modified query to exclude veggie kits
$products = $conn->query("SELECT * FROM products WHERE category != 'veggie_kit' ORDER BY name");

echo "All veggie kit products have been added or updated in the database.<br>";

$check_query = "SHOW COLUMNS FROM products WHERE Field = 'unit'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    if (strpos($row['Type'], 'enum') !== false) {
        // Alter the column type if it's an enum
        $alter_query = "ALTER TABLE products MODIFY COLUMN unit VARCHAR(10) DEFAULT 'kg'";
        if (mysqli_query($conn, $alter_query)) {
            echo "Modified unit column in products table to support numerical values<br>";
        } else {
            echo "Error modifying unit column: " . mysqli_error($conn) . "<br>";
        }
    }
}

//net_weight
$check_query = "SHOW COLUMNS FROM products LIKE 'net_weight'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) == 0) {
    // Add the net_weight column
    $alter_query = "ALTER TABLE products ADD COLUMN net_weight VARCHAR(20) DEFAULT NULL AFTER stock";
    if (mysqli_query($conn, $alter_query)) {
        echo "Added net_weight column to products table<br>";
    } else {
        echo "Error adding net_weight column: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "net_weight column already exists<br>";
}

// Create cut_types table
$sql = "CREATE TABLE IF NOT EXISTS cut_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'cut_types' created successfully<br>";
} else {
    die("Error creating cut_types table: " . mysqli_error($conn));
}

// Insert default cut types
$cut_types = [
    ['Thin Strips', 'Thinly sliced strips', 'image/thin_strips.jpg'],
    ['Thick Strips', 'Thickly sliced strips', 'image/thick_strips.jpg'],
    ['Rectangular Cut', 'Rectangular pieces', 'image/rectangular_cut.jpg'],
    ['Matchstick Cut', 'Matchstick-shaped pieces', 'image/matchstick_cut.jpeg'],
    ['Rolled Cut', 'Rolled pieces', 'image/rolled_cut.jpg'],
    ['Large Dice', 'Large diced pieces', 'image/large_dice.jpg'],
    ['Medium Dice', 'Medium diced pieces', 'image/medium_dice.webp'],
    ['Small Dice', 'Small diced pieces', 'image/small_dice.jpg'],
    ['Slice', 'Sliced pieces', 'image/slice.jpg'],
    ['Shredding', 'Shredded pieces', 'image/shredding_cut.webp'],
    ['Wedges', 'Wedge-shaped pieces', 'image/wedges_cut.jpg'],
    ['Angular Cut', 'Angular pieces', 'image/angular_cut.jpg'],
    ['Mince', 'Minced pieces', 'image/mince.jfif'],
    ['Grated', 'Grated pieces', 'image/grated.jpg']
];

foreach ($cut_types as $cut_type) {
    $name = $cut_type[0];
    $description = $cut_type[1];
    $image_path = $cut_type[2];

    // Check if the cut type already exists
    $check_query = "SELECT id FROM cut_types WHERE name = '$name'";
    $result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($result) == 0) {
        // Insert the cut type if it doesn't exist
        $insert_query = "INSERT INTO cut_types (name, description, image_path) 
                         VALUES ('$name', '$description', '$image_path')";
        if (mysqli_query($conn, $insert_query)) {
            echo "Added cut type: $name<br>";
        } else {
            echo "Error adding cut type: " . mysqli_error($conn) . "<br>";
        }
    }
}

// Create product_cut_types table (many-to-many relationship)
$sql = "CREATE TABLE IF NOT EXISTS product_cut_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    cut_type_id INT NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (cut_type_id) REFERENCES cut_types(id)
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'product_cut_types' created successfully<br>";
} else {
    die("Error creating product_cut_types table: " . mysqli_error($conn));
}


// Create orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    user_id VARCHAR(50),
    total_amount DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 50.00,
    delivery_name VARCHAR(100),
    delivery_address TEXT,
    delivery_district VARCHAR(50),
    delivery_city VARCHAR(50),
    delivery_state VARCHAR(50),
    delivery_pincode VARCHAR(10),
    delivery_phone VARCHAR(15),
    status ENUM('pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cod', 'online') DEFAULT 'cod',
    payment_upi VARCHAR(50),
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    delivery_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'orders' created successfully<br>";
} else {
    die("Error creating orders table: " . mysqli_error($conn));
}

// Create order_items table
$sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id VARCHAR(50),
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'order_items' created successfully<br>";
} else {
    die("Error creating order_items table: " . mysqli_error($conn));
}

// Create cart table
$sql = "CREATE TABLE IF NOT EXISTS cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(50),
    product_id INT,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cut_type VARCHAR(50),
    FOREIGN KEY (product_id) REFERENCES products(id)
)";

if (!mysqli_query($conn, $sql)) {
    die("Error creating cart table: " . mysqli_error($conn));
}

// Create payments table with additional fields for transaction tracking
$sql = "CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id VARCHAR(100) UNIQUE NOT NULL,
    order_id VARCHAR(50) NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cod', 'online') NOT NULL,
    payment_gateway VARCHAR(50),
    payment_reference VARCHAR(100),
    transaction_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    response_data TEXT,
    refund_id VARCHAR(100),
    refund_amount DECIMAL(10,2),
    refund_date TIMESTAMP NULL,
    refund_status ENUM('none', 'partial', 'full') DEFAULT 'none',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'payments' created successfully<br>";
} else {
    die("Error creating payments table: " . mysqli_error($conn));
}

// Insert default admin account
$admin_user_id = "admin";
$admin_username = "admin";
$admin_email = "admin@veggie.com";
$admin_password = password_hash("Admin@90", PASSWORD_DEFAULT);

$check_admin = "SELECT * FROM users WHERE user_id = 'admin'";
$result = mysqli_query($conn, $check_admin);

if (mysqli_num_rows($result) == 0) {
    $sql = "INSERT INTO users (user_id, username, email, password, role, status) 
            VALUES ('$admin_user_id', '$admin_username', '$admin_email', '$admin_password', 'admin', 'active')";
    
    if (mysqli_query($conn, $sql)) {
        echo "Default admin account created successfully<br>";
        echo "Admin Login Details:<br>";
        echo "Username: admin<br>";
        echo "Password: Admin@90<br>";
    } else {
        echo "Error creating admin account: " . mysqli_error($conn);
    }
}

// Create uploads directory for ID proofs and product images
$upload_dirs = ['uploads/id_proofs', 'uploads/products'];
foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "Created directory: $dir<br>";
    }
}

// Close the setup connection
mysqli_close($conn);
echo "Database setup completed successfully!";

// Update orders table structure
$sql = "ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending'";
if (mysqli_query($conn, $sql)) {
    echo "Added payment_status column to orders table<br>";
} else {
    echo "Error adding payment_status column: " . mysqli_error($conn) . "<br>";
}

// Update payments table structure
$sql = "ALTER TABLE payments MODIFY COLUMN payment_gateway VARCHAR(50) DEFAULT 'razorpay'";
if (mysqli_query($conn, $sql)) {
    echo "Modified payment_gateway column in payments table<br>";
} else {
    echo "Error modifying payment_gateway column: " . mysqli_error($conn) . "<br>";
}

$sql = "ALTER TABLE payments ADD COLUMN IF NOT EXISTS payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
if (mysqli_query($conn, $sql)) {
    echo "Added payment_date column to payments table<br>";
} else {
    echo "Error adding payment_date column: " . mysqli_error($conn) . "<br>";
}

$sql = "ALTER TABLE payments ADD COLUMN IF NOT EXISTS response_data TEXT";
if (mysqli_query($conn, $sql)) {
    echo "Added response_data column to payments table<br>";
} else {
    echo "Error adding response_data column: " . mysqli_error($conn) . "<br>";
}

// Add indexes for better performance
$sql = "ALTER TABLE orders ADD INDEX idx_order_id (order_id)";
if (mysqli_query($conn, $sql)) {
    echo "Added idx_order_id index to orders table<br>";
} else {
    echo "Error adding idx_order_id index: " . mysqli_error($conn) . "<br>";
}

$sql = "ALTER TABLE payments ADD INDEX idx_payment_id (payment_id)";
if (mysqli_query($conn, $sql)) {
    echo "Added idx_payment_id index to payments table<br>";
} else {
    echo "Error adding idx_payment_id index: " . mysqli_error($conn) . "<br>";
}

$sql = "ALTER TABLE payments ADD INDEX idx_order_id (order_id)";
if (mysqli_query($conn, $sql)) {
    echo "Added idx_order_id index to payments table<br>";
} else {
    echo "Error adding idx_order_id index: " . mysqli_error($conn) . "<br>";
}
?>