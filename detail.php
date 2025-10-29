<?php
session_start();
require_once 'config.php';
require_once 'youtube_utils.php';

// Get course ID from URL
if (!isset($_GET['id'])) {
    header("Location: kursus.php");
    exit();
}

$course_id = (int)$_GET['id'];

// Fetch course details
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();

if (!$course) {
    header("Location: kursus.php");
    exit();
}

// No purchase logic on tutorial detail page (sell/buy removed)
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($course['title']); ?> - Detail Tutorial</title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <link rel="stylesheet" href="detail.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="fontawesome/css/all.min.css">
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
			<a href="kursus.php" class="active">Cari Tutorial</a>
			<a href="my-courses.php">Tutorial Saya</a>
		</nav>
        <div class="right-menu">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profil.php" class="profile-link">
                    <i class="fa fa-user"></i> <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </a>
            <?php else: ?>
                <a href="login.php" class="profile-link">
                    <i class="fa fa-sign-in-alt"></i> <span>Masuk</span>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Detail Konten -->
    <main class="course-detail">
        <section class="course-header">
            <div class="title-video-wrapper">
                <div class="title">
                    <h1><?php echo nl2br(htmlspecialchars($course['title'])); ?></h1>
                </div>
                <div class="video-intro">
                    <?php
                        $media = trim((string)($course['image_path'] ?? ''));
                        $isYouTube = preg_match('/(youtube\.com|youtu\.be)/i', $media);
                        $isDrive = preg_match('/drive\.google\.com/i', $media);
                        $isMp4 = preg_match('/\.mp4($|\?)/i', $media);
                    ?>
                    <?php if ($isYouTube): ?>
                        <?php
                            // Normalize to embeddable URL
                            $embed = $media;
                            if (preg_match('/youtu\.be\/([\w-]+)/', $media, $m)) {
                                $embed = 'https://www.youtube.com/embed/' . $m[1];
                            } elseif (preg_match('/v=([\w-]+)/', $media, $m)) {
                                $embed = 'https://www.youtube.com/embed/' . $m[1];
                            }
                        ?>
                        <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px; background: #000;">
                            <iframe src="<?php echo htmlspecialchars($embed); ?>" title="YouTube video"
                                    style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    <?php elseif ($isDrive): ?>
                        <?php
                            // Attempt to get file id and use Google Drive preview
                            $embed = $media;
                            if (preg_match('/\/d\/([^\/]+)/', $media, $m)) {
                                $embed = 'https://drive.google.com/file/d/' . $m[1] . '/preview';
                            }
                        ?>
                        <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px; background: #000;">
                            <iframe src="<?php echo htmlspecialchars($embed); ?>" title="Drive video"
                                    style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;"
                                    allow="autoplay"></iframe>
                        </div>
                    <?php elseif ($isMp4): ?>
                        <div style="position: relative; border-radius: 8px; overflow: hidden; background: #000;">
                            <video controls width="100%" height="auto" style="border-radius:8px;">
                                <source src="<?php echo htmlspecialchars($media); ?>" type="video/mp4">
                                Browser Anda tidak mendukung video ini.
                            </video>
                        </div>
                    <?php else: ?>
                        <img src="<?php echo htmlspecialchars($media ?: 'assets/default-course.jpg'); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" style="width: 100%; height: auto; border-radius:8px;">
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="course-description">
            <h2>Deskripsi:</h2>
            <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>



        </section>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-content">
            <p>&copy; 2025 E-Course. Semua Hak Dilindungi.</p>
            <p>Dibuat dengan ❤️ oleh Tim E-Course</p>
        </div>
    </footer>
</body>

</html> 