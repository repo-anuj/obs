<?php
/**
 * Advanced search functions for the online book store
 */

/**
 * Get books by advanced search criteria
 * 
 * @param object $conn Database connection
 * @param string $query Search query
 * @param array $filters Optional filters (category, tags, price_min, price_max, date_from, date_to)
 * @param int $limit Maximum number of results to return
 * @param int $offset Offset for pagination
 * @return array Books matching the search criteria
 */
function getBooksByAdvancedSearch($conn, $query = '', $filters = [], $limit = 20, $offset = 0) {
    $whereClause = [];
    $params = [];
    $types = '';
    
    // Base query
    $sql = "SELECT DISTINCT b.book_isbn, b.book_title, b.book_author, b.book_image, 
            b.book_price, b.book_descr, b.publisherid, b.created_at, p.publisher_name,
            c.category_name, GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') as tags
            FROM books b
            LEFT JOIN publisher p ON b.publisherid = p.publisherid
            LEFT JOIN categories c ON b.category_id = c.category_id
            LEFT JOIN book_tags bt ON b.book_isbn = bt.book_isbn
            LEFT JOIN tags t ON bt.tag_id = t.tag_id";
    
    // Search query
    if (!empty($query)) {
        $whereClause[] = "(b.book_title LIKE ? 
                         OR b.book_author LIKE ? 
                         OR b.book_isbn LIKE ? 
                         OR b.book_descr LIKE ?)";
        $likeParam = "%$query%";
        $params[] = $likeParam;
        $params[] = $likeParam;
        $params[] = $likeParam;
        $params[] = $likeParam;
        $types .= 'ssss';
    }
    
    // Category filter
    if (!empty($filters['category'])) {
        $whereClause[] = "c.category_name = ?";
        $params[] = $filters['category'];
        $types .= 's';
    }
    
    // Tags filter (search for books that have ALL the selected tags)
    if (!empty($filters['tags']) && is_array($filters['tags'])) {
        $tagCount = count($filters['tags']);
        $tagPlaceholders = implode(',', array_fill(0, $tagCount, '?'));
        $whereClause[] = "b.book_isbn IN (
            SELECT bt.book_isbn 
            FROM book_tags bt 
            JOIN tags t ON bt.tag_id = t.tag_id 
            WHERE t.tag_name IN ($tagPlaceholders)
            GROUP BY bt.book_isbn 
            HAVING COUNT(DISTINCT t.tag_id) = $tagCount
        )";
        foreach ($filters['tags'] as $tag) {
            $params[] = $tag;
            $types .= 's';
        }
    }
    
    // Price range filter
    if (!empty($filters['price_min'])) {
        $whereClause[] = "b.book_price >= ?";
        $params[] = $filters['price_min'];
        $types .= 'd';
    }
    
    if (!empty($filters['price_max'])) {
        $whereClause[] = "b.book_price <= ?";
        $params[] = $filters['price_max'];
        $types .= 'd';
    }
    
    // Publication date filter
    if (!empty($filters['date_from'])) {
        $whereClause[] = "b.created_at >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $whereClause[] = "b.created_at <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    // Combine where clauses
    if (!empty($whereClause)) {
        $sql .= " WHERE " . implode(" AND ", $whereClause);
    }
    
    // Group by to handle the GROUP_CONCAT for tags
    $sql .= " GROUP BY b.book_isbn";
    
    // Sorting
    if (!empty($filters['sort'])) {
        switch ($filters['sort']) {
            case 'price_asc':
                $sql .= " ORDER BY b.book_price ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY b.book_price DESC";
                break;
            case 'newest':
                $sql .= " ORDER BY b.created_at DESC";
                break;
            case 'title_asc':
                $sql .= " ORDER BY b.book_title ASC";
                break;
            default:
                $sql .= " ORDER BY b.book_title ASC";
        }
    } else {
        $sql .= " ORDER BY b.book_title ASC";
    }
    
    // Add limit and offset
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    // Prepare and execute query
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $books = [];
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
    
    return $books;
}

/**
 * Get autocomplete suggestions for book search
 * 
 * @param object $conn Database connection
 * @param string $query Search query to get suggestions for
 * @param int $limit Maximum number of suggestions to return
 * @return array Suggestions matching the query
 */
function getSearchSuggestions($conn, $query, $limit = 8) {
    if (strlen($query) < 2) {
        return [];
    }
    
    $suggestions = [];
    $likeParam = "%$query%";
    
    // Get book titles
    $sql = "SELECT DISTINCT book_title as text, 'title' as type 
            FROM books 
            WHERE book_title LIKE ? 
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $likeParam, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row;
    }
    
    // Get authors if we still have room
    $remaining = $limit - count($suggestions);
    if ($remaining > 0) {
        $sql = "SELECT DISTINCT book_author as text, 'author' as type 
                FROM books 
                WHERE book_author LIKE ? 
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $likeParam, $remaining);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $suggestions[] = $row;
        }
    }
    
    // Get tags if we still have room
    $remaining = $limit - count($suggestions);
    if ($remaining > 0) {
        $sql = "SELECT DISTINCT tag_name as text, 'tag' as type 
                FROM tags 
                WHERE tag_name LIKE ? 
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $likeParam, $remaining);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $suggestions[] = $row;
        }
    }
    
    return $suggestions;
}

/**
 * Get all categories
 */
function getAllCategories($conn) {
    $sql = "SELECT * FROM categories ORDER BY category_name";
    $result = $conn->query($sql);
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    return $categories;
}

/**
 * Get all tags
 */
function getAllTags($conn) {
    $sql = "SELECT * FROM tags ORDER BY tag_name";
    $result = $conn->query($sql);
    
    $tags = [];
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    
    return $tags;
}

/**
 * Get tags for a specific book
 */
function getBookTags($conn, $book_isbn) {
    $sql = "SELECT t.* 
            FROM tags t
            JOIN book_tags bt ON t.tag_id = bt.tag_id
            WHERE bt.book_isbn = ?
            ORDER BY t.tag_name";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $book_isbn);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $tags = [];
    
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
    
    return $tags;
}

/**
 * Add or update tags for a book
 */
function updateBookTags($conn, $book_isbn, $tag_ids) {
    // First, remove all existing tags for this book
    $sql = "DELETE FROM book_tags WHERE book_isbn = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $book_isbn);
    $stmt->execute();
    
    // Now add the new tags
    if (!empty($tag_ids)) {
        $values = [];
        $params = [$book_isbn];
        $types = 's';
        
        foreach ($tag_ids as $tag_id) {
            $values[] = "(?, ?)";
            $params[] = $tag_id;
            $types .= 'i';
        }
        
        $sql = "INSERT INTO book_tags (book_isbn, tag_id) VALUES " . implode(', ', $values);
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }
    
    return true;
}

/**
 * Create a new tag
 */
function createTag($conn, $tag_name) {
    $sql = "INSERT INTO tags (tag_name) VALUES (?) ON DUPLICATE KEY UPDATE tag_id = LAST_INSERT_ID(tag_id)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $tag_name);
    $stmt->execute();
    
    return $conn->insert_id;
}

/**
 * Count total results for pagination
 */
function countSearchResults($conn, $query = '', $filters = []) {
    $whereClause = [];
    $params = [];
    $types = '';
    
    // Base query
    $sql = "SELECT COUNT(DISTINCT b.book_isbn) as total
            FROM books b
            LEFT JOIN publisher p ON b.publisherid = p.publisherid
            LEFT JOIN categories c ON b.category_id = c.category_id
            LEFT JOIN book_tags bt ON b.book_isbn = bt.book_isbn
            LEFT JOIN tags t ON bt.tag_id = t.tag_id";
    
    // Search query
    if (!empty($query)) {
        $whereClause[] = "(b.book_title LIKE ? 
                         OR b.book_author LIKE ? 
                         OR b.book_isbn LIKE ? 
                         OR b.book_descr LIKE ?)";
        $likeParam = "%$query%";
        $params[] = $likeParam;
        $params[] = $likeParam;
        $params[] = $likeParam;
        $params[] = $likeParam;
        $types .= 'ssss';
    }
    
    // Category filter
    if (!empty($filters['category'])) {
        $whereClause[] = "c.category_name = ?";
        $params[] = $filters['category'];
        $types .= 's';
    }
    
    // Tags filter
    if (!empty($filters['tags']) && is_array($filters['tags'])) {
        $tagCount = count($filters['tags']);
        $tagPlaceholders = implode(',', array_fill(0, $tagCount, '?'));
        $whereClause[] = "b.book_isbn IN (
            SELECT bt.book_isbn 
            FROM book_tags bt 
            JOIN tags t ON bt.tag_id = t.tag_id 
            WHERE t.tag_name IN ($tagPlaceholders)
            GROUP BY bt.book_isbn 
            HAVING COUNT(DISTINCT t.tag_id) = $tagCount
        )";
        foreach ($filters['tags'] as $tag) {
            $params[] = $tag;
            $types .= 's';
        }
    }
    
    // Price range filter
    if (!empty($filters['price_min'])) {
        $whereClause[] = "b.book_price >= ?";
        $params[] = $filters['price_min'];
        $types .= 'd';
    }
    
    if (!empty($filters['price_max'])) {
        $whereClause[] = "b.book_price <= ?";
        $params[] = $filters['price_max'];
        $types .= 'd';
    }
    
    // Publication date filter
    if (!empty($filters['date_from'])) {
        $whereClause[] = "b.created_at >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $whereClause[] = "b.created_at <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    // Combine where clauses
    if (!empty($whereClause)) {
        $sql .= " WHERE " . implode(" AND ", $whereClause);
    }
    
    // Prepare and execute query
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['total'];
}
?>
