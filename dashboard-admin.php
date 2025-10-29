<?php
session_start();
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$totalCourses = 0;
$totalUsers = 0;
$recentAccesses = null;
$error = null;

// Debug function
function debug_to_console($data) {
    $output = $data;
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('Debug: " . addslashes($output) . "');</script>";
}

// Fetch summary data
try {
    // Get total courses
    $result = $conn->query("SELECT COUNT(*) as total FROM courses");
    if ($result && $row = $result->fetch_assoc()) {
        $totalCourses = $row['total'];
        debug_to_console("Total Courses: " . $totalCourses);
        $result->free();
    }

    // Get total users
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
    if ($result && $row = $result->fetch_assoc()) {
        $totalUsers = $row['total'];
        debug_to_console("Total Users: " . $totalUsers);
        $result->free();
    }

    // Get recent accesses (users accessing materials)
    // skip recent accesses table as per new requirements

} catch(Exception $e) {
    $error = "Error fetching summary data: " . $e->getMessage();
}

// No revenue chart on tutorial site
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Course</title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
            margin-top: 20px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .content-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 20px 0;
        }

        .table-responsive {
            overflow-x: auto;
            margin-top: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-pending {
            background-color: #ffc107;
            color: #000;
        }

        .status-completed {
            background-color: #28a745;
            color: #fff;
        }

        .status-failed {
            background-color: #dc3545;
            color: #fff;
        }

        .status-refunded {
            background-color: #6c757d;
            color: #fff;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .summary-card i {
            font-size: 24px;
            color: #007bff;
            background: rgba(0,123,255,0.1);
            padding: 15px;
            border-radius: 8px;
        }

        .card-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .card-info p {
            color: #6c757d;
            margin: 0;
        }

        @media (max-width: 768px) {
            .summary-cards {
                grid-template-columns: 1fr;
            }
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
                <a href="dashboard-admin.php" class="nav-item active">
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
                <a href="manage-layouts.php" class="nav-item">
                    <i class="fas fa-images"></i>
                    <span>Kelola Contoh Layout</span>
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
                <a href="materials.php" class="nav-item">
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
            <h1>Dashboard Admin</h1>
            <div class="header-right">
                <div class="admin-profile">
                    <a href="admin-profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;">
                        <img src="<?php echo htmlspecialchars($_SESSION['profile_photo'] ?? 'assets/default-avatar.png'); ?>" alt="Admin Avatar">
                        <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </a>
                </div>
            </div>
        </div>

        <div class="summary-cards">
            <div class="summary-card">
                <i class="fas fa-book"></i>
                <div class="card-info">
                    <h3><?php echo number_format($totalCourses); ?></h3>
                    <p>Total Tutorial</p>
                </div>
            </div>
            <div class="summary-card">
                <i class="fas fa-users"></i>
                <div class="card-info">
                    <h3><?php echo number_format($totalUsers); ?></h3>
                    <p>Total Pengguna</p>
                </div>
            </div>

        </div>

        

    </div>

    <script>
        // Auto refresh setiap 30 detik
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html> 