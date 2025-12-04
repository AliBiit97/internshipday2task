<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['send'])) {
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    $admin_sql = "SELECT id FROM admins LIMIT 1";
    $admin_result = mysqli_query($conn, $admin_sql);
    $admin = mysqli_fetch_assoc($admin_result);
    
    if ($admin) {
        $admin_id = $admin['id'];
        $sql = "INSERT INTO messages (user_id, admin_id, sent_by, message) 
                VALUES ($user_id, $admin_id, 'user', '$message')";
        mysqli_query($conn, $sql);
    }
}

$sql = "SELECT m.*, u.username as user_name, a.username as admin_name
        FROM messages m
        LEFT JOIN users u ON m.user_id = u.id
        LEFT JOIN admins a ON m.admin_id = a.id
        WHERE m.user_id = $user_id 
        ORDER BY m.created_at ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Chat with Support</title>
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
            padding: 20px;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Navigation Bar */
        .nav-bar {
            background: white;
            border-radius: 15px;
            padding: 20px 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .nav-brand {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        
        .nav-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .nav-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .nav-btn.policy {
            background: #f0f0f0;
            color: #333;
        }
        
        .nav-btn.orders {
            background: #f0f0f0;
            color: #333;
        }
        
        .nav-btn.help {
            background: #f0f0f0;
            color: #333;
        }
        
        .nav-btn.support {
            background: #667eea;
            color: white;
        }
        
        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .nav-btn.policy:hover {
            background: #e0e0e0;
        }
        
        .nav-btn.orders:hover {
            background: #e0e0e0;
        }
        
        .nav-btn.help:hover {
            background: #e0e0e0;
        }
        
        .nav-btn.support:hover {
            background: #5568d3;
        }
        
        /* Chat Container */
        .chat-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            display: none;
        }
        
        .chat-container.active {
            display: block;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .chat-header h2 {
            color: #333;
            font-size: 24px;
        }
        
        .close-chat {
            background: #ff4757;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .close-chat:hover {
            background: #ee2f3a;
            transform: scale(1.05);
        }
        
        .messages {
            height: 500px;
            overflow-y: auto;
            border: 2px solid #f0f0f0;
            padding: 20px;
            margin-bottom: 20px;
            background: linear-gradient(to bottom, #f9f9f9, #ffffff);
            border-radius: 10px;
        }
        
        .messages::-webkit-scrollbar {
            width: 8px;
        }
        
        .messages::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .messages::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }
        
        .message {
            margin: 15px 0;
            padding: 12px 18px;
            border-radius: 15px;
            max-width: 70%;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .user-message {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        
        .admin-message {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
            margin-right: auto;
            border-bottom-left-radius: 5px;
        }
        
        .sender-name {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 13px;
        }
        
        .message-text {
            font-size: 15px;
            line-height: 1.4;
        }
        
        .time {
            font-size: 11px;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        .message-form {
            display: flex;
            gap: 10px;
        }
        
        .message-form input {
            flex: 1;
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .message-form input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .message-form button {
            padding: 14px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .message-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .message-form button:active {
            transform: translateY(0);
        }
        
        /* Welcome Screen */
        .welcome-screen {
            background: white;
            border-radius: 15px;
            padding: 60px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        
        .welcome-screen h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 32px;
        }
        
        .welcome-screen p {
            color: #666;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .nav-bar {
                padding: 15px;
            }
            
            .nav-brand {
                font-size: 20px;
            }
            
            .nav-buttons {
                width: 100%;
                justify-content: center;
            }
            
            .nav-btn {
                font-size: 13px;
                padding: 8px 15px;
            }
            
            .message {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">

        <div class="nav-bar">
            <div class="nav-brand">🛍️ User Dashboard</div>
            <div class="nav-buttons">
                <button class="nav-btn policy" onclick="window.location.href='contact_support.php'">📋 Policy</button>
                <button class="nav-btn orders" onclick="window.location.href='order_details.php'">📦 My Orders</button>
                <button class="nav-btn help" onclick="window.location.href='contact_support.php'">❓ Help</button>
                <button class="nav-btn support" onclick="toggleChat()">💬 Chat with Us</button>
            </div>
        </div>
        

        <div class="welcome-screen" id="welcomeScreen">
            <h1>👋 Welcome Back!</h1>
            <p>Click on "Chat with Us" to start a conversation with our support team.</p>
            <p>We're here to help you 24/7!</p>
        </div>

        <div class="chat-container" id="chatContainer">
            <div class="chat-header">
                <h2>💬 Chat Support</h2>
                <button class="close-chat" onclick="toggleChat()">✕ Close</button>
            </div>
            
            <div class="messages" id="messagesBox">
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <div class="message <?php echo $row['sent_by'] == 'user' ? 'user-message' : 'admin-message'; ?>">
                        <div class="sender-name">
                            <?php 
                            if($row['sent_by'] == 'user') {
                                echo "You";
                            } else {
                                echo "Support" . ($row['admin_name'] ? " (" . $row['admin_name'] . ")" : "");
                            }
                            ?>
                        </div>
                        <div class="message-text"><?php echo htmlspecialchars($row['message']); ?></div>
                        <div class="time"><?php echo $row['created_at']; ?></div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <form method="POST" class="message-form">
                <input type="text" name="message" placeholder="Type your message..." required>
                <button type="submit" name="send">Send</button>
            </form>
        </div>
    </div>
    
    <script>
 
        function toggleChat() {
            const chatContainer = document.getElementById('chatContainer');
            const welcomeScreen = document.getElementById('welcomeScreen');
            
            if (chatContainer.classList.contains('active')) {
                chatContainer.classList.remove('active');
                welcomeScreen.style.display = 'block';
            } else {
                chatContainer.classList.add('active');
                welcomeScreen.style.display = 'none';
                scrollToBottom();
            }
        }
        

        function scrollToBottom() {
            const messagesBox = document.getElementById('messagesBox');
            messagesBox.scrollTop = messagesBox.scrollHeight;
        }
 
        setInterval(function() {
            if (document.getElementById('chatContainer').classList.contains('active')) {
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
 
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;
                        const newMessages = tempDiv.querySelector('#messagesBox').innerHTML;
                        document.getElementById('messagesBox').innerHTML = newMessages;
                        scrollToBottom();
                    });
            }
        }, 3000);
    </script>
</body>
</html>