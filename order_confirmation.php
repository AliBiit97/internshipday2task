<?php
session_start();
include 'db.php';

if (!isset($_SESSION['order_success'])) {
    header("Location: cart.php");
    exit;
}

$order_number = $_SESSION['order_success'];
$guest_email = $_SESSION['guest_email'] ?? '';

$order_sql = "SELECT o.*, u.username, d.code as promo_code 
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              LEFT JOIN discounts d ON o.promo_code_id = d.id 
              WHERE o.order_number = ?";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("s", $order_number);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
  
    header("Location: cart.php");
    exit;
}


$items_sql = "SELECT * FROM order_items WHERE order_id = ?";
$stmt = $conn->prepare($items_sql);
$stmt->bind_param("i", $order['id']);
$stmt->execute();
$items_result = $stmt->get_result();
$order_items = [];

if ($items_result->num_rows > 0) {
    while ($row = $items_result->fetch_assoc()) {
        $order_items[] = $row;
    }
}

unset($_SESSION['order_success']);
unset($_SESSION['guest_email']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .confirmation-container {
            max-width: 800px;
            width: 100%;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
        }

        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 1s infinite alternate;
        }

        @keyframes bounce {
            from { transform: translateY(0px); }
            to { transform: translateY(-10px); }
        }

        h1 {
            color: #28a745;
            margin-bottom: 15px;
            font-size: 2.5em;
        }

        .order-number {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            font-size: 1.5em;
            font-weight: bold;
            margin: 20px 0;
            border: 2px dashed #28a745;
        }

        .confirmation-message {
            color: #666;
            font-size: 1.2em;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .order-details {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin: 25px 0;
            text-align: left;
        }

        .order-details h3 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin: 12px 0;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .detail-label {
            font-weight: bold;
            color: #555;
        }

        .detail-value {
            color: #333;
        }

        .order-items {
            margin: 20px 0;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: white;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .item-info {
            flex: 1;
            text-align: left;
        }

        .item-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .item-details {
            color: #666;
            font-size: 0.9em;
        }

        .item-total {
            font-weight: bold;
            color: #28a745;
            font-size: 1.1em;
        }

        .price-summary {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 8px 0;
        }

        .price-row.total {
            font-size: 1.3em;
            font-weight: bold;
            border-top: 2px solid #007bff;
            margin-top: 15px;
            padding-top: 15px;
            color: #333;
        }

        .discount {
            color: #28a745;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: #007bff;
            border: 2px solid #007bff;
        }

        .btn-outline:hover {
            background: #007bff;
            color: white;
            transform: translateY(-2px);
        }

        .email-notice {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }

        @media (max-width: 768px) {
            .confirmation-container {
                padding: 25px;
                margin: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
            
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="success-icon">✅</div>
        
        <h1>Order Confirmed!</h1>
        
        <div class="confirmation-message">
            Thank you for your order! We've received your order and will begin processing it right away.
        </div>

        <div class="order-number">
            Order #: <?= htmlspecialchars($order_number) ?>
        </div>

        <div class="email-notice">
            📧 A confirmation email has been sent to <strong><?= htmlspecialchars($order['guest_email']) ?></strong>
        </div>

        <div class="order-details">
            <h3>📦 Order Details</h3>
            
            <div class="detail-row">
                <span class="detail-label">Order Date:</span>
                <span class="detail-value"><?= date('F j, Y g:i A', strtotime($order['created_at'])) ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Customer Name:</span>
                <span class="detail-value"><?= htmlspecialchars($order['guest_name']) ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?= htmlspecialchars($order['guest_email']) ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Phone:</span>
                <span class="detail-value"><?= htmlspecialchars($order['guest_phone']) ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Shipping Address:</span>
                <span class="detail-value">
                    <?= htmlspecialchars($order['shipping_address']) ?>, 
                    <?= htmlspecialchars($order['city']) ?>, 
                    <?= htmlspecialchars($order['state']) ?> 
                    <?= htmlspecialchars($order['zip_code']) ?>, 
                    <?= htmlspecialchars($order['country']) ?>
                </span>
            </div>
            
            <?php if ($order['promo_code']): ?>
            <div class="detail-row">
                <span class="detail-label">Promo Code:</span>
                <span class="detail-value"><?= htmlspecialchars($order['promo_code']) ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($order['notes'])): ?>
            <div class="detail-row">
                <span class="detail-label">Order Notes:</span>
                <span class="detail-value"><?= htmlspecialchars($order['notes']) ?></span>
            </div>
            <?php endif; ?>
        </div>

    
        <?php if (!empty($order_items)): ?>
        <div class="order-details">
            <h3>🛍️ Order Items</h3>
            <div class="order-items">
                <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <div class="item-info">
                            <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                            <div class="item-details">
                                <?= number_format($item['product_price'], 2) ?> x <?= $item['quantity'] ?>
                            </div>
                        </div>
                        <div class="item-total">
                            <?= number_format($item['subtotal'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="price-summary">
            <h3>💰 Order Summary</h3>
            <div class="price-row">
                <span>Subtotal:</span>
                <span><?= number_format($order['subtotal'], 2) ?></span>
            </div>
            
            <?php if ($order['discount_amount'] > 0): ?>
            <div class="price-row discount">
                <span>Discount:</span>
                <span>-<?= number_format($order['discount_amount'], 2) ?></span>
            </div>
            <?php endif; ?>
            
            <div class="price-row">
                <span>Shipping:</span>
                <span><?= number_format($order['shipping_amount'], 2) ?></span>
            </div>
            
            <div class="price-row">
                <span>Tax:</span>
                <span><?= number_format($order['tax_amount'], 2) ?></span>
            </div>
            
            <div class="price-row total">
                <span>Total Amount:</span>
                <span><?= number_format($order['total_amount'], 2) ?></span>
            </div>
        </div>

        <div class="order-details">
            <h3>📋 What's Next?</h3>
            <div style="text-align: left; line-height: 1.8;">
                <p>✅ <strong>Order Confirmed</strong> - We've received your order</p>
                <p>🔄 <strong>Processing</strong> - We're preparing your items</p>
                <p>🚚 <strong>Shipping</strong> - Your order will be shipped within 2-3 business days</p>
                <p>📧 <strong>Updates</strong> - You'll receive email updates on your order status</p>
            </div>
        </div>

        <div class="action-buttons">
            <a href="index.php" class="btn btn-primary">Continue Shopping</a>
            <a href="order_details.php" class="btn btn-outline">View My Orders</a>
            <button onclick="window.print()" class="btn btn-success">Print Receipt</button>
        </div>

        <div style="margin-top: 25px; color: #666; font-size: 0.9em;">
            <p>Need help? Contact our support team at support@yourstore.com</p>
        </div>
    </div>

    <script>
    
        window.onload = function() {
            window.scrollTo(0, 0);
        };

        document.addEventListener('DOMContentLoaded', function() {
            const confetti = document.createElement('div');
            confetti.innerHTML = '🎉';
            confetti.style.position = 'fixed';
            confetti.style.top = '20px';
            confetti.style.right = '20px';
            confetti.style.fontSize = '40px';
            confetti.style.zIndex = '1000';
            document.body.appendChild(confetti);
        });
    </script>
</body>
</html>