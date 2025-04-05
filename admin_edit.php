<?php
	// Start output buffering to prevent "headers already sent" errors
	ob_start();
	
	session_start();
	require_once "./functions/admin.php";
	$title = "Edit book";
	require_once "./template/header.php";
	require_once "./functions/database_functions.php";
	require_once "./functions/search_functions.php";
	$conn = db_connect();
	
	// Get all categories and tags for selection
	$categories = getAllCategories($conn);
	$allTags = getAllTags($conn);

	if(isset($_GET['bookisbn'])){
		$book_isbn = $_GET['bookisbn'];
	} else {
		echo "Empty query!";
		exit;
	}

	if(!isset($book_isbn)){
		echo "Empty isbn! check again!";
		exit;
	}
	
	// Get book's current tags
	$bookTags = getBookTags($conn, $book_isbn);
	$bookTagIds = array_map(function($tag) {
		return $tag['tag_id'];
	}, $bookTags);

	// get book data using prepared statement to prevent SQL injection
	$query = "SELECT b.*, c.category_id, c.category_name 
			 FROM books b 
			 LEFT JOIN categories c ON b.category_id = c.category_id 
			 WHERE b.book_isbn = ?";
	$stmt = $conn->prepare($query);
	$stmt->bind_param("s", $book_isbn);
	$stmt->execute();
	$result = $stmt->get_result();
	if(!$result){
		echo $err = "Can't retrieve data " . $conn->error;
		exit;
	}else{
		$row = $result->fetch_assoc();
	}
	if(isset($_POST['edit'])){
		$isbn = trim($_POST['isbn']);
		$book_title = trim($_POST['book_title']);
		$book_author = trim($_POST['book_author']);
		$book_descr = trim($_POST['book_descr']);
		$book_price = trim($_POST['book_price']);
		$publisherid = trim($_POST['publisherid']);
		$category_id = (!empty($_POST['category_id'])) ? trim($_POST['category_id']) : NULL;
		$tag_ids = isset($_POST['tag_ids']) ? $_POST['tag_ids'] : [];
		
		// Update book details using prepared statement
		$query = "UPDATE books SET 
				book_title = ?, 
				book_author = ?, 
				book_descr = ?, 
				book_price = ?, 
				publisherid = ?, 
				category_id = ?
				WHERE book_isbn = ?";
				
		$stmt = $conn->prepare($query);
		$stmt->bind_param("sssdiii", 
			$book_title, 
			$book_author, 
			$book_descr, 
			$book_price, 
			$publisherid, 
			$category_id,
			$book_isbn);
			
		$result = $stmt->execute();
		
		// Update book tags
		$tagsUpdated = updateBookTags($conn, $book_isbn, $tag_ids);
		
		if($result){
			$_SESSION['book_success'] = "Book Details has been updated successfully";
			header("Location: admin_book.php");
			exit();
		} else {
			$err =  "Can't update data " . $conn->error;
		}
	}
?>
	<h4 class="fw-bolder text-center">Edit Book Details</h4>
	<center>
	<hr class="bg-warning" style="width:5em;height:3px;opacity:1">
	</center>
	<div class="row justify-content-center">
		<div class="col-lg-6 col-md-8 col-sm-10 col-xs-12">
			<div class="card rounded-0 shadow">
				<div class="card-body">
					<div class="container-fluid">
						<?php if(isset($err)): ?>
							<div class="alert alert-danger rounded-0">
								<?= $_SESSION['err_login'] ?>
							</div>
						<?php 
							endif;
						?>
						<form method="post" action="admin_edit.php?bookisbn=<?php echo $row['book_isbn'];?>" enctype="multipart/form-data">
								<div class="mb-3">
									<label class="control-label">ISBN</label>
									<input class="form-control rounded-0" type="text" name="isbn" value="<?php echo $row['book_isbn'];?>" readOnly="true">
								</div>
								<div class="mb-3">
									<label class="control-label">Title</label>
									<input class="form-control rounded-0" type="text" name="book_title" value="<?php echo $row['book_title'];?>" required>
								</div>
								<div class="mb-3">
									<label class="control-label">Author</label>
									<input class="form-control rounded-0" type="text" name="book_author" value="<?php echo $row['book_author'];?>" required>
								</div>
								<div class="mb-3">
									<label class="control-label">Description</label>
									<textarea class="form-control rounded-0" name="book_descr" cols="40" rows="5"><?php echo $row['book_descr'];?></textarea>
								</div>
								<div class="mb-3">
									<label class="control-label">Price</label>
									<input class="form-control rounded-0" type="text" name="book_price" value="<?php echo $row['book_price'];?>" required>
								</div>
								<div class="mb-3">
									<label class="control-label">Publisher</label>
									<select class="form-select rounded-0"  name="publisherid" required>
										<?php 
										$psql = mysqli_query($conn, "SELECT * FROM `publisher` order by publisher_name asc");
										while($row = mysqli_fetch_assoc($psql)):
										?>
										<option value="<?= $row['publisherid'] ?>" <?= $row['publisherid']==$row['publisherid'] ? 'selected' : '' ?>><?= $row['publisher_name'] ?></option>
										<?php endwhile; ?>
									</select>

								</div>
								<div class="text-center">
									<button type="submit" name="edit"  class="btn btn-primary btn-sm rounded-0">Update</button>
									<button type="reset" class="btn btn-default btn-sm rounded-0 border">Cancel</button>
								</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
	if(isset($conn)) {mysqli_close($conn);}
	require "./template/footer.php"
?>