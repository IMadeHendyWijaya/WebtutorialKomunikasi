<?php
require_once 'config.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if course ID is provided
if (!isset($_GET['course_id'])) {
    header("Location: my-courses.php");
    exit();
}

$course_id = (int)$_GET['course_id'];
$user_id = $_SESSION['user_id'];

// Handle material completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_material'])) {
    $material_id = (int)$_POST['material_id'];
    
    // Check if already completed
    $stmt = $conn->prepare("SELECT id FROM material_progress WHERE user_id = ? AND material_id = ?");
    $stmt->bind_param("ii", $user_id, $material_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Mark as completed
        $stmt = $conn->prepare("INSERT INTO material_progress (user_id, material_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $material_id);
        $stmt->execute();
    }
    $stmt->close();
    
    // Return JSON response for AJAX request
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
}

// Check if user has purchased this course
$stmt = $conn->prepare("SELECT t.* FROM transactions t 
                       WHERE t.user_id = ? AND t.course_id = ? 
                       AND t.payment_status = 'completed'");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$result = $stmt->get_result();
$has_access = $result->num_rows > 0;
$stmt->close();

if (!$has_access) {
    header("Location: courses.php?error=Anda belum membeli kursus ini");
    exit();
}

// Fetch course details
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch course materials with completion status
$stmt = $conn->prepare("
    SELECT m.*, 
           CASE WHEN mp.id IS NOT NULL THEN 1 ELSE 0 END as is_completed
    FROM materials m 
    LEFT JOIN material_progress mp ON m.id = mp.material_id AND mp.user_id = ?
    WHERE m.course_id = ? AND m.status = 'published' 
    ORDER BY m.sort_order
");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$result = $stmt->get_result();
$materials = [];
while ($row = $result->fetch_assoc()) {
    $materials[] = $row;
}
$stmt->close();

// Calculate course progress
$total_materials = count($materials);
$completed_materials = array_reduce($materials, function($carry, $item) {
    return $carry + ($item['is_completed'] ? 1 : 0);
}, 0);
$progress = $total_materials > 0 ? ($completed_materials / $total_materials) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Materi Tutorial</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .course-header {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .course-title {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .course-description {
            color: #666;
            margin-bottom: 20px;
        }

        .course-progress {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }

        .progress-bar-container {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            margin: 10px 0;
        }

        .progress-bar {
            height: 100%;
            background-color: #28a745;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .materials-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .materials-list {
            background-color: #f8f9fa;
            padding: 20px;
            border-right: 1px solid #dee2e6;
            height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .material-item {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
        }

        .material-item:hover {
            background-color: #e9ecef;
        }

        .material-item.active {
            background-color: #1461b6;
            color: white;
        }

        .material-item.completed {
            border-left: 4px solid #28a745;
        }

        .material-item .completion-status {
            position: absolute;
            right: 15px;
            color: #28a745;
        }

        .material-content {
            padding: 30px;
            height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .material-title {
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .complete-button {
            padding: 8px 16px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.3s;
        }

        .complete-button:hover {
            background-color: #218838;
        }

        .complete-button.completed {
            background-color: #6c757d;
            cursor: default;
        }

        .material-description {
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .material-file {
            margin-top: 20px;
        }

        .material-file iframe {
            width: 100%;
            height: 500px;
            border: none;
            border-radius: 8px;
        }

        .material-file img {
            max-width: 100%;
            border-radius: 8px;
        }

        .material-file a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: #1461b6;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .material-file a:hover {
            background-color: #0f4a8c;
        }

        .type-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .type-video { background-color: #ffc107; color: #000; }
        .type-document { background-color: #17a2b8; color: #fff; }
        .type-image { background-color: #28a745; color: #fff; }
        .type-link { background-color: #6c757d; color: #fff; }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="my-courses.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Tutorial
        </a>

        <div class="course-header">
            <h1 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h1>
            <p class="course-description"><?php echo htmlspecialchars($course['description']); ?></p>
            
            <div class="course-progress">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <span>Progress Tutorial</span>
                    <span><?php echo round($progress); ?>% Selesai</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                </div>
            </div>
        </div>

        <div class="materials-container">
            <div class="materials-list">
                <?php foreach ($materials as $index => $material): ?>
                    <div class="material-item <?php echo $index === 0 ? 'active' : ''; ?> <?php echo $material['is_completed'] ? 'completed' : ''; ?>" 
                         onclick="showMaterial(<?php echo $index; ?>)">
                        <?php
                            $type_icons = [
                                'video' => 'fa-video',
                                'document' => 'fa-file-alt',
                                'image' => 'fa-image',
                                'link' => 'fa-link'
                            ];
                        ?>
                        <i class="fas <?php echo $type_icons[$material['file_type']]; ?>"></i>
                        <?php echo htmlspecialchars($material['title']); ?>
                        <?php if ($material['is_completed']): ?>
                            <span class="completion-status">
                                <i class="fas fa-check-circle"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="material-content">
                <?php if (count($materials) > 0): ?>
                    <?php foreach ($materials as $index => $material): ?>
                        <div class="material-section" style="display: <?php echo $index === 0 ? 'block' : 'none'; ?>">
                            <div class="material-title">
                                <span><?php echo htmlspecialchars($material['title']); ?></span>
                                <button class="complete-button <?php echo $material['is_completed'] ? 'completed' : ''; ?>"
                                        onclick="completeMaterial(<?php echo $material['id']; ?>, this)"
                                        <?php echo $material['is_completed'] ? 'disabled' : ''; ?>>
                                    <?php if ($material['is_completed']): ?>
                                        <i class="fas fa-check"></i> Selesai
                                    <?php else: ?>
                                        <i class="fas fa-check"></i> Tandai Selesai
                                    <?php endif; ?>
                                </button>
                            </div>
                            
                            <div class="material-description">
                                <?php echo nl2br(htmlspecialchars($material['description'])); ?>
                            </div>

                            <div class="material-file">
                                <?php if ($material['file_type'] === 'video'): ?>
                                    <iframe src="<?php echo htmlspecialchars($material['file_path']); ?>" 
                                            allowfullscreen></iframe>
                                <?php elseif ($material['file_type'] === 'image'): ?>
                                    <img src="<?php echo htmlspecialchars($material['file_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($material['title']); ?>">
                                <?php elseif ($material['file_type'] === 'document'): ?>
                                    <a href="<?php echo htmlspecialchars($material['file_path']); ?>" target="_blank">
                                        <i class="fas fa-download"></i> Download Dokumen
                                    </a>
                                <?php elseif ($material['file_type'] === 'link'): ?>
                                    <a href="<?php echo htmlspecialchars($material['file_path']); ?>" target="_blank">
                                        <i class="fas fa-external-link-alt"></i> Buka Link
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-materials">
                        <p>Belum ada materi tersedia untuk kursus ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function showMaterial(index) {
            // Hide all material sections
            document.querySelectorAll('.material-section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Show selected material section
            document.querySelectorAll('.material-section')[index].style.display = 'block';
            
            // Update active state in the list
            document.querySelectorAll('.material-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelectorAll('.material-item')[index].classList.add('active');
        }

        function completeMaterial(materialId, button) {
            console.log('Attempting to complete material:', materialId);
            
            // Disable the button temporarily to prevent double-clicks
            button.disabled = true;
            
            fetch('course-content.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'complete_material=1&material_id=' + materialId
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Update button state
                    button.classList.add('completed');
                    button.disabled = true;
                    button.innerHTML = '<i class="fas fa-check"></i> Selesai';
                    
                    // Update material item in the list
                    const materialItem = document.querySelector(`.material-item:nth-child(${Array.from(document.querySelectorAll('.material-section')).findIndex(section => section.style.display === 'block') + 1})`);
                    materialItem.classList.add('completed');
                    if (!materialItem.querySelector('.completion-status')) {
                        materialItem.insertAdjacentHTML('beforeend', '<span class="completion-status"><i class="fas fa-check-circle"></i></span>');
                    }
                    
                    // Reload the page to update progress
                    window.location.reload();
                } else {
                    console.error('Failed to complete material:', data.error);
                    // Re-enable the button if there was an error
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error completing material:', error);
                // Re-enable the button if there was an error
                button.disabled = false;
                alert('Terjadi kesalahan saat menandai materi selesai. Silakan coba lagi.');
            });
        }
    </script>
</body>
</html> 