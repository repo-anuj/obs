<?php
require_once('functions/database_functions.php');

$conn = db_connect();

// Create tags table
$sql = "CREATE TABLE IF NOT EXISTS `tags` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(50) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `tag_name` (`tag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Tags table created successfully\n";
} else {
    echo "Error creating tags table: " . $conn->error . "\n";
}

// Create categories table
$sql = "CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Categories table created successfully\n";
} else {
    echo "Error creating categories table: " . $conn->error . "\n";
}

// Create book_tags table
$sql = "CREATE TABLE IF NOT EXISTS `book_tags` (
  `book_isbn` varchar(20) COLLATE latin1_general_ci NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`book_isbn`, `tag_id`),
  FOREIGN KEY (`book_isbn`) REFERENCES `books` (`book_isbn`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `tags` (`tag_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Book_tags table created successfully\n";
} else {
    echo "Error creating book_tags table: " . $conn->error . "\n";
}

// Add category_id column to books table
$sql = "ALTER TABLE `books` 
ADD COLUMN IF NOT EXISTS `category_id` int(11) DEFAULT NULL AFTER `publisherid`,
ADD CONSTRAINT `fk_books_categories` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE;";

if ($conn->query($sql) === TRUE) {
    echo "Category_id column added to books table successfully\n";
} else {
    echo "Error adding category_id column: " . $conn->error . "\n";
}

// Insert sample categories
$sql = "INSERT INTO `categories` (`category_name`) VALUES 
('Programming'), 
('Web Development'), 
('Fiction'), 
('Business'), 
('Science'), 
('Self-Help'),
('Biography');";

if ($conn->query($sql) === TRUE) {
    echo "Sample categories inserted successfully\n";
} else {
    echo "Error inserting sample categories: " . $conn->error . "\n";
}

// Insert sample tags
$sql = "INSERT INTO `tags` (`tag_name`) VALUES 
('JavaScript'), 
('PHP'), 
('MySQL'), 
('Mobile'), 
('Android'), 
('iOS'), 
('Design Patterns'),
('Beginner'),
('Advanced'),
('Best Seller');";

if ($conn->query($sql) === TRUE) {
    echo "Sample tags inserted successfully\n";
} else {
    echo "Error inserting sample tags: " . $conn->error . "\n";
}

$conn->close();
?>
