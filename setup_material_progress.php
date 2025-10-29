<?php
session_start();
require_once 'config.php';

// Only allow admin to run this script
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    die("Access denied. Only administrators can run this script.");
}

try {
    // Read and execute the SQL commands
    $sql = file_get_contents('create_material_progress_table.sql');
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>Material progress table created successfully!</p>";
    } else {
        echo "<p style='color: red;'>Error creating table: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='materials.php'>Return to Materials</a></p>"; 