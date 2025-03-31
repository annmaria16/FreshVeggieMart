<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data from database
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Define cities in Kottayam district
$kottayam_cities = ['Sreekaryam', 'Nedumangad', 'Kattakada', 'Neyyattinkara', 'Attingal', 'Varkala',  
'Kollam', 'Karunagappally', 'Punalur', 'Paravur', 'Kottarakkara',  
'Pathanamthitta', 'Adoor', 'Ranni', 'Thiruvalla', 'Pandalam',  
'Alappuzha', 'Chengannur', 'Mavelikkara', 'Kayamkulam', 'Haripad',  
'Kottayam', 'Pala', 'Changanassery', 'Vaikom', 'Ettumanoor',  
'Erattupetta', 'Kanjirappally', 'Erumely', 'Kumarakom',  
'Mundakayam', 'Pampady', 'Ponkunnam', 'Thalayolaparambu', 'Vazhoor',  
'Thodupuzha', 'Munnar', 'Kattappana', 'Peerumedu', 'Adimali', 'Nedumkandam',  
'Kochi', 'Aluva', 'Perumbavoor', 'North Paravur', 'Muvattupuzha',  
'Kothamangalam', 'Angamaly', 'Tripunithura', 'Kalamassery',  
'Thrissur', 'Chalakkudy', 'Guruvayur', 'Kodungallur', 'Kunnamkulam', 'Irinjalakuda',  
'Palakkad', 'Ottapalam', 'Shoranur', 'Chittur', 'Mannarkkad', 'Pattambi',  
'Malappuram', 'Manjeri', 'Perinthalmanna', 'Tirur', 'Ponnani',  
'Nilambur', 'Kondotty', 'Kottakkal', 'Edappal',  
'Kozhikode', 'Vadakara', 'Koyilandy', 'Feroke', 'Ramanattukara',  
'Kalpetta', 'Mananthavady', 'Sulthan Bathery',  
'Kannur', 'Thalassery', 'Payyanur', 'Taliparamba', 'Mattannur', 'Iritty',  
'Kasaragod', 'Kanhangad', 'Nileshwar', 'Uppala',
    'Kottayam', 'Pala', 'Changanassery', 'Vaikom', 'Ettumanoor', 
    'Erattupetta', 'Kanjirappally', 'Erumely', 'Kumarakom', 
    'Mundakayam', 'Pampady', 'Ponkunnam', 'Thalayolaparambu', 'Vazhoor'
];

// Handle profile update and account deletion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        // Existing profile update logic remains the same
        $new_user_id = trim($_POST['user_id']);
        $username = $_POST['username'] ?? '';
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $phone = preg_replace("/[^0-9]/", "", $_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address.";
            goto output_page;
        }

        if (!preg_match("/^[6-9][0-9]{9}$/", $phone)) {
            $error_message = "Please enter a valid 10-digit Indian phone number starting with 6, 7, 8, or 9.";
            goto output_page;
        }

        if (!empty($city) && !in_array($city, $kottayam_cities)) {
            $error_message = "Please select a valid city.";
            goto output_page;
        }

        if ($new_user_id !== $user_id) {
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND user_id != ?");
            $check_stmt->bind_param("ss", $new_user_id, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error_message = "This User ID is already taken. Please choose a different one.";
                goto output_page;
            }
        }

        $check_email_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check_email_stmt->bind_param("ss", $email, $user_id);
        $check_email_stmt->execute();
        $check_email_result = $check_email_stmt->get_result();
        
        if ($check_email_result->num_rows > 0) {
            $error_message = "This email is already registered with another account.";
            goto output_page;
        }
        
        $update_query = "UPDATE users SET user_id = ?, username = ?, email = ?, phone = ?, address = ?, city = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sssssss", $new_user_id, $username, $email, $phone, $address, $city, $user_id);

        try {
            if ($update_stmt->execute()) {
                $_SESSION['user_id'] = $new_user_id;
                $success_message = "Profile updated successfully!";
                $user_id = $new_user_id;
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $error_message = "Error updating profile. Please try again.";
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
    // Handle account deletion
    elseif (isset($_POST['delete_account'])) {
        $update_status = $conn->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?");
        $update_status->bind_param("s", $user_id);
        
        if ($update_status->execute()) {
            session_destroy();
            header("Location: login.php?message=account_deactivated");
            exit();
        } else {
            $error_message = "Error deactivating account. Please try again.";
        }
    }
}

output_page:
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshVeggieMart</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 150px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #fac031;
            outline: none;
        }
        
        .form-group input.invalid {
            border-color: #dc3545;
        }
        
        .validation-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .button-group {
            grid-column: 1 / -1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .save-button {
            background: #fac031;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
            font-weight: 500;
            flex-grow: 1;
        }

        .delete-button {
            background: #dc3545;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
            font-weight: 500;
        }

        .save-button:hover {
            background: #e5ac2c;
            transform: translateY(-2px);
        }

        .delete-button:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
            font-weight: 500;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .modal-button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: 0.3s;
        }

        .confirm-delete {
            background-color: #dc3545;
            color: white;
        }

        .cancel-delete {
            background-color:rgb(162, 173, 177);
            color: white;
        }

        .modal-button:hover {
            transform: translateY(-2px);
        }
        
        .warning-text {
            color: #dc3545;
            font-weight: 500;
            margin: 15px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <section>
        <nav>
            <div class="logo">
               <a href="index.php"> <img src="image/logo8.png" alt="Logo"></a>
            </div>
        </nav>

        <div class="profile-container">
            <?php if (isset($success_message)): ?>
                <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <div class="profile-header">
                <h1>My Profile</h1>
            </div>

            <form action="" method="POST" class="profile-form" id="profileForm" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="user_id">User ID</label>
                    <input type="text" id="user_id" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="username">Full Name</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                    <div id="phoneValidationMessage" class="validation-message">
                        Please enter a valid 10-digit Indian mobile number starting with 6, 7, 8, or 9.
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="city">City</label>
                    <select id="city" name="city" required>
                        <option value="">Select a city</option>
                        <?php foreach ($kottayam_cities as $city): ?>
                            <option value="<?php echo htmlspecialchars($city); ?>" 
                                <?php echo (isset($user['city']) && $user['city'] === $city) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($city); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="button-group">
                    <button type="submit" name="update_profile" class="save-button" id="saveButton">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="button" class="delete-button" onclick="showDeleteConfirmation()">
                        <i class="fas fa-trash"></i> Delete Account
                    </button>
                </div>
            </form>
        </div>

        <!-- Delete Account Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <h2>Delete Account</h2>
                <div class="warning-text">
                    <i class="fas fa-exclamation-triangle"></i> Warning: Your account will be deactivated, 
                    and you won't be able to use this account again after deletion.
                </div>
                <div class="modal-buttons">
                    <form action="" method="POST" style="display: inline;">
                        <button type="submit" name="delete_account" class="modal-button confirm-delete">
                            Yes, Delete Account
                        </button>
                    </form>
                    <button type="button" class="modal-button cancel-delete" onclick="hideDeleteConfirmation()">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Get phone input and validation message elements
        const phoneInput = document.getElementById('phone');
        const phoneValidationMessage = document.getElementById('phoneValidationMessage');
        const saveButton = document.getElementById('saveButton');
        
        // Add event listener for input on phone field
        phoneInput.addEventListener('input', validatePhoneNumber);
        
        // Phone number validation function
        function validatePhoneNumber() {
            const phoneValue = phoneInput.value.trim();
            const isValidIndianPhone = /^[6-9][0-9]{9}$/.test(phoneValue);
            
            if (phoneValue === '') {
                // Empty field
                phoneInput.classList.remove('invalid');
                phoneValidationMessage.style.display = 'none';
                return true;
            } else if (!isValidIndianPhone) {
                // Invalid phone number
                phoneInput.classList.add('invalid');
                phoneValidationMessage.style.display = 'block';
                return false;
            } else {
                // Valid phone number
                phoneInput.classList.remove('invalid');
                phoneValidationMessage.style.display = 'none';
                return true;
            }
        }
        
        // Clean phone input to allow only numbers
        phoneInput.addEventListener('keypress', function(e) {
            const charCode = (e.which) ? e.which : e.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                e.preventDefault();
            }
        });
        
        // Form validation
        function validateForm() {
            const userId = document.getElementById('user_id').value.trim();
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = phoneInput.value.trim();
            const address = document.getElementById('address').value.trim();
            const city = document.getElementById('city').value.trim();

            if (!userId || !username || !email || !phone || !address || !city) {
                alert('Please fill in all required fields.');
                return false;
            }

            // Validate email format
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('Please enter a valid email address.');
                return false;
            }

            // Validate Indian phone number format
            if (!/^[6-9][0-9]{9}$/.test(phone)) {
                alert('Please enter a valid 10-digit Indian phone number starting with 6, 7, 8, or 9.');
                return false;
            }

            return true;
        }

        // Delete account modal functions
        function showDeleteConfirmation() {
            document.getElementById('deleteModal').style.display = 'block';
        }

        function hideDeleteConfirmation() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                hideDeleteConfirmation();
            }
        }
    
        // Remove success/error messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                message.style.opacity = '0';
                message.style.transition = 'opacity 0.5s ease';
                setTimeout(() => message.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>