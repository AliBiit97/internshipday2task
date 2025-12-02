<?php
session_start();
include 'db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_banner'])) {
        // Add new banner
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $link = mysqli_real_escape_string($conn, $_POST['link']);
        $display_order = intval($_POST['display_order']);
        $active = isset($_POST['active']) ? 1 : 0;
        
        $sql = "INSERT INTO banners (title, image_url, description, link, display_order, active) 
                VALUES ('$title', '$image_url', '$description', '$link', $display_order, $active)";
        
        if(mysqli_query($conn, $sql)) {
            $success_message = "Banner added successfully!";
        } else {
            $error_message = "Error adding banner: " . mysqli_error($conn);
        }
    }
    
    if(isset($_POST['update_banner'])) {
       
        $id = intval($_POST['banner_id']);
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $link = mysqli_real_escape_string($conn, $_POST['link']);
        $display_order = intval($_POST['display_order']);
        $active = isset($_POST['active']) ? 1 : 0;
        
        $sql = "UPDATE banners SET 
                title = '$title',
                image_url = '$image_url',
                description = '$description',
                link = '$link',
                display_order = $display_order,
                active = $active
                WHERE id = $id";
        
        if(mysqli_query($conn, $sql)) {
            $success_message = "Banner updated successfully!";
        } else {
            $error_message = "Error updating banner: " . mysqli_error($conn);
        }
    }
    
    if(isset($_POST['delete_banner'])) {
    
        $id = intval($_POST['banner_id']);
        
        $sql = "DELETE FROM banners WHERE id = $id";
        
        if(mysqli_query($conn, $sql)) {
            $success_message = "Banner deleted successfully!";
        } else {
            $error_message = "Error deleting banner: " . mysqli_error($conn);
        }
    }
}


$banners_query = mysqli_query($conn, "SELECT * FROM banners ORDER BY display_order ASC, created_at DESC");
$banners = [];
if(mysqli_num_rows($banners_query) > 0) {
    while($banner = mysqli_fetch_assoc($banners_query)) {
        $banners[] = $banner;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Banner Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .admin-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .admin-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .admin-nav {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .nav-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .nav-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .nav-btn.dashboard {
            background: #28a745;
        }

        .nav-btn.dashboard:hover {
            background: #218838;
        }

        .admin-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        @media (max-width: 1024px) {
            .admin-content {
                grid-template-columns: 1fr;
            }
        }

        /* Form Styles */
        .form-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .form-section h2 {
            margin-bottom: 1.5rem;
            color: #333;
            font-size: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #007bff;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }

        .form-group input[type="text"],
        .form-group input[type="url"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .form-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-submit {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            flex: 1;
        }

        .btn-submit:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-update {
            background: #007bff;
        }

        .btn-update:hover {
            background: #0056b3;
        }

        .btn-delete {
            background: #dc3545;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .btn-cancel {
            background: #6c757d;
            flex: 0.5;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        /* Banners List Styles */
        .banners-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .banners-section h2 {
            margin-bottom: 1.5rem;
            color: #333;
            font-size: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #007bff;
        }

        .banners-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .banner-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .banner-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .banner-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #dee2e6;
        }

        .banner-content {
            padding: 1.5rem;
        }

        .banner-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .banner-description {
            color: #666;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .banner-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }

        .banner-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .banner-order {
            color: #666;
            font-weight: 600;
        }

        .banner-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            flex: 1;
        }

        .btn-edit {
            background: #17a2b8;
            color: white;
        }

        .btn-edit:hover {
            background: #138496;
        }

        .btn-delete-card {
            background: #dc3545;
            color: white;
        }

        .btn-delete-card:hover {
            background: #c82333;
        }

        .no-banners {
            text-align: center;
            padding: 3rem;
            color: #666;
            grid-column: 1 / -1;
        }

        /* Messages */
        .message {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            animation: slideDown 0.3s ease;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        /* Preview Section */
        .preview-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .preview-section h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .banner-preview {
            width: 100%;
            height: 200px;
            background-size: cover;
            background-position: center;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }

        .preview-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 1rem;
        }

        .preview-content h4 {
            margin-bottom: 0.25rem;
        }

        .preview-content p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

       
        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

       
        @media (max-width: 768px) {
            .admin-container {
                padding: 10px;
            }

            .admin-header {
                padding: 1.5rem;
            }

            .admin-header h1 {
                font-size: 2rem;
            }

            .form-section,
            .banners-section {
                padding: 1.5rem;
            }

            .banners-grid {
                grid-template-columns: 1fr;
            }

            .form-buttons,
            .banner-actions {
                flex-direction: column;
            }

            .form-buttons button,
            .banner-actions button {
                width: 100%;
            }
        }

        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="admin-container">
    
        <div class="admin-header">
            <h1>🎯 Banner Management</h1>
            <p>Add, edit, and manage banners for your website</p>
        </div>

       
        <div class="admin-nav">
            <a href="dashboard.php" class="nav-btn dashboard">← Back to Dashboard</a>
            <button class="nav-btn" onclick="resetForm()">➕ Add New Banner</button>
            <button class="nav-btn" onclick="refreshPage()">🔄 Refresh List</button>
        </div>

      
        <?php if(isset($success_message)): ?>
            <div class="message success">
                ✅ <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if(isset($error_message)): ?>
            <div class="message error">
                ❌ <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        
        <div class="admin-content">
           
            <div class="form-section">
                <h2 id="formTitle">Add New Banner</h2>
                <form id="bannerForm" method="POST" action="">
                    <input type="hidden" id="banner_id" name="banner_id" value="">
                    
                    <div class="form-group">
                        <label for="title">Banner Title *</label>
                        <input type="text" id="title" name="title" required 
                               placeholder="Enter banner title (e.g., Summer Sale)"
                               maxlength="255">
                    </div>

                    <div class="form-group">
                        <label for="image_url">Image URL *</label>
                        <input type="url" id="image_url" name="image_url" required 
                               placeholder="https://example.com/image.jpg"
                               onchange="updatePreview()">
                        <small style="color: #666; margin-top: 0.25rem; display: block;">
                            Tip: Use high-quality images (1200x400px recommended)
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" 
                                  placeholder="Enter banner description (optional)"
                                  maxlength="500"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="link">Link URL</label>
                        <input type="url" id="link" name="link" 
                               placeholder="https://example.com (optional)">
                    </div>

                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" 
                               value="0" min="0" max="100">
                        <small style="color: #666; margin-top: 0.25rem; display: block;">
                            Lower numbers appear first
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="active" name="active" value="1" checked>
                            <label for="active">Active (Show on website)</label>
                        </div>
                    </div>

                   
                    <div class="preview-section">
                        <h3>Preview</h3>
                        <div id="bannerPreview" class="banner-preview">
                            <div class="preview-content" id="previewContent">
                                <h4 id="previewTitle">Banner Title</h4>
                                <p id="previewDescription">Banner description will appear here</p>
                                <a href="#" id="previewLink" class="nav-btn" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Learn More</a>
                            </div>
                        </div>
                    </div>

                   
                    <div class="form-buttons">
                        <button type="submit" name="add_banner" id="submitBtn" class="btn-submit">➕ Add Banner</button>
                        <button type="submit" name="update_banner" id="updateBtn" class="btn-submit btn-update" style="display: none;">✏️ Update Banner</button>
                        <button type="submit" name="delete_banner" id="deleteBtn" class="btn-submit btn-delete" style="display: none;">🗑️ Delete Banner</button>
                        <button type="button" onclick="resetForm()" class="btn-cancel">Cancel</button>
                    </div>
                </form>
            </div>

            
            <div class="banners-section">
                <h2>Current Banners (<?php echo count($banners); ?>)</h2>
                
                <?php if(empty($banners)): ?>
                    <div class="no-banners">
                        <p style="font-size: 1.2rem; margin-bottom: 1rem;">🎈 No banners yet</p>
                        <p>Click "Add New Banner" to create your first banner!</p>
                    </div>
                <?php else: ?>
                    <div class="banners-grid">
                        <?php foreach($banners as $banner): ?>
                            <div class="banner-card">
                                <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                                     class="banner-image"
                                     onerror="this.src='https://via.placeholder.com/300x200?text=Banner+Image'">
                                <div class="banner-content">
                                    <h3 class="banner-title"><?php echo htmlspecialchars($banner['title']); ?></h3>
                                    <p class="banner-description">
                                        <?php echo htmlspecialchars($banner['description'] ?: 'No description'); ?>
                                    </p>
                                    <div class="banner-info">
                                        <span class="banner-order">Order: <?php echo $banner['display_order']; ?></span>
                                        <span class="banner-status <?php echo $banner['active'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $banner['active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                    <div class="banner-actions">
                                        <button class="btn-action btn-edit" 
                                                onclick="editBanner(<?php echo $banner['id']; ?>)">
                                            ✏️ Edit
                                        </button>
                                        <button class="btn-action btn-delete-card" 
                                                onclick="confirmDelete(<?php echo $banner['id']; ?>, '<?php echo addslashes($banner['title']); ?>')">
                                            🗑️ Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
       
        function updatePreview() {
            const imageUrl = document.getElementById('image_url').value;
            const title = document.getElementById('title').value || 'Banner Title';
            const description = document.getElementById('description').value || 'Banner description will appear here';
            const link = document.getElementById('link').value || '#';

            const preview = document.getElementById('bannerPreview');
            const previewTitle = document.getElementById('previewTitle');
            const previewDescription = document.getElementById('previewDescription');
            const previewLink = document.getElementById('previewLink');

            preview.style.backgroundImage = `url('${imageUrl}')`;
            previewTitle.textContent = title;
            previewDescription.textContent = description;
            previewLink.href = link;
        }

        
        function editBanner(bannerId) {
        
            fetch(`get_banner.php?id=${bannerId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const banner = data.banner;
                        
                       
                        document.getElementById('banner_id').value = banner.id;
                        document.getElementById('title').value = banner.title;
                        document.getElementById('image_url').value = banner.image_url;
                        document.getElementById('description').value = banner.description || '';
                        document.getElementById('link').value = banner.link || '';
                        document.getElementById('display_order').value = banner.display_order;
                        document.getElementById('active').checked = banner.active == 1;
                        document.getElementById('formTitle').textContent = 'Edit Banner';
                        document.getElementById('submitBtn').style.display = 'none';
                        document.getElementById('updateBtn').style.display = 'block';
                        document.getElementById('deleteBtn').style.display = 'block';
                
                        updatePreview();
                        
                       
                        document.querySelector('.form-section').scrollIntoView({ 
                            behavior: 'smooth' 
                        });
                    } else {
                        alert('Error loading banner: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading banner details');
                });
        }

      
        function resetForm() {
            document.getElementById('bannerForm').reset();
            document.getElementById('banner_id').value = '';
            document.getElementById('formTitle').textContent = 'Add New Banner';
            document.getElementById('submitBtn').style.display = 'block';
            document.getElementById('updateBtn').style.display = 'none';
            document.getElementById('deleteBtn').style.display = 'none';
        
            document.getElementById('bannerPreview').style.backgroundImage = '';
            document.getElementById('previewTitle').textContent = 'Banner Title';
            document.getElementById('previewDescription').textContent = 'Banner description will appear here';
            document.getElementById('previewLink').href = '#';
        }

       
        function confirmDelete(bannerId, bannerTitle) {
            if(confirm(`Are you sure you want to delete the banner "${bannerTitle}"? This action cannot be undone.`)) {
                document.getElementById('banner_id').value = bannerId;
                document.getElementById('deleteBtn').click();
            }
        }

     
        function refreshPage() {
            location.reload();
        }

        document.getElementById('title').addEventListener('input', updatePreview);
        document.getElementById('description').addEventListener('input', updatePreview);
        document.getElementById('link').addEventListener('input', updatePreview);

        updatePreview();
    </script>
</body>
</html>