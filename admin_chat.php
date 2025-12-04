<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];

$sql = "SELECT 
            u.id, 
            u.username, 
            u.email,
            COUNT(CASE WHEN m.sent_by = 'user' AND m.is_read = FALSE THEN 1 END) as unread_count,
            MAX(m.created_at) as last_message_time
        FROM users u
        LEFT JOIN messages m ON u.id = m.user_id
        GROUP BY u.id, u.username, u.email
        HAVING COUNT(m.id) > 0
        ORDER BY last_message_time DESC";

$users_result = mysqli_query($conn, $sql);

$selected_user = null;
$user_messages = [];

if (isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];

    $sql = "SELECT * FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $sql);
    $selected_user = mysqli_fetch_assoc($result);

    if (isset($_POST['send_reply'])) {
        $message = mysqli_real_escape_string($conn, $_POST['admin_message']);
        $sql = "INSERT INTO messages (user_id, admin_id, sent_by, message) 
                VALUES ($user_id, $admin_id, 'admin', '$message')";
        mysqli_query($conn, $sql);
 
        header("Location: ?user_id=$user_id");
        exit();
    }
    

    $sql = "SELECT m.*, u.username as user_name, a.username as admin_name
            FROM messages m
            LEFT JOIN users u ON m.user_id = u.id
            LEFT JOIN admins a ON m.admin_id = a.id
            WHERE m.user_id = $user_id 
            ORDER BY m.created_at ASC";
    $user_messages = mysqli_query($conn, $sql);

    $sql = "UPDATE messages SET is_read = TRUE 
            WHERE user_id = $user_id 
            AND sent_by = 'user' 
            AND is_read = FALSE";
    mysqli_query($conn, $sql);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Chat Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            overflow: hidden;
        }
        
        .admin-container {
            display: flex;
            height: 100vh;
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
            gap: 20px;
        }
        
        .sidebar {
            width: 360px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .sidebar-header h3 {
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-header p {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 5px;
            font-weight: 400;
        }
        
        .search-box {
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .users-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }
        
        .users-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .users-list::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .users-list::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }
        
        .user-item {
            padding: 16px;
            margin-bottom: 8px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }
        
        .user-item:hover {
            background: #f3f4f6;
            transform: translateX(2px);
        }
        
        .user-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .user-item.active .user-avatar {
            background: white;
            color: #667eea;
        }
        
        .user-info {
            flex: 1;
            min-width: 0;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-email {
            font-size: 12px;
            opacity: 0.7;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-time {
            font-size: 11px;
            opacity: 0.6;
            margin-top: 4px;
        }
        
        .unread-badge {
            background: #ef4444;
            color: white;
            border-radius: 20px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            position: absolute;
            top: 12px;
            right: 12px;
        }
        
        .user-item.active .unread-badge {
            background: white;
            color: #ef4444;
        }
        
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .chat-header {
            background: white;
            padding: 25px 30px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .chat-header-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 20px;
        }
        
        .chat-header-info {
            flex: 1;
        }
        
        .chat-header-name {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }
        
        .chat-header-email {
            font-size: 14px;
            color: #6b7280;
        }
        
        .messages-container {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background: #f9fafb;
        }
        
        .messages-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .messages-container::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .messages-container::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }
        
        .message {
            margin: 20px 0;
            display: flex;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message-bubble {
            max-width: 65%;
            padding: 16px 20px;
            border-radius: 18px;
            position: relative;
        }
        
        .user-message {
            justify-content: flex-start;
        }
        
        .user-message .message-bubble {
            background: white;
            border: 1px solid #e5e7eb;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .admin-message {
            justify-content: flex-end;
        }
        
        .admin-message .message-bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .sender-name {
            font-weight: 600;
        }
        
        .message-time {
            opacity: 0.7;
            font-size: 11px;
            margin-left: 10px;
        }
        
        .message-text {
            font-size: 15px;
            line-height: 1.5;
            word-wrap: break-word;
        }
        
        .reply-form {
            background: white;
            padding: 25px 30px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .reply-form input {
            flex: 1;
            padding: 16px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 14px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }
        
        .reply-form input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .reply-form button {
            padding: 16px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 14px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }
        
        .reply-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .reply-form button:active {
            transform: translateY(0);
        }
        
        .no-user-selected {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            color: #9ca3af;
        }
        
        .no-user-selected-content {
            padding: 40px;
        }
        
        .no-user-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .no-user-text {
            font-size: 18px;
            font-weight: 500;
            color: #6b7280;
        }
        
        .no-messages {
            text-align: center;
            color: #9ca3af;
            margin-top: 60px;
        }
        
        .no-messages-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .no-messages-text {
            font-size: 16px;
        }
        
        .online-indicator {
            width: 12px;
            height: 12px;
            background: #10b981;
            border-radius: 50%;
            border: 2px solid white;
            position: absolute;
            bottom: 10px;
            right: 2px;
        }
        
        @media (max-width: 1024px) {
            .admin-container {
                padding: 10px;
                gap: 10px;
            }
            
            .sidebar {
                width: 300px;
            }
        }
        
        @media (max-width: 768px) {
            .admin-container {
                padding: 0;
                gap: 0;
            }
            
            .sidebar {
                position: absolute;
                z-index: 10;
                width: 100%;
                height: 100%;
                border-radius: 0;
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .chat-area {
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">

        <div class="sidebar">
            <div class="sidebar-header">
                <h3>
                    <span>💬</span>
                    <span>Messages</span>
                </h3>
                <p>Manage customer conversations</p>
            </div>
            
            <div class="search-box">
                <input type="text" placeholder="🔍 Search conversations..." id="searchInput">
            </div>
            
            <div class="users-list" id="usersList">
                <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                    <div class="user-item <?php echo ($selected_user && $selected_user['id'] == $user['id']) ? 'active' : ''; ?>"
                         onclick="location.href='?user_id=<?php echo $user['id']; ?>'">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            <div class="online-indicator"></div>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            <div class="user-time">
                                <?php echo $user['last_message_time'] ? date('g:i A', strtotime($user['last_message_time'])) : 'No messages'; ?>
                            </div>
                        </div>
                        <?php if($user['unread_count'] > 0): ?>
                            <div class="unread-badge"><?php echo $user['unread_count']; ?></div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        

        <div class="chat-area">
            <?php if($selected_user): ?>
          
                <div class="chat-header">
                    <div class="chat-header-avatar">
                        <?php echo strtoupper(substr($selected_user['username'], 0, 1)); ?>
                        <div class="online-indicator"></div>
                    </div>
                    <div class="chat-header-info">
                        <div class="chat-header-name"><?php echo htmlspecialchars($selected_user['username']); ?></div>
                        <div class="chat-header-email"><?php echo htmlspecialchars($selected_user['email']); ?></div>
                    </div>
                </div>
      
                <div class="messages-container" id="messagesContainer">
                    <?php if($user_messages && mysqli_num_rows($user_messages) > 0): ?>
                        <?php while($msg = mysqli_fetch_assoc($user_messages)): ?>
                            <div class="message <?php echo $msg['sent_by'] == 'admin' ? 'admin-message' : 'user-message'; ?>">
                                <div class="message-bubble">
                                    <div class="message-header">
                                        <span class="sender-name">
                                            <?php 
                                            if($msg['sent_by'] == 'user') {
                                                echo htmlspecialchars($msg['user_name']);
                                            } else {
                                                echo "You" . ($msg['admin_name'] ? " (" . htmlspecialchars($msg['admin_name']) . ")" : "");
                                            }
                                            ?>
                                        </span>
                                        <span class="message-time">
                                            <?php echo date('g:i A', strtotime($msg['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="message-text"><?php echo htmlspecialchars($msg['message']); ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-messages">
                            <div class="no-messages-icon">💬</div>
                            <div class="no-messages-text">No messages yet. Start the conversation!</div>
                        </div>
                    <?php endif; ?>
                </div>
                
           
                <form method="POST" class="reply-form">
                    <input type="text" name="admin_message" placeholder="Type your message..." required autocomplete="off">
                    <button type="submit" name="send_reply">Send ➤</button>
                </form>
            <?php else: ?>
                <div class="no-user-selected">
                    <div class="no-user-selected-content">
                        <div class="no-user-icon">💭</div>
                        <div class="no-user-text">Select a conversation to start messaging</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if($selected_user): ?>
    <script>
 
        function scrollToBottom() {
            const container = document.getElementById('messagesContainer');
            container.scrollTop = container.scrollHeight;
        }
        

        setInterval(function() {
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    const newMessages = tempDiv.querySelector('#messagesContainer')?.innerHTML;
                    if(newMessages) {
                        const currentScroll = document.getElementById('messagesContainer').scrollTop;
                        const currentHeight = document.getElementById('messagesContainer').scrollHeight;
                        document.getElementById('messagesContainer').innerHTML = newMessages;
                        const newHeight = document.getElementById('messagesContainer').scrollHeight;
                        if(currentScroll + document.getElementById('messagesContainer').clientHeight >= currentHeight - 50) {
                            scrollToBottom();
                        }
                    }
                });
        }, 3000);

        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const userItems = document.querySelectorAll('.user-item');
            
            userItems.forEach(item => {
                const username = item.querySelector('.user-name').textContent.toLowerCase();
                const email = item.querySelector('.user-email').textContent.toLowerCase();
                
                if(username.includes(searchTerm) || email.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        scrollToBottom();
    </script>
    <?php endif; ?>
</body>
</html>