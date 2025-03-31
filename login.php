<?php
require_once 'config.php';
session_start();
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admindash.php");
        exit();
    } elseif ($_SESSION['role'] === 'delivery boy') { 
        header("Location: delivery_accepted_orders.php");
        exit();
    } elseif ($_SESSION['role'] === 'user') {
        header("Location: index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshVeggieMart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="designlog.css">
    <style>
        .error {
            color: red;
            font-size: 12px;
            text-align:center;
        }
        .success {
            color: green;
            font-size: 14px;
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 5px;
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translate(-50%, 20px);
            }
            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }
        .password-container {
            position: relative;
            width: 100%;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
        }
        .password-toggle:hover {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="hero">
        <header class="header">
            <div class="logo">
                <a href="main.html"><img src="image/logo8.png" alt="Logo"></a>
            </div>
        </header>
    </div>
    <div class="form">
        <div class="button">
            <div id="btn"></div>
            <button type="button" class="toggle-btn" onclick="login()">Log In</button>
            <button type="button" class="toggle-btn" onclick="register()">Register</button>
        </div>
        <center><img src="image/logo8.png" width="150px" height="70px" alt="Logo"></center>
        
        <?php if (isset($_GET['login_error'])): ?>
            <span class="error"><?php echo htmlspecialchars($_GET['login_error']); ?></span>
        <?php endif; ?>

        <!-- Login side -->
        <form id="login" class="input-group" action="process.php" method="POST" onsubmit="return validateLoginForm()">
            <input type="text" class="input-field" id="login_user_id" name="login_user_id" placeholder="User Id">
            <span class="error" id="login_user_id_error"></span>
            <div class="password-container">
                <input type="password" class="input-field" id="login_password" name="login_password" placeholder="Enter Password">
                <i class="fa-regular fa-eye password-toggle" onclick="togglePassword('login_password', this)"></i>
            </div>
            <span class="error" id="login_password_error"></span><br>
            <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <span><a href="forgot password.php" target="_blank">Forgot Password?</a></span>
            </div>
            <br>
            <button type="submit" class="submit-btn">Log In</button><br>
           
        </form>

        <!-- Registration side -->
        <form id="register" class="input-group" action="process.php" method="POST" onsubmit="return validateRegisterForm()">
        <input type="text" class="input-field" id="register_user_id" name="register_user_id" placeholder="User Id">
        <span class="error" id="register_user_id_error"></span>

            <input type="email" class="input-field" id="register_email" name="register_email" placeholder="Email Id">
            <span class="error" id="register_email_error"></span>

            <div class="password-container">
                <input type="password" class="input-field" id="register_password" name="register_password" placeholder="Enter Password">
                <i class="fa-regular fa-eye password-toggle" onclick="togglePassword('register_password', this)"></i>
            </div>
            <span class="error" id="register_password_error"></span>
            <div class="password-container">
                <input type="password" class="input-field" id="register_confirm_password" name="register_confirm_password" placeholder="Confirm Password">
                <i class="fa-regular fa-eye password-toggle" onclick="togglePassword('register_confirm_password', this)"></i>
            </div>
            <span class="error" id="register_confirm_password_error"></span><br>

            <?php if (isset($_GET['register_error'])): ?>
                <span class="error"><?php echo htmlspecialchars($_GET['register_error']); ?></span>
            <?php endif; ?>

            <input type="checkbox" class="check-box" id="terms" name="terms">
            <span><a href="terms_login.html" target="_blank">I agree to the terms & conditions</a></span>
            <span class="error" id="terms_error"></span>
            <button type="submit" class="submit-btn">Register</button>
        </form>



        <?php if (isset($_GET['success'])): ?>
            <div class="success" id="successMessage">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
            <script>
                setTimeout(function() {
                    var successMessage = document.getElementById('successMessage');
                    if(successMessage) {
                        successMessage.style.opacity = '0';
                        successMessage.style.transition = 'opacity 0.3s ease-in-out';
                        setTimeout(function() {
                            successMessage.remove();
                        }, 500);
                    }
                }, 5000);
            </script>
        <?php endif; ?>
    </div>
    <script>
         //  password visibility toggle
         function togglePassword(inputId, icon) {
            const passwordInput = document.getElementById(inputId);
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

      
        var x = document.getElementById("login");
        var y = document.getElementById("register");
        var z = document.getElementById("btn");

        function register() {
            x.style.left = "-400px";
            y.style.left = "50px";
            z.style.left = "110px";
            clearForm("login");
            clearErrorMessages();
        }

        function login() {
            x.style.left = "50px";
            y.style.left = "450px";
            z.style.left = "0px";
            clearForm("register");
            clearErrorMessages();
        }

        function clearForm(formId) {
            const form = document.getElementById(formId);
            const inputs = form.querySelectorAll(".input-field");
            const errors = form.querySelectorAll(".error");

            inputs.forEach(input => {
                input.value = "";
            });

            errors.forEach(error => {
                error.textContent = "";
            });

            // Reset password visibility icons
            const icons = form.querySelectorAll(".password-toggle");
            icons.forEach(icon => {
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            });

            // Reset password input types
            const passwordInputs = form.querySelectorAll("input[type='text']");
            passwordInputs.forEach(input => {
                if (input.classList.contains("input-field")) {
                    input.type = "password";
                }
            });
        }
        function clearErrorMessages() {
            const phpErrors = document.querySelectorAll('.error');
            phpErrors.forEach(error => {
                error.textContent = '';
            });

            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.pathname);
            }
        }

        // Validation functions
        function validateLoginForm() {
            let isValid = true;
            const userId = document.getElementById("login_user_id");
            const password = document.getElementById("login_password");
            
            // Clear previous errors
            document.getElementById("login_user_id_error").textContent = "";
            document.getElementById("login_password_error").textContent = "";

            if (userId.value.trim() === "") {
                document.getElementById("login_user_id_error").textContent = "User ID is required";
                isValid = false;
            }

            if (password.value.trim() === "") {
                document.getElementById("login_password_error").textContent = "Password is required";
                isValid = false;
            }

            return isValid;
        }

        function validateRegisterForm() {
            let isValid = true;

            // Validate User ID
            const userId = document.getElementById("register_user_id");
            const userIdError = document.getElementById("register_user_id_error");
            const alphabetCount = (userId.value.match(/[a-zA-Z]/g) || []).length;
            
            if (userId.value.trim() === "") {
                userIdError.textContent = "User ID is required";
                isValid = false;
            } else if (alphabetCount < 2) {
                userIdError.textContent = "User ID must contain at least 2 letters";
                isValid = false;
            } else if (!/^[a-zA-Z\s]+$/.test(userId.value)) {
                userIdError.textContent = "User ID must contain only letters and spaces";
                isValid = false;
            }

            // Validate Email
            const email = document.getElementById("register_email");
            const emailError = document.getElementById("register_email_error");
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email.value.trim() === "") {
                emailError.textContent = "Email is required";
                isValid = false;
            } else if (!emailPattern.test(email.value)) {
                emailError.textContent = "Please enter a valid email address";
                isValid = false;
            }

            // Validate Password
            const password = document.getElementById("register_password");
            const passwordError = document.getElementById("register_password_error");
            const strongPasswordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9])[A-Za-z\d\W_]{8,}$/;
            
            if (password.value.trim() === "") {
                passwordError.textContent = "Password is required";
                isValid = false;
            } else if (!strongPasswordPattern.test(password.value)) {
                passwordError.textContent = "Password must have 8+ chars, uppercase, lowercase, number, special char";
                isValid = false;
            }

            // Validate Confirm Password
            const confirmPassword = document.getElementById("register_confirm_password");
            const confirmPasswordError = document.getElementById("register_confirm_password_error");
            
            if (confirmPassword.value.trim() === "") {
                confirmPasswordError.textContent = "Confirm Password is required";
                isValid = false;
            } else if (confirmPassword.value !== password.value) {
                confirmPasswordError.textContent = "Passwords do not match";
                isValid = false;
            }

            // Validate Terms
            const terms = document.getElementById("terms");
            const termsError = document.getElementById("terms_error");
            
            if (!terms.checked) {
                termsError.textContent = "You must agree to the terms and conditions";
                isValid = false;
            } else {
                termsError.textContent = "";
            }

            return isValid;
        }

        // Live validation
        document.getElementById("register_user_id").addEventListener("input", function() {
            const alphabetCount = (this.value.match(/[a-zA-Z]/g) || []).length;
            const errorElement = document.getElementById("register_user_id_error");
            
            if (this.value.trim() === "") {
                errorElement.textContent = "";
            } else if (alphabetCount < 2) {
                errorElement.textContent = "User ID must contain at least 2 letters";
            } else if (!/^[a-zA-Z\s]+$/.test(this.value)) {
                errorElement.textContent = "User ID must contain only letters and spaces";
            } else {
                errorElement.textContent = "";
            }
        });

        document.getElementById("register_email").addEventListener("input", function() {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const errorElement = document.getElementById("register_email_error");
            
            if (this.value.trim() === "") {
                errorElement.textContent = "";
            } else if (!emailPattern.test(this.value)) {
                errorElement.textContent = "Please enter a valid email address";
            } else {
                errorElement.textContent = "";
            }
        });

        document.getElementById("register_password").addEventListener("input", function() {
    const strongPasswordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9])[A-Za-z\d\W_]{8,}$/;
    const errorElement = document.getElementById("register_password_error");
    
    if (this.value.trim() === "") {
        errorElement.textContent = "";
    } else if (!strongPasswordPattern.test(this.value)) {
        errorElement.textContent = "Password must have 8+ chars, uppercase, lowercase, number, special char";
    } else {
        errorElement.textContent = "";
    }
    validateConfirmPassword();
});

        document.getElementById("register_confirm_password").addEventListener("input", validateConfirmPassword);

        function validateConfirmPassword() {
            const password = document.getElementById("register_password").value;
            const confirmPassword = document.getElementById("register_confirm_password");
            const errorElement = document.getElementById("register_confirm_password_error");
            
            if (confirmPassword.value.trim() === "") {
                errorElement.textContent = "";
            } else if (confirmPassword.value !== password) {
                errorElement.textContent = "Passwords do not match";
            } else {
                errorElement.textContent = "";
            }
        }

        function clearForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll(".input-field");
    const errors = form.querySelectorAll(".error");

    inputs.forEach(input => {
        input.value = "";
        // Only reset type to password for password fields
        if (input.id.includes('password')) {
            input.type = "password";
        }
    });

    errors.forEach(error => {
        error.textContent = "";
    });

    // Reset password visibility icons
    const icons = form.querySelectorAll(".password-toggle");
    icons.forEach(icon => {
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    });
}

function togglePassword(inputId, icon) {
    const passwordInput = document.getElementById(inputId);
    // Only toggle if it's a password field
    if (inputId.includes('password')) {
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            passwordInput.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Check if there's a registration error in URL
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('register_error')) {
        // Switch to register form
        register();
        
        // Get the error message
        const errorMessage = urlParams.get('register_error');
        
        // Create or update a general error message element
        let generalErrorElement = document.getElementById('general_register_error');
        
        if (!generalErrorElement) {
            // If element doesn't exist, create it
            generalErrorElement = document.createElement('span');
            generalErrorElement.id = 'general_register_error';
            generalErrorElement.className = 'error';
            
            // Insert it at the top of the register form
            const registerForm = document.getElementById('register');
            registerForm.insertBefore(generalErrorElement, registerForm.firstChild);
        }
        
        // Set the error message
        generalErrorElement.textContent = errorMessage;
        generalErrorElement.style.display = 'block';
        generalErrorElement.style.marginBottom = '10px';
    }
});
    </script>
</body>
</html>