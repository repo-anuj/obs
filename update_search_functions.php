<?php
$oldFile = __DIR__ . '/functions/search_functions.php';
$backupFile = __DIR__ . '/functions/search_functions.php.bak';
$newFile = __DIR__ . '/functions/search_functions_new.php';

// Create backup of old file
if (file_exists($oldFile)) {
    if (copy($oldFile, $backupFile)) {
        echo "Created backup of old search_functions.php\n";
        
        // Replace old file with new file
        if (copy($newFile, $oldFile)) {
            echo "Successfully updated search_functions.php\n";
            unlink($newFile); // Remove the temporary new file
            echo "Removed temporary file\n";
        } else {
            echo "Error replacing search_functions.php\n";
        }
    } else {
        echo "Error creating backup\n";
    }
} else {
    echo "Old file not found, creating new one\n";
    if (copy($newFile, $oldFile)) {
        echo "Successfully created search_functions.php\n";
        unlink($newFile); // Remove the temporary new file
        echo "Removed temporary file\n";
    } else {
        echo "Error creating search_functions.php\n";
    }
}
?>
