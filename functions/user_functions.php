<?php
// User authentication and management functions

// Register a new user
function registerUser($conn, $name, $email, $password, $phone = null, $address = null, $city = null, $zip_code = null, $country = null) {
    // Check if user already exists
    $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return [
            'success' => false,
            'message' => 'Email address already registered. Please login or use another email.'
        ];
    }
    
    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address, city, zip_code, country) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $name, $email, $hashed_password, $phone, $address, $city, $zip_code, $country);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'user_id' => $stmt->insert_id,
            'message' => 'Registration successful! You can now login.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Registration failed: ' . $stmt->error
        ];
    }
}

// Login user
function loginUser($conn, $email, $password) {
    $stmt = $conn->prepare("SELECT user_id, name, email, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Update last login timestamp
            $updateStmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?");
            $updateStmt->bind_param("i", $user['user_id']);
            $updateStmt->execute();
            
            // Remove password before returning user data
            unset($user['password']);
            
            return [
                'success' => true,
                'user' => $user,
                'message' => 'Login successful!'
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'Invalid email or password.'
    ];
}

// Get user profile by ID
function getUserProfile($conn, $user_id) {
    $stmt = $conn->prepare("SELECT user_id, name, email, phone, address, city, zip_code, country, registration_date, last_login FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return [
            'success' => true,
            'profile' => $result->fetch_assoc()
        ];
    }
    
    return [
        'success' => false,
        'message' => 'User not found.'
    ];
}

// Update user profile
function updateUserProfile($conn, $user_id, $name, $phone, $address, $city, $zip_code, $country) {
    $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ?, city = ?, zip_code = ?, country = ? WHERE user_id = ?");
    $stmt->bind_param("ssssssi", $name, $phone, $address, $city, $zip_code, $country, $user_id);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Profile updated successfully!'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Failed to update profile: ' . $stmt->error
    ];
}

// Change user password
function changeUserPassword($conn, $user_id, $current_password, $new_password) {
    // Get current password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $updateStmt->bind_param("si", $hashed_password, $user_id);
            
            if ($updateStmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Password changed successfully!'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update password: ' . $updateStmt->error
                ];
            }
        }
    }
    
    return [
        'success' => false,
        'message' => 'Current password is incorrect.'
    ];
}

// Get user order history
function getUserOrders($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT o.orderid, o.amount, o.date, o.ship_name, o.ship_address, o.ship_city, o.ship_zip_code, o.ship_country 
        FROM orders o 
        WHERE o.user_id = ? 
        ORDER BY o.date DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $orders = $stmt->get_result();
    
    $order_list = [];
    
    // Fetch all orders
    while ($order = $orders->fetch_assoc()) {
        $orderid = $order['orderid'];
        
        // Get order items for each order
        $items_stmt = $conn->prepare("
            SELECT oi.book_isbn, oi.item_price, oi.quantity, b.book_title, b.book_image 
            FROM order_items oi 
            LEFT JOIN books b ON oi.book_isbn = b.book_isbn 
            WHERE oi.orderid = ?
        ");
        $items_stmt->bind_param("i", $orderid);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        $items = [];
        while ($item = $items_result->fetch_assoc()) {
            $items[] = $item;
        }
        
        // Add items to order
        $order['items'] = $items;
        $order_list[] = $order;
    }
    
    return $order_list;
}

// Link a guest order to a user (for when a guest checks out then registers/logs in)
function linkOrderToUser($conn, $order_id, $user_id) {
    $stmt = $conn->prepare("UPDATE orders SET user_id = ? WHERE orderid = ? AND user_id IS NULL");
    $stmt->bind_param("ii", $user_id, $order_id);
    
    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Order linked to your account successfully!'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Failed to link order to account: ' . $stmt->error
    ];
}
?>
