<?php
	session_start();

	$_SESSION['err'] = 1;
	foreach($_POST as $key => $value){
		if(trim($value) == ''){
			$_SESSION['err'] = 0;
		}
		break;
	}

	if($_SESSION['err'] == 0){
		header("Location: purchase.php");
	} else {
		unset($_SESSION['err']);
	}

	require_once "./functions/database_functions.php";
	// print out header here
	$title = "Purchase Process";
	require "./template/header.php";
	// connect database
	$conn = db_connect();
	extract($_SESSION['ship']);

	// validate post section
	$card_number = $_POST['card_number'];
	$card_PID = $_POST['card_PID'];
	$card_expire = strtotime($_POST['card_expire']);
	$card_owner = $_POST['card_owner'];

	// find customer
	$customerid = getCustomerId($name, $address, $city, $zip_code, $country);
	if($customerid == null) {
		// insert customer into database and return customerid
		$customerid = setCustomerId($name, $address, $city, $zip_code, $country);
	}
	$date = date("Y-m-d H:i:s");
	
	// Check if we have a user_id (from a logged-in user)
	$user_id = null;
	if(isset($_SESSION['ship']['user_id'])) {
		$user_id = $_SESSION['ship']['user_id'];
	} elseif(isset($_SESSION['user']['user_id'])) {
		$user_id = $_SESSION['user']['user_id'];
	}
	
	// Insert order into database
	if($user_id) {
		// Insert order with user_id
		$query = "INSERT INTO orders VALUES 
		('', '$customerid', '$user_id', '$_SESSION[total_price]', '$date', '$name', '$address', '$city', '$zip_code', '$country')";
	} else {
		// Insert order without user_id (guest checkout)
		$query = "INSERT INTO orders VALUES 
		('', '$customerid', NULL, '$_SESSION[total_price]', '$date', '$name', '$address', '$city', '$zip_code', '$country')";
	}
	
	$result = mysqli_query($conn, $query);
	if(!$result) {
		echo "Insert order failed: " . mysqli_error($conn);
		exit;
	}

	// Get the new order ID
	$orderid = mysqli_insert_id($conn);

	foreach($_SESSION['cart'] as $isbn => $qty){
		$bookprice = getbookprice($isbn);
		$query = "INSERT INTO order_items VALUES 
		('$orderid', '$isbn', '$bookprice', '$qty')";
		$result = mysqli_query($conn, $query);
		if(!$result){
			echo "Insert value false!" . mysqli_error($conn);
			exit;
		}
	}

	session_unset();
?>
	<div class="alert alert-success rounded-0 my-4">Your order has been processed sucessfully. We'll be reaching you out to confirm your order. Thanks!</div>

<?php
	if(isset($conn)){
		mysqli_close($conn);
	}
	require_once "./template/footer.php";
?>