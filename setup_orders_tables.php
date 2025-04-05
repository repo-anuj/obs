<?php
require_once "./functions/database_functions.php";
$conn = db_connect();

// Drop existing tables if they exist
$conn->query("DROP TABLE IF EXISTS order_items");
$conn->query("DROP TABLE IF EXISTS orders");

// Create orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    orderid INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    ship_name VARCHAR(100) NOT NULL,
    ship_address VARCHAR(255) NOT NULL,
    ship_city VARCHAR(100) NOT NULL,
    ship_zip_code VARCHAR(20) NOT NULL,
    ship_country VARCHAR(100) NOT NULL,
    ship_phone VARCHAR(20) NOT NULL,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Processing', 'Shipped', 'Delivered') DEFAULT 'Processing',
    FOREIGN KEY (user_id) REFERENCES customers(customerid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci";

if ($conn->query($sql)) {
    echo "Orders table created successfully<br>";
} else {
    echo "Error creating orders table: " . $conn->error . "<br>";
}

// Create order_items table
$sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    orderid INT NOT NULL,
    book_isbn VARCHAR(20) NOT NULL,
    item_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (orderid) REFERENCES orders(orderid) ON DELETE CASCADE,
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
