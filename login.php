<?php
  // Start output buffering to prevent "headers already sent" errors
  ob_start();
  
  session_start();
  
  // If user is already logged in, redirect to profile
  if(isset($_SESSION['user'])) {
    header("Location: profile.php");
    exit;
  }
  
  $title = "Login";
  require_once "./template/header.php";
  require_once "./functions/database_functions.php";
  require_once "./functions/user_functions.php";
  
  $conn = db_connect();
  
  // Process login form
  if(isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    $error = "";
    
    // Validate input
    if(empty($email) || empty($password)) {
      $error = "Please enter both email and password";
    } else if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = "Invalid email format";
    }
    
    // If no errors, proceed with login
    if(empty($error)) {
      $result = loginUser($conn, $email, $password);
      
      if($result['success']) {
        // Set user session
        $_SESSION['user'] = $result['user'];
        // Explicitly set user_id in session
        $_SESSION['user_id'] = $result['user']['user_id']; 
        
        // If there's a redirect URL stored (e.g., from checkout), go there
        if(isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])) {
          $redirect = $_SESSION['redirect_after_login'];
          unset($_SESSION['redirect_after_login']);
          header("Location: $redirect");
          exit;
        }
        
        // Otherwise, go to profile
        header("Location: profile.php");
        exit;
      } else {
        $error = $result['message'];
      }
    }
  }
?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body p-4 p-md-5">
          <h2 class="mb-4 text-center">Login to Your Account</h2>
          
          <?php if(isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
          <?php endif; ?>
          
          <?php if(isset($error) && !empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
          <?php endif; ?>
          
          <form method="post" action="login.php">
            <!-- Email -->
            <div class="mb-3">
              <label for="email" class="form-label">Email Address</label>
              <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            
            <!-- Password -->
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <div class="text-center mt-4">
              <button type="submit" name="login" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-sign-in-alt me-2"></i> Login
              </button>
            </div>
            
            <div class="text-center mt-4">
              <p>Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a></p>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
  if(isset($conn)) { mysqli_close($conn); }
  require_once "./template/footer.php";
?>
