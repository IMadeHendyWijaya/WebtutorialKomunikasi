<?php
session_start();
require_once 'config.php';

// Only allow admin to run this script
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    die("Access denied. Only administrators can run this script.");
}

try {
    // Add image_path column if it doesn't exist
    $sql = "ALTER TABLE courses ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) NOT NULL AFTER price";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>Column image_path added successfully to courses table</p>";
        
        // Update existing records to have a default value
        $update_sql = "UPDATE courses SET image_path = 'assets/default-course.jpg' WHERE image_path = ''";
        if ($conn->query($update_sql) === TRUE) {
            echo "<p style='color: green;'>Existing records updated with default image path</p>";
        }
    } else {
        echo "<p style='color: red;'>Error adding column: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

$conn->close();

echo "<p><a href='manage-courses.php'>Return to Manage Courses</a></p>"; 