<?php
// cart.php - Updated with proper guest session handling

session_start();
include 'db.php';

// Check if user is logged in, otherwise create/use guest session
if (!isset($_SESSION['user_id'])) {
    // User not logged in - create/use guest ID
    if (!isset($_SESSION['guest_id'])) {
        $_SESSION['guest_id'] = "GUEST_" . time() . "_" . rand(1000, 9999);
    }
    $user_id = null;
    $guest_id = $_SESSION['guest_id'];
    $session_id = $_SESSION['guest_id']; // Use guest_id as session_id
    $is_guest = true;
} else {
    // User is logged in
    $user_id = $_SESSION['user_id'];
    $guest_id = null;
    $session_id = null;
    $is_guest = false;
}


// Handle promo code application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_promo'])) {
    $promo_code = trim($_POST['promo_code']);
    
    $promo_sql = "SELECT * FROM discounts WHERE code = ? AND valid_from <= CURDATE() AND valid_to >= CURDATE()";
    $stmt = $conn->prepare($promo_sql);
    $stmt->bind_param("s", $promo_code);
    $stmt->execute();
    $promo_result = $stmt->get_result();
    $promo = $promo_result->fetch_assoc();
    
    if ($promo) {
        $_SESSION['applied_promo'] = $promo;
        $promo_success = "Promo code applied successfully!";
    } else {
        $promo_error = "Invalid or expired promo code.";
    }
}

if (isset($_GET['remove_promo'])) {
    unset($_SESSION['applied_promo']);
    header("Location: cart.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && !isset($_POST['place_order'])) {
    if (isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
 
        if ($user_id) {
            $where_clause = "user_id = ? AND product_id = ?";
            $where_params = [$user_id, $product_id];
            $param_types = "ii";
        } else {
            $where_clause = "session_id = ? AND product_id = ?";
            $where_params = [$guest_id, $product_id];
            $param_types = "si";
        }
        
        if ($_POST['action'] === 'increase') {
            $update_sql = "UPDATE user_carts SET quantity = quantity + 1 WHERE $where_clause";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param($param_types, ...$where_params);
            $stmt->execute();
        } 
        elseif ($_POST['action'] === 'decrease') {
            $check_sql = "SELECT quantity FROM user_carts WHERE $where_clause";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param($param_types, ...$where_params);
            $stmt->execute();
            $result = $stmt->get_result();
            $cart_item = $result->fetch_assoc();

            if ($cart_item && $cart_item['quantity'] > 1) {
                $update_sql = "UPDATE user_carts SET quantity = quantity - 1 WHERE $where_clause";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param($param_types, ...$where_params);
                $stmt->execute();
            } else {
                $delete_sql = "DELETE FROM user_carts WHERE $where_clause";
                $stmt = $conn->prepare($delete_sql);
                $stmt->bind_param($param_types, ...$where_params);
                $stmt->execute();
            }
        } 
        elseif ($_POST['action'] === 'remove') {
            $delete_sql = "DELETE FROM user_carts WHERE $where_clause";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param($param_types, ...$where_params);
            $stmt->execute();
        }
   
        if ($user_id) {
            $count_sql = "SELECT SUM(quantity) as total_items FROM user_carts WHERE user_id = ?";
            $stmt = $conn->prepare($count_sql);
            $stmt->bind_param("i", $user_id);
        } else {
            $count_sql = "SELECT SUM(quantity) as total_items FROM user_carts WHERE session_id = ?";
            $stmt = $conn->prepare($count_sql);
            $stmt->bind_param("s", $guest_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $count_data = $result->fetch_assoc();
        
        echo json_encode(['success' => true, 'total_items' => $count_data['total_items'] ?? 0]);
        exit;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $guest_email = trim($_POST['guest_email']);
    $guest_name = trim($_POST['guest_name']);
    $guest_phone = trim($_POST['guest_phone']);
    $shipping_address = trim($_POST['shipping_address']);
    $billing_address = trim($_POST['billing_address']) ?: $shipping_address;
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $zip_code = trim($_POST['zip_code']);
    $country = trim($_POST['country']);
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];
    if (empty($guest_email)) $errors[] = "Email is required";
    if (empty($guest_name)) $errors[] = "Full name is required";
    if (empty($guest_phone)) $errors[] = "Phone number is required";
    if (empty($shipping_address)) $errors[] = "Shipping address is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($state)) $errors[] = "State is required";
    if (empty($zip_code)) $errors[] = "ZIP code is required";
    if (empty($country)) $errors[] = "Country is required";
    
    if (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($errors)) {
        
        if ($user_id) {
            $cart_sql = "SELECT uc.*, p.name, p.price, p.category_id
                         FROM user_carts uc 
                         JOIN products p ON uc.product_id = p.id 
                         WHERE uc.user_id = ?";
            $stmt = $conn->prepare($cart_sql);
            $stmt->bind_param("i", $user_id);
        } else {
            $cart_sql = "SELECT uc.*, p.name, p.price, p.category_id
                         FROM user_carts uc 
                         JOIN products p ON uc.product_id = p.id 
                         WHERE uc.session_id = ?";
            $stmt = $conn->prepare($cart_sql);
            $stmt->bind_param("s", $guest_id);
        }
        
        $stmt->execute();
        $cart_result = $stmt->get_result();
        $order_cart_items = [];
        $order_total = 0;
        
        while ($row = $cart_result->fetch_assoc()) {
            $order_cart_items[] = $row;
            $order_total += $row['price'] * $row['quantity'];
        }
        
   
        $applied_promo = $_SESSION['applied_promo'] ?? null;
        $order_discount = 0;
        
        if ($applied_promo) {
            switch($applied_promo['applies_to']) {
                case 'all':
                    if ($applied_promo['type'] === 'percentage') {
                        $order_discount = ($order_total * $applied_promo['value']) / 100;
                    } else {
                        $order_discount = $applied_promo['value'];
                    }
                    break;
                    
                case 'category':
                    foreach ($order_cart_items as $item) {
                        if ($item['category_id'] == $applied_promo['category_id']) {
                            if ($applied_promo['type'] === 'percentage') {
                                $order_discount += ($item['price'] * $item['quantity'] * $applied_promo['value']) / 100;
                            } else {
                                $order_discount += $applied_promo['value'] * $item['quantity'];
                            }
                        }
                    }
                    break;
                    
                case 'product':
                    foreach ($order_cart_items as $item) {
                        if ($item['product_id'] == $applied_promo['product_id']) {
                            if ($applied_promo['type'] === 'percentage') {
                                $order_discount += ($item['price'] * $item['quantity'] * $applied_promo['value']) / 100;
                            } else {
                                $order_discount += $applied_promo['value'] * $item['quantity'];
                            }
                        }
                    }
                    break;
            }
        }
        
        $order_final_total = $order_total - $order_discount;
  
        $order_number = 'ORD-' . date('YmdHis') . '-' . mt_rand(1000, 9999);
        
       
        $conn->begin_transaction();
        
        try {
         
            $order_sql = "INSERT INTO orders (
                order_number, guest_email, guest_name, guest_phone, 
                shipping_address, city, state, zip_code, country, 
                subtotal, discount_amount, tax_amount, shipping_amount, total_amount
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $order_stmt = $conn->prepare($order_sql);
            
            if (!$order_stmt) {
                throw new Exception("Order preparation failed: " . $conn->error);
            }
            
            $tax_amount = 0.00;
            $shipping_amount = 0.00;
            
            $order_stmt->bind_param("sssssssssddddd", 
                $order_number, $guest_email, $guest_name, $guest_phone,
                $shipping_address, $city, $state, $zip_code, $country,
                $order_total, $order_discount, $tax_amount, $shipping_amount, $order_final_total
            );
            
            if (!$order_stmt->execute()) {
                throw new Exception("Order insertion failed: " . $order_stmt->error);
            }
            
            $order_id = $order_stmt->insert_id;
            
            if (!$order_id) {
                throw new Exception("Failed to get order ID");
            }
            
            $order_stmt->close();
            
      
            $items_inserted = 0;
            foreach ($order_cart_items as $item) {
                $item_sql = "INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, subtotal) 
                             VALUES (?, ?, ?, ?, ?, ?)";
                
                $item_stmt = $conn->prepare($item_sql);
                
                if (!$item_stmt) {
                    throw new Exception("Order items preparation failed: " . $conn->error);
                }
                
                $product_id = $item['product_id'];
                $product_name = $item['name'];
                $product_price = floatval($item['price']);
                $quantity = intval($item['quantity']);
                $item_subtotal = $product_price * $quantity;
                
                $item_stmt->bind_param("iisdid", 
                    $order_id, $product_id, $product_name, 
                    $product_price, $quantity, $item_subtotal
                );
                
                if (!$item_stmt->execute()) {
                    throw new Exception("Failed to insert order item: " . $item_stmt->error);
                }
                
                $items_inserted++;
                $item_stmt->close();
            }
            
         
            if ($applied_promo) {
                $update_promo_sql = "UPDATE discounts SET used_count = used_count + 1 WHERE id = ?";
                $promo_stmt = $conn->prepare($update_promo_sql);
                if ($promo_stmt) {
                    $promo_id = $applied_promo['id'];
                    $promo_stmt->bind_param("i", $promo_id);
                    $promo_stmt->execute();
                    $promo_stmt->close();
                }
            }
            
       
            if ($user_id) {
                $clear_cart_sql = "DELETE FROM user_carts WHERE user_id = ?";
                $cart_stmt = $conn->prepare($clear_cart_sql);
                $cart_stmt->bind_param("i", $user_id);
            } else {
                $clear_cart_sql = "DELETE FROM user_carts WHERE session_id = ?";
                $cart_stmt = $conn->prepare($clear_cart_sql);
                $cart_stmt->bind_param("s", $guest_id);
            }
            
            if ($cart_stmt) {
                $cart_stmt->execute();
                $cart_stmt->close();
            }
            
      
            $conn->commit();
            
        
            unset($_SESSION['applied_promo']);
            
         
            $_SESSION['order_success'] = $order_number;
            $_SESSION['guest_email'] = $guest_email;
            header("Location: order_confirmation.php");
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $order_errors[] = $e->getMessage();
        }
    } else {
        $order_errors = $errors;
    }
}

if ($user_id) {
    $cart_sql = "SELECT uc.*, p.name, p.price, p.image_url, p.short_desc, c.name as category_name, p.category_id
                 FROM user_carts uc 
                 JOIN products p ON uc.product_id = p.id 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 WHERE uc.user_id = ?";
    $stmt = $conn->prepare($cart_sql);
    $stmt->bind_param("i", $user_id);
} else {
    $cart_sql = "SELECT uc.*, p.name, p.price, p.image_url, p.short_desc, c.name as category_name, p.category_id
                 FROM user_carts uc 
                 JOIN products p ON uc.product_id = p.id 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 WHERE uc.session_id = ?";
    $stmt = $conn->prepare($cart_sql);
    $stmt->bind_param("s", $guest_id);
}

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


$applied_promo = $_SESSION['applied_promo'] ?? null;
$discount_amount = 0;
$discount_details = '';

if ($applied_promo) {
    switch($applied_promo['applies_to']) {
        case 'all':
            if ($applied_promo['type'] === 'percentage') {
                $discount_amount = ($total_price * $applied_promo['value']) / 100;
            } else {
                $discount_amount = $applied_promo['value'];
            }
            $discount_details = "Cart discount";
            break;
            
        case 'category':
            foreach ($cart_items as $item) {
                if ($item['category_id'] == $applied_promo['category_id']) {
                    if ($applied_promo['type'] === 'percentage') {
                        $discount_amount += ($item['price'] * $item['quantity'] * $applied_promo['value']) / 100;
                    } else {
                        $discount_amount += $applied_promo['value'] * $item['quantity'];
                    }
                }
            }
            $discount_details = "Category discount";
            break;
            
        case 'product':
            foreach ($cart_items as $item) {
                if ($item['product_id'] == $applied_promo['product_id']) {
                    if ($applied_promo['type'] === 'percentage') {
                        $discount_amount += ($item['price'] * $item['quantity'] * $applied_promo['value']) / 100;
                    } else {
                        $discount_amount += $applied_promo['value'] * $item['quantity'];
                    }
                }
            }
            $discount_details = "Product discount";
            break;
    }
}

$final_total = $total_price - $discount_amount;
?>


<!DOCTYPE html>
<html>
<head>
    <title>My Cart & Checkout</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            padding: 20px;
            line-height: 1.6;
        }

        .cart-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
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

        /* Cart Items Styles */
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

        /* Promo Code Styles */
        .promo-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border: 1px solid #e0e0e0;
        }
        
        .promo-form {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .promo-input {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .promo-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .promo-btn:hover {
            background: #218838;
        }
        
        .promo-success {
            color: #28a745;
            background: #d4edda;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .promo-error {
            color: #dc3545;
            background: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .applied-promo {
            background: #d1ecf1;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #17a2b8;
        }
        
        .remove-promo {
            color: #dc3545;
            text-decoration: none;
            margin-left: 10px;
        }

        /* Price Breakdown */
        .price-breakdown {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .price-row.total {
            font-size: 24px;
            font-weight: bold;
            border-bottom: none;
            color: #333;
        }
        
        .discount {
            color: #28a745;
        }

        /* Checkout Form Styles */
        .checkout-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin: 25px 0;
            border: 1px solid #e0e0e0;
        }

        .checkout-section h3 {
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            outline: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .submit-order-btn {
            width: 100%;
            padding: 15px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            cursor: pointer;
            margin-top: 20px;
        }

        .submit-order-btn:hover {
            background: #218838;
        }

        .empty-cart {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 18px;
        }

        .order-error {
            color: #dc3545;
            background: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .cart-item {
                flex-direction: column;
                text-align: center;
            }
            
            .item-image {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>

    <div class="cart-container">
        <div class="cart-header">
            <h2>🛒 My Shopping Cart & Checkout</h2>
            <div class="badge" id="badge"><?= $total_items ?> items</div>
        </div>

       
        <div class="promo-section">
            <h3>💳 Apply Promo Code</h3>
            <?php if (isset($promo_success)): ?>
                <div class="promo-success">✅ <?= $promo_success ?></div>
            <?php endif; ?>
            
            <?php if (isset($promo_error)): ?>
                <div class="promo-error">❌ <?= $promo_error ?></div>
            <?php endif; ?>
            
            <?php if ($applied_promo): ?>
                <div class="applied-promo">
                    <strong>Applied Promo:</strong> <?= $applied_promo['code'] ?> 
                    - <?= $applied_promo['type'] === 'percentage' ? $applied_promo['value'] . '% off' : '$' . $applied_promo['value'] . ' off' ?>
                    (<?= $discount_details ?>)
                    <a href="?remove_promo=1" class="remove-promo">Remove</a>
                </div>
            <?php else: ?>
                <form method="POST" class="promo-form">
                    <input type="text" name="promo_code" class="promo-input" placeholder="Enter promo code" required>
                    <button type="submit" name="apply_promo" class="promo-btn">Apply Code</button>
                </form>
            <?php endif; ?>
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
                            <?php if (!empty($item['image_url'])): ?>
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
         
            <div class="price-breakdown">
                <div class="price-row">
                    <span>Subtotal:</span>
                    <span>$<?= number_format($total_price, 2) ?></span>
                </div>
                
                <?php if ($discount_amount > 0): ?>
                <div class="price-row discount">
                    <span>Discount (<?= $applied_promo['code'] ?>):</span>
                    <span>-<?= number_format($discount_amount, 2) ?></span>
                </div>
                <?php endif; ?>
                
                <div class="price-row">
                    <span>Shipping:</span>
                    <span>0.00</span>
                </div>
                
                <div class="price-row">
                    <span>Tax:</span>
                    <span>0.00</span>
                </div>
                
                <div class="price-row total">
                    <span>Total:</span>
                    <span><?= number_format($final_total, 2) ?></span>
                </div>
            </div>

       
            <div class="checkout-section">
                <h3>🚚 Checkout Information</h3>
                
                <?php if (isset($order_errors)): ?>
                    <div class="order-error">
                        <?php foreach ($order_errors as $error): ?>
                            <div>❌ <?= $error ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="checkoutForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="guest_email">📧 Email Address *</label>
                            <input type="email" id="guest_email" name="guest_email" 
                                   value="<?= $_POST['guest_email'] ?? '' ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="guest_phone">📞 Phone Number *</label>
                            <input type="tel" id="guest_phone" name="guest_phone" 
                                   value="<?= $_POST['guest_phone'] ?? '' ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="guest_name">👤 Full Name *</label>
                        <input type="text" id="guest_name" name="guest_name" 
                               value="<?= $_POST['guest_name'] ?? '' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="shipping_address">🏠 Shipping Address *</label>
                        <textarea id="shipping_address" name="shipping_address" rows="3" required 
                                  placeholder="Street address, apartment/suite number"><?= $_POST['shipping_address'] ?? '' ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">🏙️ City *</label>
                            <input type="text" id="city" name="city" 
                                   value="<?= $_POST['city'] ?? '' ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="state">🗺️ State *</label>
                            <input type="text" id="state" name="state" 
                                   value="<?= $_POST['state'] ?? '' ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="zip_code">📮 ZIP Code *</label>
                            <input type="text" id="zip_code" name="zip_code" 
                                   value="<?= $_POST['zip_code'] ?? '' ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="country">🌍 Country *</label>
                            <select id="country" name="country" required>
                                <option value="">Select Country</option>
                                <option value="United States" <?= ($_POST['country'] ?? '') === 'United States' ? 'selected' : '' ?>>United States</option>
                                <option value="Canada" <?= ($_POST['country'] ?? '') === 'Canada' ? 'selected' : '' ?>>Canada</option>
                                <option value="United Kingdom" <?= ($_POST['country'] ?? '') === 'United Kingdom' ? 'selected' : '' ?>>United Kingdom</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="billing_address">💳 Billing Address (if different from shipping)</label>
                        <textarea id="billing_address" name="billing_address" rows="3" 
                                  placeholder="Leave empty to use shipping address"><?= $_POST['billing_address'] ?? '' ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">📝 Order Notes (Optional)</label>
                        <textarea id="notes" name="notes" rows="3" 
                                  placeholder="Special delivery instructions or notes about your order"><?= $_POST['notes'] ?? '' ?></textarea>
                    </div>
                    
                    <button type="submit" name="place_order" class="submit-order-btn">
                        Place Order - <?= number_format($final_total, 2) ?>
                    </button>
                </form>
            </div>
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
                    document.getElementById(`cart-item-${productId}`).remove();
                    document.getElementById('badge').textContent = data.total_items + ' items';
                    
                    if (data.total_items === 0) {
                        location.reload();
                    } else {
                        location.reload();
                    }
                }
            });
        }
    }

    document.getElementById('shipping_address').addEventListener('blur', function() {
        const billingAddress = document.getElementById('billing_address');
        if (!billingAddress.value) {
            billingAddress.value = this.value;
        }
    });

    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        const email = document.getElementById('guest_email').value;
        const phone = document.getElementById('guest_phone').value;
        
        if (!isValidEmail(email)) {
            alert('Please enter a valid email address');
            e.preventDefault();
            return;
        }
        
        if (!isValidPhone(phone)) {
            alert('Please enter a valid phone number');
            e.preventDefault();
            return;
        }
    });

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function isValidPhone(phone) {
        return phone.length >= 10;
    }

    
    document.addEventListener('DOMContentLoaded', function() {
        const totalItems = <?= $total_items ?>;
        document.getElementById('badge').textContent = totalItems + ' item' + (totalItems !== 1 ? 's' : '');
    });
    </script>
</body>
</html>