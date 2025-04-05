<?php
  session_start();
  $book_isbn = $_GET['bookisbn'];
  // connect to database
  require_once "./functions/database_functions.php";
  require_once "./functions/review_functions.php";
  $conn = db_connect();

  // Get book details with average rating
  $query = "SELECT b.*, p.publisher_name, c.category_name,
            COALESCE(AVG(r.rating), 0) as average_rating,
            COUNT(r.review_id) as total_reviews
            FROM books b 
            LEFT JOIN publisher p ON b.publisherid = p.publisherid
            LEFT JOIN categories c ON b.category_id = c.category_id
            LEFT JOIN reviews r ON b.book_isbn = r.book_isbn
            WHERE b.book_isbn = ?
            GROUP BY b.book_isbn";
  $stmt = $conn->prepare($query);
  $stmt->bind_param('s', $book_isbn);
  $stmt->execute();
  $result = $stmt->get_result();

  if(!$result){
    echo "Can't retrieve data " . mysqli_error($conn);
    exit;
  }

  $row = mysqli_fetch_assoc($result);
  if(!$row){
    echo "Book not found";
    exit;
  }

  $title = $row['book_title'];
  $is_logged_in = isset($_SESSION['user']);
  require "./template/header.php";
?>
      <!-- Modern Book Detail Section -->
      <nav aria-label="breadcrumb" class="animate__animated animate__fadeIn">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Home</a></li>
          <li class="breadcrumb-item"><a href="books.php" class="text-decoration-none">Books</a></li>
          <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($row['book_title']); ?></li>
        </ol>
      </nav>

      <div class="row g-4 mt-2">
        <!-- Book Image Column -->
        <div class="col-md-4 fade-in fade-in-1">
          <div class="book-detail-image h-100">
            <div class="position-relative h-100">
              <div class="img-holder overflow-hidden h-100 d-flex align-items-center justify-content-center bg-light">
                <img class="img-top shadow" src="./bootstrap/img/<?php echo $row['book_image']; ?>" alt="<?php echo htmlspecialchars($row['book_title']); ?>">
              </div>
              <!-- Book badges -->
              <div class="position-absolute top-0 start-0 m-3">
                <span class="badge bg-primary rounded-pill">Book</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Book Details Column -->
        <div class="col-md-8 fade-in fade-in-2">
          <div class="card h-100 book-details-card">
            <div class="card-body">
              <!-- Book Title and Author -->
              <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                  <h2 class="card-title mb-1"><?= htmlspecialchars($row['book_title']) ?></h2>
                  <p class="text-muted mb-0">By <span class="fw-medium"><?= htmlspecialchars($row['book_author']) ?></span></p>
                </div>
                <div>
                  <span class="book-price">$<?= number_format((float)$row['book_price'], 2) ?></span>
                </div>
              </div>

              <!-- Book Description -->
              <div class="book-description mb-4">
                <h4 class="mb-3">Description</h4>
                <p class="lead"><?php echo nl2br(htmlspecialchars($row['book_descr'])); ?></p>
              </div>

              <!-- Book Details -->
              <div class="book-specifications mb-4">
                <h4 class="mb-3">Specifications</h4>
                <div class="table-responsive">
                  <table class="table table-hover">
                    <tbody>
                      <?php foreach($row as $key => $value){
                        if($key == "book_descr" || $key == "book_image" || $key == "publisherid" || $key == "book_title" || $key == "book_price" || $key == "book_author"){
                          continue;
                        }
                        switch($key){
                          case "book_isbn":
                            $key = "ISBN";
                            break;
                          case "created_at":
                            $key = "Published Date";
                            break;
                        }
                      ?>
                      <tr>
                        <th style="width: 30%"><?php echo htmlspecialchars($key); ?></th>
                        <td><?php echo htmlspecialchars($value); ?></td>
                      </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Purchase Actions -->
              <div class="purchase-actions mt-5">
                <form method="post" action="cart.php" class="d-flex flex-column flex-sm-row gap-2">
                  <input type="hidden" name="bookisbn" value="<?php echo htmlspecialchars($book_isbn);?>">
                  <div class="input-group me-2" style="max-width: 140px;">
                    <span class="input-group-text">Qty</span>
                    <input type="number" class="form-control" name="quantity" value="1" min="1" max="10">
                  </div>
                  <button type="submit" name="cart" class="btn btn-primary flex-grow-1 animate__animated animate__pulse animate__infinite animate__slower">
                    <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                  </button>
                  <a href="#" class="btn btn-outline-secondary">
                    <i class="far fa-heart"></i>
                  </a>
                </form>
              </div>

              <!-- Social Sharing -->
              <div class="share-buttons mt-4 pt-4 border-top">
                <p class="text-muted mb-2">Share this book:</p>
                <div class="d-flex gap-2">
                  <a href="#" class="btn btn-sm btn-outline-primary"><i class="fab fa-facebook-f"></i></a>
                  <a href="#" class="btn btn-sm btn-outline-info"><i class="fab fa-twitter"></i></a>
                  <a href="#" class="btn btn-sm btn-outline-danger"><i class="fab fa-pinterest"></i></a>
                  <a href="#" class="btn btn-sm btn-outline-success"><i class="fab fa-whatsapp"></i></a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Reviews Section -->
      <section class="reviews-section mt-5 pt-4 border-top">
        <?php include "./template/review_section.php"; ?>
      </section>

      <!-- Related Books Section -->
      <section class="related-books mt-5 pt-4 border-top">
        <h3 class="section-title text-center mb-4">You May Also Like</h3>
        <div class="row g-4">
          <?php for($i = 0; $i < min(4, count($row)); $i++) { ?>
            <div class="col-lg-3 col-md-6 fade-in">
              <a href="books.php" class="card h-100 book-item text-decoration-none">
                <div class="img-holder">
                  <img src="./bootstrap/img/<?php echo $row['book_image']; ?>" class="img-top" alt="Related Book">
                </div>
                <div class="card-body text-center">
                  <h5 class="card-title"><?= htmlspecialchars($row['book_title']) ?></h5>
                  <p class="text-primary fw-bold">$<?= number_format((float)$row['book_price'], 2) ?></p>
                </div>
              </a>
            </div>
          <?php } ?>
        </div>
      </section>
      <?php if(isset($conn)) {mysqli_close($conn); } ?>
    <!-- Add to Cart Modal for Guest Users -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="loginModalLabel">Sign in Required</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Please sign in to add items to your cart and make purchases.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <a href="login.php" class="btn btn-primary">Sign In</a>
          </div>
        </div>
      </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const addToCartBtn = document.querySelector('button[name="cart"]');
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function(e) {
                <?php if (!$is_logged_in): ?>
                e.preventDefault();
                const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                loginModal.show();
                <?php endif; ?>
            });
        }
    });
    </script>

<?php
  require "./template/footer.php";
?>