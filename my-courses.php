<?php
require_once 'config.php';
require_once 'youtube_utils.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's purchased courses with proper image path
$stmt = $conn->prepare("SELECT c.*, t.payment_status, t.transaction_date 
                       FROM courses c 
                       JOIN transactions t ON c.id = t.course_id 
                       WHERE t.user_id = ? AND t.payment_status = 'completed'
                       ORDER BY t.transaction_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();

// Function to get course progress
function getCourseProgress($conn, $course_id, $user_id) {
    // Get total published materials
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM materials WHERE course_id = ? AND status = 'published'");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    if ($total === 0) return 0;

    // Get completed materials
    $stmt = $conn->prepare("SELECT COUNT(*) as completed 
                           FROM material_progress mp 
                           JOIN materials m ON mp.material_id = m.id 
                           WHERE m.course_id = ? AND mp.user_id = ?");
    $stmt->bind_param("ii", $course_id, $user_id);
    $stmt->execute();
    $completed = $stmt->get_result()->fetch_assoc()['completed'];
    $stmt->close();

    return ($completed / $total) * 100;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutorial Saya - E-Course</title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <link rel="stylesheet" href="kursus.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="fontawesome/css/all.min.css">
    <style>
        .course-progress {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            margin: 10px 0;
        }

        .progress-bar {
            height: 100%;
            background-color: #007bff;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .course-status {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .continue-btn {
            background-color: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s;
        }

        .continue-btn:hover {
            background-color: #218838;
        }

        .course-card img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
    </style>
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
        <h2 class="section-title">Tutorial Saya</h2>
        <div class="course-container">
            <?php if (empty($courses)): ?>
                <div style="text-align: center; width: 100%; padding: 40px;">
                    <h3>Anda belum memiliki tutorial</h3>
                    <p style="margin-top: 10px;">
                        <a href="kursus.php" class="continue-btn">
                            <i class="fas fa-search"></i> Jelajahi Tutorial
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <?php $thumbnail = getCourseThumbnail($course['image_path'] ?? '', $course['title']); ?>
                    <div class="course-card">
                        <div style="position: relative;">
                            <img src="<?php echo htmlspecialchars($thumbnail['src']); ?>" 
                                 alt="<?php echo htmlspecialchars($thumbnail['alt']); ?>">
                            <?php if ($thumbnail['isVideo']): ?>
                                <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                    <i class="fas fa-play"></i> Video
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                            
                            <?php 
                            $progress = getCourseProgress($conn, $course['id'], $user_id);
                            $isCompleted = $progress >= 100;
                            ?>
                            
                            <div class="course-status">
                                <span>Progress: <?php echo round($progress); ?>%</span>
                                <?php if ($isCompleted): ?>
                                    <span style="color: #28a745;"><i class="fas fa-check-circle"></i> Selesai</span>
                                <?php endif; ?>
                            </div>
                            <div class="course-progress">
                                <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            
                            <div class="card-footer">
                                <a href="course-content.php?course_id=<?php echo $course['id']; ?>" 
                                   class="continue-btn">
                                    <?php if ($progress === 0): ?>
                                        <i class="fas fa-play"></i> Mulai Belajar
                                    <?php else: ?>
                                        <i class="fas fa-book-reader"></i> Lanjutkan
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Contoh Layout Section -->
    <section class="popular-courses" style="margin-top: 10px;">
        <h2 class="section-title">Contoh Layout</h2>
        <div class="course-container">
            <?php
            $layouts = [];
            $res = $conn->query("SELECT id, title, thumbnail_url FROM layouts ORDER BY id DESC");
            if ($res) {
                while ($row = $res->fetch_assoc()) { $layouts[] = $row; }
            }
            ?>
            <?php if (empty($layouts)): ?>
                <div style="text-align:center; width:100%; padding:40px; color:#666;">Belum ada contoh layout</div>
            <?php else: ?>
                <?php foreach ($layouts as $layout): ?>
                    <div class="course-card">
                        <div style="position: relative;">
                            <img src="<?php echo htmlspecialchars(resolveThumbnailImageUrl($layout['thumbnail_url'])); ?>" alt="<?php echo htmlspecialchars($layout['title']); ?>" onerror="this.onerror=null; this.src='assets/default-course.jpg';">
                            <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">Layout</div>
                        </div>
                        <div class="card-content">
                            <h3><?php echo htmlspecialchars($layout['title']); ?></h3>
                            <div class="card-footer">
                                <a href="layout-gallery.php?id=<?php echo (int)$layout['id']; ?>" class="continue-btn" style="background:#0d6efd;">
                                    <i class="fas fa-images"></i> Lihat Contoh
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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