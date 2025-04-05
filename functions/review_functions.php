<?php
/**
 * Review and rating functions
 */

/**
 * Add or update a review
 */
function addOrUpdateReview($conn, $book_isbn, $user_id, $rating, $review_text) {
    $sql = "INSERT INTO reviews (book_isbn, user_id, rating, review_text) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            rating = VALUES(rating),
            review_text = VALUES(review_text),
            updated_at = CURRENT_TIMESTAMP";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('siis', $book_isbn, $user_id, $rating, $review_text);
    return $stmt->execute();
}

/**
 * Get reviews for a book
 */
function getBookReviews($conn, $book_isbn, $limit = 10, $offset = 0) {
    $sql = "SELECT r.*, c.name as reviewer_name,
            (SELECT COUNT(*) FROM review_votes rv WHERE rv.review_id = r.review_id AND rv.vote_type = 'helpful') as helpful_votes,
            (SELECT COUNT(*) FROM review_votes rv WHERE rv.review_id = r.review_id AND rv.vote_type = 'unhelpful') as unhelpful_votes
            FROM reviews r
            JOIN customers c ON r.user_id = c.customerid
            WHERE r.book_isbn = ?
            ORDER BY helpful_votes DESC, r.created_at DESC
            LIMIT ? OFFSET ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sii', $book_isbn, $limit, $offset);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    
    return $reviews;
}

/**
 * Get a user's review for a book
 */
function getUserReview($conn, $book_isbn, $user_id) {
    $sql = "SELECT * FROM reviews WHERE book_isbn = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $book_isbn, $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Vote on a review
 */
function voteReview($conn, $review_id, $user_id, $vote_type) {
    $sql = "INSERT INTO review_votes (review_id, user_id, vote_type) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            vote_type = VALUES(vote_type)";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iis', $review_id, $user_id, $vote_type);
    return $stmt->execute();
}

/**
 * Get user's vote on a review
 */
function getUserVote($conn, $review_id, $user_id) {
    $sql = "SELECT vote_type FROM review_votes WHERE review_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $review_id, $user_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['vote_type'] : null;
}

/**
 * Check if user has purchased the book
 */
function hasUserPurchasedBook($conn, $user_id, $book_isbn) {
    $sql = "SELECT COUNT(*) as count 
            FROM orders o 
            JOIN order_items oi ON o.orderid = oi.orderid
            WHERE o.user_id = ? AND oi.book_isbn = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $user_id, $book_isbn);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}

/**
 * Get average rating for a book
 */
function getBookRating($conn, $book_isbn) {
    $sql = "SELECT average_rating, total_reviews FROM books WHERE book_isbn = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $book_isbn);
    $stmt->execute();
    
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
?>
