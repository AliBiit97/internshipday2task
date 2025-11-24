<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = $conn->query("SELECT * FROM users WHERE email='$email' AND password='$password'");
    if($query->num_rows > 0) {
        $user = $query->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        header("Location: home.php");
        exit;
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Your App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ==================== THEME VARIABLES ==================== */
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary-color: #f59e0b;
            --success-color: #10b981;
            --error-color: #ef4444;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --bg-primary: #f9fafb;
            --bg-secondary: #ffffff;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
        }

        /* ==================== GLOBAL STYLES ==================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

       

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* ==================== CONTAINER ==================== */
        .container {
            width: 100%;
            max-width: 450px;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ==================== LOGIN CARD ==================== */
        .login-card {
            background: var(--bg-secondary);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: 40px;
            backdrop-filter: blur(10px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: var(--shadow-lg);
        }

        .logo i {
            font-size: 32px;
            color: white;
        }

        .login-header h2 {
            font-size: 28px;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-weight: 700;
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        /* ==================== ALERTS ==================== */
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background-color: #fef2f2;
            color: var(--error-color);
            border: 1px solid #fecaca;
        }

        .alert i {
            font-size: 18px;
        }

        /* ==================== FORM STYLES ==================== */
        .login-form {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .form-group label i {
            color: var(--primary-color);
            font-size: 16px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: var(--bg-primary);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        /* ==================== FORM OPTIONS ==================== */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: var(--text-secondary);
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary-color);
        }

        .forgot-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: var(--primary-dark);
        }

        /* ==================== BUTTONS ==================== */
        .btn {
            width: 100%;
            padding: 14px 24px;
            border: none;
            border-radius: var(--radius-md);
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-google {
            background-color: white;
            color: var(--text-primary);
            border: 2px solid var(--border-color);
            margin-top: 16px;
        }

        .btn-google:hover {
            background-color: var(--bg-primary);
            border-color: var(--text-secondary);
        }

        .btn-google i {
            color: #ea4335;
            font-size: 18px;
        }
        
         .btn-facebook {
            background-color: white;
            color: var(--text-primary);
            border: 2px solid var(--border-color);
            margin-top: 16px;
        }

        .btn-facebook:hover {
            background-color: var(--bg-primary);
            border-color: var(--text-secondary);
        }

        .btn-facebook i {
            color: #ea4335;
            font-size: 18px;
        }

       
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 24px 0;
            color: var(--text-secondary);
            font-size: 13px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border-color);
        }

        .divider span {
            padding: 0 16px;
        }

        /* ==================== SIGNUP LINK ==================== */
        .signup-link {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: var(--text-secondary);
        }

        .signup-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .signup-link a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px;
            }

            .login-header h2 {
                font-size: 24px;
            }

            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>





    <div class="container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h2>Welcome Back</h2>
                <p>Please login to your account</p>
            </div>

            <?php if(isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                  
                </div>

                <button type="submit" class="btn btn-primary">
                    <span>Login</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="divider">
                <span>or continue with</span>
            </div>

            <button type="button" class="btn btn-google" onclick="alert('Google login clicked (mock)')">
                <i class="fab fa-google"></i>
                <span>Login with Google</span>
            </button>
            <button type="button" class="btn btn-facebook" onclick="alert('Facebook login clicked (mock)')">
    <i class="fab fa-facebook-f"></i>
    <span>Login with Facebook</span>
</button>


            <div class="signup-link">
                Don't have an account? <a href="signup.php">Sign Up</a>
            </div>
        </div>
    </div>

   
</body>
</html>