<?php
require_once('functions/database_functions.php');

$conn = db_connect();

// Add search_index column to books table
$sql = "ALTER TABLE `books` 
ADD COLUMN IF NOT EXISTS `search_index` TEXT,
ADD FULLTEXT INDEX IF NOT EXISTS `idx_books_search` (`search_index`);";

if ($conn->query($sql) === TRUE) {
    echo "Search index column added successfully\n";
} else {
    echo "Error adding search index column: " . $conn->error . "\n";
}

// Update existing books to populate search_index
$sql = "UPDATE `books` SET search_index = CONCAT_WS(' ', 
    book_title, 
    book_author, 
    book_isbn, 
    book_descr
)";

if ($conn->query($sql) === TRUE) {
    echo "Search index populated successfully\n";
} else {
    echo "Error populating search index: " . $conn->error . "\n";
}

// Create triggers to maintain search index
$sql = "DROP TRIGGER IF EXISTS `books_before_insert`";
$conn->query($sql);

$sql = "CREATE TRIGGER `books_before_insert` BEFORE INSERT ON `books`
FOR EACH ROW
BEGIN
    SET NEW.search_index = CONCAT_WS(' ', 
        NEW.book_title, 
        NEW.book_author, 
        NEW.book_isbn, 
        NEW.book_descr
    );
END;";

if ($conn->query($sql) === TRUE) {
    echo "Insert trigger created successfully\n";
} else {
    echo "Error creating insert trigger: " . $conn->error . "\n";
}

$sql = "DROP TRIGGER IF EXISTS `books_before_update`";
$conn->query($sql);

$sql = "CREATE TRIGGER `books_before_update` BEFORE UPDATE ON `books`
FOR EACH ROW
BEGIN
    SET NEW.search_index = CONCAT_WS(' ', 
        NEW.book_title, 
        NEW.book_author, 
        NEW.book_isbn, 
        NEW.book_descr
    );
END;";

if ($conn->query($sql) === TRUE) {
    echo "Update trigger created successfully\n";
} else {
    echo "Error creating update trigger: " . $conn->error . "\n";
}

$conn->close();

echo "Done updating books table and creating triggers.\n";
?>
