-- Add tags system and enhance search capabilities

-- Create tags table
CREATE TABLE IF NOT EXISTS `tags` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(50) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `tag_name` (`tag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Create book_tags junction table for many-to-many relationship
CREATE TABLE IF NOT EXISTS `book_tags` (
  `book_isbn` varchar(20) COLLATE latin1_general_ci NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`book_isbn`, `tag_id`),
  FOREIGN KEY (`book_isbn`) REFERENCES `books` (`book_isbn`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `tags` (`tag_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Add categories table for better organization
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- Add category_id column to books table if not exists
ALTER TABLE `books` 
ADD COLUMN IF NOT EXISTS `category_id` int(11) DEFAULT NULL AFTER `publisherid`,
ADD CONSTRAINT `fk_books_categories` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Insert some sample categories
INSERT INTO `categories` (`category_name`) VALUES 
('Programming'), 
('Web Development'), 
('Fiction'), 
('Business'), 
('Science'), 
('Self-Help'),
('Biography');

-- Insert some sample tags
INSERT INTO `tags` (`tag_name`) VALUES 
('JavaScript'), 
('PHP'), 
('MySQL'), 
('Mobile'), 
('Android'), 
('iOS'), 
('Design Patterns'),
('Beginner'),
('Advanced'),
('Best Seller');

-- Add search_index column to books table for better search performance
ALTER TABLE `books` 
ADD COLUMN IF NOT EXISTS `search_index` TEXT AFTER `created_at`,
ADD FULLTEXT INDEX IF NOT EXISTS `idx_books_search` (`search_index`);

-- Update trigger to maintain search index
DELIMITER //
CREATE TRIGGER IF NOT EXISTS `books_before_insert` BEFORE INSERT ON `books`
FOR EACH ROW
BEGIN
    SET NEW.search_index = CONCAT_WS(' ', NEW.book_title, NEW.book_author, NEW.book_isbn, NEW.book_descr);
END//

CREATE TRIGGER IF NOT EXISTS `books_before_update` BEFORE UPDATE ON `books`
FOR EACH ROW
BEGIN
    SET NEW.search_index = CONCAT_WS(' ', NEW.book_title, NEW.book_author, NEW.book_isbn, NEW.book_descr);
END//
DELIMITER ;

-- Update existing books to populate search_index
UPDATE `books` SET search_index = CONCAT_WS(' ', book_title, book_author, book_isbn, book_descr);
