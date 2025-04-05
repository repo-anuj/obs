<?php
/**
 * Autocomplete Search Suggestions API
 * Returns JSON array of search suggestions based on query
 */

// Set content type to JSON
header('Content-Type: application/json');

// Required files 
require_once "./functions/database_functions.php";
require_once "./functions/search_functions.php";

// Get query parameter
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Return empty array if query is too short
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// Get database connection
$conn = db_connect();

// Get suggestions
$suggestions = getSearchSuggestions($conn, $query);

// Close connection
if(isset($conn)) { mysqli_close($conn); }

// Return JSON response
echo json_encode($suggestions);
?>
