<?php
  // Start output buffering to prevent "headers already sent" errors
  ob_start();
  
  session_start();
  
  // Redirect to login if not admin
  if(!isset($_SESSION['admin'])) {
    header("Location: admin.php");
    exit;
  }
  
  $title = "Manage Tags and Categories";
  require_once "./template/header.php";
  require_once "./functions/database_functions.php";
  require_once "./functions/search_functions.php";
  
  $conn = db_connect();
  
  // Handle add/edit/delete operations
  $success_message = '';
  $error_message = '';
  
  // Add/edit tag
  if(isset($_POST['add_tag'])) {
    $tag_name = trim($_POST['tag_name']);
    $tag_id = isset($_POST['tag_id']) ? $_POST['tag_id'] : null;
    
    if(empty($tag_name)) {
      $error_message = "Tag name cannot be empty";
    } else {
      if($tag_id) {
        // Update existing tag
        $stmt = $conn->prepare("UPDATE tags SET tag_name = ? WHERE tag_id = ?");
        $stmt->bind_param("si", $tag_name, $tag_id);
        if($stmt->execute()) {
          $success_message = "Tag updated successfully";
        } else {
          $error_message = "Error updating tag: " . $conn->error;
        }
      } else {
        // Add new tag
        $tag_id = createTag($conn, $tag_name);
        if($tag_id) {
          $success_message = "Tag added successfully";
        } else {
          $error_message = "Error adding tag: " . $conn->error;
        }
      }
    }
  }
  
  // Delete tag
  if(isset($_POST['delete_tag'])) {
    $tag_id = $_POST['tag_id'];
    
    // Check if tag is used
    $stmt = $conn->prepare("SELECT COUNT(*) as tag_count FROM book_tags WHERE tag_id = ?");
    $stmt->bind_param("i", $tag_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if($row['tag_count'] > 0) {
      $error_message = "Cannot delete tag - it is used by " . $row['tag_count'] . " book(s)";
    } else {
      $stmt = $conn->prepare("DELETE FROM tags WHERE tag_id = ?");
      $stmt->bind_param("i", $tag_id);
      if($stmt->execute()) {
        $success_message = "Tag deleted successfully";
      } else {
        $error_message = "Error deleting tag: " . $conn->error;
      }
    }
  }
  
  // Add/edit category
  if(isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $category_id = isset($_POST['category_id']) ? $_POST['category_id'] : null;
    
    if(empty($category_name)) {
      $error_message = "Category name cannot be empty";
    } else {
      if($category_id) {
        // Update existing category
        $stmt = $conn->prepare("UPDATE categories SET category_name = ? WHERE category_id = ?");
        $stmt->bind_param("si", $category_name, $category_id);
        if($stmt->execute()) {
          $success_message = "Category updated successfully";
        } else {
          $error_message = "Error updating category: " . $conn->error;
        }
      } else {
        // Add new category
        $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
        $stmt->bind_param("s", $category_name);
        if($stmt->execute()) {
          $success_message = "Category added successfully";
        } else {
          $error_message = "Error adding category: " . $conn->error;
        }
      }
    }
  }
  
  // Delete category
  if(isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];
    
    // Check if category is used
    $stmt = $conn->prepare("SELECT COUNT(*) as category_count FROM books WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if($row['category_count'] > 0) {
      $error_message = "Cannot delete category - it is used by " . $row['category_count'] . " book(s)";
    } else {
      $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
      $stmt->bind_param("i", $category_id);
      if($stmt->execute()) {
        $success_message = "Category deleted successfully";
      } else {
        $error_message = "Error deleting category: " . $conn->error;
      }
    }
  }
  
  // Get all tags and categories
  $tags = getAllTags($conn);
  $categories = getAllCategories($conn);
?>

<div class="container my-4">
  <div class="row">
    <div class="col-md-12 mb-4">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="admin_book.php">Admin Panel</a></li>
          <li class="breadcrumb-item active" aria-current="page">Manage Tags & Categories</li>
        </ol>
      </nav>
      
      <h1 class="mb-4"><?php echo $title; ?></h1>
      
      <?php if(!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?php echo $success_message; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      
      <?php if(!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo $error_message; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
      
      <div class="row">
        <!-- Tags Management -->
        <div class="col-md-6 mb-4">
          <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0">
                <i class="fas fa-tags me-2"></i> Manage Tags
              </h5>
            </div>
            <div class="card-body">
              <!-- Add Tag Form -->
              <form method="post" action="" class="mb-4">
                <div class="input-group mb-3">
                  <input type="text" class="form-control" placeholder="Enter new tag name" name="tag_name" required>
                  <input type="hidden" name="tag_id" id="edit_tag_id" value="">
                  <button class="btn btn-primary" type="submit" name="add_tag" id="tag_submit_btn">
                    <i class="fas fa-plus-circle me-1"></i> Add Tag
                  </button>
                  <button class="btn btn-secondary d-none" type="button" id="cancel_edit_tag">
                    <i class="fas fa-times me-1"></i> Cancel
                  </button>
                </div>
              </form>
              
              <!-- Tags List -->
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Tag Name</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if(count($tags) == 0): ?>
                      <tr>
                        <td colspan="3" class="text-center">No tags found</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach($tags as $tag): ?>
                        <tr>
                          <td><?php echo $tag['tag_id']; ?></td>
                          <td><?php echo htmlspecialchars($tag['tag_name']); ?></td>
                          <td>
                            <button type="button" class="btn btn-sm btn-outline-primary edit-tag-btn" 
                                    data-id="<?php echo $tag['tag_id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($tag['tag_name']); ?>">
                              <i class="fas fa-edit"></i>
                            </button>
                            <form method="post" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this tag?');">
                              <input type="hidden" name="tag_id" value="<?php echo $tag['tag_id']; ?>">
                              <button type="submit" class="btn btn-sm btn-outline-danger" name="delete_tag">
                                <i class="fas fa-trash"></i>
                              </button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Categories Management -->
        <div class="col-md-6 mb-4">
          <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
              <h5 class="mb-0">
                <i class="fas fa-folder me-2"></i> Manage Categories
              </h5>
            </div>
            <div class="card-body">
              <!-- Add Category Form -->
              <form method="post" action="" class="mb-4">
                <div class="input-group mb-3">
                  <input type="text" class="form-control" placeholder="Enter new category name" name="category_name" required>
                  <input type="hidden" name="category_id" id="edit_category_id" value="">
                  <button class="btn btn-success" type="submit" name="add_category" id="category_submit_btn">
                    <i class="fas fa-plus-circle me-1"></i> Add Category
                  </button>
                  <button class="btn btn-secondary d-none" type="button" id="cancel_edit_category">
                    <i class="fas fa-times me-1"></i> Cancel
                  </button>
                </div>
              </form>
              
              <!-- Categories List -->
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Category Name</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if(count($categories) == 0): ?>
                      <tr>
                        <td colspan="3" class="text-center">No categories found</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach($categories as $category): ?>
                        <tr>
                          <td><?php echo $category['category_id']; ?></td>
                          <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                          <td>
                            <button type="button" class="btn btn-sm btn-outline-success edit-category-btn" 
                                    data-id="<?php echo $category['category_id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($category['category_name']); ?>">
                              <i class="fas fa-edit"></i>
                            </button>
                            <form method="post" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                              <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                              <button type="submit" class="btn btn-sm btn-outline-danger" name="delete_category">
                                <i class="fas fa-trash"></i>
                              </button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Book and Tag Management -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white">
          <h5 class="mb-0">
            <i class="fas fa-book-reader me-2"></i> Book-Tag Management
          </h5>
        </div>
        <div class="card-body">
          <p class="mb-4">
            To assign tags to books, use the "Edit Book" function from the <a href="admin_book.php">Book Management</a> page.
          </p>
          
          <div class="alert alert-info">
            <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i> Tips for Effective Tagging</h5>
            <ul class="mb-0">
              <li>Use consistent naming conventions for tags</li>
              <li>Create tags that will help users find related books</li>
              <li>Consider using tags for topics, reading level, and special features</li>
              <li>Combine tags and categories for powerful filtering capabilities</li>
            </ul>
          </div>
        </div>
      </div>
      
      <!-- Manage Search Index -->
      <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
          <h5 class="mb-0">
            <i class="fas fa-search me-2"></i> Manage Search Index
          </h5>
        </div>
        <div class="card-body">
          <p>
            The search index helps users find books more easily. If you update book information directly in the database, you may need to rebuild the search index.
          </p>
          
          <form method="post" action="" class="mb-3" onsubmit="return confirm('Are you sure you want to rebuild the search index? This may take some time for large collections.');">
            <button type="submit" class="btn btn-warning" name="rebuild_index">
              <i class="fas fa-sync-alt me-1"></i> Rebuild Search Index
            </button>
          </form>
          
          <?php 
          // Process rebuild index request
          if(isset($_POST['rebuild_index'])) {
            $result = $conn->query("UPDATE books SET search_index = CONCAT_WS(' ', book_title, book_author, book_isbn, book_descr)");
            
            if($result) {
              echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                      Search index has been successfully rebuilt.
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
            } else {
              echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                      Error rebuilding search index: ' . $conn->error . '
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
            }
          }
          ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript for Tags and Categories Management -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Edit Tag
  const editTagBtns = document.querySelectorAll('.edit-tag-btn');
  const tagNameInput = document.querySelector('input[name="tag_name"]');
  const tagIdInput = document.getElementById('edit_tag_id');
  const tagSubmitBtn = document.getElementById('tag_submit_btn');
  const cancelEditTagBtn = document.getElementById('cancel_edit_tag');
  
  editTagBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const tagId = this.getAttribute('data-id');
      const tagName = this.getAttribute('data-name');
      
      tagNameInput.value = tagName;
      tagIdInput.value = tagId;
      tagSubmitBtn.innerHTML = '<i class="fas fa-save me-1"></i> Update Tag';
      cancelEditTagBtn.classList.remove('d-none');
    });
  });
  
  // Reset Tag Form
  function resetTagForm() {
    tagNameInput.value = '';
    tagIdInput.value = '';
    tagSubmitBtn.innerHTML = '<i class="fas fa-plus-circle me-1"></i> Add Tag';
    cancelEditTagBtn.classList.add('d-none');
  }
  
  // Cancel Tag Edit
  if(cancelEditTagBtn) {
    cancelEditTagBtn.addEventListener('click', resetTagForm);
  }
  
  // Edit Category
  const editCategoryBtns = document.querySelectorAll('.edit-category-btn');
  const categoryNameInput = document.querySelector('input[name="category_name"]');
  const categoryIdInput = document.getElementById('edit_category_id');
  const categorySubmitBtn = document.getElementById('category_submit_btn');
  const cancelEditCategoryBtn = document.getElementById('cancel_edit_category');
  
  editCategoryBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const categoryId = this.getAttribute('data-id');
      const categoryName = this.getAttribute('data-name');
      
      categoryNameInput.value = categoryName;
      categoryIdInput.value = categoryId;
      categorySubmitBtn.innerHTML = '<i class="fas fa-save me-1"></i> Update Category';
      cancelEditCategoryBtn.classList.remove('d-none');
    });
  });
  
  // Reset Category Form
  function resetCategoryForm() {
    categoryNameInput.value = '';
    categoryIdInput.value = '';
    categorySubmitBtn.innerHTML = '<i class="fas fa-plus-circle me-1"></i> Add Category';
    cancelEditCategoryBtn.classList.add('d-none');
  }
  
  // Cancel Category Edit
  if(cancelEditCategoryBtn) {
    cancelEditCategoryBtn.addEventListener('click', resetCategoryForm);
  }
});
</script>

<?php
  if(isset($conn)) { mysqli_close($conn); }
  require_once "./template/footer.php";
?>
