<?php
require_once "./functions/database_functions.php";
$conn = db_connect();

// Step 1: Add columns to books table
$sql = "ALTER TABLE `books`
ADD COLUMN IF NOT EXISTS `average_rating` DECIMAL(3,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `total_reviews` int(11) NOT NULL DEFAULT 0";

if ($conn->query($sql)) {
    echo "Successfully added rating columns to books table\n";
} else {
    echo "Error adding rating columns: " . $conn->error . "\n";
}

// Step 2: Create reviews table
$sql = "CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `book_isbn` varchar(20) COLLATE latin1_general_ci NOT NULL,
  `user_id` int(11) NOT NULL,
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
    echo "Successfully created reviews table\n";
} else {
    echo "Error creating reviews table: " . $conn->error . "\n";
}

// Step 3: Create review votes table
$sql = "CREATE TABLE IF NOT EXISTS `review_votes` (
  `vote_id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vote_type` enum('helpful', 'unhelpful') COLLATE latin1_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`vote_id`),
  UNIQUE KEY `unique_user_review_vote` (`review_id`, `user_id`),
  CONSTRAINT `votes_review_fk` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `votes_user_fk` FOREIGN KEY (`user_id`) REFERENCES `customers` (`customerid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci";

if ($conn->query($sql)) {
    echo "Successfully created review_votes table\n";
} else {
    echo "Error creating review_votes table: " . $conn->error . "\n";
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
    echo "Successfully created after_review_insert trigger\n";
} else {
    echo "Error creating after_review_insert trigger: " . $conn->error . "\n";
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
    echo "Successfully created after_review_update trigger\n";
} else {
    echo "Error creating after_review_update trigger: " . $conn->error . "\n";
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
    echo "Successfully created after_review_delete trigger\n";
} else {
    echo "Error creating after_review_delete trigger: " . $conn->error . "\n";
}

$conn->close();
echo "\nSetup complete!\n";
?>
