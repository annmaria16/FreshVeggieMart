<?php
session_start();
require_once 'config.php';

// Process Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_user_id']) && isset($_POST['login_password'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['login_user_id']);
    $password = $_POST['login_password'];

    // Server-side validation
    if (empty($user_id) || empty($password)) {
        header("Location: login.php?login_error=Please fill in all fields");
        exit();
    }

    // Additional validation for user_id
    if (!preg_match("/^[a-zA-Z\s]+$/", $user_id)) {
        header("Location: login.php?login_error=Invalid User ID format");
        exit();
    }

    // Check for minimum length of user_id
    if (strlen(preg_replace('/[^a-zA-Z]/', '', $user_id)) < 2) {
        header("Location: login.php?login_error=User ID must contain at least 2 letters");
        exit();
    }

    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Check if user account is active
        if ($user['status'] === 'inactive') {
            header("Location: login.php?login_error=Your account is deactivated. Contact admin for help.");
            exit();
        }
        
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['logged_in'] = true;

            // Handle "Remember Me" functionality if needed
            if (isset($_POST['remember'])) {
                // Set cookies - expire in 30 days
                setcookie("user_id", $user['user_id'], time() + (86400 * 30), "/");
                setcookie("user_role", $user['role'], time() + (86400 * 30), "/");
            }

            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header("Location: admindash.php");
                    break;
                case 'delivery_boy':
                    header("Location: delivery_dashboard.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        } else {
            header("Location: login.php?login_error=Invalid password");
            exit();
        }
    } else {
        header("Location: login.php?login_error=User not found");
        exit();
    }
    $stmt->close();
}

// Process Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_user_id'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['register_user_id']);
    $email = mysqli_real_escape_string($conn, $_POST['register_email']);
    $password = $_POST['register_password'];
    $confirm_password = $_POST['register_confirm_password'];

    // Server-side validation
    if (empty($user_id) || empty($email) || empty($password) || empty($confirm_password)) {
        header("Location: login.php?register_error=Please fill in all fields");
        exit();
    }

    // Validate user_id format
    if (!preg_match("/^[a-zA-Z\s]+$/", $user_id)) {
        header("Location: login.php?register_error=User ID must contain only letters and spaces");
        exit();
    }

    // Check for minimum length of user_id
    if (strlen(preg_replace('/[^a-zA-Z]/', '', $user_id)) < 2) {
        header("Location: login.php?register_error=User ID must contain at least 2 letters");
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: login.php?register_error=Invalid email format");
        exit();
    }

    // Validate password strength
    if (strlen($password) < 8) {
        header("Location: login.php?register_error=Password must be at least 8 characters long");
        exit();
    }

    // Check password complexity
    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
        header("Location: login.php?register_error=Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character");
        exit();
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        header("Location: login.php?register_error=Passwords do not match");
        exit();
    }

    // Verify terms checkbox
    if (!isset($_POST['terms'])) {
        header("Location: login.php?register_error=You must agree to the terms and conditions");
        exit();
    }

    // Check if user_id already exists
    $check_user = $conn->prepare("SELECT id FROM users WHERE user_id = ?");
    $check_user->bind_param("s", $user_id);
    $check_user->execute();
    $user_result = $check_user->get_result();

    if ($user_result->num_rows > 0) {
        header("Location: login.php?register_error=User ID already exists");
        exit();
    }
    $check_user->close();

    // Check if email already exists
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $email_result = $check_email->get_result();

    if ($email_result->num_rows > 0) {
        header("Location: login.php?register_error=Email already exists");
        exit();
    }
    $check_email->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (user_id, email, password, role, created_at) VALUES (?, ?, ?, 'user', NOW())");
    $stmt->bind_param("sss", $user_id, $email, $hashed_password);
    
    if ($stmt->execute()) {
        // Set success message
        header("Location: login.php?success=Registration successful! Please login.");
        exit();
    } else {
        header("Location: login.php?register_error=Registration failed: " . $stmt->error);
        exit();
    }
    $stmt->close();
}

// Close database connection
$conn->close();
?>