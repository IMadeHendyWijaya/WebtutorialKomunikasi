<?php
require_once 'config.php';

// Cek apakah kolom profile_photo sudah ada
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_photo'");

if ($check_column->num_rows == 0) {
    // Tambah kolom profile_photo jika belum ada
    $add_column = "ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) DEFAULT 'assets/default-avatar.jpg'";
    
    if ($conn->query($add_column)) {
        echo "Kolom profile_photo berhasil ditambahkan!\n";
        
        // Update user yang sudah ada dengan foto default
        $update_existing = "UPDATE users SET profile_photo = 'assets/default-avatar.jpg' WHERE profile_photo IS NULL";
        if ($conn->query($update_existing)) {
            echo "Data user yang ada berhasil diupdate dengan foto default!\n";
        } else {
            echo "Error updating existing users: " . $conn->error . "\n";
        }
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "Kolom profile_photo sudah ada.\n";
}

// Buat direktori untuk foto profil jika belum ada
$upload_path = 'assets/profile_photos';
if (!file_exists($upload_path)) {
    if (mkdir($upload_path, 0777, true)) {
        echo "Direktori untuk foto profil berhasil dibuat!\n";
    } else {
        echo "Gagal membuat direktori foto profil.\n";
    }
} else {
    echo "Direktori foto profil sudah ada.\n";
}

$conn->close();
echo "Setup selesai!\n";
?> 