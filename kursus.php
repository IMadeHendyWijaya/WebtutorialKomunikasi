<?php
session_start();
require_once 'config.php';
require_once 'youtube_utils.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil data kursus dari database
$query = "SELECT * FROM courses";
if (isset($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $query = "SELECT * FROM courses WHERE title LIKE ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Tutorial - E-Course</title>
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
            <a href="kursus.php" class="active">Cari Tutorial</a>
            <a href="my-courses.php">Tutorial Saya</a>
        </nav>
        <div class="right-menu">
            <a href="profil.php" class="profile-link">
                <i class="fa fa-user"></i> <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            </a>
        </div>
    </header>

    <section class="popular-courses">
        <h2 class="section-title">Cari Tutorial</h2>
        <div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div style="display: flex; justify-content: center; align-items: center; position: relative; margin: 30px 0;">
                <form method="GET" action="" style="width: 100%; max-width: 600px; position: relative;">
                    <div style="position: relative; display: flex; align-items: center;">
                        <i class="fa fa-search" style="position: absolute; left: 20px; color: #666; font-size: 18px;"></i>
                        <input type="text" 
                               name="search" 
                               id="searchInput" 
                                    placeholder="Cari tutorial yang ingin Anda pelajari..." 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                               style="width: 100%; height: 50px; padding: 0 50px; font-size: 16px; border: 2px solid #e0e0e0; border-radius: 25px; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s ease;"
                               autocomplete="off">
                    </div>
                </form>
            </div>
        </div>
        
        <div style="width: 1200px; margin: 0 auto;">
            <div class="course-container">
                <?php
                if ($result->num_rows > 0) {
                    while ($course = $result->fetch_assoc()) {
                        // Cek apakah kursus sudah dibeli
                        $check_purchase = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? AND course_id = ? AND payment_status = 'completed'");
                        $check_purchase->bind_param("ii", $_SESSION['user_id'], $course['id']);
                        $check_purchase->execute();
                        $is_purchased = $check_purchase->get_result()->num_rows > 0;
                        
                        $thumbnail = getCourseThumbnail($course['image_path'], $course['title']);
                ?>
                    <div class="course-card">
                        <div style="position: relative;">
                            <img src="<?php echo htmlspecialchars($thumbnail['src']); ?>" alt="<?php echo htmlspecialchars($thumbnail['alt']); ?>" style="width: 100%; height: 200px; object-fit: cover;">
                            <?php if ($thumbnail['isVideo']): ?>
                                <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-play"></i> Video
                                </div>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.6); color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                    <i class="fas fa-play" style="margin-left: 3px;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                        
                        <div class="card-footer">
                            <?php if ($is_purchased): ?>
                                <a href="course-content.php?id=<?php echo $course['id']; ?>" class="detail-btn">Lihat Materi</a>
                            <?php else: ?>
                                <a href="detail.php?id=<?php echo $course['id']; ?>" class="detail-btn">Detail Tutorial</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php
                    }
                } else {
                    echo '<p class="no-courses">Tidak ada tutorial yang ditemukan.</p>';
                }
                ?>
            </div>
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