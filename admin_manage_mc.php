<?php
session_start();
require "db.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
    exit();
}
if (isset($_POST['add_manufacturer'])) {
    $name = $_POST['manufacturer_name'];
    $conn->query("INSERT INTO manufacturers (name) VALUES ('$name')");
    header("Location: admin_manage_mc.php?success=manufacturer_added");
    exit();
}

if (isset($_POST['edit_manufacturer'])) {
    $id = $_POST['manu_id'];
    $name = $_POST['edit_manufacturer_name'];
    $conn->query("UPDATE manufacturers SET name='$name' WHERE id=$id");
    header("Location: admin_manage_mc.php?success=manufacturer_updated");
    exit();
}


if (isset($_GET['delete_manufacturer'])) {
    $id = $_GET['delete_manufacturer'];
    $conn->query("DELETE FROM manufacturers WHERE id=$id");
    header("Location: admin_manage_mc.php?success=manufacturer_deleted");
    exit();
}

if (isset($_POST['add_category'])) {
    $name = $_POST['category_name'];
    $conn->query("INSERT INTO categories (name) VALUES ('$name')");
    header("Location: admin_manage_mc.php?success=category_added");
    exit();
}

if (isset($_POST['edit_category'])) {
    $id = $_POST['cat_id'];
    $name = $_POST['edit_category_name'];
    $conn->query("UPDATE categories SET name='$name' WHERE id=$id");
    header("Location: admin_manage_mc.php?success=category_updated");
    exit();
}

if (isset($_GET['delete_category'])) {
    $id = $_GET['delete_category'];
    $conn->query("DELETE FROM categories WHERE id=$id");
    header("Location: admin_manage_mc.php?success=category_deleted");
    exit();
}

?>
<!DOCTYPE html>
<html>
<head>
<title>Manage Manufacturers & Categories</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
    min-height: 100vh;
}

.container {
    background: white;
    padding: 40px;
    max-width: 1100px;
    margin: auto;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 3px solid #f0f0f0;
}

.header h1 {
    color: #2d3748;
    font-size: 32px;
    font-weight: 700;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #f7fafc;
    color: #4a5568;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 2px solid #e2e8f0;
}

.back-link:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
    transform: translateX(-5px);
}

.success-message {
    background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
    color: #0f5132;
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(132, 250, 176, 0.3);
    animation: slideIn 0.5s ease;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.section { 
    margin-bottom: 45px;
    background: #f8f9fa;
    padding: 25px;
    border-radius: 15px;
    border: 1px solid #e9ecef;
}

.section-header { 
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h2 {
    color: #2d3748;
    font-size: 24px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.add-btn {
    width: 30%;
    padding: 8px 14px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    white-space: nowrap;
}
.add-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

ul {
    list-style: none;
    padding: 0;
}

ul li {
    padding: 18px 20px;
    background: white;
    margin-bottom: 10px;
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

ul li:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    transform: translateX(5px);
}

.item-name {
    font-weight: 600;
    color: #2d3748;
    font-size: 16px;
}

.item-actions {
    display: flex;
    gap: 10px;
}

.item-actions a {
    padding: 8px 16px;
    text-decoration: none;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
}

.edit-btn {
    color: #0066cc;
    background: #e6f2ff;
    border: 2px solid #cce5ff;
}

.edit-btn:hover {
    background: #0066cc;
    color: white;
    transform: translateY(-2px);
}

.delete-btn {
    color: #dc3545;
    background: #ffe6e6;
    border: 2px solid #ffcccc;
}

.delete-btn:hover {
    background: #dc3545;
    color: white;
    transform: translateY(-2px);
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(5px);
    z-index: 1000;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: white;
    width: 450px;
    margin: 8% auto;
    padding: 35px;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(50px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.modal-header h3 {
    color: #2d3748;
    font-size: 22px;
    font-weight: 700;
}

.close {
    cursor: pointer;
    font-size: 28px;
    color: #999;
    transition: all 0.3s ease;
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.close:hover {
    background: #f8f9fa;
    color: #333;
    transform: rotate(90deg);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #4a5568;
    font-weight: 600;
    font-size: 14px;
}

input[type=text] {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.3s ease;
    font-family: inherit;
}

input[type=text]:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

button {
    width: 100%;
    padding: 12px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #718096;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .container {
        padding: 25px;
    }
    
    .header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .modal-content {
        width: 90%;
        margin: 20% auto;
    }
}
</style>

</head>
<body>

<div class="container">

<div class="header">
    <h1><i class="fas fa-cog"></i> Manage Manufacturers & Categories</h1>
    <a href="admin_dashboard.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<?php if(isset($_GET['success'])): ?>
    <div class="success-message">
        <i class="fas fa-check-circle"></i>
        <span>Operation completed successfully!</span>
    </div>
<?php endif; ?>


<div class="section">
    <div class="section-header">
        <h2>
            <div class="section-icon">
                <i class="fas fa-industry"></i>
            </div>
            Manufacturers
        </h2>
        <button class="add-btn" onclick="openModal('addManufacturerModal')">
            <i class="fas fa-plus"></i> Add Manufacturer
        </button>
    </div>

    <ul>
        <?php
        $res = $conn->query("SELECT * FROM manufacturers ORDER BY id DESC");
        if($res->num_rows > 0):
            while($row = $res->fetch_assoc()):
        ?>
        <li>
            <span class="item-name"><i class="fas fa-building"></i> <?php echo $row['name']; ?></span>
            <div class="item-actions">
                <a class="edit-btn" href="#" onclick="openEditManufacturer(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>')">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a class="delete-btn" href="admin_manage_mc.php?delete_manufacturer=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this manufacturer?')">
                    <i class="fas fa-trash"></i> Delete
                </a>
            </div>
        </li>
        <?php 
            endwhile;
        else:
        ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No manufacturers found. Add your first one!</p>
        </div>
        <?php endif; ?>
    </ul>
</div>


<div class="section">
    <div class="section-header">
        <h2>
            <div class="section-icon">
                <i class="fas fa-tags"></i>
            </div>
            Categories
        </h2>
        <button class="add-btn" onclick="openModal('addCategoryModal')">
            <i class="fas fa-plus"></i> Add Category
        </button>
    </div>

    <ul>
        <?php
        $res = $conn->query("SELECT * FROM categories ORDER BY id DESC");
        if($res->num_rows > 0):
            while($row = $res->fetch_assoc()):
        ?>
        <li>
            <span class="item-name"><i class="fas fa-tag"></i> <?php echo $row['name']; ?></span>
            <div class="item-actions">
                <a class="edit-btn" href="#" onclick="openEditCategory(<?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>')">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a class="delete-btn" href="admin_manage_mc.php?delete_category=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this category?')">
                    <i class="fas fa-trash"></i> Delete
                </a>
            </div>
        </li>
        <?php 
            endwhile;
        else:
        ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>No categories found. Add your first one!</p>
        </div>
        <?php endif; ?>
    </ul>
</div>

</div>

<div id="addManufacturerModal" class="modal">
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fas fa-plus-circle"></i> Add Manufacturer</h3>
        <span class="close" onclick="closeModal('addManufacturerModal')">&times;</span>
    </div>

    <form method="POST">
        <div class="form-group">
            <label><i class="fas fa-building"></i> Manufacturer Name</label>
            <input type="text" name="manufacturer_name" placeholder="Enter manufacturer name" required>
        </div>
        <button type="submit" name="add_manufacturer">
            <i class="fas fa-save"></i> Add Manufacturer
        </button>
    </form>
</div>
</div>

<div id="editManufacturerModal" class="modal">
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fas fa-edit"></i> Edit Manufacturer</h3>
        <span class="close" onclick="closeModal('editManufacturerModal')">&times;</span>
    </div>

    <form method="POST">
        <input type="hidden" name="manu_id" id="edit_manu_id">
        <div class="form-group">
            <label><i class="fas fa-building"></i> Manufacturer Name</label>
            <input type="text" name="edit_manufacturer_name" id="edit_manu_name" required>
        </div>
        <button type="submit" name="edit_manufacturer">
            <i class="fas fa-save"></i> Update Manufacturer
        </button>
    </form>
</div>
</div>

<div id="addCategoryModal" class="modal">
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fas fa-plus-circle"></i> Add Category</h3>
        <span class="close" onclick="closeModal('addCategoryModal')">&times;</span>
    </div>

    <form method="POST">
        <div class="form-group">
            <label><i class="fas fa-tag"></i> Category Name</label>
            <input type="text" name="category_name" placeholder="Enter category name" required>
        </div>
        <button type="submit" name="add_category">
            <i class="fas fa-save"></i> Add Category
        </button>
    </form>
</div>
</div>

<div id="editCategoryModal" class="modal">
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fas fa-edit"></i> Edit Category</h3>
        <span class="close" onclick="closeModal('editCategoryModal')">&times;</span>
    </div>

    <form method="POST">
        <input type="hidden" name="cat_id" id="edit_cat_id">
        <div class="form-group">
            <label><i class="fas fa-tag"></i> Category Name</label>
            <input type="text" name="edit_category_name" id="edit_cat_name" required>
        </div>
        <button type="submit" name="edit_category">
            <i class="fas fa-save"></i> Update Category
        </button>
    </form>
</div>
</div>

<script>
function openModal(id){ document.getElementById(id).style.display="block"; }
function closeModal(id){ document.getElementById(id).style.display="none"; }

function openEditManufacturer(id, name){
    document.getElementById('edit_manu_id').value = id;
    document.getElementById('edit_manu_name').value = name;
    openModal('editManufacturerModal');
}

function openEditCategory(id, name){
    document.getElementById('edit_cat_id').value = id;
    document.getElementById('edit_cat_name').value = name;
    openModal('editCategoryModal');
}

window.onclick = function(e){
    document.querySelectorAll('.modal').forEach(modal=>{
        if(e.target == modal){ modal.style.display="none"; }
    });
}
</script>

</body>
</html>