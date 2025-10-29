<?php
session_start();
require_once 'config.php';

// Admin-only safeguard (optional): allow if logged in at all
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo 'Unauthorized';
    exit();
}

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$url = isset($_GET['url']) ? trim($_GET['url']) : '';

if ($courseId <= 0 || $url === '') {
    echo "Usage: set_example_video.php?id=<course_id>&url=<video_url>";
    exit();
}

$stmt = $conn->prepare("UPDATE courses SET image_path = ? WHERE id = ?");
$stmt->bind_param("si", $url, $courseId);

if ($stmt->execute()) {
    echo "Updated course #{$courseId} media to: " . htmlspecialchars($url);
    echo "<br><a href='detail.php?id=" . $courseId . "'>Open detail</a>";
} else {
    http_response_code(500);
    echo "Error: " . htmlspecialchars($conn->error);
}

$stmt->close();
$conn->close();
?>




