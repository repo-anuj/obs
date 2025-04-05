<?php
require_once "./functions/review_functions.php";

// Check if user is logged in and get user ID
$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']); // More robust check
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Get reviews for this book, including vote counts
$query = "SELECT r.*, 
                 DATE_FORMAT(r.created_at, '%M %d, %Y') as review_date,
                 COALESCE(SUM(CASE WHEN rv.vote_type = 'helpful' THEN 1 ELSE 0 END), 0) as helpful_votes,
                 COALESCE(SUM(CASE WHEN rv.vote_type = 'unhelpful' THEN 1 ELSE 0 END), 0) as unhelpful_votes,
                 u.name as reviewer_name
          FROM reviews r 
          LEFT JOIN users u ON r.user_id = u.user_id
          LEFT JOIN review_votes rv ON r.review_id = rv.review_id
          WHERE r.book_isbn = ? 
          GROUP BY r.review_id
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
$query_avg = "SELECT AVG(rating) as average_rating, COUNT(*) as total_reviews FROM reviews WHERE book_isbn = ?";
$stmt_avg = $conn->prepare($query_avg);
$stmt_avg->bind_param("s", $book_isbn);
$stmt_avg->execute();
$avg_result = $stmt_avg->get_result()->fetch_assoc();

$avg_rating = number_format($avg_result['average_rating'] ?? 0, 1);
$total_reviews = intval($avg_result['total_reviews'] ?? 0);

// Check if user purchased the book (only if logged in)
$has_purchased = false;
if ($is_logged_in) {
    $has_purchased = hasUserPurchasedBook($conn, $user_id, $book_isbn);
}

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
                    <?php if ($is_logged_in): ?>
                        <?php if ($user_review): // User has already reviewed ?>
                            <div class="alert alert-info">You have already reviewed this book.</div>
                            <!-- Optional: Add an edit button here later -->
                        <?php elseif ($has_purchased): // User is logged in, purchased, but hasn't reviewed ?>
                            <div class="write-review-section">
                                <h5>Write a Review</h5>
                                <form action="process_review.php" method="post" class="mt-3">
                                    <input type="hidden" name="book_isbn" value="<?php echo htmlspecialchars($book_isbn); ?>">
                                    <?php
                                    // Get the most recent order ID for this book and user
                                    $order_query = "SELECT o.orderid FROM orders o 
                                                   JOIN order_items oi ON o.orderid = oi.orderid 
                                                   WHERE o.user_id = ? AND oi.book_isbn = ? 
                                                   ORDER BY o.date DESC LIMIT 1";
                                    $stmt_order = $conn->prepare($order_query);
                                    $stmt_order->bind_param("is", $user_id, $book_isbn);
                                    $stmt_order->execute();
                                    $order_result = $stmt_order->get_result();
                                    if ($order = $order_result->fetch_assoc()) {
                                        echo '<input type="hidden" name="order_id" value="' . htmlspecialchars($order['orderid']) . '">';
                                    }
                                    ?>
                                    <div class="mb-3">
                                        <label class="form-label">Rating</label>
                                        <div class="rating-input">
                                            <?php for($i = 5; $i >= 1; $i--): ?>
                                                <input class="form-check-input visually-hidden" type="radio" name="rating" id="rating<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                                <label class="form-check-label" for="rating<?php echo $i; ?>">
                                                    <i class="fas fa-star"></i>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="review_text" class="form-label">Your Review</label>
                                        <textarea class="form-control" name="review_text" id="review_text" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                                </form>
                            </div>
                        <?php else: // User is logged in but hasn't purchased ?>
                            <div class="alert alert-warning">You need to purchase this book before you can review it.</div>
                        <?php endif; ?>
                    <?php else: // User is not logged in ?>
                        <div class="alert alert-info">Please <a href="login.php">sign in</a> to write a review.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews List -->
    <div class="reviews-list">
        <h3 class="mb-4">Customer Reviews</h3>
        <?php if($reviews->num_rows > 0): ?>
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
                        <?php if (isset($review['helpful_votes']) && isset($review['unhelpful_votes'])): ?>
                        <div class="review-actions mt-2 d-flex align-items-center border-top pt-2">
                            <span class="text-muted me-3 small">Helpful?</span>
                            <button class="btn btn-sm btn-outline-success me-2 vote-btn" data-review-id="<?php echo $review['review_id']; ?>" data-vote-type="helpful">
                                <i class="fas fa-thumbs-up"></i> <span class="vote-count">(<?php echo $review['helpful_votes']; ?>)</span>
                            </button>
                            <button class="btn btn-sm btn-outline-danger me-3 vote-btn" data-review-id="<?php echo $review['review_id']; ?>" data-vote-type="unhelpful">
                                <i class="fas fa-thumbs-down"></i> <span class="vote-count">(<?php echo $review['unhelpful_votes']; ?>)</span>
                            </button>
                            <small class="text-muted vote-feedback ms-auto" data-review-id="<?php echo $review['review_id']; ?>"></small> 
                        </div>
                        <?php else: ?>
                            <!-- Optional: Output a comment or error if votes aren't loaded -->
                            <!-- echo "<!-- Vote counts not available for review ID: " . $review['review_id'] . " -->"; -->
                        <?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const voteButtons = document.querySelectorAll('.vote-btn');

    voteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const reviewId = this.dataset.reviewId;
            const voteType = this.dataset.voteType;
            const feedbackElement = document.querySelector(`.vote-feedback[data-review-id="${reviewId}"]`);

            // Basic check if user is logged in (using the PHP variable)
            <?php if (!$is_logged_in): ?>
                feedbackElement.textContent = 'Please login to vote.';
                feedbackElement.style.color = 'red';
                // Optional: Redirect to login
                // window.location.href = 'login.php';
                return; 
            <?php endif; ?>

            // Send vote via Fetch API (AJAX)
            fetch('process_vote.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `review_id=${reviewId}&vote_type=${voteType}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    feedbackElement.textContent = 'Vote recorded!';
                    feedbackElement.style.color = 'green';
                    // Optionally update vote counts dynamically if needed
                    // You might need to refresh the page or update counts via JS
                    // Example: Update the button text
                    if (data.newCounts) {
                        const helpfulBtn = document.querySelector(`.vote-btn[data-review-id="${reviewId}"][data-vote-type="helpful"]`);
                        const unhelpfulBtn = document.querySelector(`.vote-btn[data-review-id="${reviewId}"][data-vote-type="unhelpful"]`);
                        if(helpfulBtn && unhelpfulBtn) {
                            const helpfulCountSpan = helpfulBtn.querySelector('.vote-count');
                            const unhelpfulCountSpan = unhelpfulBtn.querySelector('.vote-count');
                            helpfulCountSpan.textContent = `(${data.newCounts.helpful_votes})`;
                            unhelpfulCountSpan.textContent = `(${data.newCounts.unhelpful_votes})`;
                        }
                    }
                } else {
                    feedbackElement.textContent = data.message || 'Error recording vote.';
                    feedbackElement.style.color = 'red';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                feedbackElement.textContent = 'Request failed. Please try again.';
                feedbackElement.style.color = 'red';
            });
        });
    });
});
</script>

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
