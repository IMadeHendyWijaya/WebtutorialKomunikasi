<?php
session_start();
require_once 'config.php';
require_once 'youtube_utils.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$title = 'Contoh Layout';
$images = [];
if ($id > 0) {
    $stmt = $conn->prepare('SELECT * FROM layouts WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $layout = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($layout) {
        $title = $layout['title'];
        // images
        $q = $conn->prepare('SELECT image_url FROM layout_images WHERE layout_id = ? ORDER BY id DESC');
        $q->bind_param('i', $id);
        $q->execute();
        $res = $q->get_result();
        while ($row = $res->fetch_assoc()) { $images[] = resolveDisplayImageUrl($row['image_url']); }
        $q->close();
        if (empty($images)) { $images[] = resolveDisplayImageUrl($layout['image_url'] ?? ''); }
        if (empty($images)) { $images[] = 'assets/default-course.jpg'; }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contoh Layout - <?php echo htmlspecialchars($title); ?></title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <link rel="stylesheet" href="kursus.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="fontawesome/css/all.min.css">
</head>
<body>
    <header>
        <div class="logo">
            <a href="home.php" style="text-decoration:none;">
                <span style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 22px; color: #0d6efd; letter-spacing: 0.5px;">K-Tutor</span>
            </a>
        </div>
        <nav>
            <a href="home.php">Beranda</a>
            <a href="kursus.php">Cari Tutorial</a>
            <a href="my-courses.php" class="active">Tutorial Saya</a>
        </nav>
        <div class="right-menu">
            <a href="profil.php" class="profile-link">
                <i class="fa fa-user"></i> <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            </a>
        </div>
    </header>

    <section class="popular-courses">
        <h2 class="section-title">Contoh Layout - <?php echo htmlspecialchars($title); ?></h2>
        <div style="width: 1200px; margin: 0 auto; display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:14px;">
            <?php foreach ($images as $img): ?>
                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($title); ?>" style="width: 100%; height: 220px; object-fit: cover; border-radius: 10px; box-shadow: 0 6px 24px rgba(0,0,0,0.08);" onerror="this.onerror=null; this.src='assets/default-course.jpg';">
            <?php endforeach; ?>
        </div>
    </section>

    <footer class="site-footer">
        <div class="footer-content">
            <p>&copy; 2025 E-Course. Semua Hak Dilindungi.</p>
            <p>Dibuat dengan ❤️ oleh Tim E-Course</p>
        </div>
    </footer>
</body>
</html>


