<?php
  session_start();
  $count = 0;
  // connecto database
  
  $title = "Home";
  require_once "./template/header.php";
  require_once "./functions/database_functions.php";
  $conn = db_connect();
  $row = select4LatestBook($conn);
?>
      <!-- Featured Books Section -->
      <section class="mt-5 mb-5">
        <h2 class="section-title text-center mb-4">Latest Books</h2>
        
        <div class="row">
          <?php $delay = 1; foreach($row as $book) { ?>
            <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12 py-2 mb-4 fade-in fade-in-<?php echo $delay; ?>">
              <a href="book.php?bookisbn=<?php echo $book['book_isbn']; ?>" class="card h-100 book-item text-reset text-decoration-none">
                <div class="position-relative">
                  <div class="img-holder overflow-hidden">
                    <img class="img-top" src="./bootstrap/img/<?php echo $book['book_image']; ?>" alt="<?= htmlspecialchars($book['book_title']) ?>">
                  </div>
                  <div class="position-absolute top-0 end-0 m-2">
                    <span class="badge bg-primary">New</span>
                  </div>
                </div>
                <div class="card-body d-flex flex-column justify-content-between">
                  <div>
                    <h5 class="card-title text-center"><?= htmlspecialchars($book['book_title']) ?></h5>
                  </div>
                  <div class="text-center mt-3">
                    <button class="btn btn-sm btn-outline-primary view-details">View Details</button>
                  </div>
                </div>
              </a>
            </div>
          <?php $delay++; } ?>
        </div>
      </section>
      
      <!-- Categories Section -->
      <section class="my-5 py-5 bg-light rounded-3">
        <div class="container">
          <h2 class="section-title text-center mb-4">Browse by Category</h2>
          <div class="row justify-content-center text-center g-4">
            <div class="col-md-3 col-sm-6 fade-in fade-in-1">
              <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                  <div class="icon-wrapper mb-3">
                    <i class="fas fa-book fa-3x text-primary"></i>
                  </div>
                  <h5>Fiction</h5>
                  <a href="books.php" class="stretched-link"></a>
                </div>
              </div>
            </div>
            <div class="col-md-3 col-sm-6 fade-in fade-in-2">
              <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                  <div class="icon-wrapper mb-3">
                    <i class="fas fa-laptop-code fa-3x text-primary"></i>
                  </div>
                  <h5>Technology</h5>
                  <a href="books.php" class="stretched-link"></a>
                </div>
              </div>
            </div>
            <div class="col-md-3 col-sm-6 fade-in fade-in-3">
              <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                  <div class="icon-wrapper mb-3">
                    <i class="fas fa-lightbulb fa-3x text-primary"></i>
                  </div>
                  <h5>Self-Help</h5>
                  <a href="books.php" class="stretched-link"></a>
                </div>
              </div>
            </div>
            <div class="col-md-3 col-sm-6 fade-in fade-in-4">
              <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                  <div class="icon-wrapper mb-3">
                    <i class="fas fa-atom fa-3x text-primary"></i>
                  </div>
                  <h5>Science</h5>
                  <a href="books.php" class="stretched-link"></a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Newsletter Section -->
      <section class="my-5">
        <div class="row align-items-center">
          <div class="col-lg-6 fade-in fade-in-1">
            <h2 class="mb-4">Stay Updated</h2>
            <p class="lead">Subscribe to our newsletter to receive updates on new arrivals, special offers and other discount information.</p>
            <form class="d-flex flex-column flex-sm-row gap-2 mt-4">
              <input type="email" class="form-control" placeholder="Your email address">
              <button type="submit" class="btn btn-primary">Subscribe</button>
            </form>
          </div>
          <div class="col-lg-6 mt-4 mt-lg-0 fade-in fade-in-2">
            <img src="https://source.unsplash.com/random/600x400/?reading,books" alt="Reading" class="img-fluid rounded shadow-sm">
          </div>
        </div>
      </section>
<?php
  if(isset($conn)) {mysqli_close($conn);}
  require_once "./template/footer.php";
?>