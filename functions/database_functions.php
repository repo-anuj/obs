<?php
	function db_connect(){
		// Try different connection methods - changing the order to try with no password first
		// Method 1: No password (most common for fresh XAMPP installations)
		$conn = mysqli_connect("localhost", "root", "", "obs_db");
		
		// If Method 1 fails, try Method 2: With password 
		if(!$conn){
			// Try with a password
			$conn = mysqli_connect("localhost", "root", "password", "obs_db");
		}
		
		// If Method 2 fails, try Method 3: Root with no password but with different port
		if(!$conn){
			// Some XAMPP installations use a different port
			$conn = mysqli_connect("localhost:3307", "root", "", "obs_db");
		}
		
		// If all methods fail, display error and exit with helpful message
		if(!$conn){
			echo "<div class='alert alert-danger'>Database Connection Error: " . mysqli_connect_error() . "</div>";
			echo "<div class='alert alert-info'>Please check your XAMPP MySQL settings or update the connection details in database_functions.php</div>";
			echo "<div class='alert alert-info mt-2'>Possible solutions:<br>
			1. Make sure MySQL is running in XAMPP control panel<br>
			2. Create the 'obs_db' database in phpMyAdmin<br>
			3. Check if your MySQL root password is correct</div>";
			exit;
		}
		
		return $conn;
	}

	function select4LatestBook($conn){
		$row = array();
		$query = "SELECT book_isbn, book_image, book_title FROM books ORDER BY abs(unix_timestamp(created_at)) DESC";
		$result = mysqli_query($conn, $query);
		if(!$result){
		    echo "Can't retrieve data " . mysqli_error($conn);
		    exit;
		}
		for($i = 0; $i < 4; $i++){
			array_push($row, mysqli_fetch_assoc($result));
		}
		return $row;
	}

	function getBookByIsbn($conn, $isbn){
		$query = "SELECT book_title, book_author, book_price FROM books WHERE book_isbn = '$isbn'";
		$result = mysqli_query($conn, $query);
		if(!$result){
			echo "Can't retrieve data " . mysqli_error($conn);
			exit;
		}
		return $result;
	}

	function getOrderId($conn, $customerid){
		$query = "SELECT orderid FROM orders WHERE customerid = '$customerid'";
		$result = mysqli_query($conn, $query);
		if(!$result){
			echo "retrieve data failed!" . mysqli_error($conn);
			exit;
		}
		$row = mysqli_fetch_assoc($result);
		return $row['orderid'];
	}

	function insertIntoOrder($conn, $customerid, $total_price, $date, $ship_name, $ship_address, $ship_city, $ship_zip_code, $ship_country){
		$query = "INSERT INTO orders VALUES 
		('', '" . $customerid . "', '" . $total_price . "', '" . $date . "', '" . $ship_name . "', '" . $ship_address . "', '" . $ship_city . "', '" . $ship_zip_code . "', '" . $ship_country . "')";
		$result = mysqli_query($conn, $query);
		if(!$result){
			echo "Insert orders failed " . mysqli_error($conn);
			exit;
		}
	}

	function getbookprice($conn, $isbn){
		$query = "SELECT book_price FROM books WHERE book_isbn = ?";
		$stmt = $conn->prepare($query);
		$stmt->bind_param("s", $isbn);
		$stmt->execute();
		$result = $stmt->get_result();
		if($row = $result->fetch_assoc()){
			return $row['book_price'];
		}
		return 0;
	}

	function getCustomerId($name, $address, $city, $zip_code, $country){
		$conn = db_connect();
		$query = "SELECT customerid from customers WHERE 
		`name` = '$name' AND 
		`address`= '$address' AND 
		city = '$city' AND 
		zip_code = '$zip_code' AND 
		country = '$country'";
		$result = mysqli_query($conn, $query);
		// if there is customer in db, take it out
		if($result->num_rows > 0){
			$row = mysqli_fetch_assoc($result);
			return $row['customerid'];
		} else {
			return null;
		}
	}

	function setCustomerId($name, $address, $city, $zip_code, $country){
		$conn = db_connect();
		$query = "INSERT INTO customers VALUES 
			('', '" . $name . "', '" . $address . "', '" . $city . "', '" . $zip_code . "', '" . $country . "')";

		$result = mysqli_query($conn, $query);
		if(!$result){
			echo "insert false !" . mysqli_error($conn);
			exit;
		}
		$customerid = mysqli_insert_id($conn);
		return $customerid;
	}

	function getPubName($conn, $pubid){
		$query = "SELECT publisher_name FROM publisher WHERE publisherid = '$pubid'";
		$result = mysqli_query($conn, $query);
		if(!$result){
			echo "Can't retrieve data " . mysqli_error($conn);
			exit;
		}
		if(mysqli_num_rows($result) == 0){
			echo "Empty books ! Something wrong! check again";
			exit;
		}

		$row = mysqli_fetch_assoc($result);
		return $row['publisher_name'];
	}

	function getAll($conn){
		$query = "SELECT * from books ORDER BY book_isbn DESC";
		$result = mysqli_query($conn, $query);
		if(!$result){
			echo "Can't retrieve data " . mysqli_error($conn);
			exit;
		}
		return $result;
	}
?>