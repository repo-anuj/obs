<?php


spl_autoload_register(function ($class){
    $arr=['goods','interfaces','orders','reviews','serve','customer'];
    foreach ($arr as $val) {
        $path=__DIR__."/../$val/$class.php";
        if (file_exists($path))
            require_once $path;
    }
});

class Review {
    private $conn;
    private $user_id;
    private $book_isbn;
    private $rating;
    private $review_text;
    private $reviewer_name;
    private $order_id;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function setUserId($user_id) {
        $this->user_id = $user_id;
    }

    public function setBookIsbn($book_isbn) {
        $this->book_isbn = $book_isbn;
    }

    public function setRating($rating) {
        $this->rating = intval($rating);
    }

    public function setReviewText($review_text) {
        $this->review_text = trim($review_text);
    }

    public function setReviewerName($reviewer_name) {
        $this->reviewer_name = $reviewer_name;
    }

    public function setOrderId($order_id) {
        $this->order_id = $order_id;
    }

    public function canUserReview() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        $query = "SELECT o.orderid 
                 FROM orders o 
                 JOIN order_items oi ON o.orderid = oi.orderid 
                 WHERE o.user_id = ? AND oi.book_isbn = ? 
                 LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("is", $this->user_id, $this->book_isbn);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }

    public function save() {
        if (!$this->canUserReview()) {
            return false;
        }

        try {
            $this->conn->begin_transaction();

            // Check for existing review
            $query = "SELECT review_id FROM reviews WHERE user_id = ? AND book_isbn = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("is", $this->user_id, $this->book_isbn);
            $stmt->execute();
            $existing_review = $stmt->get_result()->fetch_assoc();

            if ($existing_review) {
                // Update existing review
                $query = "UPDATE reviews 
                         SET rating = ?, review_text = ?, reviewer_name = ? 
                         WHERE review_id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("issi", $this->rating, $this->review_text, 
                                $this->reviewer_name, $existing_review['review_id']);
            } else {
                // Insert new review
                $query = "INSERT INTO reviews 
                         (user_id, book_isbn, rating, review_text, reviewer_name, order_id) 
                         VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("isissi", $this->user_id, $this->book_isbn, 
                                $this->rating, $this->review_text, 
                                $this->reviewer_name, $this->order_id);
            }

            if ($stmt->execute()) {
                // Update book's average rating
                $query = "UPDATE books b 
                         SET average_rating = (SELECT AVG(rating) FROM reviews r WHERE r.book_isbn = b.book_isbn),
                             total_reviews = (SELECT COUNT(*) FROM reviews r WHERE r.book_isbn = b.book_isbn)
                         WHERE book_isbn = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("s", $this->book_isbn);
                $stmt->execute();

                $this->conn->commit();
                return true;
            }

            throw new Exception("Error executing review query");
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
}