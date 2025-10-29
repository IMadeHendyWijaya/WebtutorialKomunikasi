<?php
require_once 'config.php';

try {
    // Read and execute the SQL commands
    $sql = file_get_contents('create_admin_user.sql');
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>Admin user created successfully!</p>";
        echo "<p>Username: admin</p>";
        echo "<p>Password: password</p>";
        echo "<p>You can now log in at <a href='login.php'>login.php</a></p>";
    } else {
        echo "<p style='color: red;'>Error creating admin user: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 