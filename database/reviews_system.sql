-- Create reviews table
DROP TABLE IF EXISTS `reviews`;

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `book_isbn` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reviewer_name` varchar(100) NOT NULL,
  `rating` int(1) NOT NULL,
  `review_text` text NOT NULL,
  `order_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  UNIQUE KEY `unique_user_book_review` (`book_isbn`, `user_id`),
  FOREIGN KEY (`book_isbn`) REFERENCES `books` (`book_isbn`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `customers` (`customerid`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`orderid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Create review votes table
CREATE TABLE IF NOT EXISTS `review_votes` (
  `vote_id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vote_type` enum('helpful', 'unhelpful') COLLATE latin1_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`vote_id`),
  UNIQUE KEY `unique_user_review_vote` (`review_id`, `user_id`),
  FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `customers` (`customerid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Add average_rating column to books table
ALTER TABLE `books`
ADD COLUMN `average_rating` DECIMAL(3,2) DEFAULT NULL,
ADD COLUMN `total_reviews` int(11) NOT NULL DEFAULT 0;

-- Create trigger to update book average rating after review insert
DELIMITER //
CREATE TRIGGER after_review_insert 
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
END//

-- Create trigger to update book average rating after review update
CREATE TRIGGER after_review_update
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
END//

-- Create trigger to update book average rating after review delete
CREATE TRIGGER after_review_delete
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
END//
DELIMITER ;
