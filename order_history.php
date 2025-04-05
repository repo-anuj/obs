<?php
	// Start output buffering
	ob_start();
	
	session_start();
	require_once "./functions/database_functions.php";
	require_once "./functions/user_functions.php";
	
	// Check if user is logged in
	if (!isset($_SESSION['user'])) {
		header("Location: login.php");
		exit;
	}
	
	$title = "Order History";
	require "./template/header.php";
	
	$conn = db_connect();
	$user_id = $_SESSION['user']['user_id'];
	
	// Get user's orders
	$query = "SELECT o.*, 
			COUNT(oi.order_item_id) as total_items,
			GROUP_CONCAT(DISTINCT CONCAT(b.book_title, ':', oi.quantity) SEPARATOR '|') as books
			FROM orders o
			JOIN order_items oi ON o.order_id = oi.order_id
			JOIN books b ON oi.book_isbn = b.book_isbn
			WHERE o.user_id = ?
			GROUP BY o.order_id
			ORDER BY o.order_date DESC";
			
	$stmt = $conn->prepare($query);
	$stmt->bind_param("i", $user_id);
	$stmt->execute();
	$result = $stmt->get_result();
?>

<h4 class="fw-bolder text-center">Order History</h4>
<center>
	<hr class="bg-warning" style="width:5em;height:3px;opacity:1">
</center>

<?php if($result->num_rows > 0): ?>
	<div class="row">
		<?php while($order = $result->fetch_assoc()): ?>
			<div class="col-12 mb-3">
				<div class="card">
					<div class="card-header">
						<div class="d-flex justify-content-between align-items-center">
							<span>Order #<?php echo $order['order_id']; ?></span>
							<span class="badge bg-<?php echo ($order['order_status'] == 'Processing') ? 'warning' : 'success'; ?>">
								<?php echo $order['order_status']; ?>
							</span>
						</div>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-6">
								<h6>Order Details</h6>
								<p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($order['order_date'])); ?></p>
								<p><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
								<p><strong>Items:</strong> <?php echo $order['total_items']; ?></p>
							</div>
							<div class="col-md-6">
								<h6>Shipping Details</h6>
								<p><?php echo $order['shipping_address']; ?></p>
								<p><?php echo $order['shipping_city'] . ', ' . $order['shipping_zip_code']; ?></p>
								<p><?php echo $order['shipping_country']; ?></p>
								<p>Phone: <?php echo $order['phone']; ?></p>
							</div>
						</div>
						
						<h6 class="mt-3">Books Ordered</h6>
						<div class="list-group">
							<?php
							$books = explode('|', $order['books']);
							foreach($books as $book) {
								list($title, $qty) = explode(':', $book);
								echo "<div class='list-group-item d-flex justify-content-between align-items-center'>
										$title
										<span class='badge bg-primary rounded-pill'>Qty: $qty</span>
									</div>";
							}
							?>
						</div>
						
						<?php if($order['order_status'] == 'Delivered'): ?>
							<div class="mt-3">
								<a href="book.php?bookisbn=<?php echo $book_isbn; ?>#reviews" class="btn btn-outline-primary btn-sm">
									Write a Review
								</a>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endwhile; ?>
	</div>
<?php else: ?>
	<div class="alert alert-info">
		You haven't placed any orders yet. <a href="books.php">Start shopping</a>
	</div>
<?php endif; ?>

<?php
	if(isset($conn)){ mysqli_close($conn); }
	require_once "./template/footer.php";
?>
