<?php
require_once "./functions/database_functions.php";
$conn = db_connect();

// Create reviews table
$sql = "CREATE TABLE IF NOT EXISTS reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    book_isbn VARCHAR(20) NOT NULL,
    review TEXT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (book_isbn) REFERENCES books(book_isbn) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci";

if ($conn->query($sql)) {
    echo "Reviews table created successfully<br>";
} else {
    echo "Error creating reviews table: " . $conn->error . "<br>";
}

$conn->close();
echo "Setup complete!";
?>
