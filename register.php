<?php
  // Start output buffering to prevent "headers already sent" errors
  ob_start();
  
  session_start();
  
  // If user is already logged in, redirect to profile
  if(isset($_SESSION['user'])) {
    header("Location: profile.php");
    exit;
  }
  
  $title = "Register";
  require_once "./template/header.php";
  require_once "./functions/database_functions.php";
  require_once "./functions/user_functions.php";
  
  $conn = db_connect();
  
  // Process registration form
  if(isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    $address = isset($_POST['address']) ? trim($_POST['address']) : null;
    $city = isset($_POST['city']) ? trim($_POST['city']) : null;
    $zip_code = isset($_POST['zip_code']) ? trim($_POST['zip_code']) : null;
    $country = isset($_POST['country']) ? trim($_POST['country']) : null;
    
    $error = "";
    
    // Validate input
    if(empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
      $error = "All required fields must be filled";
    } else if(strlen($password) < 6) {
      $error = "Password must be at least 6 characters long";
    } else if($password !== $confirm_password) {
      $error = "Passwords do not match";
    } else if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = "Invalid email format";
    }
    
    // If no errors, proceed with registration
    if(empty($error)) {
      $result = registerUser($conn, $name, $email, $password, $phone, $address, $city, $zip_code, $country);
      
      if($result['success']) {
        // Set success message and redirect to login
        $_SESSION['success_message'] = $result['message'];
        header("Location: login.php");
        exit;
      } else {
        $error = $result['message'];
      }
    }
  }
?>

<div class="container my-5">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body p-4 p-md-5">
          <h2 class="mb-4 text-center">Create an Account</h2>
          
          <?php if(isset($error) && !empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
          <?php endif; ?>
          
          <form method="post" action="register.php" class="needs-validation" novalidate>
            <!-- Name -->
            <div class="mb-3">
              <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
              <div class="invalid-feedback">Please enter your name</div>
            </div>
            
            <!-- Email -->
            <div class="mb-3">
              <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
              <div class="invalid-feedback">Please enter a valid email address</div>
            </div>
            
            <!-- Password -->
            <div class="mb-3">
              <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
              <input type="password" class="form-control" id="password" name="password" required>
              <div class="form-text">Password must be at least 6 characters long</div>
            </div>
            
            <!-- Confirm Password -->
            <div class="mb-3">
              <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
              <div class="invalid-feedback">Passwords must match</div>
            </div>
            
            <div class="mb-3">
              <label for="phone" class="form-label">Phone Number (Optional)</label>
              <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
            
            <div class="text-center mt-4">
              <button type="submit" name="register" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-user-plus me-2"></i> Register
              </button>
            </div>
            
            <div class="text-center mt-4">
              <p>Already have an account? <a href="login.php" class="text-decoration-none">Login here</a></p>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Form validation script -->
<script>
  // Example starter JavaScript for disabling form submissions if there are invalid fields
  (function () {
    'use strict'
  
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.querySelectorAll('.needs-validation')
  
    // Loop over them and prevent submission
    Array.prototype.slice.call(forms)
      .forEach(function (form) {
        form.addEventListener('submit', function (event) {
          if (!form.checkValidity()) {
            event.preventDefault()
            event.stopPropagation()
          }
  
          form.classList.add('was-validated')
        }, false)
      })
    
    // Check if passwords match
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePassword() {
      if(password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity("Passwords don't match");
      } else {
        confirmPassword.setCustomValidity('');
      }
    }
    
    password.addEventListener('change', validatePassword);
    confirmPassword.addEventListener('keyup', validatePassword);
  })()
</script>

<?php
  if(isset($conn)) { mysqli_close($conn); }
  require_once "./template/footer.php";
?>
