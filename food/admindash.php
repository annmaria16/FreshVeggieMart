<?php
session_start();
require_once 'config.php';

// Ensure only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
// Handle Add Delivery Boy with ID Proof
if (isset($_POST['add_deliveryboy'])) {
    $name = $_POST['name'];
    $username = $_POST['username'];
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

    // Prepare SQL to insert delivery boy with ID proof
    $stmt = $conn->prepare("INSERT INTO users (username, name, password, role, status, id_proof) VALUES (?, ?, ?, 'delivery boy', ?, ?)");
    $stmt->bind_param("sssss", $username, $name, $password, $status, $id_proof);
    
    if ($stmt->execute()) {
        header("Location: admindash.php?section=manage_users&success=Delivery boy added successfully");
    } else {
        header("Location: admindash.php?section=add_deliveryboy&error=" . urlencode($stmt->error));
    }
    exit();
}

// Handle Edit Delivery Boy
if (isset($_POST['edit_deliveryboy'])) {
    $id = $_POST['user_id'];
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

// Fetch Statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$total_delivery_boys = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'delivery boy'")->fetch_assoc()['count'];

// Fetch Users and Delivery Boys
$users = $conn->query("SELECT * FROM users ORDER BY role");
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
         
        section {
            width: 100%;
            min-height: 100vh;
        }

        nav {
            width: 100%;
            height: 160px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--bg-color);
            box-shadow: 0 0 10px var(--shadow-color);
            z-index: 1000;
            padding: 0 40px;
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
            padding-top: 150px;
            padding-bottom: 50px;
            margin: 0 40px;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .card {
            background: var(--bg-color);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            transition: 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .card h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: var(--text-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #eee;
            border-radius: 5px;
            outline: none;
            transition: 0.3s;
        }

        .form-group input:focus {
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
            background:rgb(241, 105, 105);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f8f8;
            font-weight: 600;
            color: var(--text-color);
        }

        tr:hover {
            background: #f9fafb;
        }

        .table-actions {
            display: flex;
            gap: 10px;
        }

        .icon {
            font-size: 18px;
            color: var(--text-color);
            transition: 0.3s;
        }

        .icon:hover {
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            nav {
                padding: 0 20px;
                height: auto;
                flex-direction: column;
                padding: 20px;
            }

            nav ul {
                margin-top: 20px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .main-content {
                margin: 0 20px;
                padding-top: 200px;
            }
        }
        
        .statistics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
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

        .stat-card h3 {
            font-size: 2rem;
            color: var(--text-color);
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #666;
            font-size: 1rem;
        }

        .role-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .role-badge.admin {
            background:rgb(238, 205, 121);
            color: white;
        }

        .role-badge.delivery {
            background: #34d399;
            color: white;
        }

        .role-badge.user {
            background: #fbbf24;
            color: white;
        }       

        .status-active { 
            background:rgb(88, 247, 172);
            color: white;
         }
        .status-inactive {
            background: orange;
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
        
    </style>
</head>
<body>
    <section>
        <nav>
            <div class="logo">
                <a href="admindash.php">
                    <img src="image/logo8.png" width="240px" height="100px">
                </a>
                <center><span>&nbsp;Admin</span>&nbsp;Dashboard</center>
            </div>
            <ul>
                <li><a href="?section=add_deliveryboy"><b>Add Delivery Boy</b></a></li>
                <li><a href="?section=update_stock"><b>Update Stock</b></a></li>
                <li><a href="?section=manage_users"><b>Manage Users</b></a></li>
                <li><a href="logout.php"><b>Logout</b></a></li>
            </ul>
        </nav>
<br><br>
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
                    <h2>Manage Users</h2>
                    <?php if (isset($_GET['success'])): ?>
                        <div class="success-message"><?php echo htmlspecialchars($_GET['success']); ?></div>
                    <?php endif; ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Status</th>
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
                                    <td class="status-<?= strtolower($user['status'] ?? 'active'); ?>">
                                        <?= $user['status'] ?? 'Active'; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['role'] == 'delivery boy'): ?>
                                            <a href="?section=edit_deliveryboy&id=<?= $user['id']; ?>">Edit</a>
                                        <?php endif; ?>
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
            <?php } ?>

            <!-- Add Delivery Boy section -->
            <?php if (isset($_GET['section']) && $_GET['section'] == "add_deliveryboy") { ?>
                <div class="card">
                    <h2>Add Delivery Boy</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <input type="text" name="name" placeholder="Full Name" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="username" placeholder="Username" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="password" placeholder="Password" required>
                        </div>
                        <div class="form-group">
                            <label>ID Proof Upload</label>
                            <input type="file" name="id_proof" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <button type="submit" name="add_deliveryboy" class="btn">Add Delivery Boy</button>
                    </form>
                </div>
            <?php } ?>

            <!-- Edit Delivery Boy section -->
            <?php if (isset($_GET['section']) && $_GET['section'] == "edit_deliveryboy") { 
                $id = $_GET['id'];
                $user = $conn->query("SELECT * FROM users WHERE id = $id")->fetch_assoc();
            ?>
                <div class="card">
                    <h2>Edit Delivery Boy</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="user_id" value="<?= $id; ?>">
                        <div class="form-group">
                            <input type="text" name="name" value="<?= $user['name']; ?>" placeholder="Full Name" required>
                        </div>
                        <div class="form-group">
                            <select name="status">
                                <option value="active" <?= ($user['status'] == 'active' ? 'selected' : ''); ?>>Active</option>
                                <option value="inactive" <?= ($user['status'] == 'inactive' ? 'selected' : ''); ?>>Inactive</option>
                            </select>
                        </div>
                        <?php if ($user['id_proof']): ?>
                            <div class="form-group">
                                <label>Current ID Proof</label>
                                <img src="<?= $user['id_proof']; ?>" class="id-proof-preview">
                            </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label>Update ID Proof</label>
                            <input type="file" name="id_proof" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <button type="submit" name="edit_deliveryboy" class="btn">Update Delivery Boy</button>
                    </form>
                </div>
            <?php } ?>
<!-- Update Stock section -->
<?php if (isset($_GET['section']) && $_GET['section'] == "update_stock") { ?>
                <div class="card">
                    <h2>Update Stock</h2>
                    <?php if (isset($_GET['success'])): ?>
                        <div class="success-message"><?php echo htmlspecialchars($_GET['success']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="form-group">
                            <label>Select Product</label>
                            <select name="product_id" required>
                                <?php while ($product = $products->fetch_assoc()) { ?>
                                    <option value="<?= $product['id']; ?>">
                                        <?= $product['name']; ?> (Current Stock: <?= $product['stock']; ?>)
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <input type="number" name="quantity" placeholder="New Stock Quantity" required>
                        </div>
                        <button type="submit" name="update_stock" class="btn">Update Stock</button>
                    </form>
                </div>
            <?php } ?>
        </div>
    </section>
</body>
</html>
<?php $conn->close(); ?>