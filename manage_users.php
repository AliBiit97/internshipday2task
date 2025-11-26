<?php
include "db.php";

$message = "";

if (isset($_GET['delete_user'])) {
    $id = $_GET['delete_user'];
    mysqli_query($conn, "DELETE FROM users WHERE id=$id");
    $message = "User deleted successfully!";
}

if (isset($_GET['delete_cart_item'])) {
    $cart_id = $_GET['delete_cart_item'];
    mysqli_query($conn, "DELETE FROM user_carts WHERE id=$cart_id");
    $message = "Cart item deleted successfully!";
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");

$cart_items = [];
$view_cart_user = null;
if (isset($_GET['view_cart'])) {
    $view_cart_user = $_GET['view_cart'];
    $cart_items = mysqli_query($conn, "SELECT uc.*, p.name AS product_name
                                       FROM user_carts uc
                                       LEFT JOIN products p ON uc.product_id = p.id
                                       WHERE uc.user_id=$view_cart_user");
}

$user_info = null;
if ($view_cart_user) {
    $res = mysqli_query($conn, "SELECT * FROM users WHERE id=$view_cart_user");
    $user_info = mysqli_fetch_assoc($res);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users & Carts</title>
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
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h2 {
            color: white;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        /* Success Message */
        .success-message {
            background: #10b981;
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .success-message::before {
            content: "✓";
            display: inline-block;
            width: 24px;
            height: 24px;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
        }

        .table-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
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
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            padding: 18px 24px;
            text-align: left;
        }

        tbody tr {
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: #f9fafb;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        td {
            padding: 18px 24px;
            color: #374151;
            font-size: 14px;
        }

        td:first-child {
            font-weight: 600;
            color: #6366f1;
        }

        /* Action Links */
        .action-links {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-block;
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background: #fecaca;
            transform: translateY(-1px);
        }

        .btn-view {
            background: #dbeafe;
            color: #2563eb;
        }

        .btn-view:hover {
            background: #bfdbfe;
            transform: translateY(-1px);
        }

        /* Overlay */
        .overlay {
            display: <?= $view_cart_user ? 'block' : 'none' ?>;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 999;
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }

        /* Modal */
        .modal {
            display: <?= $view_cart_user ? 'block' : 'none' ?>;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            z-index: 1000;
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 24px 32px;
            border-radius: 20px 20px 0 0;
        }

        .modal-header h3 {
            color: white;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }

        .modal-body {
            padding: 32px;
        }

        .modal table {
            margin-bottom: 24px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .modal thead {
            background: #f3f4f6;
        }

        .modal th {
            color: #374151;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #6b7280;
        }

        .empty-state::before {
            content: "🛒";
            display: block;
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .btn-close {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 32px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.2s ease;
        }

        .btn-close:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translate(-50%, -45%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
            }

            h2 {
                font-size: 24px;
            }

            th, td {
                padding: 12px 16px;
                font-size: 13px;
            }

            .action-links {
                flex-direction: column;
                gap: 8px;
            }

            .btn {
                text-align: center;
            }

            .modal {
                width: 95%;
            }

            .modal-header, .modal-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>👥 User Management</h2>

    <?php if ($message != "") { ?>
        <div class="success-message"><?= $message ?></div>
    <?php } ?>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = mysqli_fetch_assoc($users)) { ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= $user['username'] ?></td>
                    <td><?= $user['email'] ?></td>
                    <td>
                        <div class="action-links">
                            <a href="manage_users.php?delete_user=<?= $user['id'] ?>" 
                               class="btn btn-delete"
                               onclick="return confirm('Are you sure you want to delete this user?')">
                                🗑️ Delete
                            </a>
                            <a href="manage_users.php?view_cart=<?= $user['id'] ?>" class="btn btn-view">
                                🛒 View Cart
                            </a>
                        </div>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>


<div id="overlay" class="overlay" onclick="window.location='manage_users.php'"></div>


<div id="cartModal" class="modal">
    <div class="modal-header">
        <h3>🛒 Cart for <?= $user_info['username'] ?? '' ?></h3>
    </div>
    <div class="modal-body">
        <?php if ($cart_items && mysqli_num_rows($cart_items) > 0) { ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($item = mysqli_fetch_assoc($cart_items)) { ?>
                    <tr>
                        <td><?= $item['id'] ?></td>
                        <td><?= $item['product_name'] ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>
                            <a href="manage_users.php?delete_cart_item=<?= $item['id'] ?>&view_cart=<?= $view_cart_user ?>" 
                               class="btn btn-delete"
                               onclick="return confirm('Delete this item?')">
                                🗑️ Delete
                            </a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <div class="empty-state">
                <p><strong>No items in cart</strong></p>
                <p style="margin-top: 8px;">This user hasn't added any products yet.</p>
            </div>
        <?php } ?>

        <a href="manage_users.php" class="btn-close">✕ Close</a>
    </div>
</div>

</body>
</html>