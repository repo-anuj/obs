      <hr class="mt-5">

      <footer class="fixed-bottom bg-white shadow-sm py-4">
        <div class="container">
          <div class="row align-items-center">
            <div class="col-md-4 mb-3 mb-md-0">
              <a href="index.php" class="text-decoration-none">
                <h5 class="mb-0"><i class="fas fa-book-open me-2"></i>Online Book Store</h5>
              </a>
              <p class="text-muted small mt-2 mb-0">Your gateway to knowledge and adventure.</p>
            </div>
            <div class="col-md-4 mb-3 mb-md-0 text-center">
              <div class="social-icons">
                <a href="#" class="text-decoration-none me-3"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="text-decoration-none me-3"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-decoration-none me-3"><i class="fab fa-instagram"></i></a>
                <a href="#" class="text-decoration-none"><i class="fab fa-pinterest"></i></a>
              </div>
              <p class="text-muted small mt-2 mb-0">Connect with us on social media</p>
            </div>
            <div class="col-md-4 text-md-end">
              <div class="d-flex flex-column flex-md-row justify-content-md-end">
                <a href="#" class="text-decoration-none text-muted small me-md-4 mb-1 mb-md-0">Privacy Policy</a>
                <a href="#" class="text-decoration-none text-muted small me-md-4 mb-1 mb-md-0">Terms of Use</a>
                <a href="admin.php" class="text-decoration-none text-primary">Admin Login</a>
              </div>
              <p class="text-muted small mt-2 mb-0">&copy; <?= date('Y') ?> Online Book Store. All rights reserved.</p>
            </div>
          </div>
        </div>
      </footer>
      <div class="clear-fix py-5"></div> <!-- Extra space to prevent content from being hidden by fixed footer -->
    </div> <!-- /container -->

    <!-- Back to top button -->
    <button id="back-to-top" class="btn btn-primary rounded-circle position-fixed bottom-0 end-0 translate-middle d-none">
      <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Additional scripts for enhanced UX -->     
    <script>
      // Back to top button functionality
      $(window).scroll(function() {
        if ($(this).scrollTop() > 300) {
          $('#back-to-top').removeClass('d-none').addClass('d-flex');
        } else {
          $('#back-to-top').removeClass('d-flex').addClass('d-none');
        }
      });
      
      $('#back-to-top').click(function() {
        $('html, body').animate({scrollTop: 0}, 500);
        return false;
      });
      
      // Add smooth hover effects to all buttons
      $('.btn').on('mouseenter', function() {
        $(this).addClass('shadow-sm');
      }).on('mouseleave', function() {
        $(this).removeClass('shadow-sm');
      });
    </script>
  </body>
</html>