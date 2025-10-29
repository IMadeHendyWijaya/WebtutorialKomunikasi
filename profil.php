<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle foto profil upload
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['profile_photo']['tmp_name'];
    $file_type = $_FILES['profile_photo']['type'];
    $file_ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
    
    // Validasi tipe file
    $allowed = array('jpg', 'jpeg', 'png', 'gif');
    if (in_array($file_ext, $allowed)) {
        // Buat nama file unik
        $new_file_name = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
        $upload_path = 'assets/profile_photos/';
        
        // Buat direktori jika belum ada
        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        
        $target_path = $upload_path . $new_file_name;
        
        // Pindahkan file
        if (move_uploaded_file($file_tmp, $target_path)) {
            // Update database dengan path foto baru
            $update_photo = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $update_photo->bind_param("si", $target_path, $_SESSION['user_id']);
            
            if ($update_photo->execute()) {
                $_SESSION['success_message'] = "Foto profil berhasil diperbarui!";
                
                // Hapus foto lama jika bukan foto default
                $stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $old_photo = $stmt->get_result()->fetch_assoc()['profile_photo'];
                
                if ($old_photo != 'assets/default-avatar.jpg' && 
                    file_exists($old_photo) && 
                    $old_photo != $target_path) {
                    unlink($old_photo);
                }
            } else {
                $_SESSION['error_message'] = "Gagal memperbarui foto profil dalam database.";
            }
        } else {
            $_SESSION['error_message'] = "Gagal mengupload foto ke direktori.";
        }
    } else {
        $_SESSION['error_message'] = "Tipe file tidak diizinkan. Gunakan JPG, JPEG, PNG, atau GIF.";
    }
    
    // Redirect untuk refresh halaman dan data
    header("Location: profil.php");
    exit();
}

// Ambil data user dari database (SETELAH proses upload)
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle form submission untuk update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['profile_photo'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $new_password = $_POST['password'];
    
    // Update username dan email
    if (!empty($username) && !empty($email)) {
        $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $username, $email, $user_id);
        if ($update_stmt->execute()) {
            $_SESSION['user_name'] = $username;
            $_SESSION['success_message'] = "Profil berhasil diperbarui!";
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui profil.";
        }
    }
    
    // Update password jika diisi
    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_pwd_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update_pwd_stmt->bind_param("si", $hashed_password, $user_id);
        if ($update_pwd_stmt->execute()) {
            $_SESSION['success_message'] .= " Password berhasil diperbarui!";
        } else {
            $_SESSION['error_message'] .= " Gagal memperbarui password.";
        }
    }
    
    // Redirect untuk refresh data
    header("Location: profil.php");
    exit();
}

// Ambil riwayat transaksi
$transactions_stmt = $conn->prepare("
    SELECT t.*, c.title as course_title, c.price 
    FROM transactions t 
    JOIN courses c ON t.course_id = c.id 
    WHERE t.user_id = ? 
    ORDER BY t.transaction_date DESC
");
$transactions_stmt->bind_param("i", $user_id);
$transactions_stmt->execute();
$transactions = $transactions_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - E-Course</title>
    <link rel="icon" type="image/png" href="assets/logofull.svg">
    <link rel="stylesheet" href="profil.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="fontawesome/css/all.min.css">
</head>

<body>
    <!-- Header -->
    <header>
        <div class="logo">
            <a href="home.php" style="text-decoration:none;">
                <span style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 22px; color: #0d6efd; letter-spacing: 0.5px;">K-Tutor</span>
            </a>
        </div>
        <nav>
            <a href="home.php">Beranda</a>
            <a href="kursus.php">Cari Tutorial</a>
            <a href="my-courses.php">Aktivitas</a>
        </nav>
        <div class="right-menu">
            <a href="transaction-history.php"><i class="fa fa-shopping-basket"></i></a>
            <a href="profil.php" class="profile-link active">
                <i class="fa fa-user"></i> <span><?php echo htmlspecialchars($user['username']); ?></span>
            </a>
        </div>
    </header>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="profile-section">
            <form id="photoForm" action="" method="POST" enctype="multipart/form-data">
                <div class="profile-photo-container">
                    <?php
                    $photo_path = $user['profile_photo'];
                    // Check if photo path is empty or file doesn't exist
                    if (empty($photo_path) || !file_exists($photo_path) || !is_file($photo_path)) {
                        $display_photo = 'assets/default-avatar.jpg';
                    } else {
                        $display_photo = $photo_path;
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($display_photo); ?>" alt="Foto Profil" class="profile-pic">
                    <div class="photo-overlay">
                        <label for="profile_photo" class="change-photo-btn">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="profile_photo" name="profile_photo" accept="image/*" style="display: none;">
                    </div>
                </div>
            </form>
            <h2><?php echo htmlspecialchars($user['username']); ?></h2>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
        </div>
        <div class="nav-menu">
            <a href="#informasi-akun" class="nav-link active">Informasi Akun</a>
            <a href="#riwayat-pembelian" class="nav-link">Riwayat Pembelian</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <div class="content-section active" id="informasi-akun">
            <h1>Informasi Akun</h1>
            <form class="form-edit" method="POST" action="">
                <label for="username">Nama</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($user['username']); ?>" 
                       placeholder="Nama Anda">

                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" 
                       placeholder="Email Anda">

                <label for="password">Password Baru</label>
                <input type="password" id="password" name="password" 
                       placeholder="Kosongkan jika tidak ingin mengubah password">

                <button type="submit" class="btn-save">Simpan Perubahan</button>
            </form>
        </div>

        <div class="content-section" id="riwayat-pembelian">
            <h1>Riwayat Pembelian</h1>
            <?php if ($transactions->num_rows > 0): ?>
                <div class="transactions-list">
                    <?php while ($transaction = $transactions->fetch_assoc()): ?>
                        <div class="transaction-item">
                            <h3><?php echo htmlspecialchars($transaction['course_title']); ?></h3>
                            <p>Harga: Rp. <?php echo number_format($transaction['price'], 0, ',', '.'); ?></p>
                            <p>Status: <?php echo htmlspecialchars($transaction['payment_status']); ?></p>
                            <p>Tanggal: <?php echo date('d/m/Y H:i', strtotime($transaction['transaction_date'])); ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>Belum ada riwayat pembelian.</p>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
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
        .transactions-list {
            margin-top: 20px;
        }
        .transaction-item {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        .transaction-item h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .transaction-item p {
            margin: 5px 0;
            color: #666;
        }
        .profile-photo-container {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto;
            border-radius: 50%;
            overflow: hidden;
        }
        .profile-pic {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.5);
            padding: 5px;
            display: none;
        }
        .profile-photo-container:hover .photo-overlay {
            display: block;
        }
        .change-photo-btn {
            color: white;
            cursor: pointer;
            display: block;
            text-align: center;
        }
        .change-photo-btn:hover {
            color: #007bff;
        }
    </style>

    <script>
        // Navigasi sidebar
        const navLinks = document.querySelectorAll('.nav-link');
        const sections = document.querySelectorAll('.content-section');

        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.getAttribute('href').startsWith('#')) {
                    e.preventDefault();

                    navLinks.forEach(l => l.classList.remove('active'));
                    sections.forEach(section => section.classList.remove('active'));

                    this.classList.add('active');
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.classList.add('active');
                    }
                }
            });
        });

        // Auto submit form when file is selected
        document.getElementById('profile_photo').addEventListener('change', function() {
            document.getElementById('photoForm').submit();
        });
    </script>

</body>
</html> 