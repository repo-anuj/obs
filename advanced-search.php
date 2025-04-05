<?php
  // Start output buffering to prevent "headers already sent" errors
  ob_start();
  
  session_start();
  
  $title = "Advanced Search";
  require_once "./template/header.php";
  require_once "./functions/database_functions.php";
  require_once "./functions/search_functions.php";
  
  $conn = db_connect();
  
  // Get all categories and tags for filters
  $categories = getAllCategories($conn);
  $tags = getAllTags($conn);
  
  // Default values for filters
  $search_query = isset($_GET['query']) ? htmlspecialchars($_GET['query']) : '';
  $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
  $items_per_page = 12;
  $offset = ($current_page - 1) * $items_per_page;
  
  // Extract filter parameters
  $filters = [
    'category' => isset($_GET['category']) ? $_GET['category'] : '',
    'tags' => isset($_GET['tags']) && is_array($_GET['tags']) ? $_GET['tags'] : [],
    'price_min' => isset($_GET['price_min']) && is_numeric($_GET['price_min']) ? (float)$_GET['price_min'] : '',
    'price_max' => isset($_GET['price_max']) && is_numeric($_GET['price_max']) ? (float)$_GET['price_max'] : '',
    'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : '',
    'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : '',
    'sort' => isset($_GET['sort']) ? $_GET['sort'] : 'title_asc'
  ];
  
  // Get search results
  $books = [];
  $total_results = 0;
  $is_search = !empty($search_query) || !empty($filters['category']) || !empty($filters['tags']) || 
              !empty($filters['price_min']) || !empty($filters['price_max']) || 
              !empty($filters['date_from']) || !empty($filters['date_to']);
              
  if ($is_search) {
    $books = getBooksByAdvancedSearch($conn, $search_query, $filters, $items_per_page, $offset);
    $total_results = countSearchResults($conn, $search_query, $filters);
  } else {
    // Get all books if no search criteria
    $stmt = $conn->prepare("SELECT * FROM books ORDER BY book_title LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $items_per_page, $offset);
    $stmt->execute();
    $books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Count total books
    $result = $conn->query("SELECT COUNT(*) as total FROM books");
    $row = $result->fetch_assoc();
    $total_results = $row['total'];
  }
  
  // Calculate pagination
  $total_pages = ceil($total_results / $items_per_page);
?>

<!-- Advanced Search Page -->
<div class="container my-4">
  <h1 class="mb-4">Advanced Book Search</h1>
  
  <!-- Search Form -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="GET" action="advanced-search.php" id="advanced-search-form">
        <div class="row g-3">
          <!-- Search Query -->
          <div class="col-md-12 mb-3">
            <div class="input-group">
              <input type="text" class="form-control" id="search-query" name="query" 
                     value="<?php echo $search_query; ?>" 
                     placeholder="Search by title, author, ISBN..." aria-label="Search query">
              <button class="btn btn-primary" type="submit">
                <i class="fas fa-search me-1"></i> Search
              </button>
              <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" 
                      data-bs-target="#advancedFilters" aria-expanded="false" aria-controls="advancedFilters">
                <i class="fas fa-sliders-h me-1"></i> Filters
              </button>
            </div>
            <!-- Search Suggestions -->
            <div id="search-suggestions" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 1000;"></div>
          </div>
          
          <!-- Advanced Filters (collapsible) -->
          <div class="collapse col-12" id="advancedFilters">
            <div class="card card-body border-0 bg-light">
              <div class="row g-3">
                <!-- Category Filter -->
                <div class="col-md-4 mb-3">
                  <label for="category" class="form-label">Category</label>
                  <select class="form-select" id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                      <option value="<?php echo htmlspecialchars($category['category_name']); ?>" 
                              <?php echo ($filters['category'] == $category['category_name']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['category_name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <!-- Tags Filter -->
                <div class="col-md-8 mb-3">
                  <label for="tags" class="form-label">Tags</label>
                  <select class="form-select" id="tags" name="tags[]" multiple>
                    <?php foreach ($tags as $tag): ?>
                      <option value="<?php echo htmlspecialchars($tag['tag_name']); ?>"
                              <?php echo (in_array($tag['tag_name'], $filters['tags'])) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tag['tag_name']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <!-- Price Range Filter -->
                <div class="col-md-6 mb-3">
                  <label class="form-label">Price Range ($)</label>
                  <div class="row">
                    <div class="col">
                      <div class="input-group">
                        <span class="input-group-text">Min</span>
                        <input type="number" class="form-control" name="price_min" 
                               value="<?php echo $filters['price_min']; ?>" 
                               placeholder="0" min="0" step="0.01">
                      </div>
                    </div>
                    <div class="col">
                      <div class="input-group">
                        <span class="input-group-text">Max</span>
                        <input type="number" class="form-control" name="price_max" 
                               value="<?php echo $filters['price_max']; ?>" 
                               placeholder="1000" min="0" step="0.01">
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Publication Date Filter -->
                <div class="col-md-6 mb-3">
                  <label class="form-label">Publication Date</label>
                  <div class="row">
                    <div class="col">
                      <div class="input-group">
                        <span class="input-group-text">From</span>
                        <input type="date" class="form-control" name="date_from" 
                               value="<?php echo $filters['date_from']; ?>">
                      </div>
                    </div>
                    <div class="col">
                      <div class="input-group">
                        <span class="input-group-text">To</span>
                        <input type="date" class="form-control" name="date_to" 
                               value="<?php echo $filters['date_to']; ?>">
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Sort By -->
                <div class="col-md-4 mb-3">
                  <label for="sort" class="form-label">Sort By</label>
                  <select class="form-select" id="sort" name="sort">
                    <option value="title_asc" <?php echo ($filters['sort'] == 'title_asc') ? 'selected' : ''; ?>>
                      Title (A-Z)
                    </option>
                    <option value="price_asc" <?php echo ($filters['sort'] == 'price_asc') ? 'selected' : ''; ?>>
                      Price (Low to High)
                    </option>
                    <option value="price_desc" <?php echo ($filters['sort'] == 'price_desc') ? 'selected' : ''; ?>>
                      Price (High to Low)
                    </option>
                    <option value="newest" <?php echo ($filters['sort'] == 'newest') ? 'selected' : ''; ?>>
                      Newest First
                    </option>
                  </select>
                </div>
                
                <!-- Filter Actions -->
                <div class="col-md-8 d-flex align-items-end justify-content-end mb-3">
                  <button type="reset" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-undo me-1"></i> Reset
                  </button>
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i> Apply Filters
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Search Results -->
  <div class="mb-3">
    <?php if ($is_search): ?>
      <h2 class="h4 mb-3">
        <?php 
        if ($total_results > 0) {
          echo "Found $total_results results";
          if (!empty($search_query)) {
            echo " for \"" . htmlspecialchars($search_query) . "\"";
          }
        } else {
          echo "No results found";
          if (!empty($search_query)) {
            echo " for \"" . htmlspecialchars($search_query) . "\"";
          }
        }
        ?>
      </h2>
    <?php else: ?>
      <h2 class="h4 mb-3">Browse All Books</h2>
    <?php endif; ?>
    
    <!-- Active Filters -->
    <?php if (!empty($filters['category']) || !empty($filters['tags']) || 
              !empty($filters['price_min']) || !empty($filters['price_max']) || 
              !empty($filters['date_from']) || !empty($filters['date_to'])): ?>
      <div class="mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
          <span class="text-muted me-1">Active filters:</span>
          
          <?php if (!empty($filters['category'])): ?>
            <span class="badge bg-primary">
              Category: <?php echo htmlspecialchars($filters['category']); ?>
              <a href="<?php 
                $params = $_GET;
                unset($params['category']);
                echo 'advanced-search.php?' . http_build_query($params);
              ?>" class="text-white text-decoration-none ms-1" title="Remove filter">×</a>
            </span>
          <?php endif; ?>
          
          <?php if (!empty($filters['tags'])): 
            foreach ($filters['tags'] as $tag): ?>
              <span class="badge bg-secondary">
                Tag: <?php echo htmlspecialchars($tag); ?>
                <a href="<?php 
                  $params = $_GET;
                  $newTags = array_diff($params['tags'], [$tag]);
                  $params['tags'] = $newTags;
                  echo 'advanced-search.php?' . http_build_query($params);
                ?>" class="text-white text-decoration-none ms-1" title="Remove filter">×</a>
              </span>
            <?php endforeach;
          endif; ?>
          
          <?php if (!empty($filters['price_min']) || !empty($filters['price_max'])): ?>
            <span class="badge bg-info text-dark">
              Price: 
              <?php 
                echo (!empty($filters['price_min'])) ? '$' . $filters['price_min'] : '$0';
                echo ' - ';
                echo (!empty($filters['price_max'])) ? '$' . $filters['price_max'] : 'Any';
              ?>
              <a href="<?php 
                $params = $_GET;
                unset($params['price_min']);
                unset($params['price_max']);
                echo 'advanced-search.php?' . http_build_query($params);
              ?>" class="text-dark text-decoration-none ms-1" title="Remove filter">×</a>
            </span>
          <?php endif; ?>
          
          <?php if (!empty($filters['date_from']) || !empty($filters['date_to'])): ?>
            <span class="badge bg-warning text-dark">
              Date: 
              <?php 
                echo (!empty($filters['date_from'])) ? $filters['date_from'] : 'Any';
                echo ' - ';
                echo (!empty($filters['date_to'])) ? $filters['date_to'] : 'Any';
              ?>
              <a href="<?php 
                $params = $_GET;
                unset($params['date_from']);
                unset($params['date_to']);
                echo 'advanced-search.php?' . http_build_query($params);
              ?>" class="text-dark text-decoration-none ms-1" title="Remove filter">×</a>
            </span>
          <?php endif; ?>
          
          <a href="advanced-search.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-times me-1"></i> Clear All
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
  
  <!-- Books Grid -->
  <?php if (count($books) > 0): ?>
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4 mb-4">
      <?php foreach ($books as $book): ?>
        <div class="col fadeIn">
          <div class="card h-100 book-item shadow-sm">
            <div class="position-relative">
              <div class="img-holder overflow-hidden">
                <img src="./bootstrap/img/<?php echo htmlspecialchars($book['book_image']); ?>" 
                     class="img-top" alt="<?php echo htmlspecialchars($book['book_title']); ?>">
              </div>
              
              <?php if (isset($book['tags']) && !empty($book['tags'])): ?>
                <div class="position-absolute bottom-0 start-0 m-2 d-flex flex-wrap gap-1">
                  <?php 
                  $tagArray = explode(', ', $book['tags']);
                  $displayTags = array_slice($tagArray, 0, 2); // Show only first 2 tags
                  foreach ($displayTags as $tag): 
                  ?>
                    <span class="badge bg-primary px-2 py-1 rounded-pill" style="font-size: 0.7rem;">
                      <?php echo htmlspecialchars($tag); ?>
                    </span>
                  <?php endforeach; 
                  
                  // Show +N more if there are more than 2 tags
                  if (count($tagArray) > 2):
                  ?>
                    <span class="badge bg-secondary px-2 py-1 rounded-pill" style="font-size: 0.7rem;">
                      +<?php echo count($tagArray) - 2; ?> more
                    </span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
            
            <div class="card-body">
              <h5 class="card-title" title="<?php echo htmlspecialchars($book['book_title']); ?>">
                <?php 
                  // Limit title length
                  $title = $book['book_title'];
                  echo (strlen($title) > 30) ? htmlspecialchars(substr($title, 0, 30) . '...') : htmlspecialchars($title);
                ?>
              </h5>
              <p class="card-text text-muted mb-1">
                <small>By <?php echo htmlspecialchars($book['book_author']); ?></small>
              </p>
              <?php if (isset($book['category_name']) && !empty($book['category_name'])): ?>
                <p class="card-text text-muted mb-1">
                  <small>Category: <?php echo htmlspecialchars($book['category_name']); ?></small>
                </p>
              <?php endif; ?>
              <div class="d-flex justify-content-between align-items-center mt-2">
                <span class="text-primary fw-bold">$<?php echo number_format($book['book_price'], 2); ?></span>
                <a href="book.php?bookisbn=<?php echo urlencode($book['book_isbn']); ?>" class="btn btn-sm btn-outline-primary">
                  View Details
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <nav aria-label="Search results pagination">
        <ul class="pagination justify-content-center">
          <!-- Previous page link -->
          <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php 
              $params = $_GET;
              $params['page'] = max(1, $current_page - 1);
              echo 'advanced-search.php?' . http_build_query($params);
            ?>" aria-label="Previous">
              <span aria-hidden="true">&laquo;</span>
            </a>
          </li>
          
          <!-- Page number links -->
          <?php
          // Display a range of page links
          $max_links = 5;
          $start_page = max(1, min($current_page - floor($max_links / 2), $total_pages - $max_links + 1));
          $end_page = min($total_pages, $start_page + $max_links - 1);
          $start_page = max(1, $end_page - $max_links + 1);
          
          if ($start_page > 1): ?>
            <li class="page-item">
              <a class="page-link" href="<?php 
                $params = $_GET;
                $params['page'] = 1;
                echo 'advanced-search.php?' . http_build_query($params);
              ?>">1</a>
            </li>
            <?php if ($start_page > 2): ?>
              <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
          <?php endif; ?>
          
          <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
              <a class="page-link" href="<?php 
                $params = $_GET;
                $params['page'] = $i;
                echo 'advanced-search.php?' . http_build_query($params);
              ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>
          
          <?php if ($end_page < $total_pages): ?>
            <?php if ($end_page < $total_pages - 1): ?>
              <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
            <li class="page-item">
              <a class="page-link" href="<?php 
                $params = $_GET;
                $params['page'] = $total_pages;
                echo 'advanced-search.php?' . http_build_query($params);
              ?>"><?php echo $total_pages; ?></a>
            </li>
          <?php endif; ?>
          
          <!-- Next page link -->
          <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php 
              $params = $_GET;
              $params['page'] = min($total_pages, $current_page + 1);
              echo 'advanced-search.php?' . http_build_query($params);
            ?>" aria-label="Next">
              <span aria-hidden="true">&raquo;</span>
            </a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
  <?php else: ?>
    <!-- No Results -->
    <div class="card shadow-sm">
      <div class="card-body text-center py-5">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h3>No books found</h3>
        <p class="text-muted">Try adjusting your search criteria or browse our catalog</p>
        <a href="books.php" class="btn btn-primary mt-2">Browse All Books</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Autocomplete and Search JS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('search-query');
  const suggestionsContainer = document.getElementById('search-suggestions');
  let typingTimer;
  
  // Setup multiselect for tags
  if (typeof jQuery !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
    $('#tags').select2({
      placeholder: 'Select tags',
      allowClear: true,
      closeOnSelect: false
    });
    
    $('#category').select2({
      placeholder: 'Select category'
    });
  }
  
  // Toggle advanced filters if any are already set
  const hasActiveFilters = <?php echo 
    (!empty($filters['category']) || !empty($filters['tags']) || 
     !empty($filters['price_min']) || !empty($filters['price_max']) || 
     !empty($filters['date_from']) || !empty($filters['date_to'])) ? 'true' : 'false'; 
  ?>;
  
  if (hasActiveFilters) {
    const filtersCollapse = document.getElementById('advancedFilters');
    const bsCollapse = new bootstrap.Collapse(filtersCollapse, {
      toggle: true
    });
  }
  
  // Handle reset button
  document.querySelector('button[type="reset"]').addEventListener('click', function(e) {
    e.preventDefault();
    // Clear all form fields
    document.getElementById('advanced-search-form').reset();
    // Clear select2 if it exists
    if (typeof jQuery !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
      $('#tags').val(null).trigger('change');
      $('#category').val(null).trigger('change');
    }
    // Redirect to empty search
    window.location.href = 'advanced-search.php';
  });
  
  // Handle search input for autocomplete
  searchInput.addEventListener('input', function() {
    clearTimeout(typingTimer);
    
    if (searchInput.value.length < 2) {
      suggestionsContainer.classList.add('d-none');
      return;
    }
    
    typingTimer = setTimeout(function() {
      fetchSuggestions(searchInput.value);
    }, 300);
  });
  
  // Hide suggestions when clicking outside
  document.addEventListener('click', function(e) {
    if (e.target !== searchInput && !suggestionsContainer.contains(e.target)) {
      suggestionsContainer.classList.add('d-none');
    }
  });
  
  // Fetch autocomplete suggestions
  function fetchSuggestions(query) {
    // Create a new XMLHttpRequest object
    const xhr = new XMLHttpRequest();
    
    // Configure it: GET-request for the URL
    xhr.open('GET', 'search-suggestions.php?query=' + encodeURIComponent(query), true);
    
    // Send the request
    xhr.send();
    
    // This will be called after the response is received
    xhr.onload = function() {
      if (xhr.status === 200) {
        const suggestions = JSON.parse(xhr.responseText);
        displaySuggestions(suggestions);
      }
    };
  }
  
  // Display suggestion results
  function displaySuggestions(suggestions) {
    if (suggestions.length === 0) {
      suggestionsContainer.classList.add('d-none');
      return;
    }
    
    // Clear previous suggestions
    suggestionsContainer.innerHTML = '';
    
    // Build suggestion items
    suggestions.forEach(function(suggestion) {
      const suggestionItem = document.createElement('a');
      suggestionItem.href = 'advanced-search.php?query=' + encodeURIComponent(suggestion.text);
      suggestionItem.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
      
      // Create main text
      const textSpan = document.createElement('span');
      textSpan.textContent = suggestion.text;
      
      // Create badge for type
      const badgeSpan = document.createElement('span');
      badgeSpan.className = 'badge rounded-pill ';
      
      switch(suggestion.type) {
        case 'title':
          badgeSpan.classList.add('bg-primary');
          badgeSpan.textContent = 'Title';
          break;
        case 'author':
          badgeSpan.classList.add('bg-success');
          badgeSpan.textContent = 'Author';
          break;
        case 'isbn':
          badgeSpan.classList.add('bg-info');
          badgeSpan.textContent = 'ISBN';
          break;
        case 'tag':
          badgeSpan.classList.add('bg-secondary');
          badgeSpan.textContent = 'Tag';
          break;
      }
      
      suggestionItem.appendChild(textSpan);
      suggestionItem.appendChild(badgeSpan);
      
      // Handle suggestion click
      suggestionItem.addEventListener('click', function(e) {
        e.preventDefault();
        searchInput.value = suggestion.text;
        document.getElementById('advanced-search-form').submit();
      });
      
      suggestionsContainer.appendChild(suggestionItem);
    });
    
    // Show suggestions
    suggestionsContainer.classList.remove('d-none');
  }
});
</script>

<?php
  if(isset($conn)) { mysqli_close($conn); }
  require_once "./template/footer.php";
?>
