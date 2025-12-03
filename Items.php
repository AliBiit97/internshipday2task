<?php
session_start();
include 'db.php';

$check_table = "SHOW TABLES LIKE 'recently_viewed'";
$table_result = $conn->query($check_table);
if ($table_result->num_rows == 0) {
    $create_table = "CREATE TABLE recently_viewed (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        session_id VARCHAR(100) NULL,
        product_id INT NOT NULL,
        viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_product (user_id, product_id),
        UNIQUE KEY unique_session_product (session_id, product_id)
    )";
    $conn->query($create_table);
}

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

if (isset($_POST['track_recently_viewed'])) {
    $product_id = intval($_POST['track_recently_viewed']);
    
    if ($user_id) {
        $check_sql = "SELECT id FROM recently_viewed WHERE user_id = ? AND product_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $check_res = $stmt->get_result();
        
        if ($check_res->num_rows > 0) {
            $update_sql = "UPDATE recently_viewed SET viewed_at = NOW() WHERE user_id = ? AND product_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
        } else {
            $insert_sql = "INSERT INTO recently_viewed (user_id, product_id) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            
            $count_sql = "SELECT COUNT(*) as count FROM recently_viewed WHERE user_id = ?";
            $stmt = $conn->prepare($count_sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $count_result = $stmt->get_result()->fetch_assoc();
            
            if ($count_result['count'] > 10) {
                $delete_oldest = "DELETE FROM recently_viewed WHERE user_id = ? ORDER BY viewed_at ASC LIMIT 1";
                $stmt = $conn->prepare($delete_oldest);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
            }
        }
    } else {
        $check_sql = "SELECT id FROM recently_viewed WHERE session_id = ? AND product_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("si", $guest_id, $product_id);
        $stmt->execute();
        $check_res = $stmt->get_result();
        
        if ($check_res->num_rows > 0) {
            $update_sql = "UPDATE recently_viewed SET viewed_at = NOW() WHERE session_id = ? AND product_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("si", $guest_id, $product_id);
            $stmt->execute();
        } else {
            $insert_sql = "INSERT INTO recently_viewed (session_id, product_id) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("si", $guest_id, $product_id);
            $stmt->execute();
            
            $count_sql = "SELECT COUNT(*) as count FROM recently_viewed WHERE session_id = ?";
            $stmt = $conn->prepare($count_sql);
            $stmt->bind_param("s", $guest_id);
            $stmt->execute();
            $count_result = $stmt->get_result()->fetch_assoc();
            
            if ($count_result['count'] > 10) {
                $delete_oldest = "DELETE FROM recently_viewed WHERE session_id = ? ORDER BY viewed_at ASC LIMIT 1";
                $stmt = $conn->prepare($delete_oldest);
                $stmt->bind_param("s", $guest_id);
                $stmt->execute();
            }
        }
    }
    
    echo "tracked";
    exit;
}

if ($user_id) {
    $rv_sql = "SELECT p.* FROM recently_viewed rv
               JOIN products p ON p.id = rv.product_id
               WHERE rv.user_id = ? AND p.enabled = TRUE
               ORDER BY rv.viewed_at DESC
               LIMIT 10";
    $stmt = $conn->prepare($rv_sql);
    $stmt->bind_param("i", $user_id);
} else {
    $rv_sql = "SELECT p.* FROM recently_viewed rv
               JOIN products p ON p.id = rv.product_id
               WHERE rv.session_id = ? AND p.enabled = TRUE
               ORDER BY rv.viewed_at DESC
               LIMIT 10";
    $stmt = $conn->prepare($rv_sql);
    $stmt->bind_param("s", $guest_id);
}
$stmt->execute();
$recently_viewed_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$recently_viewed_ids = [];
foreach ($recently_viewed_products as $product) {
    $recently_viewed_ids[] = $product['id'];
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

// NEW: Fetch multiple images for products
$product_ids = array_column($products, 'id');
$product_images = [];
if (!empty($product_ids)) {
    $images_sql = "SELECT product_id, image_url FROM product_images WHERE product_id IN (" . implode(',', $product_ids) . ") ORDER BY id ASC";
    $images_result = $conn->query($images_sql);
    if ($images_result->num_rows > 0) {
        while ($row = $images_result->fetch_assoc()) {
            $product_images[$row['product_id']][] = $row['image_url'];
        }
    }
}

$recommended_products = [];
if (!empty($products)) {
    $category_id = $products[0]['category_id'];
    $rec_sql = "SELECT * FROM products 
                WHERE category_id = ? AND id != ?
                ORDER BY RAND() 
                LIMIT 5";
    $stmt = $conn->prepare($rec_sql);
    $stmt->bind_param("ii", $category_id, $products[0]['id']);
    $stmt->execute();
    $recommended_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
if ($products_result->num_rows > 0) {
    $reviews_sql = "SELECT r.*, u.username FROM reviews r 
                    JOIN users u ON r.user_id = u.id 
                    WHERE r.product_id IN (" . implode(',', array_column($products, 'id')) . ")
                    ORDER BY r.created_at DESC";
    $reviews_result = $conn->query($reviews_sql);
    if ($reviews_result->num_rows > 0) {
        while ($review = $reviews_result->fetch_assoc()) {
            $all_reviews[$review['product_id']][] = $review;
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

        /* NEW: Multiple Images Gallery Styles */
        .product-image-container {
            position: relative;
            width: 100%;
            height: 200px;
            margin-bottom: 15px;
            margin-top:  5px;
        }

        .main-product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .main-product-image:hover {
            transform: scale(1.03);
        }

        .thumbnail-container {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            overflow-x: auto;
            padding: 5px;
            scrollbar-width: thin;
        }

        .thumbnail-container::-webkit-scrollbar {
            height: 4px;
        }

        .thumbnail-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .thumbnail-container::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }

        .image-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .image-thumbnail:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .image-thumbnail.active {
            border-color: #764ba2;
            box-shadow: 0 0 8px rgba(102, 126, 234, 0.5);
        }

        /* NEW: Image Preview Modal */
        .image-preview-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .preview-image-container {
            max-width: 90%;
            max-height: 90%;
            position: relative;
        }

        .preview-image {
            max-width: 100%;
            max-height: 85vh;
            border-radius: 8px;
            object-fit: contain;
        }

        .preview-nav {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            transform: translateY(-50%);
            padding: 0 20px;
        }

        .preview-nav-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .preview-nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .close-preview {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close-preview:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .item-category {
            display: inline-block;
            padding: 6px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 25px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 10px;
            margin-top: 70px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .item-name {
            font-size: 22px;
            font-weight: 800;
            color: #222;
            margin-bottom: 5px;
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
            top: 15px;
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
            top: 5px;
            right: 5px;
            background: white;
            border: 2px solid #f0f0f0;
            font-size: 24px;
            cursor: pointer;
            width: 30px;
            height: 30px;
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

        recently-viewed-section {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .section-title {
            margin-bottom: 15px;
            color: #333;
            font-size: 1.3rem;
            padding-bottom: 8px;
            border-bottom: 2px solid #007bff;
        }

        .recently-viewed-container {
            display: flex;
            overflow-x: auto;
            gap: 12px;
            padding: 8px;
            scrollbar-width: thin;
        }

        .recent-product-card {
            min-width: 180px;
            background: white;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            flex-shrink: 0;
        }

        .recent-product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .recent-product-img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 8px;
        }

        .recent-product-img.placeholder {
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .recent-product-info {
            text-align: center;
        }

        .recent-product-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 4px;
            color: #333;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .recent-product-price {
            color: #007bff;
            font-weight: bold;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .btn-view-product {
            background: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background 0.3s;
            width: 100%;
        }

        .btn-view-product:hover {
            background: #218838;
        }

        .recent-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 123, 255, 0.9);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            z-index: 10;
            font-weight: 500;
        }

        .recently-viewed-container::-webkit-scrollbar {
            height: 6px;
        }

        .recently-viewed-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .recently-viewed-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .recently-viewed-container::-webkit-scrollbar-thumb:hover {
            background: #555;
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
    <!-- NEW: Image Preview Modal -->
    <div id="imagePreviewModal" class="image-preview-modal">
        <button class="close-preview" onclick="closePreview()">✕</button>
        <div class="preview-image-container">
            <img id="previewImage" class="preview-image" src="" alt="Preview">
            <div class="preview-nav">
                <button class="preview-nav-btn" onclick="prevImage()">❮</button>
                <button class="preview-nav-btn" onclick="nextImage()">❯</button>
            </div>
        </div>
    </div>

    <div class="container">
        <h1>🛍️ Shop Our Collection</h1>

        <?php if (!empty($recently_viewed_products)): ?>
        <div class="recently-viewed-section" id="recentlyViewedSection">
            <h2 class="section-title">Recently Viewed</h2>
            <div class="recently-viewed-container" id="recentlyViewedContainer">
                <?php foreach($recently_viewed_products as $product): ?>
                <div class="recent-product-card" onclick="viewProduct(<?= $product['id'] ?>)">
                    <?php if($product['image_url']): ?>
                        <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="recent-product-img">
                    <?php else: ?>
                        <div class="recent-product-img placeholder">📱</div>
                    <?php endif; ?>
                    <div class="recent-product-info">
                        <div class="recent-product-name"><?= htmlspecialchars($product['name']) ?></div>
                        <div class="recent-product-price"><?= number_format($product['price'], 2) ?></div>
                        <button class="btn-view-product" onclick="event.stopPropagation(); viewProduct(<?= $product['id'] ?>)">
                            View Product
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

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
            <button class="btn-action" onclick="window.location.href='whishlist.php'">💖 Show Wishlist</button>
            <button class="btn-action" onclick="window.location.href='Cart.php'">View Cart</button>
        </div>

        <div class="results-count" id="resultsCount"></div>

        <div class="items-grid" id="itemsGrid">
            <?php foreach($products as $item): ?>
            <div class="item-card" 
                 data-name="<?= strtolower(htmlspecialchars($item['name'])) ?>" 
                 data-category="<?= htmlspecialchars($item['category_name']) ?>" 
                 data-price="<?= $item['price'] ?>" 
                 data-stock="<?= $item['stock_quantity'] ?>"
                 data-id="<?= $item['id'] ?>"
                 onmouseenter="startHoverTimer(<?= $item['id'] ?>)"
                 onmouseleave="clearHoverTimer(<?= $item['id'] ?>)">
                
                <!-- Recently Viewed Badge -->
                <?php if(in_array($item['id'], $recently_viewed_ids)): ?>
                <div class="recent-badge" id="badge-<?= $item['id'] ?>">
                    🕐 Recently Viewed
                </div>
                <?php endif; ?>

                <input type="checkbox" class="compare-checkbox" data-id="<?= $item['id'] ?>" id="compare-<?= $item['id'] ?>">
                <label for="compare-<?= $item['id'] ?>" class="compare-label">Compare</label>
                
                <button class="wishlist-btn" data-id="<?= $item['id'] ?>">
                    <?= in_array($item['id'], $wishlist) ? "💖" : "🤍" ?>
                </button>

                <!-- NEW: Product Images Container -->
                <div class="product-image-container">
                    <?php 
                    $mainImage = $item['image_url'];
                    $allImages = isset($product_images[$item['id']]) ? $product_images[$item['id']] : [];
                    
                    // If there are additional images, include main image in the array
                    if (!empty($allImages)) {
                        array_unshift($allImages, $mainImage);
                    } else {
                        $allImages = [$mainImage];
                    }
                    ?>
                    
                    <!-- Main Product Image -->
                    <img src="<?= htmlspecialchars($mainImage ?: '') ?>" 
                         alt="<?= htmlspecialchars($item['name']) ?>" 
                         class="main-product-image"
                         onclick="openImagePreview(<?= $item['id'] ?>, 0)"
                      
                    
                    <!-- Thumbnail Images -->
                    <?php if(count($allImages) > 1): ?>
                    <div class="thumbnail-container" id="thumbnails-<?= $item['id'] ?>">
                        <?php foreach($allImages as $index => $image): ?>
                        <img src="<?= htmlspecialchars($image) ?>" 
                             alt="Thumbnail <?= $index + 1 ?>"
                             class="image-thumbnail <?= $index === 0 ? 'active' : '' ?>"
                             onclick="changeMainImage(<?= $item['id'] ?>, <?= $index ?>, this)"
                             onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"60\" height=\"60\" viewBox=\"0 0 60 60\"><rect width=\"60\" height=\"60\" fill=\"%23f0f0f0\"/><text x=\"30\" y=\"30\" font-family=\"Arial\" font-size=\"10\" fill=\"%23666\" text-anchor=\"middle\" dy=\".3em\"></text></svg>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
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
        let hoverTimers = {};
        let recentlyViewedIds = <?= json_encode($recently_viewed_ids) ?>;
        
        // NEW: Variables for image preview
        let currentPreviewImages = [];
        let currentPreviewIndex = 0;
        let currentPreviewProductId = null;

        // NEW: Store all product images data
        let productImagesData = <?= json_encode($product_images) ?>;
        let allProductImages = {};
        
        // Initialize product images data
        document.addEventListener('DOMContentLoaded', function() {
            // Prepare all product images data
            allProducts.forEach(product => {
                let mainImage = product.image_url;
                let additionalImages = productImagesData[product.id] || [];
                let allImages = [mainImage, ...additionalImages].filter(img => img);
                allProductImages[product.id] = allImages;
            });
            
            displayItems();
            
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('wishlist-btn')) {
                    const btn = e.target;
                    const product_id = btn.dataset.id;
                    
                    $.post('', {wishlist_product_id: product_id}, function(response) {
                        if (response === 'added') {
                            btn.textContent = '💖';
                            showToast('Product added to wishlist!');
                        } else if (response === 'removed') {
                            btn.textContent = '🤍';
                            showToast('Product removed from wishlist');
                        } else {
                            showToast('Please login to use wishlist!', true);
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
        });

        // NEW: Change main image when thumbnail is clicked
        function changeMainImage(productId, imageIndex, thumbnailElement) {
            const allImages = allProductImages[productId];
            if (!allImages || !allImages[imageIndex]) return;
            
            // Update main image
            const mainImage = document.querySelector(`[data-id="${productId}"] .main-product-image`);
            if (mainImage) {
                mainImage.src = allImages[imageIndex];
            }
            
            // Update active thumbnail
            const thumbnailsContainer = document.getElementById(`thumbnails-${productId}`);
            if (thumbnailsContainer) {
                const thumbnails = thumbnailsContainer.querySelectorAll('.image-thumbnail');
                thumbnails.forEach(thumb => thumb.classList.remove('active'));
                thumbnailElement.classList.add('active');
            }
        }

        // NEW: Open image preview modal
        function openImagePreview(productId, startIndex = 0) {
            const allImages = allProductImages[productId];
            if (!allImages || allImages.length === 0) return;
            
            currentPreviewImages = allImages;
            currentPreviewIndex = startIndex;
            currentPreviewProductId = productId;
            
            const modal = document.getElementById('imagePreviewModal');
            const previewImage = document.getElementById('previewImage');
            
            previewImage.src = currentPreviewImages[currentPreviewIndex];
            modal.style.display = 'flex';
            
            // Prevent scrolling when modal is open
            document.body.style.overflow = 'hidden';
        }

        // NEW: Close preview modal
        function closePreview() {
            const modal = document.getElementById('imagePreviewModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // NEW: Navigate to previous image
        function prevImage() {
            if (currentPreviewImages.length === 0) return;
            
            currentPreviewIndex = (currentPreviewIndex - 1 + currentPreviewImages.length) % currentPreviewImages.length;
            document.getElementById('previewImage').src = currentPreviewImages[currentPreviewIndex];
            
            // Update active thumbnail in product card
            updateActiveThumbnail();
        }

        // NEW: Navigate to next image
        function nextImage() {
            if (currentPreviewImages.length === 0) return;
            
            currentPreviewIndex = (currentPreviewIndex + 1) % currentPreviewImages.length;
            document.getElementById('previewImage').src = currentPreviewImages[currentPreviewIndex];
            
            // Update active thumbnail in product card
            updateActiveThumbnail();
        }

        // NEW: Update active thumbnail when navigating in preview
        function updateActiveThumbnail() {
            if (!currentPreviewProductId) return;
            
            const thumbnailsContainer = document.getElementById(`thumbnails-${currentPreviewProductId}`);
            if (thumbnailsContainer) {
                const thumbnails = thumbnailsContainer.querySelectorAll('.image-thumbnail');
                thumbnails.forEach((thumb, index) => {
                    if (index === currentPreviewIndex) {
                        thumb.classList.add('active');
                        // Also update main image
                        const mainImage = document.querySelector(`[data-id="${currentPreviewProductId}"] .main-product-image`);
                        if (mainImage) {
                            mainImage.src = currentPreviewImages[currentPreviewIndex];
                        }
                    } else {
                        thumb.classList.remove('active');
                    }
                });
            }
        }

        // Close modal when clicking outside the image
        document.getElementById('imagePreviewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePreview();
            }
        });

        // Keyboard navigation for image preview
        document.addEventListener('keydown', function(e) {
            const modal = document.getElementById('imagePreviewModal');
            if (modal.style.display === 'flex') {
                if (e.key === 'Escape') {
                    closePreview();
                } else if (e.key === 'ArrowLeft') {
                    prevImage();
                } else if (e.key === 'ArrowRight') {
                    nextImage();
                }
            }
        });

        function startHoverTimer(productId) {
            if (hoverTimers[productId]) {
                clearTimeout(hoverTimers[productId]);
            }
            
            hoverTimers[productId] = setTimeout(() => {
                addToRecentlyViewed(productId);
            }, 4000);
        }

        function clearHoverTimer(productId) {
            if (hoverTimers[productId]) {
                clearTimeout(hoverTimers[productId]);
                delete hoverTimers[productId];
            }
        }

        function addToRecentlyViewed(productId) {
            if (recentlyViewedIds.includes(productId.toString())) {
                return;
            }
            
            const product = allProducts.find(p => p.id == productId);
            if (!product) return;
            
            $.post('', {track_recently_viewed: productId}, function(response) {
                if (response === 'tracked') {
                    recentlyViewedIds.push(productId.toString());
                    
                    const badge = document.getElementById(`badge-${productId}`);
                    if (!badge) {
                        const itemCard = document.querySelector(`[data-id="${productId}"]`);
                        if (itemCard) {
                            const badgeDiv = document.createElement('div');
                            badgeDiv.className = 'recent-badge';
                            badgeDiv.id = `badge-${productId}`;
                            badgeDiv.textContent = '🕐 Recently Viewed';
                            badgeDiv.style.display = 'block';
                            itemCard.insertBefore(badgeDiv, itemCard.firstChild);
                        }
                    } else {
                        badge.style.display = 'block';
                    }
                    
                    updateRecentlyViewedSection(product);
                    
                    showToast(`"${product.name}" added to recently viewed`);
                }
            });
        }

        function updateRecentlyViewedSection(product) {
            let recentlyViewedSection = document.getElementById('recentlyViewedSection');
            let recentlyViewedContainer = document.getElementById('recentlyViewedContainer');
            
            const productCard = `
                <div class="recent-product-card" onclick="viewProduct(${product.id})">
                    ${product.image_url ? 
                        `<img src="${product.image_url}" alt="${product.name}" class="recent-product-img">` : 
                        '<div class="recent-product-img placeholder">📱</div>'
                    }
                    <div class="recent-product-info">
                        <div class="recent-product-name">${product.name}</div>
                        <div class="recent-product-price">${parseFloat(product.price).toFixed(2)}</div>
                        <button class="btn-view-product" onclick="event.stopPropagation(); viewProduct(${product.id})">
                            View Product
                        </button>
                    </div>
                </div>
            `;
            
            recentlyViewedContainer.insertAdjacentHTML('afterbegin', productCard);
            
            const cards = recentlyViewedContainer.querySelectorAll('.recent-product-card');
            if (cards.length > 10) {
                cards[cards.length - 1].remove();
            }
           
            if (recentlyViewedSection.style.display === 'none') {
                recentlyViewedSection.style.display = 'block';
            }
        }

        function viewProduct(productId) {
            window.location.href = `product-details.php?id=${productId}`;
        }

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

            html += '<tr><td><strong>Images</strong></td>';
            compareList.forEach(id => {
                const images = allProductImages[id] || [];
                html += `<td>${images.length} image(s)</td>`;
            });
            html += '</tr>';

            html += '</table>';
            html += '<button class="btn-action" onclick="showAllProducts()" style="margin-top: 20px;">← Back to Products</button>';

            document.getElementById('itemsGrid').innerHTML = html;
            document.getElementById('resultsCount').textContent = `Comparing ${compareList.length} products`;
        }

        function showAllProducts() {
            window.location.reload();
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
                } else if (response === 'login_required') {
                    showToast('Please login to submit a review!', true);
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