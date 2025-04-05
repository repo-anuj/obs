<?php
require_once "./functions/database_functions.php";
$conn = db_connect();

// Create orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_zip_code VARCHAR(20) NOT NULL,
    shipping_country VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    order_status ENUM('Processing', 'Shipped', 'Delivered') DEFAULT 'Processing',
    FOREIGN KEY (user_id) REFERENCES customers(customerid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci";

if ($conn->query($sql)) {
    echo "Orders table created successfully<br>";
} else {
    echo "Error creating orders table: " . $conn->error . "<br>";
}

// Create order_items table
$sql = "CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    book_isbn VARCHAR(20) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (book_isbn) REFERENCES books(book_isbn) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci";

if ($conn->query($sql)) {
    echo "Order items table created successfully<br>";
} else {
    echo "Error creating order items table: " . $conn->error . "<br>";
}

$conn->close();
echo "Setup complete!";
?>
