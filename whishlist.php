<?php
session_start();
include 'db.php'; // Your DB connection

// Logged-in user (for demo)
$user_id = $_SESSION['user_id'] ?? 1;

// 1️⃣ Remove from wishlist
if(isset($_GET['remove_id'])){
    $remove_id = $_GET['remove_id'];
    $conn->query("DELETE FROM wishlist WHERE id='$remove_id' AND user_id='$user_id'");
    header("Location: wishlist.php");
    exit;
}

// 2️⃣ Move to cart (with stock check)
if(isset($_GET['move_id'])){
    $product_id = $_GET['move_id'];

    // Check total stock
    $stock_res = $conn->query("SELECT SUM(quantity) AS total_stock FROM stock WHERE product_id='$product_id'");
    $stock_row = $stock_res->fetch_assoc();
    $available_stock = $stock_row['total_stock'] ?? 0;

    if($available_stock > 0){
        $cart_res = $conn->query("SELECT * FROM user_carts WHERE user_id='$user_id' AND product_id='$product_id'");
        if($cart_res->num_rows > 0){
            $conn->query("UPDATE user_carts SET quantity = quantity + 1 WHERE user_id='$user_id' AND product_id='$product_id'");
        } else {
            $conn->query("INSERT INTO user_carts (user_id, product_id, quantity) VALUES ('$user_id','$product_id',1)");
        }
        $conn->query("DELETE FROM wishlist WHERE user_id='$user_id' AND product_id='$product_id'");
        $msg = "✅ Product moved to cart!";
        $msg_type = "success";
    } else {
        $msg = "❌ Product is out of stock!";
        $msg_type = "error";
    }
}

// 3️⃣ Fetch wishlist items
$result = $conn->query("
    SELECT w.id AS wish_id, p.id AS product_id, p.name, p.price, p.image_url,
    (SELECT SUM(quantity) FROM stock s WHERE s.product_id=p.id) AS total_stock
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    WHERE w.user_id='$user_id'
");

if(!$result){
    die("Query failed: ".$conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h1 {
            color: #2d3748;
            font-size: 32px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .heart-icon {
            color: #e53e3e;
            font-size: 36px;
        }

        .notification {
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideIn 0.4s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .notification.success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #38a169;
        }

        .notification.error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #e53e3e;
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }

        .wishlist-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeIn 0.5s ease;
        }

        .wishlist-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            width: 90%;
            height: 250px;
            object-fit: cover;
            background: #f7fafc;
            margin-top: 10px;
        }

        .card-content {
            padding: 20px;
        }

        .product-name {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .product-price {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 12px;
        }

        .stock-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .stock-in {
            background: #c6f6d5;
            color: #22543d;
        }

        .stock-low {
            background: #feebc8;
            color: #7c2d12;
        }

        .stock-out {
            background: #fed7d7;
            color: #742a2a;
        }

        .card-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            font-size: 14px;
        }

        .btn-cart {
            background: #667eea;
            color: white;
        }

        .btn-cart:hover {
            background: #5568d3;
            transform: scale(1.02);
        }

        .btn-remove {
            background: #feb2b2;
            color: #742a2a;
        }

        .btn-remove:hover {
            background: #fc8181;
        }

        .btn-disabled {
            background: #e2e8f0;
            color: #a0aec0;
            cursor: not-allowed;
        }

        .empty-wishlist {
            background: white;
            padding: 60px 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .empty-wishlist h2 {
            color: #4a5568;
            font-size: 24px;
            margin-bottom: 12px;
        }

        .empty-wishlist p {
            color: #718096;
            font-size: 16px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 15px;
                padding: 20px;
            }

            h1 {
                font-size: 24px;
            }

            .wishlist-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>
                <span class="heart-icon">♥</span>
                My Wishlist
            </h1>
        </header>

        <?php if(isset($msg)): ?>
            <div class="notification <?= $msg_type ?>">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <?php if($result->num_rows > 0): ?>
            <div class="wishlist-grid">
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="wishlist-card">
                        <img src="<?= htmlspecialchars($row['image_url']) ?>" 
                             alt="<?= htmlspecialchars($row['name']) ?>" 
                             class="product-image">
                        
                        <div class="card-content">
                            <h3 class="product-name"><?= htmlspecialchars($row['name']) ?></h3>
                            <div class="product-price"><?= number_format($row['price'], 2) ?></div>
                            
                            <?php 
                                $stock = $row['total_stock'];
                                if($stock > 5): 
                            ?>
                                <span class="stock-badge stock-in">✓ In Stock</span>
                            <?php elseif($stock > 0): ?>
                                <span class="stock-badge stock-low">⚠ Only <?= $stock ?> left</span>
                            <?php else: ?>
                                <span class="stock-badge stock-out">✗ Out of Stock</span>
                            <?php endif; ?>

                            <div class="card-actions">
                                <?php if($stock > 0): ?>
                                     <button class="btn btn-cart" onclick="window.location.href='Cart.php'">  🛒 Add to Cart</button>
                               
                                <?php else: ?>
                                    <button class="btn btn-disabled" disabled>Out of Stock</button>
                                <?php endif; ?>
                                
                                <a href="wishlist.php?remove_id=<?= $row['wish_id'] ?>" 
                                   class="btn btn-remove"
                                   onclick="return confirm('Remove this item from wishlist?')">
                                    🗑️
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-wishlist">
                <h2>Your wishlist is empty</h2>
                <p>Start adding products you love to your wishlist!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>