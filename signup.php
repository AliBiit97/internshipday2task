<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    if($password !== $confirmPassword) {
        $error = "Passwords do not match!";
    } else {

        $check = $conn->query("SELECT * FROM users WHERE email='$email'");
        if($check->num_rows > 0) {
            $error = "Email already registered!";
        } else {
            $insert = $conn->query("INSERT INTO users (username, email, password) VALUES ('$username','$email','$password')");
            if($insert) {
                $success = "Sign-Up successful! <a href='login.php' style='color: #667eea; font-weight: 600;'>Login now</a>";
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Create Account</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .signup-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .signup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2.5rem 2rem;
            text-align: center;
            color: white;
        }

        .signup-header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .signup-header p {
            opacity: 0.95;
            font-size: 0.95rem;
        }

        .signup-body {
            padding: 2rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            animation: fadeIn 0.3s;
        }

        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background-color: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
            color: #999;
        }

        .form-group input {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 3rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            background-color: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group input:focus + .input-icon {
            color: #667eea;
        }

        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 0.5rem;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .divider {
            text-align: center;
            margin: 1.5rem 0;
            color: #999;
            font-size: 0.9rem;
        }

        .login-link {
            text-align: center;
            padding: 1.5rem 2rem;
            background-color: #f8f9fa;
            border-top: 1px solid #e0e0e0;
        }

        .login-link p {
            color: #666;
            font-size: 0.95rem;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .login-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .password-strength {
            height: 4px;
            background-color: #e0e0e0;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }

        .strength-weak { 
            width: 33%; 
            background-color: #ff4444; 
        }

        .strength-medium { 
            width: 66%; 
            background-color: #ffbb33; 
        }

        .strength-strong { 
            width: 100%; 
            background-color: #00C851; 
        }

        @media (max-width: 480px) {
            .signup-header h2 {
                font-size: 1.6rem;
            }

            .signup-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-header">
            <h2>✨ Create Account</h2>
            <p>Join us today and get started!</p>
        </div>

        <div class="signup-body">
            <?php if(isset($error)): ?>
                <div class="alert alert-error">
                    ⚠️ <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if(isset($success)): ?>
                <div class="alert alert-success">
                    ✅ <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" placeholder="Enter your username" required>
                        <span class="input-icon">👤</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        <span class="input-icon">✉️</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                        <span class="input-icon">🔒</span>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <div class="input-wrapper">
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required>
                        <span class="input-icon">🔒</span>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Create Account</button>
            </form>
        </div>

        <div class="login-link">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <script>
     
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            
            let strength = 0;
            if(password.length >= 6) strength++;
            if(password.length >= 10) strength++;
            if(/[A-Z]/.test(password)) strength++;
            if(/[0-9]/.test(password)) strength++;
            if(/[^A-Za-z0-9]/.test(password)) strength++;

            strengthBar.className = 'password-strength-bar';
            
            if(strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if(strength <= 4) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });

       
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirmPassword');

        function validatePassword() {
            if(password.value !== confirmPassword.value && confirmPassword.value !== '') {
                confirmPassword.setCustomValidity("Passwords don't match");
            } else {
                confirmPassword.setCustomValidity('');
            }
        }

        password.addEventListener('change', validatePassword);
        confirmPassword.addEventListener('keyup', validatePassword);
    </script>
</body>
</html>