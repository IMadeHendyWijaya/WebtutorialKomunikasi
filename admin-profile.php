<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle profile photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_photo']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            // Create upload directory if it doesn't exist
            $upload_dir = 'assets/profile_photos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $new_filename = 'admin_' . $user_id . '_' . time() . '.' . $filetype;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                // Update database
                $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                $stmt->bind_param("si", $upload_path, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['profile_photo'] = $upload_path;
                    $success_message = "Foto profil berhasil diperbarui!";
                } else {
                    $error_message = "Gagal memperbarui foto profil dalam database.";
                }
                $stmt->close();
            } else {
                $error_message = "Gagal mengunggah file.";
            }
        } else {
            $error_message = "Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.";
        }
    }
}

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Admin - E-Course</title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .profile-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 20px auto;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: block;
            object-fit: cover;
            border: 3px solid #007bff;
        }

        .photo-upload {
            text-align: center;
            margin-bottom: 30px;
        }

        .file-input-container {
            position: relative;
            margin: 20px 0;
        }

        .file-input-container input[type="file"] {
            display: none;
        }

        .file-input-label {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .file-input-label:hover {
            background-color: #0056b3;
        }

        .upload-button {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .upload-button:hover {
            background-color: #218838;
        }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .profile-info {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #dee2e6;
        }

        .info-item {
            margin-bottom: 15px;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }

        .info-value {
            color: #212529;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="assets/logofull.svg" alt="E-Course Logo">
            <span>E-Course Admin</span>
        </div>
        <ul class="nav-menu">
            <li>
                <a href="dashboard-admin.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="manage-courses.php" class="nav-item">
                    <i class="fas fa-book"></i>
                    <span>Kelola Kursus</span>
                </a>
            </li>
            <li>
                <a href="manage-users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Kelola Pengguna</span>
                </a>
            </li>
            <li>
                <a href="transactions.php" class="nav-item">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Transaksi</span>
                </a>
            </li>
            <li>
                <a href="materials.php" class="nav-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Materi</span>
                </a>
            </li>
            <li>
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Keluar</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Profil Admin</h1>
            <div class="header-right">
                <div class="admin-profile">
                    <img src="<?php echo htmlspecialchars($user['profile_photo'] ?? 'assets/default-avatar.png'); ?>" alt="Admin Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
            </div>
        </div>

        <div class="profile-container">
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="profile-header">
                <img src="<?php echo htmlspecialchars($user['profile_photo'] ?? 'assets/default-avatar.png'); ?>" 
                     alt="Profile Photo" 
                     class="profile-photo" 
                     id="preview-photo">
                <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                <p>Administrator</p>
            </div>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="photo-upload">
                    <div class="file-input-container">
                        <label for="profile_photo" class="file-input-label">
                            <i class="fas fa-camera"></i> Pilih Foto
                        </label>
                        <input type="file" 
                               id="profile_photo" 
                               name="profile_photo" 
                               accept="image/*"
                               onchange="previewImage(this);">
                    </div>
                    <button type="submit" class="upload-button">
                        <i class="fas fa-upload"></i> Unggah Foto
                    </button>
                </div>
            </form>

            <div class="profile-info">
                <div class="info-item">
                    <div class="info-label">Username</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Role</div>
                    <div class="info-value">Administrator</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-photo').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html> 