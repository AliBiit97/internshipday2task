<?php
include "db.php";


if (isset($_POST['add_stock'])) {
    $product_id = $_POST['product_id'];
    $attribute = $_POST['attribute_combination'];
    $quantity = $_POST['quantity'];

    $sql = "INSERT INTO stock (product_id, attribute_combination, quantity)
            VALUES ($product_id, '$attribute', $quantity)";

    mysqli_query($conn, $sql);
    $msg = "Stock Added Successfully!";
}

$sql = "SELECT p.id, p.name, 
       IFNULL(SUM(s.quantity), 0) AS total_stock
       FROM products p
       LEFT JOIN stock s ON p.id = s.product_id
       GROUP BY p.id";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        h2 {
            color: #1a202c;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #718096;
            font-size: 16px;
        }

        .success-msg {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        th {
            color: white;
            font-weight: 600;
            text-align: left;
            padding: 20px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background: #f7fafc;
            transform: scale(1.01);
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        td {
            padding: 20px;
            color: #2d3748;
            font-size: 15px;
        }

        .stock-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .stock-high {
            background: #d4edda;
            color: #155724;
        }

        .stock-low {
            background: #fff3cd;
            color: #856404;
        }

        .stock-out {
            background: #f8d7da;
            color: #721c24;
        }

        .btn-add {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-add:active {
            transform: translateY(0);
        }

        /* Modal Styles */
        #popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 500px;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translate(-50%, -40%);
                opacity: 0;
            }
            to {
                transform: translate(-50%, -50%);
                opacity: 1;
            }
        }

        .modal-header {
            margin-bottom: 30px;
        }

        .modal-header h3 {
            font-size: 24px;
            color: #1a202c;
            margin-bottom: 8px;
        }

        .modal-header p {
            color: #718096;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #2d3748;
            font-weight: 600;
            font-size: 14px;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        input[type="text"]:focus,
        input[type="number"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn-save {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-cancel {
            flex: 1;
            background: #e2e8f0;
            color: #2d3748;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        .btn-cancel:hover {
            background: #cbd5e0;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
            }

            .header {
                padding: 20px;
            }

            h2 {
                font-size: 24px;
            }

            th, td {
                padding: 12px;
                font-size: 13px;
            }

            .modal-content {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>

<div class="container">
    <div class="header">
        <h2>📦 Stock Management System</h2>
        <p class="subtitle">Manage your inventory and track product stock levels</p>
    </div>

    <?php if (!empty($msg)) { ?>
        <div class="success-msg">
            ✓ <?= $msg ?>
        </div>
    <?php } ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Stock Level</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)) { 
                    $stock = $row['total_stock'];
                    if ($stock > 50) {
                        $badge_class = 'stock-high';
                    } elseif ($stock > 0) {
                        $badge_class = 'stock-low';
                    } else {
                        $badge_class = 'stock-out';
                    }
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['name']); ?></strong></td>
                    <td>
                        <span class="stock-badge <?= $badge_class ?>">
                            <?= $stock ?> units
                        </span>
                    </td>
                    <td>
                        <button class="btn-add" onclick="openPopup(<?= $row['id']; ?>, '<?= htmlspecialchars($row['name'], ENT_QUOTES); ?>')">
                            + Add Stock
                        </button>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div id="popup" onclick="closeIfOutside(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3>Add Stock</h3>
            <p id="product-name-display">Select a product to add stock</p>
        </div>
        
        <form method="POST">
            <input type="hidden" name="product_id" id="product_id">

            <div class="form-group">
                <label>Attribute Combination (Optional)</label>
                <input type="text" name="attribute_combination" placeholder="e.g., Size: L, Color: Blue">
            </div>

            <div class="form-group">
                <label>Quantity <span style="color: #e53e3e;">*</span></label>
                <input type="number" name="quantity" min="1" required placeholder="Enter quantity">
            </div>

            <div class="button-group">
                <button type="submit" name="add_stock" class="btn-save">Save Stock</button>
                <button type="button" onclick="closePopup()" class="btn-cancel">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openPopup(id, productName) {
        document.getElementById("product_id").value = id;
        document.getElementById("product-name-display").textContent = "Adding stock for: " + productName;
        document.getElementById("popup").style.display = "block";
        document.body.style.overflow = "hidden";
    }

    function closePopup() {
        document.getElementById("popup").style.display = "none";
        document.body.style.overflow = "auto";
    }

    function closeIfOutside(event) {
        if (event.target.id === "popup") {
            closePopup();
        }
    }


    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            closePopup();
        }
    });
</script>

</body>
</html>