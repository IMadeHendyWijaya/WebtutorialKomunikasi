<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    header("Location: manage-courses.php");
    exit();
}

$id = (int)$_GET['id'];

// Handle course update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_course'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = 0; // pricing removed for tutorials
    $image_path = $_POST['image'];

    try {
        $stmt = $conn->prepare("UPDATE courses SET title = ?, description = ?, price = ?, image_path = ? WHERE id = ?");
        $stmt->execute([$title, $description, $price, $image_path, $id]);
        header("Location: manage-courses.php?success=Course updated successfully");
        exit();
    } catch(PDOException $e) {
        $error = "Error updating course: " . $e->getMessage();
    }
}

// Fetch course details
try {
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        header("Location: manage-courses.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Error fetching course: " . $e->getMessage();
    $course = null;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tutorial - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reuse styles from dashboard-admin.php */
        <?php include 'dashboard-admin.php'; ?>
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-group textarea {
            height: 100px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .btn-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="assets/elogo.svg" alt="E-Course Logo">
        </div>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard-admin.php" style="color: white; text-decoration: none;">
                    <i class="fas fa-home"></i>Dashboard
                </a>
            </li>
            <li class="nav-item active">
                <a href="manage-courses.php" style="color: white; text-decoration: none;">
                    <i class="fas fa-book"></i>Kelola Tutorial
                </a>
            </li>
            <li class="nav-item">
                <a href="manage-users.php" style="color: white; text-decoration: none;">
                    <i class="fas fa-users"></i>Daftar Pengguna
                </a>
            </li>
            <li class="nav-item">
                <a href="transactions.php" style="color: white; text-decoration: none;">
                    <i class="fas fa-money-bill"></i>Transaksi
                </a>
            </li>
            <li class="nav-item">
                <a href="materials.php" style="color: white; text-decoration: none;">
                    <i class="fas fa-file-alt"></i>Materi Tutorial
                </a>
            </li>
            <li class="nav-item">
                <a href="reports.php" style="color: white; text-decoration: none;">
                    <i class="fas fa-chart-bar"></i>Laporan
                </a>
            </li>
            <li class="nav-item">
                <a href="logout.php" style="color: white; text-decoration: none;">
                    <i class="fas fa-sign-out-alt"></i>Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Edit Tutorial</h1>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Judul Tutorial</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($course['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($course['description']); ?></textarea>
                </div>
                
                
                <div class="form-group">
                    <label for="image">URL Video (YouTube/Drive/MP4)</label>
                    <input type="text" id="image" name="image" value="<?php echo htmlspecialchars($course['image_path']); ?>" required placeholder="https://youtu.be/... atau https://drive.google.com/file/d/ID/view">
                </div>
                
                <div class="btn-container">
                    <button type="submit" name="update_course" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="manage-courses.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 