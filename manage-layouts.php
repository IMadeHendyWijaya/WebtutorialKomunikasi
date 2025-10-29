<?php
session_start();
require_once 'config.php';
require_once 'youtube_utils.php';

// Admin guard
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

// Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_layout'])) {
    $title = trim($_POST['title'] ?? '');
    $thumbnail_url = trim($_POST['thumbnail_url'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    if ($title && $thumbnail_url && $image_url) {
        $stmt = $conn->prepare('INSERT INTO layouts (title, thumbnail_url, image_url) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $title, $thumbnail_url, $image_url);
        if ($stmt->execute()) {
            header('Location: manage-layouts.php?success=Layout ditambahkan');
            exit();
        }
        $stmt->close();
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM layouts WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    header('Location: manage-layouts.php?success=Layout dihapus');
    exit();
}

// Add gallery image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_image'])) {
    $layout_id = (int)($_POST['layout_id'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');
    if ($layout_id > 0 && $image_url) {
        $stmt = $conn->prepare('INSERT INTO layout_images (layout_id, image_url) VALUES (?, ?)');
        $stmt->bind_param('is', $layout_id, $image_url);
        $stmt->execute();
        $stmt->close();
        header('Location: manage-layouts.php?success=Gambar ditambahkan');
        exit();
    }
}

// Delete image
if (isset($_GET['delete_image'])) {
    $id = (int)$_GET['delete_image'];
    $stmt = $conn->prepare('DELETE FROM layout_images WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    header('Location: manage-layouts.php?success=Gambar dihapus');
    exit();
}

// List
$layouts = [];
$res = $conn->query('SELECT * FROM layouts ORDER BY id DESC');
if ($res) { while ($r = $res->fetch_assoc()) { $layouts[] = $r; } }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Contoh Layout - Admin</title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <link rel="stylesheet" href="admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="fontawesome/css/all.min.css">
    <style>
        .thumbnail { width: 72px; height: 48px; object-fit: cover; border-radius: 6px; }
    </style>
    </head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="assets/logofull.svg" alt="E-Course Logo">
            <span>E-Course Admin</span>
        </div>
        <ul class="nav-menu">
            <li><a href="dashboard-admin.php" class="nav-item"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="manage-courses.php" class="nav-item"><i class="fas fa-book"></i><span>Kelola Tutorial</span></a></li>
            <li><a href="manage-layouts.php" class="nav-item active"><i class="fas fa-images"></i><span>Kelola Contoh Layout</span></a></li>
            <li><a href="manage-users.php" class="nav-item"><i class="fas fa-users"></i><span>Kelola Pengguna</span></a></li>
            <li><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Keluar</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Kelola Contoh Layout</h1>
            <div class="header-right">
                <div class="admin-profile">
                    <img src="<?php echo htmlspecialchars($_SESSION['profile_photo'] ?? 'assets/default-avatar.png'); ?>" alt="Admin Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <div class="content-card">
            <h2>Tambah Contoh Layout</h2>
            <form method="POST" action="" class="form-grid">
                <div class="form-group">
                    <label for="title">Judul</label>
                    <input type="text" id="title" name="title" required placeholder="Judul layout">
                </div>
                <div class="form-group">
                    <label for="thumbnail_url">URL Thumbnail (Drive/Gambar)</label>
                    <input type="text" id="thumbnail_url" name="thumbnail_url" required placeholder="https://drive.google.com/file/d/ID/view atau https://.../thumb.jpg">
                </div>
                <div class="form-group">
                    <label for="image_url">URL Gambar Utama (Drive/Gambar)</label>
                    <input type="text" id="image_url" name="image_url" required placeholder="https://drive.google.com/file/d/ID/view atau https://.../image.jpg">
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_layout" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Layout</button>
                </div>
            </form>
        </div>

        <div class="content-card">
            <h2>Daftar Contoh Layout</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Thumbnail</th>
                            <th>Judul</th>
                            <th>Gambar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($layouts)): ?>
                            <tr><td colspan="4" class="text-center">Belum ada data</td></tr>
                        <?php else: ?>
                            <?php foreach ($layouts as $l): ?>
                                <tr>
                                    <td>#<?php echo $l['id']; ?></td>
                                    <td><img class="thumbnail" src="<?php echo htmlspecialchars(resolveThumbnailImageUrl($l['thumbnail_url'])); ?>" alt="thumb" onerror="this.onerror=null; this.src='assets/default-course.jpg';"></td>
                                    <td><?php echo htmlspecialchars($l['title']); ?></td>
                                    <td>
                                        <?php
                                        $imgs = [];
                                        $q = $conn->prepare('SELECT * FROM layout_images WHERE layout_id = ? ORDER BY id DESC');
                                        $q->bind_param('i', $l['id']);
                                        $q->execute();
                                        $r = $q->get_result();
                                        while ($img = $r->fetch_assoc()) { $imgs[] = $img; }
                                        $q->close();
                                        ?>
                                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                            <?php foreach ($imgs as $img): ?>
                                                <div style="position:relative;">
                                                    <img src="<?php echo htmlspecialchars(resolveThumbnailImageUrl($img['image_url'])); ?>" style="width:60px; height:40px; object-fit:cover; border-radius:4px;">
                                                    <a href="manage-layouts.php?delete_image=<?php echo $img['id']; ?>" title="Hapus" style="position:absolute; top:-6px; right:-6px; background:#dc3545; color:#fff; width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:10px; text-decoration:none;">×</a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <form method="POST" style="margin-top:8px; display:flex; gap:8px;">
                                            <input type="hidden" name="layout_id" value="<?php echo $l['id']; ?>">
                                            <input type="text" name="image_url" placeholder="URL gambar (Drive/web)" style="flex:1;">
                                            <button type="submit" name="add_image" class="btn btn-secondary"><i class="fas fa-plus"></i> Tambah</button>
                                        </form>
                                    </td>
                                    <td class="actions">
                                        <a href="layout-gallery.php?id=<?php echo $l['id']; ?>" class="btn btn-secondary" title="Lihat"><i class="fas fa-eye"></i></a>
                                        <a href="manage-layouts.php?delete=<?php echo $l['id']; ?>" class="btn btn-danger" onclick="return confirm('Hapus layout ini?')" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>


