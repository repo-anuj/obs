<?php
	// Start output buffering
	ob_start();
	
	session_start();
	require_once "./functions/database_functions.php";
	require_once "./functions/user_functions.php";
	
	// Check if user is logged in
	if (!isset($_SESSION['user'])) {
		$_SESSION['redirect_after_login'] = 'checkout.php';
		header("Location: login.php");
		exit;
	}
	
	// print out header here
	$title = "Checking out";
	require "./template/header.php";

	if(isset($_POST['submit_order'])) {
		$conn = db_connect();
		
		// Get delivery details
		$address = trim($_POST['address']);
		$city = trim($_POST['city']);
		$zip_code = trim($_POST['zip_code']);
		$country = trim($_POST['country']);
		$phone = trim($_POST['phone']);
		
		// Create order
		$user_id = $_SESSION['user']['user_id'];
		$amount = $_SESSION['total_price'];
		
		// Insert order
		$query = "INSERT INTO orders (user_id, amount, ship_name, ship_address, ship_city, ship_zip_code, ship_country, ship_phone) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt = $conn->prepare($query);
		$stmt->bind_param("idssssss", 
			$user_id,
			$amount,
			$_SESSION['user']['name'],
			$address,
			$city,
			$zip_code,
			$country,
			$phone
		);
		
		if($stmt->execute()) {
			$order_id = $stmt->insert_id;
			
			// Insert order items
			$query = "INSERT INTO order_items (orderid, book_isbn, item_price, quantity) VALUES (?, ?, ?, ?)";
			$stmt = $conn->prepare($query);
			
			foreach($_SESSION['cart'] as $isbn => $qty){
				$book = mysqli_fetch_assoc(getBookByIsbn($conn, $isbn));
				$price = $book['book_price'];
				
				$stmt->bind_param("isdi", $order_id, $isbn, $price, $qty);
				$stmt->execute();
			}
			
			// Clear the cart
			unset($_SESSION['cart']);
			unset($_SESSION['total_items']);
			unset($_SESSION['total_price']);
			
			// Show success message and redirect
			$_SESSION['success_message'] = "Your order has been placed successfully!";
			header("Location: profile.php#orders");
			exit;
		} else {
			echo '<div class="alert alert-danger">Error placing order. Please try again.</div>';
		}
	}
	?>
	<h4 class="fw-bolder text-center">Checkout</h4>
	<center>
		<hr class="bg-warning" style="width:5em;height:3px;opacity:1">
	</center>
	<?php
	if(isset($_SESSION['cart']) && (array_count_values($_SESSION['cart']))){
	?>
	<div class="card rounded-0 shadow mb-3">
		<div class="card-body">
			<div class="container-fluid">
				<table class="table">
					<tr>
						<th>Item</th>
						<th>Price</th>
						<th>Quantity</th>
						<th>Total</th>
					</tr>
					<?php
						foreach($_SESSION['cart'] as $isbn => $qty){
							$conn = db_connect();
							$book = mysqli_fetch_assoc(getBookByIsbn($conn, $isbn));
					?>
					<tr>
						<td><?php echo $book['book_title'] . " by " . $book['book_author']; ?></td>
						<td><?php echo "$" . $book['book_price']; ?></td>
						<td><?php echo $qty; ?></td>
						<td><?php echo "$" . $qty * $book['book_price']; ?></td>
					</tr>
					<?php } ?>
					<tr>
						<th>&nbsp;</th>
						<th>&nbsp;</th>
						<th><?php echo $_SESSION['total_items']; ?></th>
						<th><?php echo "$" . $_SESSION['total_price']; ?></th>
					</tr>
				</table>
			</div>
		</div>
	</div>

	<div class="card rounded-0 shadow">
		<div class="card-header">
			<h5 class="card-title">Delivery Details</h5>
		</div>
		<div class="card-body">
			<form method="post" action="checkout.php">
				<div class="mb-3">
					<label for="address" class="form-label">Delivery Address</label>
					<input type="text" class="form-control" id="address" name="address" required>
				</div>
				<div class="row mb-3">
					<div class="col-md-6">
						<label for="city" class="form-label">City</label>
						<input type="text" class="form-control" id="city" name="city" required>
					</div>
					<div class="col-md-6">
						<label for="zip_code" class="form-label">ZIP Code</label>
						<input type="text" class="form-control" id="zip_code" name="zip_code" required>
					</div>
				</div>
				<div class="row mb-3">
					<div class="col-md-6">
						<label for="country" class="form-label">Country</label>
						<input type="text" class="form-control" id="country" name="country" required>
					</div>
					<div class="col-md-6">
						<label for="phone" class="form-label">Phone Number</label>
						<input type="tel" class="form-control" id="phone" name="phone" required>
					</div>
				</div>
				<div class="d-grid">
					<button type="submit" name="submit_order" class="btn btn-primary">Place Order</button>
				</div>
			</form>
		</div>
	</div>
	<?php
	} else {
		echo '<div class="alert alert-info">Your cart is empty! <a href="books.php">Continue shopping</a></div>';
	}
	?>

<?php
	require "./template/footer.php";
?>