<?php
session_start();
include 'db.php';

// For demo - in real app, use proper authentication
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Demo user
}
$user_id = $_SESSION['user_id'];

// Handle quantity updates and removals
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
        
        if ($_POST['action'] === 'increase') {
            // Increase quantity
            $update_sql = "UPDATE user_carts SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
        } 
        elseif ($_POST['action'] === 'decrease') {
            // Get current quantity
            $check_sql = "SELECT quantity FROM user_carts WHERE user_id = ? AND product_id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $cart_item = $result->fetch_assoc();
            
            if ($cart_item && $cart_item['quantity'] > 1) {
                // Decrease quantity
                $update_sql = "UPDATE user_carts SET quantity = quantity - 1 WHERE user_id = ? AND product_id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("ii", $user_id, $product_id);
                $stmt->execute();
            } else {
                // Remove item if quantity is 1
                $delete_sql = "DELETE FROM user_carts WHERE user_id = ? AND product_id = ?";
                $stmt = $conn->prepare($delete_sql);
                $stmt->bind_param("ii", $user_id, $product_id);
                $stmt->execute();
            }
        } 
        elseif ($_POST['action'] === 'remove') {
            // Remove item completely
            $delete_sql = "DELETE FROM user_carts WHERE user_id = ? AND product_id = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
        }
        
        // Return updated cart count
        $count_sql = "SELECT SUM(quantity) as total_items FROM user_carts WHERE user_id = ?";
        $stmt = $conn->prepare($count_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count_data = $result->fetch_assoc();
        
        echo json_encode(['success' => true, 'total_items' => $count_data['total_items'] ?? 0]);
        exit;
    }
}

// Fetch cart items with product details
$cart_sql = "SELECT uc.*, p.name, p.price, p.image_url, p.short_desc, c.name as category_name 
             FROM user_carts uc 
             JOIN products p ON uc.product_id = p.id 
             LEFT JOIN categories c ON p.category_id = c.id 
             WHERE uc.user_id = ?";
$stmt = $conn->prepare($cart_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();
$cart_items = [];
$total_price = 0;
$total_items = 0;

if ($cart_result->num_rows > 0) {
    while ($row = $cart_result->fetch_assoc()) {
        $cart_items[] = $row;
        $total_price += $row['price'] * $row['quantity'];
        $total_items += $row['quantity'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Cart</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial;
            background: #f2f2f2;
            padding: 40px;
        }

        .cart-container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .cart-item {
            display: flex;
            align-items: center;
            background: #fafafa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            border: 1px solid #e0e0e0;
        }

        .item-image {
            width: 80px;
            height: 80px;
            margin-right: 20px;
            border-radius: 8px;
            object-fit: cover;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .item-details {
            flex-grow: 1;
        }

        .item-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .item-description {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .item-price {
            font-size: 18px;
            color: green;
            font-weight: bold;
        }

        .item-category {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }

        .qty-controls {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .qty-btn {
            background: #007bff;
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        .qty-btn:hover {
            background: #0056c7;
        }

        .qty-display {
            font-size: 16px;
            font-weight: bold;
            min-width: 30px;
            text-align: center;
        }

        .remove-btn {
            background: #dc3545;
            border: none;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .remove-btn:hover {
            background: #c82333;
        }

        .item-subtotal {
            font-size: 16px;
            color: #333;
            margin-top: 5px;
            font-weight: bold;
        }

        .total-box {
            text-align: right;
            font-size: 24px;
            margin-top: 30px;
            font-weight: bold;
            color: #333;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 18px;
            margin-top: 20px;
            transition: background 0.3s;
        }

        .submit-btn:hover {
            background: #218838;
        }

        .submit-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .empty-cart {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 18px;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        .badge {
            background: #007bff;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="cart-container">
        <div class="cart-header">
            <h2>My Shopping Cart</h2>
            <div class="badge" id="badge"><?= $total_items ?> items</div>
        </div>

        <div id="cartItems">
            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <p>🛒 Your cart is empty!</p>
                    <p>Add some products to get started.</p>
                </div>
            <?php else: ?>
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item" id="cart-item-<?= $item['product_id'] ?>">
                       <div class="item-image">
    <?php if (!empty($item['image_url']) && file_exists($item['image_url'])): ?>
        <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
    <?php else: ?>
     
        <div style="width:100%;height:100%;background:#f8f9fa;display:flex;align-items:center;justify-content:center;border-radius:8px;font-size:24px;">
            📦
        </div>
    <?php endif; ?>
</div>

                        <div class="item-details">
                            <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="item-price">Price: <?= number_format($item['price'], 2) ?></div>
                            <div class="item-subtotal">Subtotal: <?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                            <div class="item-category">Category: <?= htmlspecialchars($item['category_name']) ?></div>

                            <div class="qty-controls">
                                <button class="qty-btn" onclick="updateQuantity(<?= $item['product_id'] ?>, 'decrease')">-</button>
                                <span class="qty-display" id="qty-<?= $item['product_id'] ?>"><?= $item['quantity'] ?></span>
                                <button class="qty-btn" onclick="updateQuantity(<?= $item['product_id'] ?>, 'increase')">+</button>
                            </div>
                        </div>

                        <button class="remove-btn" onclick="removeItem(<?= $item['product_id'] ?>)">Remove</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($cart_items)): ?>
            <div class="total-box" id="totalPrice">
                Total: <?= number_format($total_price, 2) ?>
            </div>

            <button class="submit-btn" onclick="goToCheckout()">Proceed to Checkout</button>
        <?php endif; ?>
    </div>

<script>
function updateQuantity(productId, action) {
    $.post('', {
        action: action,
        product_id: productId
    }, function(response) {
        const data = JSON.parse(response);
        if (data.success) {
            // Reload the page to show updated cart
            location.reload();
        }
    });
}

function removeItem(productId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        $.post('', {
            action: 'remove',
            product_id: productId
        }, function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                // Remove item from DOM
                document.getElementById(`cart-item-${productId}`).remove();
                
                // Update badge
                document.getElementById('badge').textContent = data.total_items + ' items';
                
                // Reload if cart is empty
                if (data.total_items === 0) {
                    location.reload();
                } else {
                    // Recalculate total (you might want to reload the page for simplicity)
                    location.reload();
                }
            }
        });
    }
}

function goToCheckout() {
    // You can implement checkout logic here
    alert('Proceeding to checkout!');
    // window.location.href = "checkout.php";
}

// Update badge on page load
document.addEventListener('DOMContentLoaded', function() {
    const totalItems = <?= $total_items ?>;
    document.getElementById('badge').textContent = totalItems + ' item' + (totalItems !== 1 ? 's' : '');
});
</script>

</body>
</html>