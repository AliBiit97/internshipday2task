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

<style>
body { font-family: Arial; background:#eef1f4; padding:20px; }
.container {
    background:white; padding:20px; max-width:900px; margin:auto;
    border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1);
}
.section { margin-bottom:40px; }

.section h2 { 
    display:flex; justify-content:space-between; align-items:center; 
}

.add-btn {
    padding:6px 12px; background:#28a745; color:white;
    border-radius:5px; cursor:pointer;
}

ul { list-style:none; padding:0; }
ul li {
    padding:10px; background:#f8f8f8; margin-bottom:6px; border-radius:5px;
    display:flex; justify-content:space-between; align-items:center;
    border:1px solid #ddd;
}

.item-actions a {
    margin-left:10px; text-decoration:none; font-weight:bold;
}

.edit-btn { color:blue; }
.delete-btn { color:red; }

.modal {
    display:none; position:fixed; top:0; left:0; width:100%; height:100%;
    background:rgba(0,0,0,0.5);
}

.modal-content {
    background:white; width:350px; margin:12% auto; padding:20px;
    border-radius:10px;
}

.close { float:right; cursor:pointer; font-size:22px; }
input[type=text] { width:100%; padding:8px; margin-top:10px; }
button { padding:8px 14px; background:#007bff; color:white; border:none; border-radius:5px; margin-top:10px; cursor:pointer; }

</style>

</head>
<body>

<div class="container">

<h1>Manage Manufacturers & Categories</h1>
<a href="admin_dashboard.php">⬅ Back to Dashboard</a>

<?php if(isset($_GET['success'])): ?>
    <p style="color:green; font-weight:bold;">✔ Operation successful!</p>
<?php endif; ?>


<div class="section">
    <h2>Manufacturers
        <span class="add-btn" onclick="openModal('addManufacturerModal')">+ Add</span>
    </h2>

    <ul>
        <?php
        $res = $conn->query("SELECT * FROM manufacturers ORDER BY id DESC");
        while($row = $res->fetch_assoc()):
        ?>
        <li>
            <?php echo $row['name']; ?>
            <div class="item-actions">
                <a class="edit-btn" href="#" onclick="openEditManufacturer(<?php echo $row['id']; ?>, '<?php echo $row['name']; ?>')">Edit</a>
                <a class="delete-btn" href="admin_manage_mc.php?delete_manufacturer=<?php echo $row['id']; ?>">Delete</a>
            </div>
        </li>
        <?php endwhile; ?>
    </ul>
</div>


<div class="section">
    <h2>Categories
        <span class="add-btn" onclick="openModal('addCategoryModal')">+ Add</span>
    </h2>

    <ul>
        <?php
        $res = $conn->query("SELECT * FROM categories ORDER BY id DESC");
        while($row = $res->fetch_assoc()):
        ?>
        <li>
            <?php echo $row['name']; ?>
            <div class="item-actions">
                <a class="edit-btn" href="#" onclick="openEditCategory(<?php echo $row['id']; ?>, '<?php echo $row['name']; ?>')">Edit</a>
                <a class="delete-btn" href="admin_manage_mc.php?delete_category=<?php echo $row['id']; ?>">Delete</a>
            </div>
        </li>
        <?php endwhile; ?>
    </ul>
</div>

</div>

<div id="addManufacturerModal" class="modal">
<div class="modal-content">
    <span class="close" onclick="closeModal('addManufacturerModal')">&times;</span>
    <h3>Add Manufacturer</h3>

    <form method="POST">
        <input type="text" name="manufacturer_name" placeholder="Manufacturer Name" required>
        <button type="submit" name="add_manufacturer">Add Manufacturer</button>
    </form>
</div>
</div>

<div id="editManufacturerModal" class="modal">
<div class="modal-content">
    <span class="close" onclick="closeModal('editManufacturerModal')">&times;</span>
    <h3>Edit Manufacturer</h3>

    <form method="POST">
        <input type="hidden" name="manu_id" id="edit_manu_id">
        <input type="text" name="edit_manufacturer_name" id="edit_manu_name" required>
        <button type="submit" name="edit_manufacturer">Update Manufacturer</button>
    </form>
</div>
</div>

<div id="addCategoryModal" class="modal">
<div class="modal-content">
    <span class="close" onclick="closeModal('addCategoryModal')">&times;</span>
    <h3>Add Category</h3>

    <form method="POST">
        <input type="text" name="category_name" placeholder="Category Name" required>
        <button type="submit" name="add_category">Add Category</button>
    </form>
</div>
</div>

<div id="editCategoryModal" class="modal">
<div class="modal-content">
    <span class="close" onclick="closeModal('editCategoryModal')">&times;</span>
    <h3>Edit Category</h3>

    <form method="POST">
        <input type="hidden" name="cat_id" id="edit_cat_id">
        <input type="text" name="edit_category_name" id="edit_cat_name" required>
        <button type="submit" name="edit_category">Update Category</button>
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
