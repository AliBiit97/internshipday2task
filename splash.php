<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Splash Screen</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        /* Splash Screen Styles */
        #splash-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease-out;
        }

        #splash-screen.fade-out {
            opacity: 0;
        }

        .splash-content {
            text-align: center;
            color: white;
        }

        .splash-logo {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 60px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .splash-title {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .spinner-dots {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .spinner-dots div {
            width: 15px;
            height: 15px;
            background: white;
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out both;
        }

        .spinner-dots div:nth-child(1) {
            animation-delay: -0.32s;
        }

        .spinner-dots div:nth-child(2) {
            animation-delay: -0.16s;
        }

        @keyframes bounce {
            0%, 80%, 100% {
                transform: scale(0);
            }
            40% {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>

    <!-- Splash Screen -->
    <div id="splash-screen">
        <div class="splash-content">
            <div class="splash-logo">
                🚀
            </div>
            <h1 class="splash-title">MyApp</h1>
            <p class="fs-5">Loading your experience...</p>
            <div class="spinner-dots">
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Splash screen timer - redirects to login page after 3 seconds
        window.addEventListener('load', function() {
            setTimeout(function() {
                const splashScreen = document.getElementById('splash-screen');
                
                // Fade out splash screen
                splashScreen.classList.add('fade-out');
                
                // After fade out animation, redirect to login page
                setTimeout(function() {
                    window.location.href = 'login.php'; // Change this to your login page URL
                }, 500);
            }, 3000); // 3 seconds
        });
    </script>

</body>
</html>