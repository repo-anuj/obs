<?php
require_once "./functions/review_functions.php";

// Check if user is logged in and get user ID
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Get reviews for this book
$query = "SELECT r.*, DATE_FORMAT(r.created_at, '%M %d, %Y') as review_date
          FROM reviews r 
          WHERE r.book_isbn = ? 
          ORDER BY r.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $book_isbn);
$stmt->execute();
$reviews = $stmt->get_result();

// Get user's review if logged in
$user_review = null;
if ($is_logged_in) {
    $query = "SELECT * FROM reviews WHERE book_isbn = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $book_isbn, $user_id);
    $stmt->execute();
    $user_review = $stmt->get_result()->fetch_assoc();
}

// Get average rating and total reviews
$avg_rating = number_format($row['average_rating'] ?? 0, 1);
$total_reviews = intval($row['total_reviews'] ?? 0);
?>

<div class="reviews-container">
    <!-- Review Summary -->
    <div class="review-summary card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4 text-center">
                    <h2 class="display-4 mb-0"><?php echo $avg_rating; ?></h2>
                    <div class="text-warning h4 mb-2">
                        <?php 
                        $rating_value = floatval($avg_rating);
                        for($i = 1; $i <= 5; $i++) {
                            if($i <= $rating_value) {
                                echo '<i class="fas fa-star"></i>';
                            } else if($i - $rating_value < 1 && $i - $rating_value > 0) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            } else {
                                echo '<i class="far fa-star"></i>';
                            }
                        }
                        ?>
                    </div>
                    <p class="text-muted mb-0"><?php echo $total_reviews; ?> <?php echo $total_reviews == 1 ? 'review' : 'reviews'; ?></p>
                </div>
                <div class="col-md-8">
                    <?php if($is_logged_in): ?>
                        <?php if(hasUserPurchasedBook($conn, $user_id, $book_isbn)): ?>
                            <div class="write-review-section">
                                <h5><?php echo $user_review ? 'Update Your Review' : 'Write a Review'; ?></h5>
                                <form action="process_review.php" method="post" class="mt-3">
                                    <input type="hidden" name="book_isbn" value="<?php echo htmlspecialchars($book_isbn); ?>">
                                    <?php
                                    // Get the order ID for this book
                                    $order_query = "SELECT o.orderid FROM orders o 
                                                   JOIN order_items oi ON o.orderid = oi.orderid 
                                                   WHERE o.user_id = ? AND oi.book_isbn = ? 
                                                   ORDER BY o.date DESC LIMIT 1";
                                    $stmt = $conn->prepare($order_query);
                                    $stmt->bind_param("is", $user_id, $book_isbn);
                                    $stmt->execute();
                                    $order_result = $stmt->get_result();
                                    if ($order = $order_result->fetch_assoc()) {
                                        echo '<input type="hidden" name="order_id" value="' . htmlspecialchars($order['orderid']) . '">';
                                    }
                                    ?>
                                    <div class="mb-3">
                                        <label class="form-label">Rating</label>
                                        <div class="rating-input d-flex gap-2">
                                            <?php for($i = 5; $i >= 1; $i--): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="rating" value="<?php echo $i; ?>" 
                                                        <?php echo ($user_review && $user_review['rating'] == $i) ? 'checked' : ''; ?> required>
                                                    <label class="form-check-label">
                                                        <i class="fas fa-star text-warning"></i> <?php echo $i; ?>
                                                    </label>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="review_text" class="form-label">Your Review</label>
                                        <textarea class="form-control" name="review_text" id="review_text" rows="3" required><?php 
                                            echo $user_review ? htmlspecialchars($user_review['review_text']) : ''; 
                                        ?></textarea>
                                    </div>
                                    <button type="submit" name="submit_review" class="btn btn-primary">
                                        <?php echo $user_review ? 'Update Review' : 'Submit Review'; ?>
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                You need to purchase this book before you can review it.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Please <a href="login.php">sign in</a> to write a review.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews List -->
    <div class="reviews-list">
        <h3 class="mb-4">Customer Reviews</h3>
        <?php if($reviews && $reviews->num_rows > 0): ?>
            <?php while($review = $reviews->fetch_assoc()): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($review['reviewer_name']); ?></h5>
                                <small class="text-muted"><?php echo $review['review_date']; ?></small>
                            </div>
                            <div class="text-warning">
                                <?php 
                                $rating = intval($review['rating']);
                                for($i = 1; $i <= 5; $i++) {
                                    if($i <= $rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <div class="review-content mt-3">
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-light text-center">
                <p class="mb-0">No reviews yet. Be the first to review this book!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}

.rating-input input {
    display: none;
}

.rating-input label {
    cursor: pointer;
    font-size: 1.5em;
    color: #ddd;
    margin: 0 2px;
}

.rating-input label:hover,
.rating-input label:hover ~ label,
.rating-input input:checked ~ label {
    color: #f8ce0b;
}

.rating-stars {
    color: #f8ce0b;
}

.review-item {
    border-left: 4px solid #007bff;
}

.review-header {
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.review-votes {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #eee;
}
</style>
