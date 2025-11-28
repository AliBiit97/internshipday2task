<?php
session_start();
include 'db.php';

// Assume logged-in user
$user_id = $_SESSION['user_id'] ?? null;

if(!$user_id){
    die("Please login to view your orders.");
}

// Fetch all orders of the user
$orders_sql = "SELECT * FROM orders WHERE user_id='$user_id' ORDER BY created_at DESC";
$orders_res = $conn->query($orders_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Orders</title>
</head>
<body>
<h1>My Orders</h1>

<?php if($orders_res->num_rows > 0): ?>
<table border="1" cellpadding="10">
    <tr>
        <th>Order Number</th>
        <th>Order Date</th>
        <th>Total Amount</th>
        <th>Status</th>
        <th>Payment Status</th>
        <th>Actions</th>
    </tr>

    <?php while($order = $orders_res->fetch_assoc()): ?>
    <tr>
        <td><?= $order['order_number'] ?></td>
        <td><?= $order['created_at'] ?></td>
        <td>$<?= $order['total_amount'] ?></td>
        <td><?= ucfirst($order['status']) ?></td>
        <td><?= ucfirst($order['payment_status']) ?></td>
        <td>
            <!-- View Order Details -->
            <a href="order_details.php?order_number=<?= $order['order_number'] ?>">View</a> | 
            
            <!-- Return/Exchange Button: Only if delivered and paid -->
            <?php if($order['status'] == 'delivered' && $order['payment_status'] == 'paid'): ?>
                <a href="return_exchange.php?order_number=<?= $order['order_number'] ?>">Return / Exchange</a>
            <?php else: ?>
                <span style="color: gray;">Return / Exchange</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
    <p>You have no orders yet.</p>
<?php endif; ?>

</body>
</html>
