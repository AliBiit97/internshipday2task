<?php
session_start();
include 'db.php';

// Optional: Admin check
// if(!$_SESSION['is_admin']) { header('Location: login.php'); exit; }

// Handle status update
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_return'])) {
    $return_id = intval($_POST['return_id']);
    $status = $_POST['status'];

    $update_sql = "UPDATE returns SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $status, $return_id);
    $stmt->execute();

    $msg = "Return/Exchange request #$return_id updated to '$status'.";
}

// Build filter query
$where_clauses = [];
$params = [];
$types = '';

if(!empty($_GET['status'])) {
    $where_clauses[] = "r.status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

if(!empty($_GET['request_type'])) {
    $where_clauses[] = "r.request_type = ?";
    $params[] = $_GET['request_type'];
    $types .= 's';
}

if(!empty($_GET['search'])) {
    $where_clauses[] = "(r.order_number LIKE ? OR u.username LIKE ? OR r.reason LIKE ?)";
    $search_term = '%' . $_GET['search'] . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

if(!empty($_GET['date_from'])) {
    $where_clauses[] = "DATE(r.created_at) >= ?";
    $params[] = $_GET['date_from'];
    $types .= 's';
}

if(!empty($_GET['date_to'])) {
    $where_clauses[] = "DATE(r.created_at) <= ?";
    $params[] = $_GET['date_to'];
    $types .= 's';
}

// Base query
$sql = "SELECT r.*, u.username 
        FROM returns r
        LEFT JOIN users u ON r.user_id = u.id";

if(!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY r.created_at DESC";

// Execute with filters
if(!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Get counts for badges
$count_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM returns";
$counts = $conn->query($count_sql)->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Returns/Exchanges</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 40px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px 40px;
            background: #f8f9fa;
            border-bottom: 1px solid #e1e8ed;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #6c757d;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
        }

        .filters {
            padding: 30px 40px;
            background: white;
            border-bottom: 2px solid #e1e8ed;
        }

        .filters h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
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
            font-weight: 600;
            color: #495057;
            margin-bottom: 6px;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            font-family: inherit;
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
            margin-top: 15px;
        }

        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .msg {
            background: #d4edda;
            color: #155724;
            padding: 15px 40px;
            border-left: 4px solid #28a745;
            font-weight: 500;
        }

        .table-container {
            padding: 0 40px 40px 40px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 16px;
            text-align: left;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            color: #495057;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e1e8ed;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #e1e8ed;
            font-size: 14px;
            color: #2c3e50;
        }

        tbody tr {
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-accepted {
            background: #d4edda;
            color: #155724;
        }

        .badge-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-return {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-exchange {
            background: #e2e3e5;
            color: #383d41;
        }

        .action-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .action-form select {
            padding: 6px 10px;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
            font-size: 13px;
            transition: all 0.2s;
        }

        .action-form select:focus {
            outline: none;
            border-color: #667eea;
        }

        .action-form button {
            padding: 6px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .action-form button:hover {
            background: #5568d3;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .no-results svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .header, .filters, .table-container {
                padding: 20px;
            }

            .stats-grid {
                padding: 20px;
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Returns & Exchanges Management</h1>
            <p>Manage and process customer return and exchange requests</p>
        </div>

        <?php if(isset($msg)): ?>
            <div class="msg"><?= $msg ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Requests</div>
                <div class="stat-value"><?= $counts['total'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending</div>
                <div class="stat-value" style="color: #ffc107;"><?= $counts['pending'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Accepted</div>
                <div class="stat-value" style="color: #28a745;"><?= $counts['accepted'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Rejected</div>
                <div class="stat-value" style="color: #dc3545;"><?= $counts['rejected'] ?></div>
            </div>
        </div>

        <div class="filters">
            <h3>🔍 Filter Requests</h3>
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?= $_GET['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="accepted" <?= $_GET['status'] == 'accepted' ? 'selected' : '' ?>>Accepted</option>
                            <option value="rejected" <?= $_GET['status'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="request_type">Request Type</label>
                        <select name="request_type" id="request_type">
                            <option value="">All Types</option>
                            <option value="return" <?= $_GET['request_type'] == 'return' ? 'selected' : '' ?>>Return</option>
                            <option value="exchange" <?= $_GET['request_type'] == 'exchange' ? 'selected' : '' ?>>Exchange</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="date_from">Date From</label>
                        <input type="date" name="date_from" id="date_from" value="<?= $_GET['date_from'] ?? '' ?>">
                    </div>

                    <div class="filter-group">
                        <label for="date_to">Date To</label>
                        <input type="date" name="date_to" id="date_to" value="<?= $_GET['date_to'] ?? '' ?>">
                    </div>

                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" placeholder="Order #, User, Reason..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="?" class="btn btn-secondary">Clear All</a>
                </div>
            </form>
        </div>

        <div class="table-container">
            <?php if($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Order Number</th>
                            <th>Customer</th>
                            <th>Reason</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong>#<?= $row['id'] ?></strong></td>
                            <td><?= htmlspecialchars($row['order_number']) ?></td>
                            <td><?= htmlspecialchars($row['username'] ?? 'Guest') ?></td>
                            <td><?= htmlspecialchars(substr($row['reason'], 0, 50)) . (strlen($row['reason']) > 50 ? '...' : '') ?></td>
                            <td>
                                <span class="badge badge-<?= $row['request_type'] ?>">
                                    <?= ucfirst($row['request_type']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $row['status'] ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="return_id" value="<?= $row['id'] ?>">
                                    <select name="status">
                                        <?php
                                        $statuses = ['pending','accepted','rejected'];
                                        foreach($statuses as $st) {
                                            $sel = $row['status']==$st ? 'selected' : '';
                                            echo "<option value='$st' $sel>" . ucfirst($st) . "</option>";
                                        }
                                        ?>
                                    </select>
                                    <button type="submit" name="update_return">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3>No requests found</h3>
                    <p>Try adjusting your filters or search terms</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>