<?php
session_start();
require_once "./functions/database_functions.php";
require_once "./functions/review_functions.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_isbn'])) {
    $conn = db_connect();
    $user_id = $_SESSION['user_id'];
    $book_isbn = $_POST['book_isbn'];
    $rating = intval($_POST['rating']);
    $review_text = trim($_POST['review_text']);

    // Validate inputs
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = "Invalid rating value.";
        header("Location: book.php?bookisbn=" . urlencode($book_isbn));
        exit;
    }

    if (empty($review_text)) {
        $_SESSION['error'] = "Review text cannot be empty.";
        header("Location: book.php?bookisbn=" . urlencode($book_isbn));
        exit;
    }

    // Check if user has purchased the book
    if (!hasUserPurchasedBook($conn, $user_id, $book_isbn)) {
        $_SESSION['error'] = "You can only review books you have purchased.";
        header("Location: book.php?bookisbn=" . urlencode($book_isbn));
        exit;
    }

    // Get user's name
    $query = "SELECT name FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $reviewer_name = $user['name'];

    // Get order ID
    $query = "SELECT o.orderid 
             FROM orders o 
             JOIN order_items oi ON o.orderid = oi.orderid 
             WHERE o.user_id = ? AND oi.book_isbn = ? 
             ORDER BY o.date DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $book_isbn);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $order_id = $order['orderid'];

    try {
        $conn->begin_transaction();

        // Check for existing review
        $query = "SELECT review_id FROM reviews WHERE user_id = ? AND book_isbn = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $user_id, $book_isbn);
        $stmt->execute();
        $existing_review = $stmt->get_result()->fetch_assoc();

        if ($existing_review) {
            // Update existing review
            $query = "UPDATE reviews 
                     SET rating = ?, review_text = ?, reviewer_name = ? 
                     WHERE review_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("issi", $rating, $review_text, $reviewer_name, $existing_review['review_id']);
        } else {
            // Insert new review
            $query = "INSERT INTO reviews 
                     (user_id, book_isbn, rating, review_text, reviewer_name, order_id) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isissi", $user_id, $book_isbn, $rating, $review_text, $reviewer_name, $order_id);
        }

        if ($stmt->execute()) {
            // Update book's average rating
            $query = "UPDATE books b 
                     SET average_rating = (SELECT AVG(rating) FROM reviews r WHERE r.book_isbn = b.book_isbn),
                         total_reviews = (SELECT COUNT(*) FROM reviews r WHERE r.book_isbn = b.book_isbn)
                     WHERE book_isbn = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $book_isbn);
            $stmt->execute();

            $conn->commit();
            $_SESSION['success'] = $existing_review ? "Review updated successfully!" : "Review submitted successfully!";
        } else {
            throw new Exception("Error executing review query");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error " . ($existing_review ? "updating" : "submitting") . " review. Please try again.";
    }

    mysqli_close($conn);
    header("Location: book.php?bookisbn=" . urlencode($book_isbn));
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>
