<?php
require_once "./functions/database_functions.php";
$conn = db_connect();

// First, let's check the structure of books and customers tables
echo "Checking table structures:\n\n";

$tables = ['books', 'customers'];
foreach ($tables as $table) {
    $result = $conn->query("DESCRIBE `$table`");
    if ($result) {
        echo "Structure of $table table:\n";
        while ($row = $result->fetch_assoc()) {
            echo "{$row['Field']} - {$row['Type']} - {$row['Key']}\n";
        }
        echo "\n";
    } else {
        echo "Error checking $table structure: " . $conn->error . "\n\n";
    }
}

// Now try to create just the reviews table
$sql = "CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `book_isbn` varchar(20) COLLATE latin1_general_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL,
  `review_text` text COLLATE latin1_general_ci,
  `created_at` datetime NOT NULL DEFAULT current_timestamp,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp ON UPDATE current_timestamp,
  PRIMARY KEY (`review_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci";

if ($conn->query($sql)) {
    echo "Basic reviews table created successfully\n";
    
    // Now try to add the foreign key constraints one by one
    $constraints = [
        "ALTER TABLE `reviews` ADD UNIQUE KEY `unique_user_book_review` (`book_isbn`, `user_id`)",
        "ALTER TABLE `reviews` ADD CONSTRAINT `reviews_book_fk` FOREIGN KEY (`book_isbn`) REFERENCES `books` (`book_isbn`) ON DELETE CASCADE ON UPDATE CASCADE",
        "ALTER TABLE `reviews` ADD CONSTRAINT `reviews_user_fk` FOREIGN KEY (`user_id`) REFERENCES `customers` (`customerid`) ON DELETE CASCADE ON UPDATE CASCADE",
        "ALTER TABLE `reviews` ADD CONSTRAINT `check_rating` CHECK (rating >= 1 AND rating <= 5)"
    ];
    
    foreach ($constraints as $constraint) {
        if ($conn->query($constraint)) {
            echo "Successfully added constraint: " . substr($constraint, 0, 50) . "...\n";
        } else {
            echo "Error adding constraint: " . $conn->error . "\n";
            echo "Failed constraint was: " . $constraint . "\n";
        }
    }
} else {
    echo "Error creating basic reviews table: " . $conn->error . "\n";
}

$conn->close();
?>
