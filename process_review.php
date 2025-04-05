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

    // -- DEBUGGING START --
    error_log("Processing Review:");
    error_log("User ID: " . $user_id);
    error_log("Book ISBN: " . $book_isbn);
    error_log("Rating: " . $rating);
    error_log("Review Text: " . $review_text);
    error_log("Reviewer Name: " . $reviewer_name);
    error_log("Has Purchased Check: " . (hasUserPurchasedBook($conn, $user_id, $book_isbn) ? 'Yes' : 'No'));
    // -- DEBUGGING END --

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
                     SET rating = ?, review_text = ? 
                     WHERE review_id = ?";
            $stmt_update = $conn->prepare($query);
            $stmt_update->bind_param("isi", $rating, $review_text, $existing_review['review_id']);
        } else {
            // Insert new review
            $query = "INSERT INTO reviews 
                     (user_id, book_isbn, rating, review_text) 
                     VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($query);
            $stmt_insert->bind_param("isis", $user_id, $book_isbn, $rating, $review_text);
        }

        if ($existing_review) {
            $stmt_update->execute();
        } else {
            $stmt_insert->execute();
        }

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
    } catch (Exception $e) {
        $conn->rollback();
        // Log the specific database error
        error_log("Review Save Error: " . $e->getMessage()); 
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
