<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: materials.php");
    exit();
}

$id = (int)$_GET['id'];

// Handle material update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_material'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $course_id = (int)$_POST['course_id'];
    $file_type = $_POST['file_type'];
    $current_file_path = $_POST['current_file_path'];
    $file_path = $current_file_path;
    
    // Handle file upload if new file is selected
    if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === 0) {
        $upload_dir = 'uploads/materials/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Delete old file if exists
        if (!empty($current_file_path) && file_exists($current_file_path) && $file_type !== 'link') {
            unlink($current_file_path);
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
        $stmt = $conn->prepare("UPDATE materials SET title = ?, description = ?, course_id = ?, file_type = ?, file_path = ? WHERE id = ?");
        $stmt->bind_param("ssissi", $title, $description, $course_id, $file_type, $file_path, $id);
        
        if ($stmt->execute()) {
            header("Location: materials.php?success=Materi berhasil diperbarui");
            exit();
        } else {
            $error = "Error memperbarui materi: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch material details
$stmt = $conn->prepare("SELECT * FROM materials WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$material = $result->fetch_assoc();
$stmt->close();

if (!$material) {
    header("Location: materials.php");
    exit();
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Materi - Admin Dashboard</title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .current-file {
            margin-top: 5px;
            font-size: 0.9em;
            color: #666;
        }

        #external_link_group,
        #file_upload_group {
            display: none;
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
            <h1>Edit Materi</h1>
            <div class="header-right">
                <a href="materials.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <div class="admin-profile">
                    <img src="assets/default-avatar.png" alt="Admin Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <form method="POST" action="" enctype="multipart/form-data" class="form-grid">
                <div class="form-group">
                    <label for="course_id">Tutorial</label>
                    <select id="course_id" name="course_id" required>
                        <option value="">Pilih Tutorial</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" 
                                    <?php echo $course['id'] === $material['course_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">Judul Materi</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($material['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($material['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="file_type">Tipe Materi</label>
                    <select id="file_type" name="file_type" required onchange="toggleFileInput()">
                        <option value="">Pilih Tipe</option>
                        <option value="video" <?php echo $material['file_type'] === 'video' ? 'selected' : ''; ?>>Video</option>
                        <option value="document" <?php echo $material['file_type'] === 'document' ? 'selected' : ''; ?>>Dokumen</option>
                        <option value="image" <?php echo $material['file_type'] === 'image' ? 'selected' : ''; ?>>Gambar</option>
                        <option value="link" <?php echo $material['file_type'] === 'link' ? 'selected' : ''; ?>>Link External</option>
                    </select>
                </div>

                <input type="hidden" name="current_file_path" value="<?php echo htmlspecialchars($material['file_path']); ?>">

                <div class="form-group" id="file_upload_group">
                    <label for="material_file">File Materi</label>
                    <input type="file" id="material_file" name="material_file">
                    <?php if ($material['file_type'] !== 'link' && !empty($material['file_path'])): ?>
                        <div class="current-file">
                            File saat ini: <?php echo htmlspecialchars(basename($material['file_path'])); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group" id="external_link_group">
                    <label for="external_link">Link External</label>
                    <input type="url" id="external_link" name="external_link" 
                           value="<?php echo $material['file_type'] === 'link' ? htmlspecialchars($material['file_path']) : ''; ?>" 
                           placeholder="https://...">
                </div>

                <div class="form-actions">
                    <button type="submit" name="update_material" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleFileInput() {
            const fileType = document.getElementById('file_type').value;
            const fileUploadGroup = document.getElementById('file_upload_group');
            const externalLinkGroup = document.getElementById('external_link_group');
            
            if (fileType === 'link') {
                fileUploadGroup.style.display = 'none';
                externalLinkGroup.style.display = 'block';
                document.getElementById('material_file').removeAttribute('required');
                document.getElementById('external_link').setAttribute('required', 'required');
            } else if (fileType === '') {
                fileUploadGroup.style.display = 'none';
                externalLinkGroup.style.display = 'none';
                document.getElementById('material_file').removeAttribute('required');
                document.getElementById('external_link').removeAttribute('required');
            } else {
                fileUploadGroup.style.display = 'block';
                externalLinkGroup.style.display = 'none';
                document.getElementById('external_link').removeAttribute('required');
            }
        }

        // Initialize the form state
        document.addEventListener('DOMContentLoaded', function() {
            toggleFileInput();
        });
    </script>
</body>
</html> 