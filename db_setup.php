<?php
require_once 'config.php';

// Create initial connection without database
$conn = mysqli_connect($servername, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS veggie";
if (mysqli_query($conn, $sql)) {
    echo "Database 'veggie' created successfully<br>";
} else {
    die("Error creating database: " . mysqli_error($conn));
}

// Select the veggie database
mysqli_select_db($conn, "veggie");

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

// Create orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    user_id VARCHAR(50),
    total_amount DECIMAL(10,2) NOT NULL,
    delivery_address TEXT,
    delivery_city VARCHAR(50),
    delivery_phone VARCHAR(15),
    status ENUM('pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cod', 'online') DEFAULT 'cod',
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
    FOREIGN KEY (product_id) REFERENCES products(id)
)";

if (!mysqli_query($conn, $sql)) {
    die("Error creating cart table: " . mysqli_error($conn));
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
?>