<?php
require_once "./functions/database_functions.php";
$conn = db_connect();

$tables = ['books', 'customers'];

foreach ($tables as $table) {
    $query = "SHOW CREATE TABLE $table";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Table structure for $table:\n";
        echo $row['Create Table'] . "\n\n";
    } else {
        echo "Error getting structure for $table: " . $conn->error . "\n";
    }
}

$conn->close();
?>
