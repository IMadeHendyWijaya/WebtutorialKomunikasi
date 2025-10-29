<?php
session_start();
require_once 'config.php';
require_once 'youtube_utils.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch popular courses
$stmt = $conn->prepare("SELECT c.*, COUNT(t.id) as purchase_count 
                       FROM courses c 
                       LEFT JOIN transactions t ON c.id = t.course_id AND t.payment_status = 'completed'
                       GROUP BY c.id 
                       ORDER BY purchase_count DESC 
                       LIMIT 3");
$stmt->execute();
$popular_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Course</title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <link rel="stylesheet" href="home.css">
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
            <a href="home.php" class="active">Beranda</a>
            <a href="kursus.php">Cari Tutorial</a>
            <a href="my-courses.php">Tutorial Saya</a>
        </nav>
        <div class="right-menu">
            <a href="profil.php" class="profile-link">
                <i class="fa fa-user"></i> <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            </a>
        </div>
    </header>

    <!-- Section 1 -->
    <section class="hero">
        <div class="hero-text">
            <h1>Selamat datang di E-Tutorial</h1>
            <p>Temukan tutorial terbaik dan perluas ilmu kamu di E-Tutorial!</p>
            <a href="kursus.php" class="kursus-btn">
                <button>Cari Tutorial</button>
            </a>
        </div>
        <div class="hero-image">
            <img src="assets/vektor.svg" alt="Ilustrasi">
        </div>
    </section>

    <!-- Section 2 -->
    <section style="width: 100%; padding: 60px 0;">
        <h2 style="text-align: center; font-size: 32px; margin-bottom: 40px;">Tutorial Populer</h2>
        
        <!-- Container with fixed width -->
        <div style="width: 1200px; margin: 0 auto;">
            <!-- Course cards container -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; justify-items: center;">
                <?php foreach ($popular_courses as $course): ?>
                    <?php $thumbnail = getCourseThumbnail($course['image_path'] ?? '', $course['title']); ?>
                    <div style="width: 360px; background: white; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 20px;">
                        <div style="position: relative;">
                            <img src="<?php echo htmlspecialchars($thumbnail['src']); ?>" 
                                 alt="<?php echo htmlspecialchars($thumbnail['alt']); ?>"
                                 style="width: 100%; height: 200px; object-fit: cover; border-radius: 12px; margin-bottom: 15px;">
                            <?php if ($thumbnail['isVideo']): ?>
                                <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                    <i class="fas fa-play"></i> Video
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3 style="font-size: 20px; margin-bottom: 10px;"><?php echo htmlspecialchars($course['title']); ?></h3>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <a href="detail.php?id=<?php echo $course['id']; ?>" 
                               style="background-color: #007BFF; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 14px;">
                                Detail Tutorial
                            </a>
                            
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Selengkapnya button -->
        <div style="text-align: center; margin-top: 40px;">
            <a href="kursus.php" 
               style="display: inline-block; background-color: #007BFF; color: white; padding: 12px 30px; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;">
                Selengkapnya
            </a>
        </div>
    </section>

    <footer class="site-footer">
        <div class="footer-content">
            <p>&copy; 2025 K-Tutor. Semua Hak Dilindungi.</p>
            <p>Dibuat dengan ❤️ oleh Tim K-Tutor</p>
        </div>
    </footer>

</body>

</html> 