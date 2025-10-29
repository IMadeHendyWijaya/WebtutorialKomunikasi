<?php
require_once 'config.php';

// Function to check if a string looks like a bcrypt hash
function is_bcrypt_hash($string) {
    return (strlen($string) == 60 && substr($string, 0, 4) === '$2y$');
}

// Get all users
$query = "SELECT id, password FROM users";
$result = $conn->query($query);

$updated = 0;
$errors = 0;

while ($user = $result->fetch_assoc()) {
    // If the password doesn't look like a bcrypt hash
    if (!is_bcrypt_hash($user['password'])) {
        // Hash the plain text password
        $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
        
        // Update the user's password
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $hashed_password, $user['id']);
        
        if ($stmt->execute()) {
            $updated++;
        } else {
            $errors++;
        }
        $stmt->close();
    }
}

echo "Password fix complete!\n";
echo "Updated passwords: " . $updated . "\n";
echo "Errors encountered: " . $errors . "\n";

$conn->close();
?> 