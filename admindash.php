<?php
session_start();
require_once 'config.php';

// Ensure only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle User Deletion
if (isset($_GET['delete_user'])) {
    $id = intval($_GET['delete_user']);
    
    // Instead of physically deleting, update status to inactive
    $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: admindash.php?");
    } else {
        header("Location: admindash.php?section=manage_users&error=" . urlencode($stmt->error));
    }
    exit();
}

// Handle User Reactivation
if (isset($_GET['reactivate_user'])) {
    $id = intval($_GET['reactivate_user']);
    
    $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: admindash.php");
    } else {
        header("Location: admindash.php?section=manage_users&error=" . urlencode($stmt->error));
    }
    exit();
}

// Handle Add Delivery Boy with ID Proof
if (isset($_POST['add_deliveryboy'])) {
    $user_id = $_POST['user_id'];
    $email = $_POST['email']; // Changed from username to email
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $status = 'active'; // Default status

    // Handle ID Proof Upload
    $id_proof = null;
    if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] == 0) {
        $upload_dir = 'uploads/id_proofs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $id_proof_filename = $upload_dir . uniqid() . '_' . $_FILES['id_proof']['name'];
        if (move_uploaded_file($_FILES['id_proof']['tmp_name'], $id_proof_filename)) {
            $id_proof = $id_proof_filename;
        }
    }

    // Generate a unique user_id for the delivery boy (e.g., DB001, DB002)
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'delivery boy'");
    $count = $result->fetch_assoc()['count'] + 1;
    $user_id = 'DB' . str_pad($count, 3, '0', STR_PAD_LEFT);

    // Prepare SQL to insert delivery boy with ID proof
    $stmt = $conn->prepare("INSERT INTO users (user_id, username, email, password, role, status, profile_picture) VALUES (?, ?, ?, ?, 'delivery boy', ?, ?)");
    $stmt->bind_param("ssssss", $user_id, $name, $email, $password, $status, $profile_picture);
    
    if ($stmt->execute()) {
        header("Location: admindash.php?section=manage_users&success=Delivery boy added successfully");
    } else {
        header("Location: admindash.php?section=add_deliveryboy&error=" . urlencode($stmt->error));
    }
    exit();
}

// Handle Edit Delivery Boy
if (isset($_POST['edit_deliveryboy'])) {
    $user_id = $_POST['user_id'];
    $name = $_POST['name'];
    $status = $_POST['status'];

    // Handle ID Proof Update
    $id_proof = null;
    if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] == 0) {
        $upload_dir = 'uploads/id_proofs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $id_proof_filename = $upload_dir . uniqid() . '_' . $_FILES['id_proof']['name'];
        if (move_uploaded_file($_FILES['id_proof']['tmp_name'], $id_proof_filename)) {
            $id_proof = $id_proof_filename;
        }
    }

    // Prepare update SQL
    if ($id_proof) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, status = ?, id_proof = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $status, $id_proof, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $status, $id);
    }
    
    if ($stmt->execute()) {
        header("Location: admindash.php?section=manage_users&success=Delivery boy updated successfully");
    } else {
        header("Location: admindash.php?section=edit_deliveryboy&id=$id&error=" . urlencode($stmt->error));
    }
    exit();
}

// Check database schema
$result = $conn->query("DESCRIBE users");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

// If status column doesn't exist, add it
if (!in_array('status', $columns)) {
    $conn->query("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
}

// Fetch Statistics (only for active users)
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND status = 'active'")->fetch_assoc()['count'];
$total_delivery_boys = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'delivery boy' AND status = 'active'")->fetch_assoc()['count'];

// Fetch products for stock update section
$products = $conn->query("SELECT * FROM products");

// Fetch only active users
$users = $conn->query("SELECT * FROM users WHERE status = 'active' ORDER BY role");

// Fetch inactive users
$inactive_users = $conn->query("SELECT * FROM users WHERE status = 'inactive' ORDER BY role");

// Handle Add/Update Product
if (isset($_POST['update_product'])) {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $net_weight = $_POST['net_weight'];
    $unit = $_POST['unit'];
    $category = $_POST['category'];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'image/';
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $file_extension;
        $image_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            // Update with new image
            $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, net_weight=?, stock=?, unit=?, category=?, image_path=? WHERE id=?");
            $stmt->bind_param("ssssisssi", $name, $description, $price, $net_weight, $stock, $unit, $category, $image_path, $product_id);
        }
    } else {
        // Update without changing image
        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, net_weight=?, stock=?, unit=?, category=? WHERE id=?");
        $stmt->bind_param("ssssissi", $name, $description, $price, $net_weight, $stock, $unit, $category, $product_id);
    }
    
    if ($stmt->execute()) {
        header("Location: admindash.php?section=update_stock&success=Product updated successfully");
    } else {
        header("Location: admindash.php?section=update_stock&error=" . urlencode($stmt->error));
    }
    exit();
}

// Handle Add New Product
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $net_weight = $_POST['net_weight'];
    $unit = $_POST['unit'];
    $category = $_POST['category'];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'image/';
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $file_extension;
        $image_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, net_weight, stock, unit, category, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssisss", $name, $description, $price, $net_weight, $stock, $unit, $category, $image_path);
            
            if ($stmt->execute()) {
                header("Location: admindash.php?section=update_stock&success=New product added successfully");
            } else {
                header("Location: admindash.php?section=update_stock&error=" . urlencode($stmt->error));
            }
        } else {
            header("Location: admindash.php?section=update_stock&error=Failed to upload image");
        }
    } else {
        header("Location: admindash.php?section=update_stock&error=Please select an image");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>FreshVeggieMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: sans-serif;
        }

        :root {
            --primary-color: #fac031;
            --text-color: #000;
            --bg-color: #fff;
            --shadow-color: rgba(0, 0, 0, 0.5);
        }
         
        body {
            overflow: hidden;
        }

        section {
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
        }

        nav {
            width: 100%;
            height: 160px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--bg-color);
            box-shadow: 0 0 10px var(--shadow-color);
            z-index: 1000;
            padding: 0 40px;
            position: fixed;
            top: 0;
            left: 0;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: var(--text-color);
        }

        .logo span {
            color: var(--primary-color);
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 30px;
        }

        nav ul li a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            font-size: 17px;
            transition: 0.2s;
            position: relative;
        }

        nav ul li a::after {
            content: '';
            width: 0;
            height: 2px;
            background: var(--primary-color);
            display: block;
            transition: 0.2s linear;
        }

        nav ul li a:hover::after {
            width: 100%;
        }

        nav ul li a:hover {
            color: var(--primary-color);
        }

        .main-content {
            position: fixed;
            top: 160px;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 20px 40px;
            overflow-y: auto;
            background-color: var(--bg-color);
            z-index: 999;
            scroll-behavior: smooth;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card h2 {
            padding: 20px;
            margin: 0;
            border-bottom: 1px solid #eee;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .table-container {
            position: relative;
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .table-container thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background: #f8f8f8;
        }

        .table-container thead tr {
            background: #f8f8f8;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .table-container thead th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border: none;
            background: #f8f8f8;
            position: relative;
        }

        .table-container thead th::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: #eee;
        }

        .table-container tbody tr {
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }

        .table-container tbody tr:last-child {
            border-bottom: none;
        }

        .table-container tbody td {
            padding: 15px;
            vertical-align: middle;
        }

        .table-container tbody tr:hover {
            background: #f9fafb;
        }


        .statistics {
            /* position: fixed;
            top: 160px; */
            left: 0;
            right: 0;
            background: white;
            padding: 20px 40px;
            z-index: 999;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .stat-card {
    background: var(--bg-color);
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: 0.3s;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.stat-card i {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 10px;
}

.card:first-of-type {
    margin-top: 180px;
}

@media (max-width: 768px) {
    .statistics {
        top: 200px;
    }
    
    .card:first-of-type {
        margin-top: 400px;
    }
}
        .stat-card h3 {
            font-size: 2rem;
            color: var(--text-color);
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #666;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #eee;
            border-radius: 5px;
            outline: none;
            transition: 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
        }

        .btn {
            background: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(250, 192, 49, 0.3);
        }

        .btn-danger {
            background: rgb(247, 95, 95);
        }

        .btn-reactivate {
            background: rgb(110, 243, 165);
        }

        .role-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .role-badge.admin {
            background: #fbbf24;
            color: white;
        }

        .role-badge.delivery {
            background: #fbbf24;
            color: white;
        }

        .role-badge.user {
            background: #fbbf24;
            color: white;
        }       

        .id-proof-preview {
            max-width: 100px;
            max-height: 100px;
        } 

        .success-message { 
            color: green; 
            margin: 10px 0; 
        }

        .error-message { 
            color: red; 
            margin: 10px 0; 
        }

        @media (max-width: 768px) {
            nav {
                padding: 20px;
                height: auto;
                flex-direction: column;
            }

            nav ul {
                margin-top: 20px;
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }

            .main-content {
                top: 200px;
            }

            .statistics {
                grid-template-columns: 1fr;
            }

            .table-container {
                overflow-x: auto;
            }
    
            .table-container table {
                min-width: 800px;
            }
        }

.add-delivery-form {
    max-width: 500px;
    margin: 0 auto;
    padding: 30px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
}

.add-delivery-form h2 {
    text-align: center;
    color: var(--text-color);
    margin-bottom: 30px;
    font-size: 24px;
}

.add-delivery-form .form-group {
    margin-bottom: 20px;
}

.add-delivery-form .form-group label {
    display: block;
    margin-bottom: 8px;
    color: var(--text-color);
    font-weight: 500;
}

.add-delivery-form .form-group input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #eee;
    border-radius: 8px;
    outline: none;
    transition: 0.3s;
    font-size: 14px;
}

.add-delivery-form .form-group input[type="file"] {
    padding: 8px;
    border: 2px dashed #eee;
    background: #f8f8f8;
}

.add-delivery-form .form-group input:focus {
    border-color: var(--primary-color);
}

.add-delivery-form .btn {
    width: 100%;
    padding: 12px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: 0.3s;
}

.add-delivery-form .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(250, 192, 49, 0.3);
}

.add-product-form input:focus,
.add-product-form select:focus,
.add-product-form textarea:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 2px rgba(250, 192, 49, 0.2);
}

.add-product-form button[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(250, 192, 49, 0.3);
}

.add-product-form input[type="file"]:hover {
    border-color: var(--primary-color);
    background: #fff;
}

.add-product-form input[type="text"],
.add-product-form input[type="number"],
.add-product-form select,
.add-product-form input[type="file"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.table-container input[type="text"],
.table-container input[type="number"],
.table-container select {
    width: 100%;
    padding: 6px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.table-container input[type="file"] {
    width: 100%;
    margin-bottom: 5px;
}

.success-message {
    background: #d4edda;
    color: #155724;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 20px;
 }

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 20px;
}


    </style>
</head>
<body>
    <section>
        <nav>
            <div class="logo">
                <a href="admindash.php">
                    <img src="image/logo8.png" width="240px" height="100px">
                </a>
                <!-- <center><span>&nbsp;Admin</span>&nbsp;Dashboard</center> -->
            </div>
            <ul>
                <li><a href="?section=add_deliveryboy"><b>Add Delivery Boy</b></a></li>
                <li><a href="?section=update_stock"><b>Update Stock</b></a></li>
                <li><a href="?section=manage_users"><b>Manage Users</b></a></li>
                <li><a href="logout.php"><b>Logout</b></a></li>
            </ul>
        </nav>
<br><br>
         
      <!-- Main content -->
        <div class="main-content">
        <div class="statistics">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-motorcycle"></i>
                    <h3><?php echo $total_delivery_boys; ?></h3>
                    <p>Delivery Boys</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-shopping-bag"></i>
                    <br><br><br>
                    <p>Today's Orders</p>
                </div>
            </div>

            <!-- Manage Users section -->
            <?php if (!isset($_GET['section']) || $_GET['section'] == "manage_users") { ?>
                <div class="card">
                    <h2>Active Users</h2>
                    <?php if (isset($_GET['success'])): ?>
                        <div class="success-message"><?php echo htmlspecialchars($_GET['success']); ?></div>
                    <?php endif; ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users->fetch_assoc()) { 
                                    $roleClass = $user['role'] == 'admin' ? 'admin' : 
                                               ($user['role'] == 'delivery boy' ? 'delivery' : 'user');
                                ?>
                                    <tr>
                                        <td><?= $user['id']; ?></td>
                                        <td><?= $user['user_id']; ?></td>
                                        <td><span class="role-badge <?= $roleClass ?>"><?= $user['role']; ?></span></td>
                                        <td><?= $user['created_at']; ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="admindash.php?delete_user=<?= $user['id']; ?>" 
                                                   class="btn btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                                   <i class="fas fa-trash"></i>&nbsp;Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Inactive Users section -->
                <div class="card">
                    <h2>Inactive Users</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($inactive_user = $inactive_users->fetch_assoc()) { 
                                    $roleClass = $inactive_user['role'] == 'admin' ? 'admin' : 
                                               ($inactive_user['role'] == 'delivery boy' ? 'delivery' : 'user');
                                ?>
                                    <tr>
                                        <td><?= $inactive_user['id']; ?></td>
                                        <td><?= $inactive_user['user_id']; ?></td>
                                        <td><span class="role-badge <?= $roleClass ?>"><?= $inactive_user['role']; ?></span></td>
                                        <td><?= $inactive_user['created_at']; ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="admindash.php?reactivate_user=<?= $inactive_user['id']; ?>" 
                                                   class="btn btn-reactivate" 
                                                   onclick="return confirm('Are you sure you want to reactivate this user?')">
                                                   Reactivate
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php } ?>

            <!-- Add Delivery Boy section -->
            <?php if (isset($_GET['section']) && $_GET['section'] == "add_deliveryboy") { ?>
    <div class="card">
        <h2 style="text-align: center; margin-bottom: 25px;">Add Delivery Boy</h2>
        <form method="POST" enctype="multipart/form-data" style="max-width: 800px; margin: 0 auto;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <input type="text" name="name" placeholder="User Id" required 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email Id" required 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <div class="form-group">
                    <input type="file" name="profile_picture" accept=".pdf,.jpg,.jpeg,.png" 
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 25px;">
                <button type="submit" name="add_deliveryboy" class="btn" 
                        style="width: 200px; padding: 12px; background: var(--primary-color); color: white; 
                               border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                    Add Delivery Boy
                </button>
            </div>
        </form>
    </div>
<?php } ?>

           <!-- Edit Delivery Boy section -->
<?php if (isset($_GET['section']) && $_GET['section'] == "edit_deliveryboy") { 
    $id = $_GET['id'];
    $user = $conn->query("SELECT * FROM users WHERE id = $id")->fetch_assoc();
?>
    <div class="card">
        <h2 style="text-align: center; margin-bottom: 25px;">Edit Delivery Boy</h2>
        <form method="POST" enctype="multipart/form-data" style="max-width: 800px; margin: 0 auto;">
            <input type="hidden" name="user_id" value="<?= $id; ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <input type="text" name="name" value="<?= $user['name']; ?>" placeholder="Full Name" required 
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <div class="form-group">
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="active" <?= ($user['status'] == 'active' ? 'selected' : ''); ?>>Active</option>
                        <option value="inactive" <?= ($user['status'] == 'inactive' ? 'selected' : ''); ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <?php if ($user['id_proof']): ?>
                <div class="form-group" style="margin: 20px 0;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 500;">Current ID Proof</label>
                    <img src="<?= $user['id_proof']; ?>" class="id-proof-preview" 
                         style="max-width: 200px; border: 1px solid #ddd; border-radius: 5px; padding: 5px;">
                </div>
            <?php endif; ?>

            <div class="form-group" style="margin: 20px 0;">
                <label style="display: block; margin-bottom: 10px; font-weight: 500;">Update ID Proof</label>
                <input type="file" name="id_proof" accept=".pdf,.jpg,.jpeg,.png" 
                       style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
            </div>

            <div style="text-align: center; margin-top: 25px;">
                <button type="submit" name="edit_deliveryboy" class="btn" 
                        style="width: 200px; padding: 12px; background: var(--primary-color); color: white; 
                               border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
                    Update Delivery Boy
                </button>
            </div>
        </form>
    </div>
<?php } ?>

<!-- Update Stock section -->
<?php if (isset($_GET['section']) && $_GET['section'] == "update_stock") { ?>
                <div class="card">
                    <h2>Manage Products</h2>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="success-message"><?php echo htmlspecialchars($_GET['success']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
                    <?php endif; ?>

        <!-- Add New Product Form -->
<div class="add-product-form" style="margin-bottom: 40px; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="text-align: center; margin-bottom: 30px; color: #333; font-size: 24px;">Add New Vegetable</h3>
    <form method="POST" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <!-- First Column -->
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; color: #555; font-weight: 500;">Vegetable Name</label>
                <input type="text" name="name" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; transition: border-color 0.3s;">
            </div>
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; color: #555; font-weight: 500;">Price (₹)</label>
                <input type="number" name="price" step="0.01" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; color: #555; font-weight: 500;">Net Weight</label>
                <input type="text" name="net_weight" placeholder="e.g., 500 g, 1 kg" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            
            <!-- Second Column -->
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; color: #555; font-weight: 500;">Stock Quantity</label>
                <input type="number" name="stock" required 
                       style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; color: #555; font-weight: 500;">Unit</label>
                <select name="unit" required 
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; background-color: white;">
                    <option value="kg">Kilogram (kg)</option>
                    <option value="g">Gram (g)</option>
                    <option value="packets">Packets</option>
                </select>
            </div>
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; color: #555; font-weight: 500;">Category</label>
                <select name="category" required 
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; background-color: white;">
                    <option value="regular">Regular Product</option>
                    <option value="veggie_kit">Veggie Kit</option>
                </select>
            </div>
        </div>

        <!-- Description - Full Width -->
        <div class="form-group" style="margin-top: 20px;">
            <label style="display: block; margin-bottom: 8px; color: #555; font-weight: 500;">Product Description</label>
            <textarea name="description" rows="3" 
                      style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; resize: vertical;"></textarea>
        </div>

        <!-- Image Upload and Submit Button -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
            <div class="form-group">
                <label style="display: block; margin-bottom: 8px; color: #555; font-weight: 500;">Product Image</label>
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" required 
                       style="width: 100%; padding: 10px; border: 2px dashed #ddd; border-radius: 8px; background: #f8f9fa;">
            </div>
            <div class="form-group" style="display: flex; align-items: flex-end;">
                <button type="submit" name="add_product" 
                        style="width: 100%; padding: 14px; background: var(--primary-color); color: white; border: none; 
                               border-radius: 8px; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;">
                    Add Vegetable
                </button>
            </div>
        </div>
    </form>
</div>

        <div class="container mt-5">
    <h2>Manage Products</h2>
    
    <!-- Filter options -->
   <div class="mb-3">
                        <label for="category-filter" class="form-label">Filter by Category:</label>
                        <select id="category-filter" class="form-select" onchange="filterProducts()">
                            <option value="all">All Products</option>
                            <option value="regular">Regular Products</option>
                            <option value="veggie_kit">Veggie Kits</option>
                        </select>
                    </div>
        <!-- Existing Products Table -->
        <div class="table-container">
        <table>
    <thead>
        <tr>
            <th>Image</th>
            <th>Name</th>
            <th>Description</th>
            <th>Price (₹)</th>
            <th>Net Weight</th>
            <th>Stock</th>
            <th>Unit</th>
            <th>Category</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php 
    $products = $conn->query("SELECT * FROM products ORDER BY name");
    while ($product = $products->fetch_assoc()) { 
        $category = !empty($product['category']) ? $product['category'] : 'regular';
    ?>
        <tr class="product-row" data-category="<?php echo htmlspecialchars($category); ?>">
            <td>
                <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     style="width: 50px; height: 50px; object-fit: cover;">
            </td>
            <td>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </td>
            <td>
                <textarea name="description" rows="3" style="width: 100%; padding: 5px;"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
            </td>
            <td>
                <input type="number" name="price" value="<?php echo $product['price']; ?>" step="0.01" required>
            </td>
            <td>
                <input type="text" name="net_weight" value="<?php echo htmlspecialchars($product['net_weight'] ?? ''); ?>" placeholder="e.g., 500 g, 1 kg">
            </td>
            <td>
                <input type="number" name="stock" value="<?php echo $product['stock']; ?>" required>
            </td>
            <td>
                <select name="unit" required>
                    <option value="kg" <?php echo ($product['unit'] == 'kg') ? 'selected' : ''; ?>>Kilogram (kg)</option>
                    <option value="g" <?php echo ($product['unit'] == 'g') ? 'selected' : ''; ?>>Gram (g)</option>
                    <option value="packets" <?php echo ($product['unit'] == 'packets') ? 'selected' : ''; ?>>Packets</option>
                </select>
            </td>
            <td>
                <select name="category" required>
                    <option value="regular" <?php echo ($category == 'regular') ? 'selected' : ''; ?>>Regular Product</option>
                    <option value="veggie_kit" <?php echo ($category == 'veggie_kit') ? 'selected' : ''; ?>>Veggie Kit</option>
                </select>
            </td>
            <td>
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
                <button type="submit" name="update_product" class="btn">Update</button>
                </form>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
</div>

    </section>


    <script>
        function filterProducts() {
    const filter = document.getElementById('category-filter').value;
    const rows = document.querySelectorAll('.product-row');
    
    rows.forEach(row => {
        const category = row.getAttribute('data-category');
        if (filter === 'all' || category === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product?')) {
        // Send AJAX request to delete product
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'delete_product.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (this.status === 200) {
                alert('Product deleted successfully');
                // Reload the page to update the product list
                location.reload();
            } else {
                alert('Error deleting product: ' + this.responseText);
            }
        };
        xhr.send('product_id=' + productId);
    }
}
    </script>
</body>
</html>
<?php $conn->close(); }?>