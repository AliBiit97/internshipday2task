<?php
session_start();
include 'db.php';


if (!isset($_SESSION['user_id'])) {
   
    session_unset();
session_destroy();
session_start();
    if (!isset($_SESSION['guest_id'])) {
        $_SESSION['guest_id'] = "GUEST_" . time() . "_" . rand(1000, 9999);
    }
    $current_user = $_SESSION['guest_id'];
    $user_id = null;
    $guest_id = $_SESSION['guest_id'];
} else {

    $user_id = $_SESSION['user_id'];
    $guest_id = null;
    $current_user = $user_id;
}


if (isset($_POST['wishlist_product_id'])) {
 
    if (!$user_id) {
        echo "login_required";
        exit;
    }
    
    $product_id = intval($_POST['wishlist_product_id']);
    
    $check_sql = "SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $check_res = $stmt->get_result();
    
    if ($check_res->num_rows > 0) {
      
        $delete_sql = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        echo "removed";
    } else {
 
        $insert_sql = "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        echo "added";
    }
    exit;
}

if (isset($_POST['cart_product_id'])) {
    $product_id = intval($_POST['cart_product_id']);
 
    if ($user_id) {
        $check_sql = "SELECT * FROM user_carts WHERE user_id = ? AND product_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ii", $user_id, $product_id);
    } else {
  
        $check_sql = "SELECT * FROM user_carts WHERE session_id = ? AND product_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("si", $guest_id, $product_id);
    }

    $stmt->execute();
    $check_res = $stmt->get_result();

    if ($check_res->num_rows > 0) {
 
        if ($user_id) {
            $update_sql = "UPDATE user_carts SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ii", $user_id, $product_id);
        } else {
            $update_sql = "UPDATE user_carts SET quantity = quantity + 1 WHERE session_id = ? AND product_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("si", $guest_id, $product_id);
        }
        $stmt->execute();
    } else {

        if ($user_id) {
            $insert_sql = "INSERT INTO user_carts (user_id, product_id, quantity) VALUES (?, ?, 1)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ii", $user_id, $product_id);
        } else {
            $insert_sql = "INSERT INTO user_carts (session_id, product_id, quantity) VALUES (?, ?, 1)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("si", $guest_id, $product_id);
        }
        $stmt->execute();
    }

    echo "added";
    exit;
}

if (isset($_POST['review_product_id']) && isset($_POST['review_rating']) && isset($_POST['review_comment'])) {

    if (!$user_id) {
        echo "login_required";
        exit;
    }
    
    $product_id = intval($_POST['review_product_id']);
    $rating = intval($_POST['review_rating']);
    $comment = trim($_POST['review_comment']);
    
    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
      
        $check_table_sql = "SHOW TABLES LIKE 'reviews'";
        $table_result = $conn->query($check_table_sql);
        
        if ($table_result->num_rows == 0) {
     
            $create_reviews_sql = "CREATE TABLE reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                rating INT CHECK (rating >= 1 AND rating <= 5),
                comment TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )";
            $conn->query($create_reviews_sql);
        }
        
        $insert_sql = "INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iiis", $user_id, $product_id, $rating, $comment);
        $stmt->execute();
        echo "success";
    } else {
        echo "error";
    }
    exit;
}

$products_sql = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 WHERE p.enabled = TRUE
                 ORDER BY p.created_at DESC";
$products_result = $conn->query($products_sql);
$products = [];
if ($products_result->num_rows > 0) {
    while ($row = $products_result->fetch_assoc()) {
        
        $stock_sql = "SELECT SUM(quantity) as total_stock FROM stock WHERE product_id = ?";
        $stmt = $conn->prepare($stock_sql);
        $stmt->bind_param("i", $row['id']);
        $stmt->execute();
        $stock_result = $stmt->get_result();
        $stock_data = $stock_result->fetch_assoc();
        
        $row['stock_quantity'] = $stock_data['total_stock'] ?? 0;
        $products[] = $row;
    }
}

$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];
if ($categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

$wishlist = [];
if ($user_id) {
    $wishlist_sql = "SELECT product_id FROM wishlist WHERE user_id = ?";
    $stmt = $conn->prepare($wishlist_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $wishlist_res = $stmt->get_result();
    if ($wishlist_res->num_rows > 0) {
        while ($row = $wishlist_res->fetch_assoc()) {
            $wishlist[] = $row['product_id'];
        }
    }
}

$all_reviews = [];
$check_table_sql = "SHOW TABLES LIKE 'reviews'";
$table_result = $conn->query($check_table_sql);
if ($table_result->num_rows > 0) {
    $reviews_sql = "SELECT r.*, u.username, p.name as product_name 
                    FROM reviews r 
                    JOIN users u ON r.user_id = u.id 
                    JOIN products p ON r.product_id = p.id 
                    ORDER BY r.created_at DESC";
    $reviews_result = $conn->query($reviews_sql);
    if ($reviews_result->num_rows > 0) {
        while ($row = $reviews_result->fetch_assoc()) {
            $all_reviews[$row['product_id']][] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🛍️ Shop Our Collection</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.08) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: relative;
            z-index: 1;
        }

        h1 {
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 40px;
            font-size: 3em;
            font-weight: 800;
            letter-spacing: -1px;
            animation: fadeInDown 0.6s ease;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .filters-section {
            display: flex;
            gap: 20px;
            margin-bottom: 35px;
            flex-wrap: wrap;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 16px;
            box-shadow: inset 0 2px 8px rgba(0,0,0,0.05);
            animation: fadeIn 0.8s ease;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 16px 24px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 700;
            color: #333;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-group select {
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            cursor: pointer;
            background: white;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .filter-group select:hover {
            border-color: #667eea;
        }

        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.2);
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 14px 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-action::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn-action:hover::before {
            left: 100%;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-action:active {
            transform: translateY(0);
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }

        .item-card {
            background: white;
            border: 2px solid #f0f0f0;
            border-radius: 20px;
            padding: 25px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .item-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .item-card:hover::before {
            opacity: 1;
        }

        .item-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.25);
            border-color: #667eea;
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 15px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }

        .item-category {
            display: inline-block;
            padding: 6px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 25px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .item-name {
            font-size: 22px;
            font-weight: 800;
            color: #222;
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .item-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 18px;
            line-height: 1.6;
        }

        .item-price {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 18px;
        }

        .stock-info {
            font-size: 12px;
            color: #666;
            margin-bottom: 15px;
        }

        .in-stock {
            color: #28a745;
            font-weight: 600;
        }

        .out-of-stock {
            color: #dc3545;
            font-weight: 600;
        }

        .compare-checkbox {
            position: absolute;
            top: 18px;
            left: 18px;
            transform: scale(1.4);
            cursor: pointer;
            accent-color: #667eea;
            z-index: 10;
        }

        .compare-label {
            position: absolute;
            top: 25px;
            left: 42px;
            font-size: 12px;
            font-weight: 600;
            color: #667eea;
            background: white;
            padding: 2px 8px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            pointer-events: none;
        }

        .wishlist-btn {
            position: absolute;
            top: 15px;
            right: 18px;
            background: white;
            border: 2px solid #f0f0f0;
            font-size: 24px;
            cursor: pointer;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .wishlist-btn:hover {
            transform: scale(1.15);
            box-shadow: 0 4px 16px rgba(255, 59, 107, 0.3);
            border-color: #ff3b6b;
        }

        .add-to-cart-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            margin-bottom: 12px;
        }

        .add-to-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .add-to-cart-btn:active {
            transform: translateY(0);
        }

        .add-to-cart-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .reviews-section {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 2px solid #f0f0f0;
        }

        .reviews-section h4 {
            margin-bottom: 12px;
            font-size: 16px;
            color: #333;
            font-weight: 700;
        }

        .reviews-list {
            max-height: 150px;
            overflow-y: auto;
            margin-bottom: 12px;
        }

        .reviews-list::-webkit-scrollbar {
            width: 6px;
        }

        .reviews-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .reviews-list::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }

        .review-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 13px;
            line-height: 1.5;
        }

        .review-rating,
        .review-comment {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 10px;
            border: 2px solid #e0e0f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border 0.3s ease;
        }

        .review-rating:focus,
        .review-comment:focus {
            outline: none;
            border-color: #667eea;
        }

        .submit-review-btn {
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
        }

        .submit-review-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .no-results {
            text-align: center;
            padding: 80px 20px;
            color: #999;
            font-size: 18px;
            font-weight: 500;
            grid-column: 1 / -1;
        }

        .results-count {
            color: #666;
            margin-bottom: 25px;
            font-size: 16px;
            font-weight: 600;
        }

        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .comparison-table th,
        .comparison-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0f0;
        }

        .comparison-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 700;
        }

        .comparison-table tr:hover td {
            background: #f8f9fa;
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: #28a745;
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(400px);
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.error {
            background: #dc3545;
        }

        @media (max-width: 768px) {
            .container {
                padding: 25px;
            }

            h1 {
                font-size: 2em;
            }

            .items-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .filters-section {
                padding: 20px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛍️ Shop Our Collection</h1>

        <div class="filters-section">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search products..." onkeyup="filterItems()">
            </div>

            <div class="filter-group">
                <label>Category</label>
                <select id="categoryFilter" onchange="filterItems()">
                    <option value="all">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Price Range</label>
                <select id="priceFilter" onchange="filterItems()">
                    <option value="all">All Prices</option>
                    <option value="0-50">0 - 50</option>
                    <option value="51-100">51 - 100</option>
                    <option value="101-200">101 - 200</option>
                    <option value="201-plus">201+</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Sort By</label>
                <select id="sortFilter" onchange="filterItems()">
                    <option value="newest">Newest First</option>
                    <option value="price-low">Price: Low to High</option>
                    <option value="price-high">Price: High to Low</option>
                    <option value="name">Name: A to Z</option>
                </select>
            </div>
        </div>

        <div class="action-buttons">
            <button class="btn-action" onclick="showComparison()">📊 Compare Selected Products</button>
            <button class="btn-action" onclick="showWishlist()">💖 Show Wishlist</button>
            <button class="btn-action" onclick="window.location.href='Cart.php'">View Cart</button>
        </div>

        <div class="results-count" id="resultsCount"></div>
        
        <div class="items-grid" id="itemsGrid">
            <?php foreach($products as $item): ?>
            <div class="item-card" data-name="<?= strtolower(htmlspecialchars($item['name'])) ?>" 
                 data-category="<?= htmlspecialchars($item['category_name']) ?>" 
                 data-price="<?= $item['price'] ?>" 
                 data-stock="<?= $item['stock_quantity'] ?>">
                
                <input type="checkbox" class="compare-checkbox" data-id="<?= $item['id'] ?>" id="compare-<?= $item['id'] ?>">
                <label for="compare-<?= $item['id'] ?>" class="compare-label">Compare</label>
                
                <button class="wishlist-btn" data-id="<?= $item['id'] ?>">
                    <?= in_array($item['id'], $wishlist) ? "💖" : "🤍" ?>
                </button>

                <div class="product-image">
                    <?= $item['image_url'] ? '<img src="'.htmlspecialchars($item['image_url']).'" alt="'.htmlspecialchars($item['name']).'" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">' : '📱' ?>
                </div>

                <span class="item-category"><?= htmlspecialchars($item['category_name']) ?></span>
                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                <div class="item-description"><?= htmlspecialchars($item['short_desc'] ?: $item['long_desc']) ?></div>
                <div class="item-price"><?= number_format($item['price'], 2) ?></div>
                
                <div class="stock-info <?= $item['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                    <?= $item['stock_quantity'] > 0 ? "✅ In Stock ($item[stock_quantity] available)" : "❌ Out of Stock" ?>
                </div>

                <button class="add-to-cart-btn" data-id="<?= $item['id'] ?>" <?= $item['stock_quantity'] <= 0 ? 'disabled' : '' ?>>
                    <?= $item['stock_quantity'] > 0 ? '🛒 Add to Cart' : 'Out of Stock' ?>
                </button>

                <div class="reviews-section">
                    <h4>⭐ Reviews</h4>
                    <div class="reviews-list" id="reviews-list-<?= $item['id'] ?>">
                        <?php if(isset($all_reviews[$item['id']])): ?>
                            <?php foreach($all_reviews[$item['id']] as $review): ?>
                                <div class="review-item">
                                    <strong><?= htmlspecialchars($review['username']) ?></strong> - 
                                    <?= str_repeat('⭐', $review['rating']) ?><br>
                                    <?= htmlspecialchars($review['comment']) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="review-item">No reviews yet. Be the first to review!</div>
                        <?php endif; ?>
                    </div>
                    
                    <select class="review-rating" id="rating-<?= $item['id'] ?>">
                        <option value="">Rate this product</option>
                        <option value="1">1⭐</option>
                        <option value="2">2⭐</option>
                        <option value="3">3⭐</option>
                        <option value="4">4⭐</option>
                        <option value="5">5⭐</option>
                    </select>
                    <input type="text" class="review-comment" id="comment-<?= $item['id'] ?>" placeholder="Write your review...">
                    <button class="submit-review-btn" onclick="submitReview(<?= $item['id'] ?>)">Submit Review</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        let compareList = [];
        let allProducts = <?= json_encode($products) ?>;
        let currentDisplay = 'all';

      
        document.addEventListener('DOMContentLoaded', function() {
            displayItems();
        });

        function displayItems() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const categoryFilter = document.getElementById('categoryFilter').value;
            const priceFilter = document.getElementById('priceFilter').value;
            const sortFilter = document.getElementById('sortFilter').value;

            const items = Array.from(document.querySelectorAll('.item-card'));
            let visibleItems = [];
            
            items.forEach(item => {
                const name = item.dataset.name;
                const category = item.dataset.category;
                const price = parseFloat(item.dataset.price);
                const stock = parseInt(item.dataset.stock);

                let show = true;
            
                if (searchTerm && !name.includes(searchTerm)) {
                    show = false;
                }
                
           
                if (categoryFilter !== 'all' && category !== categoryFilter) {
                    show = false;
                }
                
               
                if (priceFilter !== 'all') {
                    if (priceFilter === '0-50' && price > 50) show = false;
                    if (priceFilter === '51-100' && (price < 51 || price > 100)) show = false;
                    if (priceFilter === '101-200' && (price < 101 || price > 200)) show = false;
                    if (priceFilter === '201-plus' && price < 201) show = false;
                }
                
             
                if (currentDisplay === 'wishlist') {
                    const wishlistBtn = item.querySelector('.wishlist-btn');
                    if (wishlistBtn.textContent !== '💖') {
                        show = false;
                    }
                }

                item.style.display = show ? 'block' : 'none';
                if (show) {
                    visibleItems.push({element: item, price: price, name: name});
                }
            });

           
            visibleItems.sort((a, b) => {
                switch(sortFilter) {
                    case 'price-low':
                        return a.price - b.price;
                    case 'price-high':
                        return b.price - a.price;
                    case 'name':
                        return a.name.localeCompare(b.name);
                    default: 
                        return 0; 
                }
            });

           
            const grid = document.getElementById('itemsGrid');
            visibleItems.forEach(item => {
                grid.appendChild(item.element);
            });

            document.getElementById('resultsCount').textContent = `Showing ${visibleItems.length} product(s)`;
        }

        function filterItems() {
            displayItems();
        }

        function showWishlist() {
            currentDisplay = 'wishlist';
            displayItems();
            showToast('Showing your wishlist items');
        }

        function showAllProducts() {
            currentDisplay = 'all';
            document.getElementById('searchInput').value = '';
            document.getElementById('categoryFilter').value = 'all';
            document.getElementById('priceFilter').value = 'all';
            document.getElementById('sortFilter').value = 'newest';
            displayItems();
            showToast('Showing all products');
        }

      
        document.addEventListener('click', function(e) {
          
            if (e.target.classList.contains('wishlist-btn')) {
                const btn = e.target;
                const product_id = btn.dataset.id;
                
                $.post('', {wishlist_product_id: product_id}, function(response) {
                    if (response === 'added') {
                        btn.textContent = '💖';
                        showToast('Product added to wishlist!');
                    } else {
                        btn.textContent = '🤍';
                        showToast('Product removed from wishlist');
                    }
                });
            }

         
            if (e.target.classList.contains('add-to-cart-btn') && !e.target.disabled) {
                const btn = e.target;
                const product_id = btn.dataset.id;
                
                $.post('', {cart_product_id: product_id}, function(response) {
                    if (response === 'added') {
                        showToast('🛒 Product added to cart!');
                    }
                });
            }
        });

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('compare-checkbox')) {
                const id = e.target.dataset.id;
                if (e.target.checked) {
                    if (compareList.length < 4) {
                        compareList.push(id);
                        showToast('Product added to comparison');
                    } else {
                        e.target.checked = false;
                        showToast('Maximum 4 products can be compared!', true);
                    }
                } else {
                    compareList = compareList.filter(i => i !== id);
                    showToast('Product removed from comparison');
                }
            }
        });

        function showComparison() {
            if (compareList.length === 0) {
                showToast('Please select products to compare!', true);
                return;
            }

            let html = '<h3 style="margin-bottom: 20px; text-align: center;">📊 Product Comparison</h3>';
            html += '<table class="comparison-table">';
            html += '<tr><th>Feature</th>';
            
            compareList.forEach(id => {
                const product = allProducts.find(p => p.id == id);
                html += `<th>${product.name}</th>`;
            });
            html += '</tr>';

           
            html += '<tr><td><strong>Price</strong></td>';
            compareList.forEach(id => {
                const product = allProducts.find(p => p.id == id);
                html += `<td>$${parseFloat(product.price).toFixed(2)}</td>`;
            });
            html += '</tr>';

            html += '<tr><td><strong>Category</strong></td>';
            compareList.forEach(id => {
                const product = allProducts.find(p => p.id == id);
                html += `<td>${product.category_name}</td>`;
            });
            html += '</tr>';

            html += '<tr><td><strong>Description</strong></td>';
            compareList.forEach(id => {
                const product = allProducts.find(p => p.id == id);
                html += `<td>${product.short_desc || product.long_desc || 'No description available'}</td>`;
            });
            html += '</tr>';

            html += '<tr><td><strong>Stock</strong></td>';
            compareList.forEach(id => {
                const product = allProducts.find(p => p.id == id);
                const stockStatus = product.stock_quantity > 0 ? 
                    `In Stock (${product.stock_quantity})` : 'Out of Stock';
                html += `<td>${stockStatus}</td>`;
            });
            html += '</tr>';

            html += '</table>';
            html += '<button class="btn-action" onclick="showAllProducts()" style="margin-top: 20px;">← Back to Products</button>';

            document.getElementById('itemsGrid').innerHTML = html;
            document.getElementById('resultsCount').textContent = `Comparing ${compareList.length} products`;
        }

        function submitReview(productId) {
            const rating = document.getElementById(`rating-${productId}`).value;
            const comment = document.getElementById(`comment-${productId}`).value;

            if (!rating || !comment.trim()) {
                showToast('Please provide both rating and comment!', true);
                return;
            }

            $.post('', {
                review_product_id: productId,
                review_rating: rating,
                review_comment: comment.trim()
            }, function(response) {
                if (response === 'success') {
                    showToast('Review submitted successfully!');
                   
                    document.getElementById(`rating-${productId}`).value = '';
                    document.getElementById(`comment-${productId}`).value = '';
                   
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error submitting review!', true);
                }
            });
        }

        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = isError ? 'toast error show' : 'toast show';
            
            setTimeout(() => {
                toast.className = 'toast';
            }, 3000);
        }
    </script>
</body>
</html>