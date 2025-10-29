<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi password match
    if ($password !== $confirm_password) {
        $error = "Password tidak cocok!";
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Email sudah terdaftar!";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
                $stmt->bind_param("sss", $username, $email, $hashed_password);
                
                if ($stmt->execute()) {
                    // Get the new user's ID
                    $user_id = $stmt->insert_id;
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_name'] = $username;
                    $_SESSION['user_email'] = $email;
                    
                    // Redirect to home page
                    header("Location: home.php");
                    exit();
                } else {
                    $error = "Gagal mendaftar: " . $conn->error;
                }
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - E-Course</title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h3 class="login-title">Daftar Akun Baru</h3>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="register-form">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Masukkan username">
                </div>
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" required placeholder="nama@email.com">
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Minimal 8 karakter">
                </div>
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Masukkan ulang password">
                </div>
                <button type="submit" class="btn-submit">Daftar</button>
            </form>

            <div class="register-link">
                <p>Sudah punya akun? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>
</body>
</html> 