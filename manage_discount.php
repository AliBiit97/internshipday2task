<?php
include "db.php";

$message = "";

if (isset($_POST['save_discount'])) {

    $id = !empty($_POST['id']) ? $_POST['id'] : null;
    $name = $_POST['name'];
    $code = $_POST['code'];
    $type = $_POST['type'];
    $applies_to = $_POST['applies_to'];
    $value = $_POST['value'];
    $valid_from = $_POST['valid_from'];
    $valid_to = $_POST['valid_to'];

    $product_id = !empty($_POST['product_id']) ? $_POST['product_id'] : "NULL";
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : "NULL";

    $overlap_sql = "SELECT * FROM discounts WHERE applies_to='$applies_to' ";

    if ($applies_to == "product") $overlap_sql .= "AND product_id=$product_id ";
    if ($applies_to == "category") $overlap_sql .= "AND category_id=$category_id ";
    if ($id) $overlap_sql .= "AND id != $id "; // exclude current if editing

    $overlap_sql .= "AND ((valid_from <= '$valid_to' AND valid_to >= '$valid_from'))";

    $overlap_res = mysqli_query($conn, $overlap_sql);
    if (mysqli_num_rows($overlap_res) > 0) {
        $message = "Error: Overlapping discount exists for this product/category!";
    } else {
        if ($id) {
         
            $sql = "UPDATE discounts SET
                        name='$name', code='$code', type='$type',
                        applies_to='$applies_to', product_id=$product_id, category_id=$category_id,
                        value=$value, valid_from='$valid_from', valid_to='$valid_to'
                    WHERE id=$id";
            mysqli_query($conn, $sql);
            $message = "Discount updated successfully!";
        } else {
      
            $sql = "INSERT INTO discounts (name, code, type, applies_to, product_id, category_id, value, valid_from, valid_to)
                    VALUES ('$name','$code','$type','$applies_to',$product_id,$category_id,$value,'$valid_from','$valid_to')";
            mysqli_query($conn, $sql);
            $message = "Discount added successfully!";
        }
    }
}


if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM discounts WHERE id=$id");
    $message = "Discount deleted successfully!";
}

$discounts = mysqli_query($conn, "SELECT d.*, p.name AS product_name, c.name AS category_name
                                  FROM discounts d
                                  LEFT JOIN products p ON d.product_id = p.id
                                  LEFT JOIN categories c ON d.category_id = c.id
                                  ORDER BY d.id DESC");


$products = mysqli_query($conn, "SELECT id, name FROM products");

$categories = mysqli_query($conn, "SELECT id, name FROM categories");


$edit_discount = null;
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $res = mysqli_query($conn, "SELECT * FROM discounts WHERE id=$id");
    $edit_discount = mysqli_fetch_assoc($res);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discount Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .message {
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message.success {
            border-left: 4px solid #10b981;
            color: #065f46;
        }

        .message.error {
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }

        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            align-items: start;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: sticky;
            top: 20px;
        }

        .form-card h2 {
            color: #1f2937;
            margin-bottom: 25px;
            font-size: 1.5em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #374151;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.95em;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1em;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #6b7280;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .table-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .table-card h2 {
            color: #1f2937;
            margin-bottom: 25px;
            font-size: 1.5em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        thead th {
            padding: 15px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        thead th:first-child {
            border-radius: 8px 0 0 0;
        }

        thead th:last-child {
            border-radius: 0 8px 0 0;
        }

        tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: #f9fafb;
        }

        tbody td {
            padding: 15px;
            color: #4b5563;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .badge-percentage {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-fixed {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-all {
            background: #e0e7ff;
            color: #3730a3;
        }

        .badge-product {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-category {
            background: #fce7f3;
            color: #9f1239;
        }

        .action-links {
            display: flex;
            gap: 10px;
        }

        .action-links a {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .action-links .edit {
            color: #2563eb;
            background: #dbeafe;
        }

        .action-links .edit:hover {
            background: #bfdbfe;
        }

        .action-links .delete {
            color: #dc2626;
            background: #fee2e2;
        }

        .action-links .delete:hover {
            background: #fecaca;
        }

        @media (max-width: 1024px) {
            .content-wrapper {
                grid-template-columns: 1fr;
            }

            .form-card {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }

            .table-wrapper {
                font-size: 0.9em;
            }
        }
    </style>
    <script>
        function toggleAppliesTo() {
            let v = document.getElementById("applies_to").value;
            document.getElementById("product_select").style.display = (v === "product") ? "block" : "none";
            document.getElementById("category_select").style.display = (v === "category") ? "block" : "none";
        }

        window.onload = function() {
            toggleAppliesTo();
        }
    </script>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tags"></i> Discount Management</h1>
            <p>Create and manage promotional discounts for your products</p>
        </div>

        <?php if ($message != "") { 
            $isError = strpos($message, 'Error') !== false;
        ?>
            <div class="message <?= $isError ? 'error' : 'success' ?>">
                <i class="fas <?= $isError ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
                <?= $message ?>
            </div>
        <?php } ?>

        <div class="content-wrapper">
            <!-- FORM SECTION -->
            <div class="form-card">
                <h2>
                    <i class="fas <?= $edit_discount ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                    <?= $edit_discount ? "Edit Discount" : "Add New Discount" ?>
                </h2>

                <form method="POST">
                    <input type="hidden" name="id" value="<?= $edit_discount['id'] ?? '' ?>">

                    <div class="form-group">
                        <label><i class="fas fa-signature"></i> Discount Name</label>
                        <input type="text" name="name" required value="<?= $edit_discount['name'] ?? '' ?>" placeholder="e.g., Summer Sale">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-ticket-alt"></i> Discount Code</label>
                        <input type="text" name="code" required value="<?= $edit_discount['code'] ?? '' ?>" placeholder="e.g., SUMMER2024">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-chart-pie"></i> Type</label>
                        <select name="type" required>
                            <option value="percentage" <?= isset($edit_discount['type']) && $edit_discount['type']=='percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                            <option value="fixed" <?= isset($edit_discount['type']) && $edit_discount['type']=='fixed' ? 'selected' : '' ?>>Fixed Amount ($)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-layer-group"></i> Applies To</label>
                        <select name="applies_to" id="applies_to" onchange="toggleAppliesTo()" required>
                            <option value="all" <?= isset($edit_discount['applies_to']) && $edit_discount['applies_to']=='all' ? 'selected' : '' ?>>All Products</option>
                            <option value="product" <?= isset($edit_discount['applies_to']) && $edit_discount['applies_to']=='product' ? 'selected' : '' ?>>Specific Product</option>
                            <option value="category" <?= isset($edit_discount['applies_to']) && $edit_discount['applies_to']=='category' ? 'selected' : '' ?>>Category</option>
                        </select>
                    </div>

                    <div id="product_select" class="form-group" style="display:none;">
                        <label><i class="fas fa-box"></i> Select Product</label>
                        <select name="product_id">
                            <option value="">-- Select Product --</option>
                            <?php
                            mysqli_data_seek($products, 0);
                            while($p = mysqli_fetch_assoc($products)) { ?>
                                <option value="<?= $p['id'] ?>" <?= isset($edit_discount['product_id']) && $edit_discount['product_id']==$p['id'] ? 'selected' : '' ?>>
                                    <?= $p['name'] ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div id="category_select" class="form-group" style="display:none;">
                        <label><i class="fas fa-folder"></i> Select Category</label>
                        <select name="category_id">
                            <option value="">-- Select Category --</option>
                            <?php
                            mysqli_data_seek($categories, 0);
                            while($c = mysqli_fetch_assoc($categories)) { ?>
                                <option value="<?= $c['id'] ?>" <?= isset($edit_discount['category_id']) && $edit_discount['category_id']==$c['id'] ? 'selected' : '' ?>>
                                    <?= $c['name'] ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-percentage"></i> Discount Value</label>
                        <input type="number" step="0.01" name="value" required value="<?= $edit_discount['value'] ?? '' ?>" placeholder="e.g., 20">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Valid From</label>
                        <input type="date" name="valid_from" required value="<?= $edit_discount['valid_from'] ?? '' ?>">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-calendar-check"></i> Valid To</label>
                        <input type="date" name="valid_to" required value="<?= $edit_discount['valid_to'] ?? '' ?>">
                    </div>

                    <div class="button-group">
                        <button type="submit" name="save_discount" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <?= $edit_discount ? "Update Discount" : "Save Discount" ?>
                        </button>
                        <?php if ($edit_discount) { ?>
                            <a href="manage_discount.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php } ?>
                    </div>
                </form>
            </div>

            <div class="table-card">
                <h2><i class="fas fa-list"></i> All Discounts</h2>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Applies To</th>
                                <th>Target</th>
                                <th>Valid From</th>
                                <th>Valid To</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($d = mysqli_fetch_assoc($discounts)) { ?>
                            <tr>
                                <td><strong><?= $d['name'] ?></strong></td>
                                <td><code style="background: #f3f4f6; padding: 4px 8px; border-radius: 4px;"><?= $d['code'] ?></code></td>
                                <td>
                                    <span class="badge badge-<?= $d['type'] ?>">
                                        <?= ucfirst($d['type']) ?>
                                    </span>
                                </td>
                                <td><strong><?= $d['value'] ?><?= $d['type']=='percentage' ? '%' : '$' ?></strong></td>
                                <td>
                                    <span class="badge badge-<?= $d['applies_to'] ?>">
                                        <?= ucfirst($d['applies_to']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                        if ($d['applies_to'] == 'product') echo $d['product_name'];
                                        else if ($d['applies_to'] == 'category') echo $d['category_name'];
                                        else echo "All Products";
                                    ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($d['valid_from'])) ?></td>
                                <td><?= date('M d, Y', strtotime($d['valid_to'])) ?></td>
                                <td>
                                    <div class="action-links">
                                        <a href="manage_discount.php?edit_id=<?= $d['id'] ?>" class="edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="manage_discount.php?delete_id=<?= $d['id'] ?>" class="delete" onclick="return confirm('Are you sure you want to delete this discount?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>