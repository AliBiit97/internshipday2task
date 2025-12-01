<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php'; 

$username = $_SESSION['username'] ?? 'User';
$email = $_SESSION['email'] ?? 'user@example.com';


$categories_query = mysqli_query($conn, "SELECT * FROM categories ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Home</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --border-color: #dee2e6;
            --card-bg: #ffffff;
            --primary-color: #007bff;
            --primary-hover: #0056b3;
            --shadow: rgba(0, 0, 0, 0.1);
        }

        body.dark-theme {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --border-color: #404040;
            --card-bg: #2d2d2d;
            --shadow: rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s, color 0.3s;
        }

        .navbar {
            background-color: var(--card-bg);
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .navbar h1 {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .nav-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background-color 0.3s;
            color: var(--text-primary);
            font-size: 1.2rem;
        }

        .icon-btn:hover {
            background-color: var(--bg-secondary);
        }
        .login-btn {
    background: #4A90E2;       /* Blue theme */
    color: #fff;
    border: none;
    padding: 8px 16px;
    font-size: 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.25s ease-in-out;
}

.login-btn:hover {
    background: #357ABD;
    transform: translateY(-2px);
}

.login-btn:active {
    transform: translateY(0);
}

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 3rem 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            color: white;
            box-shadow: 0 4px 6px var(--shadow);
        }

        .welcome-section h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .welcome-section p {
            opacity: 0.9;
        }

        .cards-section h3 {
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 2rem;
            border-radius: 12px;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 2px 4px var(--shadow);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px var(--shadow);
        }

        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .card h4 {
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .card p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .no-categories {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }

        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: var(--card-bg);
            padding: 2rem;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideUp 0.3s;
            box-shadow: 0 8px 16px var(--shadow);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-header h3 {
            color: var(--text-primary);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
        }

        .close-btn:hover {
            color: var(--text-primary);
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-info h4 {
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }

        .setting-info p {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 26px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary-color);
        }

        input:checked + .slider:before {
            transform: translateX(24px);
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .profile-item {
            padding: 1rem;
            background-color: var(--bg-secondary);
            border-radius: 8px;
        }

        .profile-item label {
            display: block;
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }

        .profile-item .value {
            font-size: 1rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        .logout-btn {
            width: 100%;
            padding: 0.75rem;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 1rem;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🏠 Dashboard</h1>
        <div class="nav-actions">
            <button class="icon-btn" onclick="openSettingsModal()" title="Settings">⚙️</button>
            <button class="icon-btn" onclick="openProfileModal()" title="Profile">👤</button>
             <button class="login-btn" onclick="window.location.href='login.php'" title="Profile">Login</button>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-section">
            <h2>Welcome back, <?php echo htmlspecialchars($username); ?>! 👋</h2>
            <p>Here's what's happening with your account today.</p>
        </div>

        <div class="cards-section">
            <h3>Categories</h3>
            <div class="cards-container">
                <?php
                if(mysqli_num_rows($categories_query) > 0) {
                    while($category = mysqli_fetch_assoc($categories_query)) {
                        $category_id = $category['id'];
                        $category_name = htmlspecialchars($category['name']);
                        $category_description = htmlspecialchars($category['description'] ?? 'Explore our ' . $category['name'] . ' collection');
                        $category_icon = htmlspecialchars($category['icon'] ?? '📦');
                        ?>
                        <div class="card" onclick="window.location.href='Items.php?category_id=<?php echo $category_id; ?>'">
                            <div class="card-icon"><?php echo $category_icon; ?></div>
                            <h4><?php echo $category_name; ?></h4>
                            <p><?php echo $category_description; ?></p>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="no-categories">
                            <p>No categories available at the moment.</p>
                          </div>';
                }
                ?>
            </div>
        </div>
    </div>

    <div id="settingsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>⚙️ Settings</h3>
                <button class="close-btn" onclick="closeModal('settingsModal')">&times;</button>
            </div>

            <div class="setting-item">
                <div class="setting-info">
                    <h4>Dark Theme</h4>
                    <p>Toggle between light and dark mode</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="themeToggle" onchange="toggleTheme()">
                    <span class="slider"></span>
                </label>
            </div>

            <div class="setting-item">
                <div class="setting-info">
                    <h4>Notifications</h4>
                    <p>Enable or disable notifications</p>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="notificationToggle" checked>
                    <span class="slider"></span>
                </label>
            </div>

            <button class="logout-btn" onclick="logout()">Logout</button>
        </div>
    </div>

    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>👤 My Profile</h3>
                <button class="close-btn" onclick="closeModal('profileModal')">&times;</button>
            </div>

            <div class="profile-info">
                <div class="profile-item">
                    <label>Username</label>
                    <div class="value"><?php echo htmlspecialchars($username); ?></div>
                </div>

                <div class="profile-item">
                    <label>Email Address</label>
                    <div class="value"><?php echo htmlspecialchars($email); ?></div>
                </div>

                <div class="profile-item">
                    <label>User ID</label>
                    <div class="value"><?php echo htmlspecialchars($_SESSION['user_id']); ?></div>
                </div>

                <div class="profile-item">
                    <label>Account Status</label>
                    <div class="value">✅ Active</div>
                </div>
            </div>
        </div>
    </div>

    <script>
       
        if(localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-theme');
            document.getElementById('themeToggle').checked = true;
        }

        if(localStorage.getItem('notifications') === 'false') {
            document.getElementById('notificationToggle').checked = false;
        }

        function openSettingsModal() {
            document.getElementById('settingsModal').classList.add('active');
        }

        function openProfileModal() {
            document.getElementById('profileModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function toggleTheme() {
            const isChecked = document.getElementById('themeToggle').checked;
            if(isChecked) {
                document.body.classList.add('dark-theme');
                localStorage.setItem('theme', 'dark');
            } else {
                document.body.classList.remove('dark-theme');
                localStorage.setItem('theme', 'light');
            }
        }

        
        document.getElementById('notificationToggle').addEventListener('change', function() {
            localStorage.setItem('notifications', this.checked);
            alert('Notification settings saved!');
        });

        function logout() {
            if(confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

     
        window.onclick = function(event) {
            if(event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>