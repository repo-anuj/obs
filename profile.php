<?php
  // Start output buffering to prevent "headers already sent" errors
  ob_start();
  
  session_start();
  
  // Redirect to login if not logged in
  if(!isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = 'profile.php';
    header("Location: login.php");
    exit;
  }
  
  $title = "My Profile";
  require_once "./template/header.php";
  require_once "./functions/database_functions.php";
  require_once "./functions/user_functions.php";
  
  $conn = db_connect();
  $user_id = $_SESSION['user']['user_id'];
  
  // Get updated user profile
  $profile_result = getUserProfile($conn, $user_id);
  if($profile_result['success']) {
    $profile = $profile_result['profile'];
  }
  
  // Process profile update
  if(isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $zip_code = trim($_POST['zip_code']);
    $country = trim($_POST['country']);
    
    $error = "";
    
    // Validate input
    if(empty($name)) {
      $error = "Name field is required";
    }
    
    // If no errors, proceed with update
    if(empty($error)) {
      $result = updateUserProfile($conn, $user_id, $name, $phone, $address, $city, $zip_code, $country);
      
      if($result['success']) {
        // Update session data and profile data
        $_SESSION['user']['name'] = $name;
        $message = $result['message'];
        
        // Refresh profile data
        $profile_result = getUserProfile($conn, $user_id);
        if($profile_result['success']) {
          $profile = $profile_result['profile'];
        }
      } else {
        $error = $result['message'];
      }
    }
  }
  
  // Process password change
  if(isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    $password_error = "";
    
    // Validate input
    if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
      $password_error = "All password fields are required";
    } else if(strlen($new_password) < 6) {
      $password_error = "New password must be at least 6 characters long";
    } else if($new_password !== $confirm_password) {
      $password_error = "New passwords do not match";
    }
    
    // If no errors, proceed with password change
    if(empty($password_error)) {
      $result = changeUserPassword($conn, $user_id, $current_password, $new_password);
      
      if($result['success']) {
        $password_message = $result['message'];
      } else {
        $password_error = $result['message'];
      }
    }
  }
  
  // Fetch user's order history
  $query = "SELECT o.orderid, o.user_id, o.amount, o.ship_name, o.ship_address, o.ship_city, 
			o.ship_zip_code, o.ship_country, o.ship_phone, o.date, o.status,
			COUNT(oi.id) as total_items,
			GROUP_CONCAT(DISTINCT CONCAT(b.book_title, ':', oi.quantity, ':', b.book_isbn) SEPARATOR '|') as books
			FROM orders o
			JOIN order_items oi ON o.orderid = oi.orderid
			JOIN books b ON oi.book_isbn = b.book_isbn
			WHERE o.user_id = ?
			GROUP BY o.orderid
			ORDER BY o.date DESC";
			
	$stmt = $conn->prepare($query);
	$stmt->bind_param("i", $user_id);
	$stmt->execute();
	$orders_result = $stmt->get_result();
?>

<div class="container my-5">
  <div class="row">
    <!-- Sidebar navigation -->
    <div class="col-md-3 mb-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center mb-3">
            <div class="avatar-circle bg-primary text-white me-3">
              <?php echo strtoupper(substr($profile['name'] ?? $_SESSION['user']['name'], 0, 1)); ?>
            </div>
            <div>
              <h5 class="mb-0"><?php echo htmlspecialchars($profile['name'] ?? $_SESSION['user']['name']); ?></h5>
              <small class="text-muted"><?php echo htmlspecialchars($profile['email'] ?? $_SESSION['user']['email']); ?></small>
            </div>
          </div>
          
          <div class="list-group list-group-flush">
            <a href="#profile-section" class="list-group-item list-group-item-action active" data-bs-toggle="list">
              <i class="fas fa-user-circle fa-fw me-2"></i> My Profile
            </a>
            <a href="#orders-section" class="list-group-item list-group-item-action" data-bs-toggle="list">
              <i class="fas fa-shopping-bag fa-fw me-2"></i> Order History
            </a>
            <a href="#security-section" class="list-group-item list-group-item-action" data-bs-toggle="list">
              <i class="fas fa-shield-alt fa-fw me-2"></i> Security
            </a>
            <a href="logout.php" class="list-group-item list-group-item-action text-danger">
              <i class="fas fa-sign-out-alt fa-fw me-2"></i> Logout
            </a>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Main content -->
    <div class="col-md-9">
      <div class="tab-content">
        <!-- Profile Section -->
        <div class="tab-pane fade show active" id="profile-section">
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <h4 class="mb-0">Personal Information</h4>
            </div>
            <div class="card-body">
              <?php if(isset($error) && !empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
              <?php endif; ?>
              
              <?php if(isset($message) && !empty($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
              <?php endif; ?>
              
              <form method="post" action="profile.php">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($profile['name'] ?? ''); ?>" required>
                  </div>
                  
                  <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" disabled>
                    <small class="form-text text-muted">Email cannot be changed</small>
                  </div>
                  
                  <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                  </div>
                  
                  <div class="col-12 mb-3">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($profile['address'] ?? ''); ?>">
                  </div>
                  
                  <div class="col-md-6 mb-3">
                    <label for="city" class="form-label">City</label>
                    <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($profile['city'] ?? ''); ?>">
                  </div>
                  
                  <div class="col-md-3 mb-3">
                    <label for="zip_code" class="form-label">Zip Code</label>
                    <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($profile['zip_code'] ?? ''); ?>">
                  </div>
                  
                  <div class="col-md-3 mb-3">
                    <label for="country" class="form-label">Country</label>
                    <input type="form-control" id="country" name="country" value="<?php echo htmlspecialchars($profile['country'] ?? ''); ?>">
                  </div>
                </div>
                
                <div class="mt-3">
                  <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Save Changes
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
        
        <!-- Order History Section -->
        <div class="tab-pane fade" id="orders-section">
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <h4 class="mb-0">Order History</h4>
            </div>
            <div class="card-body">
              <?php if($orders_result->num_rows > 0): ?>
                <div class="accordion" id="orderAccordion">
                  <?php while($order = $orders_result->fetch_assoc()): ?>
                    <div class="accordion-item mb-3 border">
                      <h2 class="accordion-header" id="heading<?php echo $order['orderid']; ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#order<?php echo $order['orderid']; ?>">
                          <div class="d-flex w-100 justify-content-between align-items-center">
                            <div>
                              <span class="badge bg-primary me-2">Order #<?php echo htmlspecialchars($order['orderid']); ?></span>
                              <small class="text-muted"><?php echo date('F j, Y, g:i a', strtotime($order['date'])); ?></small>
                              <span class="badge bg-<?php echo ($order['status'] == 'Processing') ? 'warning' : 'success'; ?> ms-2"><?php echo $order['status']; ?></span>
                            </div>
                            <span class="ms-auto me-3">$<?php echo number_format($order['amount'], 2); ?></span>
                          </div>
                        </button>
                      </h2>
                      <div id="order<?php echo $order['orderid']; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $order['orderid']; ?>" data-bs-parent="#orderAccordion">
                        <div class="accordion-body">
                          <div class="row">
                            <div class="col-md-6">
                              <h6>Shipping Information</h6>
                              <p>
                                <?php echo htmlspecialchars($order['ship_name']); ?><br>
                                <?php echo htmlspecialchars($order['ship_address']); ?><br>
                                <?php echo htmlspecialchars($order['ship_city']) . ', ' . htmlspecialchars($order['ship_zip_code']); ?><br>
                                <?php echo htmlspecialchars($order['ship_country']); ?>
                              </p>
                            </div>
                            <div class="col-md-6">
                              <h6>Order Summary</h6>
                              <p>
                                <strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['date'])); ?><br>
                                <strong>Total:</strong> $<?php echo number_format($order['amount'], 2); ?><br>
                              </p>
                            </div>
                          </div>
                          
                          <h6 class="mt-3">Order Items</h6>
                          <div class="table-responsive">
                            <table class="table table-hover">
                              <thead>
                                <tr>
                                  <th>Product</th>
                                  <th>Price</th>
                                  <th>Quantity</th>
                                  <th class="text-end">Total</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php
                                $books = explode('|', $order['books']);
                                foreach($books as $book) {
                                  list($title, $qty, $isbn) = explode(':', $book);
                                  $book_price = getbookprice($conn, $isbn);
                                  echo "<tr>
                                    <td>
                                      <div class='d-flex align-items-center'>
                                        <img src='./bootstrap/img/$isbn.jpg' alt='$title' class='me-3' style='width: 50px; height: 70px; object-fit: cover;'>
                                        <div>
                                          <h6 class='mb-0'>$title</h6>
                                          <small class='text-muted'>ISBN: $isbn</small>
                                        </div>
                                      </div>
                                    </td>
                                    <td>$" . number_format($book_price, 2) . "</td>
                                    <td>$qty</td>
                                    <td class='text-end'>$" . number_format($book_price * $qty, 2) . "</td>
                                  </tr>";
                                }
                                ?>
                              </tbody>
                            </table>
                          </div>
                          
                          <?php if($order['status'] == 'Delivered'): ?>
                            <h6 class="mt-3">Write a Review</h6>
                            <form method="post" action="profile.php#orders-section">
                              <input type="hidden" name="order_id" value="<?php echo $order['orderid']; ?>">
                              <input type="hidden" name="book_isbn" value="<?php echo $isbn; ?>">
                              <div class="mb-3">
                                <label for="review" class="form-label">Review</label>
                                <textarea class="form-control" id="review" name="review" rows="3"></textarea>
                              </div>
                              <div class="mb-3">
                                <label for="rating" class="form-label">Rating</label>
                                <select class="form-select" id="rating" name="rating">
                                  <option value="1">1 Star</option>
                                  <option value="2">2 Stars</option>
                                  <option value="3">3 Stars</option>
                                  <option value="4">4 Stars</option>
                                  <option value="5">5 Stars</option>
                                </select>
                              </div>
                              <button type="submit" name="submit_review" class="btn btn-primary">
                                <i class="fas fa-pencil-alt me-2"></i> Submit Review
                              </button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  <?php endwhile; ?>
                </div>
              <?php else: ?>
                <div class="text-center py-5">
                  <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                  <p class="lead">You haven't placed any orders yet.</p>
                  <a href="books.php" class="btn btn-primary">Start Shopping</a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        
        <!-- Security Section -->
        <div class="tab-pane fade" id="security-section">
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <h4 class="mb-0">Change Password</h4>
            </div>
            <div class="card-body">
              <?php if(isset($password_error) && !empty($password_error)): ?>
                <div class="alert alert-danger"><?php echo $password_error; ?></div>
              <?php endif; ?>
              
              <?php if(isset($password_message) && !empty($password_message)): ?>
                <div class="alert alert-success"><?php echo $password_message; ?></div>
              <?php endif; ?>
              
              <form method="post" action="profile.php" class="needs-validation" novalidate>
                <div class="mb-3">
                  <label for="current_password" class="form-label">Current Password</label>
                  <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                
                <div class="mb-3">
                  <label for="new_password" class="form-label">New Password</label>
                  <input type="password" class="form-control" id="new_password" name="new_password" required>
                  <div class="form-text">Password must be at least 6 characters long</div>
                </div>
                
                <div class="mb-3">
                  <label for="confirm_password" class="form-label">Confirm New Password</label>
                  <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="mt-3">
                  <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-key me-2"></i> Change Password
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
  }
</style>

<script>
  // Activate tabs based on hash URL
  document.addEventListener('DOMContentLoaded', function() {
    const hash = window.location.hash || '#profile-section';
    const triggerEl = document.querySelector(`.list-group-item[href="${hash}"]`);
    if (triggerEl) {
      // Remove active from all tabs
      document.querySelectorAll('.list-group-item').forEach(el => {
        el.classList.remove('active');
      });
      // Add active to current tab
      triggerEl.classList.add('active');
      
      // Show tab content
      const tabContent = document.querySelector(hash);
      if (tabContent) {
        document.querySelectorAll('.tab-pane').forEach(el => {
          el.classList.remove('show', 'active');
        });
        tabContent.classList.add('show', 'active');
      }
    }
    
    // Add click listener to tabs
    document.querySelectorAll('.list-group-item[data-bs-toggle="list"]').forEach(tab => {
      tab.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Update hash
        const href = this.getAttribute('href');
        window.location.hash = href;
        
        // Remove active from all tabs
        document.querySelectorAll('.list-group-item').forEach(el => {
          el.classList.remove('active');
        });
        // Add active to current tab
        this.classList.add('active');
        
        // Show tab content
        const tabContent = document.querySelector(href);
        if (tabContent) {
          document.querySelectorAll('.tab-pane').forEach(el => {
            el.classList.remove('show', 'active');
          });
          tabContent.classList.add('show', 'active');
        }
      });
    });
  });
  
  // Validate password form
  (function () {
    'use strict'
    
    var forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function (form) {
      form.addEventListener('submit', function (event) {
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        if (newPassword.value !== confirmPassword.value) {
          confirmPassword.setCustomValidity("Passwords don't match");
          event.preventDefault();
          event.stopPropagation();
        } else {
          confirmPassword.setCustomValidity('');
        }
        
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        
        form.classList.add('was-validated');
      }, false);
    });
  })();
</script>

<?php
  // Keep connection open for footer
  // if(isset($conn)) {mysqli_close($conn); }
  require_once "./template/footer.php";
?>

<?php
  // Process review submission
  if(isset($_POST['submit_review'])) {
    $order_id = $_POST['order_id'];
    $book_isbn = $_POST['book_isbn'];
    $review = $_POST['review'];
    $rating = $_POST['rating'];
    
    $query = "INSERT INTO reviews (order_id, book_isbn, review, rating) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $order_id, $book_isbn, $review, $rating);
    $stmt->execute();
    
    if($stmt->affected_rows > 0) {
      $_SESSION['success_message'] = "Review submitted successfully!";
      header("Location: profile.php#orders-section");
      exit;
    } else {
      echo "Error submitting review: " . $conn->error;
    }
  }
?>

<?php
  // Fetch user's order history
  $query = "SELECT o.orderid, o.user_id, o.amount, o.ship_name, o.ship_address, o.ship_city, 
			o.ship_zip_code, o.ship_country, o.ship_phone, o.date, o.status,
			COUNT(oi.id) as total_items,
			GROUP_CONCAT(DISTINCT CONCAT(b.book_title, ':', oi.quantity, ':', b.book_isbn) SEPARATOR '|') as books
			FROM orders o
			JOIN order_items oi ON o.orderid = oi.orderid
			JOIN books b ON oi.book_isbn = b.book_isbn
			WHERE o.user_id = ?
			GROUP BY o.orderid
			ORDER BY o.date DESC";
			
	$stmt = $conn->prepare($query);
	$stmt->bind_param("i", $user_id);
	$stmt->execute();
	$orders_result = $stmt->get_result();

  $orders = array();
  while($order = $orders_result->fetch_assoc()) {
    $orderid = $order['orderid'];
    if(!isset($orders[$orderid])) {
      $orders[$orderid] = array(
        'orderid' => $orderid,
        'date' => $order['date'],
        'total_amount' => $order['amount'],
        'status' => $order['status'],
        'items' => array()
      );
    }
    $books = explode('|', $order['books']);
    foreach($books as $book) {
      list($title, $qty, $isbn) = explode(':', $book);
      $orders[$orderid]['items'][] = array(
        'book_title' => $title,
        'book_isbn' => $isbn,
        'quantity' => $qty,
        'review_id' => null // Initialize review_id
      );
    }
  }

  foreach($orders as &$order) { // Use reference to modify the original array
    // Check if reviews table exists
    $result = $conn->query("SHOW TABLES LIKE 'reviews'");
    if ($result->num_rows == 0) {
        // Create reviews table if it doesn't exist
        $sql = file_get_contents('./database/reviews_system.sql');
        $conn->multi_query($sql);
        while ($conn->more_results()) {
            $conn->next_result();
        }
    }

    // Now try to get reviews
    $query = "SELECT r.review_id, r.rating, r.review_text, r.book_isbn
              FROM reviews r
              JOIN order_items oi ON r.book_isbn = oi.book_isbn
              WHERE oi.orderid = ? AND r.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $order['orderid'], $user_id);
    $stmt->execute();
    $review_result = $stmt->get_result();

    while($review = $review_result->fetch_assoc()) {
      foreach($order['items'] as &$item) {
        if($item['book_isbn'] == $review['book_isbn']) {
          $item['review_id'] = $review['review_id'];
          $item['rating'] = $review['rating'];
          $item['review_text'] = $review['review_text'];
        }
      }
    }
  }
?>

<div class="container mt-4">
  <div class="row">
    <div class="col-md-12">
      <h2>My Profile</h2>
      <div class="card mb-4">
        <div class="card-body">
          <h5 class="card-title">Personal Information</h5>
          <p><strong>Name:</strong> <?php echo htmlspecialchars($profile['name'] ?? $_SESSION['user']['name']); ?></p>
          <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email'] ?? $_SESSION['user']['email']); ?></p>
          <p><strong>Address:</strong> <?php echo htmlspecialchars($profile['address'] ?? ''); ?></p>
          <p><strong>City:</strong> <?php echo htmlspecialchars($profile['city'] ?? ''); ?></p>
          <p><strong>Zip Code:</strong> <?php echo htmlspecialchars($profile['zip_code'] ?? ''); ?></p>
        </div>
      </div>

      <h3>My Orders</h3>
      <?php if(empty($orders)): ?>
        <div class="alert alert-info">You haven't placed any orders yet.</div>
      <?php else: ?>
        <?php foreach($orders as $order): ?>
          <div class="card mb-4">
            <div class="card-header">
              <div class="row">
                <div class="col-md-4">
                  <strong>Order ID:</strong> <?php echo htmlspecialchars($order['orderid']); ?>
                </div>
                <div class="col-md-4">
                  <strong>Date:</strong> <?php echo date('F j, Y', strtotime($order['date'])); ?>
                </div>
                <div class="col-md-4">
                  <strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?>
                </div>
              </div>
            </div>
            <div class="card-body">
              <?php foreach($order['items'] as $item): ?>
                <div class="row mb-3">
                  <div class="col-md-2">
                    <img src="<?php echo htmlspecialchars('./bootstrap/img/' . $item['book_isbn'] . '.jpg'); ?>" alt="<?php echo htmlspecialchars($item['book_title']); ?>" class="img-fluid">
                  </div>
                  <div class="col-md-6">
                    <h5><?php echo htmlspecialchars($item['book_title']); ?></h5>
                    <p>Quantity: <?php echo htmlspecialchars($item['quantity']); ?></p>
                    <p>Price: $<?php echo htmlspecialchars(number_format(getbookprice($conn, $item['book_isbn']), 2)); ?></p>
                  </div>
                  <div class="col-md-4">
                    <?php if(isset($item['review_id'])): ?>
                      <div class="review-summary">
                        <h6>Your Review</h6>
                        <div class="rating">
                          <?php for($i = 1; $i <= 5; $i++): ?>
                            <?php if($i <= $item['rating']): ?>
                              <i class="fas fa-star text-warning"></i>
                            <?php else: ?>
                              <i class="far fa-star text-warning"></i>
                            <?php endif; ?>
                          <?php endfor; ?>
                        </div>
                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($item['review_text'])); ?></p>
                      </div>
                    <?php else: ?>
                      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reviewModal<?php echo $order['orderid'] . '-' . $item['book_isbn']; ?>">
                        Write a Review
                      </button>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="card-footer">
              <strong>Total Amount:</strong> $<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?>
            </div>
          </div>

          <!-- Review Modal -->
          <?php foreach($order['items'] as $item_modal): ?>
          <?php if(!isset($item_modal['review_id'])): ?>
            <div class="modal fade" id="reviewModal<?php echo $order['orderid'] . '-' . $item_modal['book_isbn']; ?>" tabindex="-1" role="dialog" aria-labelledby="reviewModalLabel<?php echo $order['orderid'] . '-' . $item_modal['book_isbn']; ?>" aria-hidden="true">
              <div class="modal-dialog" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="reviewModalLabel<?php echo $order['orderid'] . '-' . $item_modal['book_isbn']; ?>">Write a Review for <?php echo htmlspecialchars($item_modal['book_title']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <form action="process_review.php" method="post">
                    <div class="modal-body">
                      <input type="hidden" name="book_isbn" value="<?php echo $item_modal['book_isbn']; ?>">
                      <input type="hidden" name="order_id" value="<?php echo $order['orderid']; ?>">
                      
                      <div class="form-group mb-3">
                        <label>Rating</label>
                        <div class="rating">
                          <?php for($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                            <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                          <?php endfor; ?>
                        </div>
                      </div>
                      <div class="form-group mb-3">
                        <label>Your Review</label>
                        <textarea name="review_text" class="form-control" rows="4" required></textarea>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="submit" class="btn btn-primary">Submit Review</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          <?php endif; ?>
          <?php endforeach; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
.rating {
  display: flex;
  flex-direction: row-reverse;
  justify-content: flex-end;
}

.rating input {
  display: none;
}

.rating label {
  cursor: pointer;
  width: 25px;
  height: 25px;
  margin: 0;
  padding: 0;
  font-size: 25px;
  color: #ddd;
}

.rating label:hover,
.rating label:hover ~ label,
.rating input:checked ~ label {
  color: #ffc107;
}

.review-summary .rating {
  pointer-events: none;
}
</style>

<?php
require_once "./template/footer.php";
?>
