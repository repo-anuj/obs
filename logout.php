<?php
  // Start output buffering to prevent "headers already sent" errors
  ob_start();
  
  session_start();
  
  // Clear the user session
  unset($_SESSION['user']);
  
  // Set logout message
  $_SESSION['success_message'] = "You have been successfully logged out.";
  
  // Redirect to login page
  header("Location: login.php");
  exit;
?>
