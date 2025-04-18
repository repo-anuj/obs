<?php
  session_start();
  $count = 0;
  // connecto database
  require_once "./functions/database_functions.php";
  $conn = db_connect();

  $query = "SELECT book_isbn, book_image, book_title FROM books";
  $result = mysqli_query($conn, $query);
  if(!$result){
    echo "Can't retrieve data " . mysqli_error($conn);
    exit;
  }

  $title = "List of Books";
  require_once "./template/header.php";
?>
  <!-- Search Bar -->
  <div class="row justify-content-center my-4">
    <div class="col-md-6">
      <form class="d-flex" action="advanced-search.php" method="GET">
        <div class="input-group">
          <input class="form-control" type="search" name="query" placeholder="Search books by title, author, ISBN..." aria-label="Search">
          <button class="btn btn-primary" type="submit">
            <i class="fas fa-search"></i>
          </button>
          <a href="advanced-search.php" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Advanced Search">
            <i class="fas fa-sliders-h"></i>
          </a>
        </div>
      </form>
    </div>
  </div>
  <p class="lead text-center text-muted">List of All Books</p>
    <?php for($i = 0; $i < mysqli_num_rows($result); $i++){ ?>
      <div class="row">
        <?php while($book = mysqli_fetch_assoc($result)){ ?>
          <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12 py-2 mb-2">
      		<a href="book.php?bookisbn=<?php echo $book['book_isbn']; ?>" class="card rounded-0 shadow book-item text-reset text-decoration-none">
            <div class="img-holder overflow-hidden">
              <img class="img-top" src="./bootstrap/img/<?php echo $book['book_image']; ?>">
            </div>
            <div class="card-body">
              <div class="card-title fw-bolder h5 text-center"><?= $book['book_title'] ?></div>
            </div>
          </a>
      	</div>
        <?php
          $count++;
          if($count >= 4){
              $count = 0;
              break;
            }
          } ?> 
      </div>
<?php
      }
  if(isset($conn)) { mysqli_close($conn); }
  require_once "./template/footer.php";
?>