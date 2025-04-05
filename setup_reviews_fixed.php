<?php
require_once "./functions/database_functions.php";
$conn = db_connect();

// Drop existing tables if they exist
$conn->query("DROP TABLE IF EXISTS review_votes");
$conn->query("DROP TABLE IF EXISTS reviews");

// Step 1: Add columns to books table
$sql = "ALTER TABLE `books`
ADD COLUMN IF NOT EXISTS `average_rating` DECIMAL(3,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `total_reviews` int(11) NOT NULL DEFAULT 0";

if ($conn->query($sql)) {
    echo "Successfully added rating columns to books table<br>";
} else {
    echo "Error adding rating columns: " . $conn->error . "<br>";
}

// Step 2: Create reviews table with correct data types
$sql = "CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `book_isbn` varchar(20) COLLATE latin1_general_ci NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `rating` int(1) NOT NULL,
  `review_text` text COLLATE latin1_general_ci,
  `created_at` datetime NOT NULL DEFAULT current_timestamp,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp ON UPDATE current_timestamp,
  PRIMARY KEY (`review_id`),
  UNIQUE KEY `unique_user_book_review` (`book_isbn`, `user_id`),
  CONSTRAINT `reviews_book_fk` FOREIGN KEY (`book_isbn`) REFERENCES `books` (`book_isbn`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `reviews_user_fk` FOREIGN KEY (`user_id`) REFERENCES `customers` (`customerid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `check_rating` CHECK (rating >= 1 AND rating <= 5)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci";

if ($conn->query($sql)) {
    echo "Successfully created reviews table<br>";
} else {
    echo "Error creating reviews table: " . $conn->error . "<br>";
}

// Step 3: Create review votes table
$sql = "CREATE TABLE IF NOT EXISTS `review_votes` (
  `vote_id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `vote_type` enum('helpful', 'unhelpful') COLLATE latin1_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`vote_id`),
  UNIQUE KEY `unique_user_review_vote` (`review_id`, `user_id`),
  CONSTRAINT `votes_review_fk` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `votes_user_fk` FOREIGN KEY (`user_id`) REFERENCES `customers` (`customerid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci";

if ($conn->query($sql)) {
    echo "Successfully created review_votes table<br>";
} else {
    echo "Error creating review_votes table: " . $conn->error . "<br>";
}

// Step 4: Create triggers
$sql = "DROP TRIGGER IF EXISTS after_review_insert";
$conn->query($sql);

$sql = "CREATE TRIGGER after_review_insert 
AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    UPDATE books b
    SET 
        b.average_rating = (
            SELECT AVG(rating)
            FROM reviews
            WHERE book_isbn = NEW.book_isbn
        ),
        b.total_reviews = (
            SELECT COUNT(*)
            FROM reviews
            WHERE book_isbn = NEW.book_isbn
        )
    WHERE b.book_isbn = NEW.book_isbn;
END";

if ($conn->query($sql)) {
    echo "Successfully created after_review_insert trigger<br>";
} else {
    echo "Error creating after_review_insert trigger: " . $conn->error . "<br>";
}

$sql = "DROP TRIGGER IF EXISTS after_review_update";
$conn->query($sql);

$sql = "CREATE TRIGGER after_review_update
AFTER UPDATE ON reviews
FOR EACH ROW
BEGIN
    UPDATE books b
    SET 
        b.average_rating = (
            SELECT AVG(rating)
            FROM reviews
            WHERE book_isbn = NEW.book_isbn
        ),
        b.total_reviews = (
            SELECT COUNT(*)
            FROM reviews
            WHERE book_isbn = NEW.book_isbn
        )
    WHERE b.book_isbn = NEW.book_isbn;
END";

if ($conn->query($sql)) {
    echo "Successfully created after_review_update trigger<br>";
} else {
    echo "Error creating after_review_update trigger: " . $conn->error . "<br>";
}

$sql = "DROP TRIGGER IF EXISTS after_review_delete";
$conn->query($sql);

$sql = "CREATE TRIGGER after_review_delete
AFTER DELETE ON reviews
FOR EACH ROW
BEGIN
    UPDATE books b
    SET 
        b.average_rating = (
            SELECT AVG(rating)
            FROM reviews
            WHERE book_isbn = OLD.book_isbn
        ),
        b.total_reviews = (
            SELECT COUNT(*)
            FROM reviews
            WHERE book_isbn = OLD.book_isbn
        )
    WHERE b.book_isbn = OLD.book_isbn;
END";

if ($conn->query($sql)) {
    echo "Successfully created after_review_delete trigger<br>";
} else {
    echo "Error creating after_review_delete trigger: " . $conn->error . "<br>";
}

$conn->close();
echo "<br>Setup complete!<br>";
?>
