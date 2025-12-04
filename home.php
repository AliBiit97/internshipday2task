<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'db.php'; 

$username = $_SESSION['username'] ?? 'User';
$email = $_SESSION['email'] ?? 'user@example.com';

$banner_query = mysqli_query($conn, "SELECT * FROM banners WHERE active = 1 ORDER BY display_order ASC");
$banners = [];
if(mysqli_num_rows($banner_query) > 0) {
    while($banner = mysqli_fetch_assoc($banner_query)) {
        $banners[] = $banner;
    }
}

if(empty($banners)) {
    $banners = [
        ['id' => 1, 'title' => 'Welcome to Our Store', 'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1200&h=400&fit=crop', 'link' => '#', 'description' => 'Discover amazing products'],
        ['id' => 2, 'title' => 'Summer Sale', 'image_url' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1200&h=400&fit=crop', 'link' => '#', 'description' => 'Up to 50% off on selected items'],
        ['id' => 3, 'title' => 'New Arrivals', 'image_url' => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=1200&h=400&fit=crop', 'link' => '#', 'description' => 'Check out our latest products'],
    ];
}

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

        /* Modal fixes */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex !important;
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease-out;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--text-primary);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-secondary);
            line-height: 1;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }

        .close-btn:hover {
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Rest of your CSS remains the same... */
        .banner-section {
            margin: 0 2rem;
            margin-top: 1rem;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px var(--shadow);
            position: relative;
            height: 400px;
        }

        .banner-slider {
            position: relative;
            height: 100%;
            width: 100%;
        }

        .banner-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            padding: 0 4rem;
        }

        .banner-slide.active {
            opacity: 1;
            z-index: 1;
        }

        .banner-content {
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 500px;
            backdrop-filter: blur(5px);
        }

        .banner-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .banner-description {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        .banner-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .banner-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }

        .banner-controls {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 10px;
            z-index: 2;
        }

        .banner-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: background-color 0.3s, transform 0.3s;
        }

        .banner-dot.active {
            background: white;
            transform: scale(1.2);
        }

        .banner-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            transition: background-color 0.3s;
        }

        .banner-nav:hover {
            background: rgba(0, 0, 0, 0.8);
        }

        .banner-prev {
            left: 20px;
        }

        .banner-next {
            right: 20px;
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
            background: #4A90E2;
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

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 20px;
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
            padding: 20px;
        }

        .profile-item {
            padding: 1rem;
            background-color: var(--bg-secondary);
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .profile-item:last-child {
            margin-bottom: 0;
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
            width: calc(100% - 40px);
            margin: 20px;
            padding: 12px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .banner-section {
                height: 300px;
                margin: 0 1rem;
                margin-top: 1rem;
            }

            .banner-slide {
                padding: 0 2rem;
            }

            .banner-content {
                padding: 1.5rem;
                max-width: 100%;
            }

            .banner-title {
                font-size: 1.5rem;
            }

            .banner-description {
                font-size: 1rem;
            }

            .banner-nav {
                width: 35px;
                height: 35px;
                font-size: 1.2rem;
            }

            .container {
                padding: 0 1rem;
            }

            .cards-container {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                margin: 10px;
            }
        }

        @media (max-width: 480px) {
            .banner-section {
                height: 250px;
            }

            .banner-slide {
                padding: 0 1rem;
            }

            .banner-content {
                padding: 1rem;
            }

            .banner-title {
                font-size: 1.2rem;
            }

            .banner-description {
                font-size: 0.9rem;
            }

            .navbar {
                padding: 1rem;
            }

            .nav-actions {
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🏠 Dashboard</h1>
        <div class="nav-actions">
            <button class="icon-btn" onclick="openModal('settingsModal')" title="Settings">⚙️</button>
            <button class="icon-btn" onclick="openModal('profileModal')" title="Profile">👤</button>
            <button class="login-btn" onclick="window.location.href='logout.php'">Sign-In</button>
        </div>
    </nav>

    <div class="banner-section">
        <div class="banner-slider" id="bannerSlider">
            <?php foreach($banners as $index => $banner): ?>
                <div class="banner-slide <?php echo $index === 0 ? 'active' : ''; ?>" 
                     style="background-image: url('<?php echo htmlspecialchars($banner['image_url']); ?>')">
                    <div class="banner-content">
                        <h2 class="banner-title"><?php echo htmlspecialchars($banner['title']); ?></h2>
                        <p class="banner-description"><?php echo htmlspecialchars($banner['description'] ?? ''); ?></p>
                        <?php if(!empty($banner['link'])): ?>
                            <a href="<?php echo htmlspecialchars($banner['link']); ?>" class="banner-btn">
                                Learn More
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <button class="banner-nav banner-prev" onclick="prevSlide()">❮</button>
            <button class="banner-nav banner-next" onclick="nextSlide()">❯</button>
            
            <div class="banner-controls">
                <?php foreach($banners as $index => $banner): ?>
                    <div class="banner-dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                         onclick="goToSlide(<?php echo $index; ?>)"></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

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

    <!-- Settings Modal -->
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
                    <input type="checkbox" id="themeToggle">
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

            <button class="logout-btn" onclick="window.location.href='user_chat.php'">My Account</button>
        </div>
    </div>

    <!-- Profile Modal -->
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
        // Banner slider functionality
        let currentSlide = 0;
        const slides = document.querySelectorAll('.banner-slide');
        const dots = document.querySelectorAll('.banner-dot');
        let slideInterval;

        function showSlide(n) {
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            currentSlide = (n + slides.length) % slides.length;
            
            slides[currentSlide].classList.add('active');
            dots[currentSlide].classList.add('active');
        }

        function nextSlide() {
            showSlide(currentSlide + 1);
            resetInterval();
        }

        function prevSlide() {
            showSlide(currentSlide - 1);
            resetInterval();
        }

        function goToSlide(n) {
            showSlide(n);
            resetInterval();
        }

        function startAutoSlide() {
            slideInterval = setInterval(() => {
                nextSlide();
            }, 5000);
        }

        function resetInterval() {
            clearInterval(slideInterval);
            startAutoSlide();
        }

        // Modal functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden'; // Prevent scrolling
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = ''; // Restore scrolling
            }
        }

        // Theme toggle
        function toggleTheme() {
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                const isDarkMode = themeToggle.checked;
                document.body.classList.toggle('dark-theme', isDarkMode);
                localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
            }
        }

        // Initialize theme from localStorage
        document.addEventListener('DOMContentLoaded', () => {
            // Start banner slider
            startAutoSlide();
            
            // Initialize theme
            const savedTheme = localStorage.getItem('theme');
            const themeToggle = document.getElementById('themeToggle');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-theme');
                if (themeToggle) themeToggle.checked = true;
            }
            
            // Banner hover pause
            const banner = document.querySelector('.banner-slider');
            if (banner) {
                banner.addEventListener('mouseenter', () => clearInterval(slideInterval));
                banner.addEventListener('mouseleave', startAutoSlide);
            }
            
            // Theme toggle event
            if (themeToggle) {
                themeToggle.addEventListener('change', toggleTheme);
            }
            
            // Notification toggle
            const notificationToggle = document.getElementById('notificationToggle');
            if (notificationToggle) {
                notificationToggle.addEventListener('change', function() {
                    localStorage.setItem('notifications', this.checked);
                });
                
                // Load saved notification preference
                const savedNotifications = localStorage.getItem('notifications');
                if (savedNotifications !== null) {
                    notificationToggle.checked = savedNotifications === 'true';
                }
            }
            
            // Close modal when clicking outside
            window.addEventListener('click', (event) => {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (event.target === modal) {
                        closeModal(modal.id);
                    }
                });
            });
            
            // Close modal with Escape key
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    const modals = document.querySelectorAll('.modal.active');
                    modals.forEach(modal => {
                        closeModal(modal.id);
                    });
                }
            });
        });

        function logout() {
            if(confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>