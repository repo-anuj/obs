<?php
session_start();

// Check if admin is logged in
if(!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    header("Location: admin_login.php");
    exit;
}

require_once "./functions/database_functions.php";
require_once "./template/header.php";

$conn = db_connect();

// Handle status update
if(isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    $query = "UPDATE orders SET status = ? WHERE orderid = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
}

// Fetch all orders with customer details
$query = "SELECT o.*, u.name as customer_name, 
          GROUP_CONCAT(CONCAT(b.book_title, ' (', oi.quantity, ')') SEPARATOR ', ') as order_items,
          SUM(oi.quantity * oi.item_price) as total_amount
          FROM orders o
          JOIN users u ON o.user_id = u.user_id
          JOIN order_items oi ON o.orderid = oi.orderid
          JOIN books b ON oi.book_isbn = b.book_isbn
          GROUP BY o.orderid
          ORDER BY o.date DESC";

$result = $conn->query($query);
?>

<div class="container mt-4">
    <h2 class="mb-4">Order Management</h2>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['orderid']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($order['ship_address']); ?>,
                                    <?php echo htmlspecialchars($order['ship_city']); ?>,
                                    <?php echo htmlspecialchars($order['ship_zip_code']); ?>,
                                    <?php echo htmlspecialchars($order['ship_country']); ?>
                                </small>
                            </td>
                            <td><?php echo htmlspecialchars($order['order_items']); ?></td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo date('M j, Y', strtotime($order['date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $order['status'] == 'Processing' ? 'warning' : 
                                        ($order['status'] == 'Shipped' ? 'info' : 
                                        ($order['status'] == 'Delivered' ? 'success' : 'secondary')); 
                                ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="order_id" value="<?php echo $order['orderid']; ?>">
                                    <select name="new_status" class="form-select form-select-sm d-inline-block w-auto me-2">
                                        <option value="Processing" <?php echo $order['status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="Shipped" <?php echo $order['status'] == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="Delivered" <?php echo $order['status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="Cancelled" <?php echo $order['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
if(isset($conn)) { $conn->close(); }
require_once "./template/footer.php";
?>
