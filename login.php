<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

// Get error message from session if exists
$error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : null;
// Clear the error message from session
unset($_SESSION['login_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // First check if email exists
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Check if role matches
            if (($role === 'admin' && $user['role'] === 'admin') || 
                ($role === 'user' && ($user['role'] === 'user' || $user['role'] === NULL))) {
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['username'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['profile_photo'] = $user['profile_photo'];
                
                if ($role === 'admin') {
                    $_SESSION['is_admin'] = true;
                    header("Location: dashboard-admin.php");
                } else {
                    header("Location: home.php");
                }
                exit();
            } else {
                $_SESSION['login_error'] = "Peran yang dipilih tidak sesuai dengan akun Anda";
                header("Location: login.php");
                exit();
            }
        } else {
            $_SESSION['login_error'] = "Password tidak cocok";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['login_error'] = "Email tidak ditemukan";
        header("Location: login.php");
        exit();
    }
    $stmt->close();
}

// Remove debug session variables if they exist
unset($_SESSION['debug_hash']);
unset($_SESSION['debug_input']);

// Jika ada redirect dari register.php dengan pesan sukses
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = "Pendaftaran berhasil! Silakan login dengan akun Anda.";
    // Redirect to clean URL
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Course</title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="login.css" />
    <style>
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            text-align: center;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h3 class="login-title">Login ke E-Course</h3>
            <?php if (isset($success)): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form id="loginForm" method="POST" action="">
                <div class="form-group">
                    <label for="role" class="form-label">Login Sebagai</label>
                    <select id="role" name="role" required>
                        <option value="">-- Pilih Peran --</option>
                        <option value="user">Pengguna</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" placeholder="nama@email.com" required />
                </div>

                <div class="form-group password-wrapper">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input type="password" name="password" id="password" placeholder="********" required />
                </div>

                <button type="submit" class="btn-submit">Masuk</button>
            </form>
            <div class="register-link">
                <p>Belum punya akun? <a href="register.php">Daftar Akun</a></p>
            </div>
        </div>
    </div>
</body>
</html> 