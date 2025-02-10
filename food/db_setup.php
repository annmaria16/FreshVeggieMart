<?php
$servername = "localhost";
$username = "root";
$password = "";

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

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100),
    email VARCHAR(100),
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
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'products' created successfully<br>";
} else {
    die("Error creating products table: " . mysqli_error($conn));
}

// Insert some sample products if not exists
$check_products = "SELECT COUNT(*) as count FROM products";
$result = mysqli_query($conn, $check_products);
$product_count = mysqli_fetch_assoc($result)['count'];

if ($product_count == 0) {
    $sample_products = [
        "Tomatoes",
        "Carrots", 
        "Spinach", 
        "Potatoes", 
        "Onions"
    ];

    foreach ($sample_products as $product) {
        $stmt = $conn->prepare("INSERT INTO products (name, stock) VALUES (?, 100)");
        $stmt->bind_param("s", $product);
        $stmt->execute();
    }
    echo "Sample products added successfully<br>";
}

// Insert default admin account
$admin_user_id = "admin";
$admin_username = "admin";
$admin_email = "admin@veggie.com";
$admin_name = "Admin User";
$admin_password = password_hash("Admin@90", PASSWORD_DEFAULT);

$check_admin = "SELECT * FROM users WHERE user_id = 'admin'";
$result = mysqli_query($conn, $check_admin);

if (mysqli_num_rows($result) == 0) {
    $sql = "INSERT INTO users (user_id, username, name, email, password, role, status) 
            VALUES ('$admin_user_id', '$admin_username', '$admin_name', '$admin_email', '$admin_password', 'admin', 'active')";
    
    if (mysqli_query($conn, $sql)) {
        echo "Default admin account created successfully<br>";
        echo "Admin Login Details:<br>";
        echo "Username: admin<br>";
        echo "Password: Admin@90<br>";
    } else {
        echo "Error creating admin account: " . mysqli_error($conn);
    }
}

// Create uploads directory for ID proofs
$upload_dir = '../uploads/id_proofs';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Close the setup connection
mysqli_close($conn);
echo "Database setup completed!";
?>