<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo $title; ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Bootstrap CSS -->
    <link href="./bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="./bootstrap/css/styles.css" rel="stylesheet">
    
    <!-- Modern UI Styles -->
    <link href="./bootstrap/css/modern-style.css" rel="stylesheet">
    
    <!-- Animation Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    
    <!-- Font Awesome JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/js/all.min.js" integrity="sha512-6PM0qYu5KExuNcKt5bURAoT6KCThUmHRewN3zUFNaoI6Di7XJPTMoT6K0nsagZKk2OB4L7E3q1uQKHNHd4stIQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- Select2 CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <!-- Core JavaScript -->
    <script type="text/javascript" src="./bootstrap/js/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="./bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Loading Animation Script -->
    <script>
      $(window).on('load', function() {
        // When page is loaded
        setTimeout(function() {
          $('.loading').fadeOut(300);
        }, 500);
      });
      
      $(document).ready(function() {
        // Add fade-in animations to books
        $('.book-item').addClass('fade-in');
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl);
        });
      });
    </script>
  </head>

  <body>
    <!-- Loading animation -->
    <div class="loading">
      <div class="loading-spinner"></div>
    </div>
    
    <div class="clear-fix pt-5 pb-3"></div>
    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top">
      <div class="container">
        <a class="navbar-brand animate__animated animate__fadeIn" href="index.php">
          <i class="fas fa-book-open me-2"></i>Online Book Store
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav" aria-controls="topNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="topNav">
          <ul class="navbar-nav me-auto">
            <?php if(isset($_SESSION['admin']) && $_SESSION['admin'] == true): ?>
                <!-- Admin Navigation -->
                <ul class="navbar-nav">
                  <li class="nav-item animate__animated animate__fadeIn animate__delay-1s">
                    <a class="nav-link" href="admin_dashboard.php">
                      <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                  </li>
                  <li class="nav-item animate__animated animate__fadeIn animate__delay-1s">
                    <a class="nav-link" href="admin_book.php">
                      <i class="fa fa-th-list me-1"></i> Book List
                    </a>
                  </li>
                  <li class="nav-item animate__animated animate__fadeIn animate__delay-2s">
                    <a class="nav-link" href="admin_add.php">
                      <i class="far fa-plus-square me-1"></i> Add New Book
                    </a>
                  </li>
                  <li class="nav-item animate__animated animate__fadeIn animate__delay-2s">
                    <a class="nav-link" href="admin_orders.php">
                      <i class="fas fa-shopping-cart me-1"></i> Orders
                    </a>
                  </li>
                  <li class="nav-item animate__animated animate__fadeIn animate__delay-2s">
                    <a class="nav-link" href="admin_tags.php">
                      <i class="fas fa-tags me-1"></i> Manage Tags
                    </a>
                  </li>
                </ul>
                </li>
                
            <?php else: ?>
                <!-- Customer Navigation -->
                <li class="nav-item animate__animated animate__fadeIn animate__delay-1s">
                  <a class="nav-link" href="publisher_list.php">
                    <i class="fa fa-paperclip me-1"></i> Publishers
                  </a>
                </li>
                <li class="nav-item animate__animated animate__fadeIn animate__delay-2s">
                  <a class="nav-link" href="books.php">
                    <i class="fa fa-book me-1"></i> Books
                  </a>
                </li>
            <?php endif; ?>
          </ul>
          
          <!-- Right Navigation Section -->
          <ul class="navbar-nav ms-auto d-flex align-items-center">
            <?php if(isset($_SESSION['admin']) && $_SESSION['admin'] == true): ?>
                <!-- Admin Logout -->
                <li class="nav-item animate__animated animate__fadeIn animate__delay-3s">
                  <a class="nav-link" href="admin_signout.php">
                    <i class="fa fa-sign-out-alt me-1"></i> Logout
                  </a>
                </li>
            <?php else: ?>
                <!-- Cart Link -->
                <li class="nav-item animate__animated animate__fadeIn animate__delay-3s me-3">
                  <a class="nav-link" href="cart.php">
                    <i class="fa fa-shopping-cart me-1"></i> 
                    Cart
                    <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                      <span class="badge bg-primary rounded-pill"><?php echo count($_SESSION['cart']); ?></span>
                    <?php endif; ?>
                  </a>
                </li>

                <?php if(isset($_SESSION['user'])): ?>
                  <!-- User Account Dropdown - Enhanced Version -->
                  <li class="nav-item dropdown animate__animated animate__fadeIn animate__delay-3s">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                      <div class="avatar-circle bg-primary text-white me-2" style="width:38px;height:38px;font-size:16px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 5px rgba(0,0,0,0.1);">
                        <?php echo strtoupper(substr($_SESSION['user']['name'], 0, 1)); ?>
                      </div>
                      <div class="d-none d-md-block">
                        <div class="fw-bold" style="line-height:1.2;font-size:0.9rem;"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></div>
                        <div class="text-muted" style="font-size:0.75rem;">My Account <i class="fas fa-angle-down ms-1"></i></div>
                      </div>
                      <div class="d-md-none ms-1">
                        <span>Account <i class="fas fa-angle-down"></i></span>
                      </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="width:240px;margin-top:10px;" aria-labelledby="userDropdown">
                      <div class="px-3 py-2 bg-light border-bottom">
                        <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></div>
                        <div class="small text-muted"><?php echo htmlspecialchars($_SESSION['user']['email']); ?></div>
                      </div>
                      <li><a class="dropdown-item py-2" href="profile.php"><i class="fas fa-user-circle me-2 text-primary"></i> My Profile</a></li>
                      <li><a class="dropdown-item py-2" href="profile.php#orders-section"><i class="fas fa-shopping-bag me-2 text-primary"></i> Order History</a></li>
                      <li><a class="dropdown-item py-2" href="profile.php#security-section"><i class="fas fa-shield-alt me-2 text-primary"></i> Security Settings</a></li>
                      <li><hr class="dropdown-divider my-1"></li>
                      <li><a class="dropdown-item py-2 text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                  </li>
                <?php else: ?>
                  <!-- Login/Register/Admin Login -->
                  <li class="nav-item animate__animated animate__fadeIn animate__delay-3s">
                    <a href="login.php" class="btn btn-outline-primary me-2"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                  </li>
                  <li class="nav-item animate__animated animate__fadeIn animate__delay-4s">
                    <a href="register.php" class="btn btn-primary me-2"><i class="fas fa-user-plus me-1"></i> Register</a>
                  </li>
                  <li class="nav-item animate__animated animate__fadeIn animate__delay-5s">
                    <a href="admin.php" class="nav-link">Admin Login</a>
                  </li>
                <?php endif; ?>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>
    
    <?php if(isset($title) && $title == "Home"): ?>
    <!-- Hero section for home page -->
    <div class="container mt-4">
      <div class="row align-items-center py-4 animate__animated animate__fadeIn">
        <div class="col-md-6">
          <h1 class="display-4 fw-bold">Discover Your Next Favorite Book</h1>
          <p class="lead text-muted">Explore our collection of books across various genres and authors.</p>
          <a href="books.php" class="btn btn-primary btn-lg mt-3">
            Browse Collection <i class="fas fa-arrow-right ms-2"></i>
          </a>
        </div>
        <div class="col-md-6">
          <img src="template/Book lover-amico.png" alt="Books" class="img-fluid rounded shadow animate__animated animate__fadeInUp">
        </div>
      </div>
      <hr class="my-4">
    </div>
    <?php endif; ?>

    <div class="container py-4">
    <!-- Start Content Wrapper for Sticky Footer -->
    <div class="content-wrap">