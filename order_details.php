<?php
session_start();
include 'db.php';

// Assume logged-in user
$user_id = $_SESSION['user_id'] ?? null;

if(!$user_id){
    die("Please login to view your orders.");
}

// Handle Return/Exchange Request Submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_request'])){
    $order_number = $_POST['order_number'];
    $reason = $_POST['reason'];
    $request_type = $_POST['request_type'];
    
    // Verify order belongs to user and is delivered
    $verify_sql = "SELECT * FROM orders WHERE order_number='$order_number' AND user_id='$user_id' AND status='delivered' AND payment_status='paid'";
    $verify_res = $conn->query($verify_sql);
    
    if($verify_res->num_rows > 0){
        // Check if request already exists
        $check_sql = "SELECT * FROM returns WHERE order_number='$order_number' AND user_id='$user_id'";
        $check_res = $conn->query($check_sql);
        
        if($check_res->num_rows > 0){
            $message = "You have already submitted a request for this order.";
            $message_type = "error";
        } else {
            $insert_sql = "INSERT INTO returns (order_number, user_id, reason, request_type) VALUES ('$order_number', '$user_id', '$reason', '$request_type')";
            if($conn->query($insert_sql)){
                $message = "Your " . ucfirst($request_type) . " request has been submitted successfully!";
                $message_type = "success";
            } else {
                $message = "Error submitting request. Please try again.";
                $message_type = "error";
            }
        }
    } else {
        $message = "Invalid order for return/exchange request.";
        $message_type = "error";
    }
}

// Fetch all orders of the user with return status
$orders_sql = "SELECT o.*, r.status as return_status, r.request_type 
               FROM orders o 
               LEFT JOIN returns r ON o.order_number = r.order_number AND r.user_id = o.user_id
               WHERE o.user_id='$user_id' 
               ORDER BY o.created_at DESC";
$orders_res = $conn->query($orders_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Orders</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            font-size: 32px;
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
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
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .orders-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .orders-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        
        .orders-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .orders-table tbody tr {
            transition: background 0.3s ease;
        }
        
        .orders-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-processing {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-paid {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 2px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.4);
        }
        
        .btn-disabled {
            background: #6c757d;
            color: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .no-orders {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-orders img {
            width: 200px;
            opacity: 0.5;
            margin-bottom: 20px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 22px;
        }
        
        .close {
            color: white;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .close:hover {
            transform: scale(1.2);
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            font-family: inherit;
        }
        
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #eee;
            text-align: right;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            margin-right: 10px;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
        }
        
        .return-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 5px;
        }
        
        .return-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .return-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .return-rejected {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>📦 My Orders</h1>

    <?php if(isset($message)): ?>
        <div class="message <?= $message_type ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <?php if($orders_res->num_rows > 0): ?>
    <table class="orders-table">
        <thead>
            <tr>
                <th>Order Number</th>
                <th>Order Date</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($order = $orders_res->fetch_assoc()): ?>
            <tr>
                <td><strong><?= $order['order_number'] ?></strong></td>
                <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                <td><strong><?= number_format($order['total_amount'], 2) ?></strong></td>
                <td>
                    <span class="status-badge status-<?= $order['status'] ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </td>
                <td>
                    <span class="status-badge status-<?= $order['payment_status'] ?>">
                        <?= ucfirst($order['payment_status']) ?>
                    </span>
                </td>
                <td>
                    <a href="order_details.php?order_number=<?= $order['order_number'] ?>" class="btn btn-primary">View Details</a>
                    
                    <?php if($order['status'] == 'delivered' && $order['payment_status'] == 'paid'): ?>
                        <?php if($order['return_status']): ?>
                            <span class="return-status return-<?= $order['return_status'] ?>">
                                <?= ucfirst($order['request_type']) ?> <?= ucfirst($order['return_status']) ?>
                            </span>
                        <?php else: ?>
                            <button class="btn btn-success" onclick="openModal('<?= $order['order_number'] ?>')">
                                Return/Exchange
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn btn-disabled" disabled>Return/Exchange</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="no-orders">
            <h2>No Orders Yet</h2>
            <p>You haven't placed any orders yet. Start shopping now!</p>
        </div>
    <?php endif; ?>
</div>

<!-- Return/Exchange Request Modal -->
<div id="requestModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Submit Return/Exchange Request</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="order_number" id="modal_order_number">
                
                <div class="form-group">
                    <label for="request_type">Request Type *</label>
                    <select name="request_type" id="request_type" required>
                        <option value="">-- Select Type --</option>
                        <option value="return">Return (Refund)</option>
                        <option value="exchange">Exchange (Replace Item)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="reason">Reason for Request *</label>
                    <textarea name="reason" id="reason" required placeholder="Please describe the reason for your return/exchange request..."></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" name="submit_request" class="btn btn-success">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(orderNumber) {
    document.getElementById('modal_order_number').value = orderNumber;
    document.getElementById('requestModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('requestModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('request_type').value = '';
    document.getElementById('reason').value = '';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('requestModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

</body>
</html>