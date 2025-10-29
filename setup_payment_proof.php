<?php
session_start();
require_once 'config.php';

// Only allow admin to run this script
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    die("Access denied. Only administrators can run this script.");
}

try {
    // Add payment_proof column
    $sql = file_get_contents('add_payment_proof_column.sql');
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>Column payment_proof added successfully to transactions table</p>";
    } else {
        echo "<p style='color: red;'>Error adding column: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='manage-courses.php'>Return to Manage Courses</a></p>"; 