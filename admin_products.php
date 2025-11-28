<?php
session_start();
require "db.php";

if(!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}


$manufacturers = $conn->query("SELECT * FROM manufacturers");
$categories = $conn->query("SELECT * FROM categories");
$attributes = $conn->query("SELECT * FROM attributes");

$message = '';
$message_type = '';

if(isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
  
    $conn->query("DELETE FROM product_attributes WHERE product_id = $delete_id");
    
   
    if($conn->query("DELETE FROM products WHERE id = $delete_id")) {
        $message = "Product deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting product!";
        $message_type = "error";
    }
}

if(isset($_POST['update_product'])) {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $manufacturer_id = $_POST['manufacturer'];
    $category_id = $_POST['category'];
    $keywords = $_POST['keywords'];
    $image_url = $_POST['image_url'];
    $short_desc = $_POST['short_desc'];
    $long_desc = $_POST['long_desc'];
    $meta_desc = $_POST['meta_desc'];
    $valid_from = $_POST['valid_from'];
    $enabled = isset($_POST['enabled']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE products SET name=?, price=?, manufacturer_id=?, category_id=?, keywords=?, image_url=?, short_desc=?, long_desc=?, meta_desc=?, valid_from=?, enabled=? WHERE id=?");
    
    if($stmt) {
        $stmt->bind_param("sdiissssssii", $name, $price, $manufacturer_id, $category_id, $keywords, $image_url, $short_desc, $long_desc, $meta_desc, $valid_from, $enabled, $product_id);
        
        if($stmt->execute()) {
           
            $conn->query("DELETE FROM product_attributes WHERE product_id = $product_id");
            
           
            if(isset($_POST['attributes'])) {
                foreach($_POST['attributes'] as $attr_id => $value) {
                    if(!empty($value)) {
                        $stmt2 = $conn->prepare("INSERT INTO product_attributes (product_id, attribute_id, value) VALUES (?, ?, ?)");
                        $stmt2->bind_param("iis", $product_id, $attr_id, $value);
                        $stmt2->execute();
                        
                    }
                }
            }
            
            $message = "Product updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating product!";
            $message_type = "error";
        }
    }
}


if(isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $manufacturer_id = $_POST['manufacturer'];
    $category_id = $_POST['category'];
    $keywords = $_POST['keywords'];
    $image_url = $_POST['image_url'];
    $short_desc = $_POST['short_desc'];
    $long_desc = $_POST['long_desc'];
    $meta_desc = $_POST['meta_desc'];
    $valid_from = $_POST['valid_from'];
    $enabled = isset($_POST['enabled']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO products (name, price, manufacturer_id, category_id, keywords, image_url, short_desc, long_desc, meta_desc, valid_from, enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if($stmt) {
        $stmt->bind_param("sdiissssssi", $name, $price, $manufacturer_id, $category_id, $keywords, $image_url, $short_desc, $long_desc, $meta_desc, $valid_from, $enabled);
        
        if($stmt->execute()) {
            $product_id = $stmt->insert_id;

            if(isset($_POST['attributes'])) {
                foreach($_POST['attributes'] as $attr_id => $value) {
                    if(!empty($value)) {
                        $stmt2 = $conn->prepare("INSERT INTO product_attributes (product_id, attribute_id, value) VALUES (?, ?, ?)");
                        $stmt2->bind_param("iis", $product_id, $attr_id, $value);
                        $stmt2->execute();
                    }
                }
            }

            $message = "Product added successfully!";
            $message_type = "success";
        }
    }
}


$products = $conn->query("SELECT p.*, m.name AS manufacturer_name, c.name AS category_name FROM products p 
                          JOIN manufacturers m ON p.manufacturer_id = m.id 
                          JOIN categories c ON p.category_id = c.id
                          ORDER BY p.id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Products Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .add-btn {
            padding: 12px 25px;
            background: white;
            color: #667eea;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255,255,255,0.3);
        }
        
        .content {
            padding: 30px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            animation: slideDown 0.5s;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .enabled {
            background: #d4edda;
            color: #155724;
        }
        
        .disabled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-btn {
            padding: 8px 15px;
            margin: 0 3px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .edit-btn {
            background: #3498db;
            color: white;
        }
        
        .edit-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .delete-btn {
            background: #e74c3c;
            color: white;
        }
        
        .delete-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
     
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background: rgba(0,0,0,0.6);
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background: white;
            margin: 3% auto;
            padding: 0;
            border-radius: 15px;
            width: 70%;
            max-width: 800px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.3s;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close {
            color: white;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            line-height: 1;
        }
        
        .close:hover {
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .attributes-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }
        
        .attributes-section h3 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .price-tag {
            font-weight: bold;
            color: #667eea;
            font-size: 16px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1><i class="fas fa-box"></i> Product Management</h1>
        <button class="add-btn" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add New Product
        </button>
    </div>
    
    <div class="content">
        <?php if($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Manufacturer</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($p = $products->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $p['id']; ?></td>
                        <td>
                            <?php if($p['image_url']): ?>
                                <img src="<?php echo $p['image_url']; ?>" alt="Product" class="product-image">
                            <?php else: ?>
                                <i class="fas fa-image" style="font-size: 40px; color: #ccc;"></i>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo $p['name']; ?></strong></td>
                        <td><span class="price-tag">$<?php echo number_format($p['price'], 2); ?></span></td>
                        <td><?php echo $p['manufacturer_name']; ?></td>
                        <td><?php echo $p['category_name']; ?></td>
                        <td>
                            <span class="status-badge <?php echo $p['enabled'] ? 'enabled' : 'disabled'; ?>">
                                <?php echo $p['enabled'] ? 'Enabled' : 'Disabled'; ?>
                            </span>
                        </td>
                        <td>
                            <button class="action-btn edit-btn" onclick="openEditModal(<?php echo $p['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="action-btn delete-btn" onclick="deleteProduct(<?php echo $p['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Add New Product</h2>
            <span class="close" onclick="closeAddModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Product Name</label>
                        <input type="text" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-dollar-sign"></i> Price</label>
                        <input type="number" step="0.01" name="price" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-industry"></i> Manufacturer</label>
                        <select name="manufacturer" required>
                            <option value="">Select Manufacturer</option>
                            <?php 
                            $manufacturers->data_seek(0);
                            while($m = $manufacturers->fetch_assoc()) {
                                echo "<option value='{$m['id']}'>{$m['name']}</option>";
                            } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-list"></i> Category</label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <?php 
                            $categories->data_seek(0);
                            while($c = $categories->fetch_assoc()) {
                                echo "<option value='{$c['id']}'>{$c['name']}</option>";
                            } ?>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label><i class="fas fa-key"></i> Keywords</label>
                        <input type="text" name="keywords">
                    </div>
                    
                    <div class="form-group full-width">
                        <label><i class="fas fa-image"></i> Image URL</label>
                        <input type="text" name="image_url">
                    </div>
                    
                    <div class="form-group full-width">
                        <label><i class="fas fa-align-left"></i> Short Description</label>
                        <textarea name="short_desc"></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label><i class="fas fa-align-justify"></i> Long Description</label>
                        <textarea name="long_desc"></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label><i class="fas fa-file-alt"></i> Meta Description</label>
                        <input type="text" name="meta_desc">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Valid From</label>
                        <input type="date" name="valid_from">
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-group">
                            <input type="checkbox" name="enabled" checked>
                            <span>Enable Product</span>
                        </label>
                    </div>
                </div>

                <div class="attributes-section">
                    <h3><i class="fas fa-cogs"></i> Product Attributes</h3>
                    <div class="form-grid">
                        <?php 
                        $attributes->data_seek(0);
                        while($a = $attributes->fetch_assoc()) { ?>
                            <div class="form-group">
                                <label><?php echo $a['name']; ?></label>
                                <input type="text" name="attributes[<?php echo $a['id']; ?>]">
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <button type="submit" name="add_product" class="submit-btn">
                    <i class="fas fa-save"></i> Add Product
                </button>
            </form>
        </div>
    </div>
</div>


<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-edit"></i> Edit Product</h2>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" action="" id="editForm">
                <input type="hidden" name="product_id" id="edit_product_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Product Name</label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-dollar-sign"></i> Price</label>
                        <input type="number" step="0.01" name="price" id="edit_price" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-industry"></i> Manufacturer</label>
                        <select name="manufacturer" id="edit_manufacturer" required>
                            <?php 
                            $manufacturers->data_seek(0);
                            while($m = $manufacturers->fetch_assoc()) {
                                echo "<option value='{$m['id']}'>{$m['name']}</option>";
                            } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-list"></i> Category</label>
                        <select name="category" id="edit_category" required>
                            <?php 
                            $categories->data_seek(0);
                            while($c = $categories->fetch_assoc()) {
                                echo "<option value='{$c['id']}'>{$c['name']}</option>";
                            } ?>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label><i class="fas fa-key"></i> Keywords</label>
                        <input type="text" name="keywords" id="edit_keywords">
                    </div>
                    
                    <div class="form-group full-width">
                        <label><i class="fas fa-image"></i> Image URL</label>
                        <input type="text" name="image_url" id="edit_image_url">
                    </div>
                    
                    <div class="form-group full-width">
                        <label><i class="fas fa-align-left"></i> Short Description</label>
                        <textarea name="short_desc" id="edit_short_desc"></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label><i class="fas fa-align-justify"></i> Long Description</label>
                        <textarea name="long_desc" id="edit_long_desc"></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label><i class="fas fa-file-alt"></i> Meta Description</label>
                        <input type="text" name="meta_desc" id="edit_meta_desc">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Valid From</label>
                        <input type="date" name="valid_from" id="edit_valid_from">
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-group">
                            <input type="checkbox" name="enabled" id="edit_enabled">
                            <span>Enable Product</span>
                        </label>
                    </div>
                </div>

                <div class="attributes-section">
                    <h3><i class="fas fa-cogs"></i> Product Attributes</h3>
                    <div class="form-grid" id="edit_attributes">
                      
                    </div>
                </div>
                  <label>Attribute Combination (Optional):</label>
    <input type="text" name="attribute_combination" placeholder="Red-Large">

    <label>Quantity:</label>
    <input type="number" name="quantity" min="0" required>

                <button type="submit" name="update_product" class="submit-btn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<script>

function openAddModal() {
    document.getElementById('addModal').style.display = 'block';
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
}
function openEditModal(productId) {
 
    fetch('get_product.php?id=' + productId)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_product_id').value = data.id;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_price').value = data.price;
            document.getElementById('edit_manufacturer').value = data.manufacturer_id;
            document.getElementById('edit_category').value = data.category_id;
            document.getElementById('edit_keywords').value = data.keywords || '';
            document.getElementById('edit_image_url').value = data.image_url || '';
            document.getElementById('edit_short_desc').value = data.short_desc || '';
            document.getElementById('edit_long_desc').value = data.long_desc || '';
            document.getElementById('edit_meta_desc').value = data.meta_desc || '';
            document.getElementById('edit_valid_from').value = data.valid_from || '';
            document.getElementById('edit_enabled').checked = data.enabled == 1;
            
           
            let attributesHtml = '';
            data.all_attributes.forEach(attr => {
                const value = data.attributes[attr.id] || '';
                attributesHtml += `
                    <div class="form-group">
                        <label>${attr.name}</label>
                        <input type="text" name="attributes[${attr.id}]" value="${value}">
                    </div>
                `;
            });
            document.getElementById('edit_attributes').innerHTML = attributesHtml;
            
            document.getElementById('editModal').style.display = 'block';
        });
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
function deleteProduct(productId) {
    if(confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        window.location.href = '?delete_id=' + productId;
    }
}
window.onclick = function(event) {
    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    if (event.target == addModal) {
        addModal.style.display = "none";
    }
    if (event.target == editModal) {
        editModal.style.display = "none";
    }
}
</script>

</body>
</html>