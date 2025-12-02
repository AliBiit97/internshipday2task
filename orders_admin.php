<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    $payment_status = $_POST['payment_status'];

    $update_sql = "UPDATE orders SET status = ?, payment_status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssi", $status, $payment_status, $order_id);
    if ($stmt->execute()) {
        $msg = "Order #$order_id updated successfully.";
        $msg_type = "success";
    } else {
        $msg = "Error updating order: " . $conn->error;
        $msg_type = "error";
    }
}

$where_conditions = [];
$params = [];
$types = "";


if (!empty($_GET['filter_status'])) {
    $where_conditions[] = "status = ?";
    $params[] = $_GET['filter_status'];
    $types .= "s";
}

if (!empty($_GET['filter_payment'])) {
    $where_conditions[] = "payment_status = ?";
    $params[] = $_GET['filter_payment'];
    $types .= "s";
}

if (!empty($_GET['date_from'])) {
    $where_conditions[] = "DATE(created_at) >= ?";
    $params[] = $_GET['date_from'];
    $types .= "s";
}

if (!empty($_GET['date_to'])) {
    $where_conditions[] = "DATE(created_at) <= ?";
    $params[] = $_GET['date_to'];
    $types .= "s";
}


if (!empty($_GET['search'])) {
    $where_conditions[] = "(order_number LIKE ? OR guest_email LIKE ? OR guest_phone LIKE ? OR guest_name LIKE ?)";
    $search_term = "%{$_GET['search']}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

$orders_sql = "SELECT * FROM orders";
if (!empty($where_conditions)) {
    $orders_sql .= " WHERE " . implode(" AND ", $where_conditions);
}
$orders_sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($orders_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders_res = $stmt->get_result();


$stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_revenue
FROM orders";
$stats_res = $conn->query($stats_sql);
$stats = $stats_res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        h1 {
            color: #1a202c;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #718096;
            font-size: 14px;
        }

    
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-label {
            color: #718096;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .stat-value {
            color: #1a202c;
            font-size: 24px;
            font-weight: 700;
        }

        .stat-card.revenue .stat-value {
            color: #48bb78;
        }

        .filters {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filters h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #1a202c;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 13px;
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 5px;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: slideIn 0.3s ease;
        }

        .message.success {
            background: #c6f6d5;
            color: #22543d;
            border-left: 4px solid #48bb78;
        }

        .message.error {
            background: #fed7d7;
            color: #742a2a;
            border-left: 4px solid #f56565;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e2e8f0;
        }

        .table-header h2 {
            font-size: 18px;
            color: #1a202c;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f7fafc;
        }

        th {
            padding: 15px 20px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
            color: #2d3748;
        }

        tbody tr {
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #f7fafc;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            min-width: 80px;
            text-align: center;
        }

        .badge.pending { background: #fef5e7; color: #d68910; }
        .badge.confirmed { background: #e3f2fd; color: #1976d2; }
        .badge.shipped { background: #f3e5f5; color: #7b1fa2; }
        .badge.delivered { background: #e8f5e9; color: #388e3c; }
        .badge.cancelled { background: #ffebee; color: #c62828; }
        .badge.paid { background: #e8f5e9; color: #388e3c; }
        .badge.pending-payment { background: #fef5e7; color: #d68910; }
        .badge.failed { background: #ffebee; color: #c62828; }

        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 25px 30px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 20px;
            color: #1a202c;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            color: #718096;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .close-modal:hover {
            background: #f7fafc;
            color: #4a5568;
        }

        .modal-body {
            padding: 30px;
        }

        .order-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .detail-section {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
        }

        .detail-section h4 {
            font-size: 14px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }

        .detail-item {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            padding-bottom: 8px;
            border-bottom: 1px dashed #e2e8f0;
        }

        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .detail-label {
            color: #4a5568;
            font-weight: 500;
            font-size: 14px;
        }

        .detail-value {
            color: #2d3748;
            font-weight: 600;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .products-table th {
            background: #edf2f7;
            padding: 12px 15px;
            font-size: 12px;
        }

        .products-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .update-form {
            background: #f7fafc;
            padding: 25px;
            border-radius: 10px;
            margin-top: 30px;
        }

        .update-form h4 {
            margin-bottom: 20px;
            color: #4a5568;
            font-size: 16px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .form-group select {
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }

        .btn-view {
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-view:hover {
            background: #5a67d8;
            transform: translateY(-1px);
        }

        .amount {
            font-weight: 600;
            color: #48bb78;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #718096;
        }

        @media (max-width: 768px) {
            .filter-grid,
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .order-details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="header">
            <h1>📦 Orders Management</h1>
            <p class="subtitle">Manage and track all customer orders</p>
        </div>

     
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending</div>
                <div class="stat-value"><?= number_format($stats['pending']) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Confirmed</div>
                <div class="stat-value"><?= number_format($stats['confirmed']) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Shipped</div>
                <div class="stat-value"><?= number_format($stats['shipped']) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Delivered</div>
                <div class="stat-value"><?= number_format($stats['delivered']) ?></div>
            </div>
            <div class="stat-card revenue">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value"><?= number_format($stats['total_revenue'], 2) ?></div>
            </div>
        </div>

        <?php if(isset($msg)): ?>
            <div class="message <?= $msg_type ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>


        <div class="filters">
            <h2>🔍 Filter Orders</h2>
            <form method="GET">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" placeholder="Order #, Email, Phone, Name" 
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    <div class="filter-group">
                        <label for="filter_status">Order Status</label>
                        <select id="filter_status" name="filter_status">
                            <option value="">All Statuses</option>
                            <?php
                            $statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
                            foreach($statuses as $st) {
                                $sel = ($_GET['filter_status'] ?? '') === $st ? 'selected' : '';
                                echo "<option value='$st' $sel>" . ucfirst($st) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter_payment">Payment Status</label>
                        <select id="filter_payment" name="filter_payment">
                            <option value="">All Payments</option>
                            <option value="paid" <?= ($_GET['filter_payment'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="pending" <?= ($_GET['filter_payment'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="failed" <?= ($_GET['filter_payment'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="date_from">Date From</label>
                        <input type="date" id="date_from" name="date_from" 
                               value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                    </div>
                    <div class="filter-group">
                        <label for="date_to">Date To</label>
                        <input type="date" id="date_to" name="date_to" 
                               value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="?" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">Clear Filters</a>
                </div>
            </form>
        </div>


        <div class="table-container">
            <div class="table-header">
                <h2>Orders List</h2>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer Name</th>
                            <th>Order Status</th>
                            <th>Payment Status</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($orders_res->num_rows > 0): ?>
                            <?php while($order = $orders_res->fetch_assoc()): 
                                // Get order items for this order
                                $items_sql = "SELECT oi.*, p.name as product_name, p.image_url 
                                            FROM order_items oi 
                                            LEFT JOIN products p ON oi.product_id = p.id 
                                            WHERE oi.order_id = ?";
                                $items_stmt = $conn->prepare($items_sql);
                                $items_stmt->bind_param("i", $order['id']);
                                $items_stmt->execute();
                                $items_res = $items_stmt->get_result();
                                $order_items = $items_res->fetch_all(MYSQLI_ASSOC);
                            ?>
                            <tr>
                                <td>
                                    <span style="font-weight: 600; color: #667eea;">#<?= htmlspecialchars($order['order_number']) ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($order['guest_name']) ?></strong>
                                    <?php if($order['user_id']): ?>
                                        <br><small style="color: #718096;">User ID: <?= $order['user_id'] ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $order['status'] ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $order['payment_status'] ?>">
                                        <?= ucfirst($order['payment_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="amount"><?= number_format($order['total_amount'], 2) ?></span>
                                </td>
                                <td>
                                    <?= date('M d, Y', strtotime($order['created_at'])) ?>
                                </td>
                                <td>
                                    <button class="btn-view" onclick="viewOrderDetails(<?= htmlspecialchars(json_encode($order)) ?>, <?= htmlspecialchars(json_encode($order_items)) ?>)">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-results">
                                    <strong>No orders found</strong><br>
                                    <span style="font-size: 13px;">Try adjusting your filters</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Order Details -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Order Details</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Content will be loaded here via JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Function to view order details
        function viewOrderDetails(order, items) {
            // Format date
            const orderDate = new Date(order.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            // Build modal content
            let html = `
                <div class="order-details-grid">
                    <div class="detail-section">
                        <h4>Order Information</h4>
                        <div class="detail-item">
                            <span class="detail-label">Order Number:</span>
                            <span class="detail-value">#${order.order_number}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Order Date:</span>
                            <span class="detail-value">${orderDate}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Order Status:</span>
                            <span class="detail-value"><span class="badge ${order.status}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Payment Status:</span>
                            <span class="detail-value"><span class="badge ${order.payment_status}">${order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1)}</span></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Amount:</span>
                            <span class="detail-value" style="color: #48bb78; font-weight: 700;">${parseFloat(order.total_amount).toFixed(2)}</span>
                        </div>
                    </div>

                    <div class="detail-section">
                        <h4>Customer Information</h4>
                        <div class="detail-item">
                            <span class="detail-label">Name:</span>
                            <span class="detail-value">${order.guest_name}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value">${order.guest_email}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value">${order.guest_phone}</span>
                        </div>
                        ${order.user_id ? `
                        <div class="detail-item">
                            <span class="detail-label">User ID:</span>
                            <span class="detail-value">${order.user_id}</span>
                        </div>
                        ` : ''}
                    </div>

                    <div class="detail-section">
                        <h4>Shipping Information</h4>
                        <div class="detail-item">
                            <span class="detail-label">Address:</span>
                            <span class="detail-value">${order.shipping_address || 'Not specified'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">City:</span>
                            <span class="detail-value">${order.shipping_city || 'Not specified'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Notes:</span>
                            <span class="detail-value">${order.notes || 'No notes'}</span>
                        </div>
                    </div>
                </div>

                <h4 style="margin: 25px 0 15px 0; color: #4a5568;">Order Items</h4>
                ${items.length > 0 ? `
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${items.map(item => `
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        ${item.image_url ? `<img src="${item.image_url}" alt="${item.product_name}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: cover;">` : ''}
                                        <div>
                                            <div style="font-weight: 500;">${item.product_name || 'Product #' + item.product_id}</div>
                                            ${item.variation ? `<div style="font-size: 12px; color: #718096;">${item.variation}</div>` : ''}
                                        </div>
                                    </div>
                                </td>
                                <td>${item.quantity}</td>
                                <td>${parseFloat(item.product_price).toFixed(2)}</td>
                                <td>${parseFloat(item.subtotal).toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                ` : '<p style="color: #718096;">No items found</p>'}

                <form method="POST" class="update-form" onsubmit="return confirm('Are you sure you want to update this order?')">
                    <h4>Update Order</h4>
                    <input type="hidden" name="order_id" value="${order.id}">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Order Status</label>
                            <select name="status" required>
                                ${['pending','confirmed','shipped','delivered','cancelled'].map(status => 
                                    `<option value="${status}" ${order.status === status ? 'selected' : ''}>${status.charAt(0).toUpperCase() + status.slice(1)}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Payment Status</label>
                            <select name="payment_status" required>
                                ${['pending','paid','failed'].map(payment => 
                                    `<option value="${payment}" ${order.payment_status === payment ? 'selected' : ''}>${payment.charAt(0).toUpperCase() + payment.slice(1)}</option>`
                                ).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_order" class="btn btn-primary btn-sm">Update Order</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal()">Cancel</button>
                    </div>
                </form>
            `;

            // Update modal title and content
            document.getElementById('modalTitle').textContent = `Order #${order.order_number}`;
            document.getElementById('modalContent').innerHTML = html;
            
            // Show modal
            document.getElementById('orderModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Function to close modal
        function closeModal() {
            document.getElementById('orderModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        document.getElementById('orderModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>