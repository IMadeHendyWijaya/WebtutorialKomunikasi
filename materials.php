<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Handle material deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get file path before deletion to remove the actual file
    $stmt = $conn->prepare("SELECT file_path FROM materials WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['file_path']) && file_exists($row['file_path'])) {
            unlink($row['file_path']);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM materials WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: materials.php?success=Materi berhasil dihapus");
        exit();
    } else {
        $error = "Error menghapus materi: " . $conn->error;
    }
    $stmt->close();
}

// Handle material status update
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $stmt = $conn->prepare("UPDATE materials SET status = IF(status = 'published', 'draft', 'published') WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: materials.php?success=Status materi berhasil diubah");
        exit();
    } else {
        $error = "Error mengubah status materi: " . $conn->error;
    }
    $stmt->close();
}

// Handle material addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_material'])) {
    $course_id = (int)$_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $file_type = $_POST['file_type'];
    $status = 'draft';
    $file_path = '';
    
    // Handle file upload
    if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === 0) {
        $upload_dir = 'uploads/materials/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['material_file']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['material_file']['tmp_name'], $target_path)) {
            $file_path = $target_path;
        } else {
            $error = "Error mengunggah file";
        }
    } elseif ($file_type === 'link') {
        $file_path = $_POST['external_link'];
    }

    if (!isset($error)) {
        $stmt = $conn->prepare("INSERT INTO materials (course_id, title, description, file_path, file_type, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $course_id, $title, $description, $file_path, $file_type, $status);
        
        if ($stmt->execute()) {
            header("Location: materials.php?success=Materi berhasil ditambahkan");
            exit();
        } else {
            $error = "Error menambah materi: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all courses for the dropdown
$courses = [];
$result = $conn->query("SELECT id, title FROM courses ORDER BY title");
if ($result) {
    while($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    $result->free();
}

// Fetch all materials with course details
$materials = [];
$query = "SELECT m.*, c.title as course_title 
          FROM materials m 
          JOIN courses c ON m.course_id = c.id 
          ORDER BY c.title, m.sort_order";

$result = $conn->query($query);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $materials[] = $row;
    }
    $result->free();
} else {
    $error = "Error mengambil data materi: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Materi - Admin Dashboard</title>
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
                <a href="manage-courses.php" class="nav-item">
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
                <a href="transactions.php" class="nav-item">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Transaksi</span>
                </a>
            </li>
            <li>
                <a href="materials.php" class="nav-item active">
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
            <h1>Kelola Materi</h1>
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
            <h2>Tambah Materi Baru</h2>
            <form method="POST" action="" enctype="multipart/form-data" class="form-grid">
                <div class="form-group">
                    <label for="course_id">Pilih Tutorial</label>
                    <select id="course_id" name="course_id" required>
                        <option value="">Pilih Tutorial</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>">
                                <?php echo htmlspecialchars($course['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="title">Judul Materi</label>
                    <input type="text" id="title" name="title" required placeholder="Masukkan judul materi">
                </div>
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description" required placeholder="Masukkan deskripsi materi" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="file_type">Tipe Materi</label>
                    <select id="file_type" name="file_type" required onchange="toggleFileInput(this.value)">
                        <option value="file">File Upload</option>
                        <option value="link">Link External</option>
                    </select>
                </div>
                <div class="form-group" id="file_upload_group">
                    <label for="material_file">Upload File</label>
                    <input type="file" id="material_file" name="material_file">
                </div>
                <div class="form-group" id="link_group" style="display: none;">
                    <label for="external_link">Link External</label>
                    <input type="url" id="external_link" name="external_link" placeholder="https://example.com/resource">
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_material" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Materi
                    </button>
                </div>
            </form>
        </div>

        <div class="content-card">
            <h2>Daftar Materi</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kursus</th>
                            <th>Judul</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($materials) > 0): ?>
                            <?php foreach ($materials as $material): ?>
                                <tr>
                                    <td>#<?php echo $material['id']; ?></td>
                                    <td><?php echo htmlspecialchars($material['course_title']); ?></td>
                                    <td><?php echo htmlspecialchars($material['title']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $material['file_type'] === 'file' ? 'primary' : 'secondary'; ?>">
                                            <?php echo $material['file_type'] === 'file' ? 'File' : 'Link'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($material['status']); ?>">
                                            <?php echo ucfirst($material['status']); ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="edit-material.php?id=<?php echo $material['id']; ?>" 
                                           class="btn btn-secondary"
                                           title="Edit Materi">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?toggle_status=<?php echo $material['id']; ?>" 
                                           class="btn btn-info"
                                           title="<?php echo $material['status'] === 'published' ? 'Set Draft' : 'Publish'; ?>">
                                            <i class="fas fa-<?php echo $material['status'] === 'published' ? 'eye-slash' : 'eye'; ?>"></i>
                                        </a>
                                        <a href="?delete=<?php echo $material['id']; ?>" 
                                           class="btn btn-danger"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus materi ini?')"
                                           title="Hapus Materi">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada materi yang ditambahkan</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function toggleFileInput(type) {
        const fileUploadGroup = document.getElementById('file_upload_group');
        const linkGroup = document.getElementById('link_group');
        
        if (type === 'file') {
            fileUploadGroup.style.display = 'block';
            linkGroup.style.display = 'none';
            document.getElementById('external_link').value = '';
        } else {
            fileUploadGroup.style.display = 'none';
            linkGroup.style.display = 'block';
            document.getElementById('material_file').value = '';
        }
    }
    </script>
</body>
</html> 