<?php
session_start();
require_once "./functions/database_functions.php";
require_once "./functions/review_functions.php";

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login to vote on reviews']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = db_connect();
    $user_id = $_SESSION['user']['user_id'];
    $review_id = intval($_POST['review_id']);
    $vote_type = $_POST['vote_type'];
    
    // Validate vote type
    if (!in_array($vote_type, ['helpful', 'unhelpful'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid vote type']);
        exit;
    }

    // Check if user has already voted on this review
    $query = "SELECT vote_type FROM review_votes WHERE review_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $review_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $existing_vote = $result->fetch_assoc();
        if ($existing_vote['vote_type'] === $vote_type) {
            // Remove the vote if clicking the same button again
            $query = "DELETE FROM review_votes WHERE review_id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $review_id, $user_id);
            $stmt->execute();
            
            $message = "Vote removed";
        } else {
            // Update the vote if changing from helpful to unhelpful or vice versa
            $query = "UPDATE review_votes SET vote_type = ? WHERE review_id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sii", $vote_type, $review_id, $user_id);
            $stmt->execute();
            
            $message = "Vote updated";
        }
    } else {
        // Add new vote
        $query = "INSERT INTO review_votes (review_id, user_id, vote_type) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iis", $review_id, $user_id, $vote_type);
        $stmt->execute();
        
        $message = "Vote recorded";
    }

    // Get updated vote counts
    $query = "SELECT 
        SUM(CASE WHEN vote_type = 'helpful' THEN 1 ELSE 0 END) as helpful_count,
        SUM(CASE WHEN vote_type = 'unhelpful' THEN 1 ELSE 0 END) as unhelpful_count
        FROM review_votes 
        WHERE review_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $review_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $counts = $result->fetch_assoc();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $message,
        'helpful_count' => (int)$counts['helpful_count'],
        'unhelpful_count' => (int)$counts['unhelpful_count']
    ]);
    exit;
}

// If not POST request
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit;
?>
