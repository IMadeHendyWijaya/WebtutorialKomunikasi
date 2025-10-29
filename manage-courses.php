<?php
session_start();
require_once 'config.php';
require_once 'youtube_utils.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Handle course deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: manage-courses.php?success=Tutorial berhasil dihapus");
        exit();
    } else {
        $error = "Error menghapus kursus: " . $conn->error;
    }
    $stmt->close();
}

// Handle course addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
$price = 0; // tutorial site: no pricing
$image_path = $_POST['image'];

    $stmt = $conn->prepare("INSERT INTO courses (title, description, price, image_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $title, $description, $price, $image_path);
    
    if ($stmt->execute()) {
        header("Location: manage-courses.php?success=Tutorial berhasil ditambahkan");
        exit();
    } else {
        $error = "Error menambah kursus: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all courses
$courses = [];
$result = $conn->query("SELECT * FROM courses ORDER BY id DESC");
if ($result) {
    while($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    $result->free();
} else {
    $error = "Error mengambil data kursus: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tutorial - Admin Dashboard</title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
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
                <a href="manage-courses.php" class="nav-item active">
                    <i class="fas fa-book"></i>
                    <span>Kelola Tutorial</span>
                </a>
            </li>
            <li>
                <a href="manage-users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Kelola Pengguna</span>
                </a>
            </li>
            <li>
                <a href="manage-layouts.php" class="nav-item">
                    <i class="fas fa-images"></i>
                    <span>Kelola Contoh Layout</span>
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
            <h1>Kelola Tutorial</h1>
            <div class="header-right">
                <div class="admin-profile">
                    <img src="<?php echo htmlspecialchars($_SESSION['profile_photo'] ?? 'assets/default-avatar.png'); ?>" alt="Admin Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <h2>Tambah Tutorial Baru</h2>
            <form method="POST" action="" class="form-grid">
                <div class="form-group">
                    <label for="title">Judul Tutorial</label>
                    <input type="text" id="title" name="title" required placeholder="Masukkan judul kursus">
                </div>
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description" required placeholder="Masukkan deskripsi kursus" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="image">URL Video (YouTube/Drive/MP4)</label>
                    <input type="text" id="image" name="image" required placeholder="https://youtu.be/.... atau https://drive.google.com/file/d/ID/view">
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_course" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Tutorial
                    </button>
                </div>
            </form>
        </div>

        <div class="content-card">
            <h2>Daftar Tutorial</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Media</th>
                            <th>Judul</th>
                            <th>Deskripsi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($courses) > 0): ?>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td>#<?php echo $course['id']; ?></td>
                                    <td>
                                        <?php 
                                        $thumbnail = getCourseThumbnail($course['image_path'], $course['title']);
                                        ?>
                                        <div class="thumbnail-container" style="position: relative; display: inline-block;">
                                            <img src="<?php echo htmlspecialchars($thumbnail['src']); ?>" 
                                                 alt="<?php echo htmlspecialchars($thumbnail['alt']); ?>" 
                                                 class="course-thumbnail"
                                                 style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                                            <?php if ($thumbnail['isVideo']): ?>
                                                <div style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold;">
                                                    <i class="fas fa-play" style="font-size: 8px;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($course['title']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></td>
                                    <td class="actions">
                                        <a href="edit-course.php?id=<?php echo $course['id']; ?>" 
                                           class="btn btn-secondary" 
                                           title="Edit Tutorial">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="manage-courses.php?delete=<?php echo $course['id']; ?>" 
                                           class="btn btn-danger"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus kursus ini?')"
                                           title="Hapus Tutorial">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada kursus yang ditambahkan</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 