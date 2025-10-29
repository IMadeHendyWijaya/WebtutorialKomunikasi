<?php
require_once 'config.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's transactions
$stmt = $conn->prepare("SELECT t.*, c.title as course_title, c.image_path, c.price 
                       FROM transactions t 
                       JOIN courses c ON t.course_id = c.id 
                       WHERE t.user_id = ? 
                       ORDER BY t.transaction_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - E-Course</title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <link rel="stylesheet" href="kursus.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="fontawesome/css/all.min.css">
    <style>
        .transaction-card {
            background-color: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 800px;
            padding: 20px;
            margin: 15px auto;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .transaction-card img {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .transaction-details {
            flex: 1;
        }

        .transaction-title {
            font-size: 18px;
            margin-bottom: 5px;
            color: #222;
        }

        .transaction-info {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .transaction-amount {
            font-weight: bold;
            color: #007BFF;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-refunded {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .transaction-date {
            font-size: 14px;
            color: #666;
        }

        .payment-proof {
            font-size: 14px;
            color: #007BFF;
            text-decoration: none;
            display: block;
            margin-top: 5px;
        }

        .payment-proof:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .alert-info {
            background-color: #e8f4fd;
            color: #004085;
            border: 1px solid #b8daff;
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
            <a href="my-courses.php">Aktivitas</a>
        </nav>
        <div class="right-menu">
            <a href="transaction-history.php" class="active"><i class="fa fa-shopping-basket"></i></a>
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

    <section class="popular-courses">
        <h2 class="section-title">Riwayat Transaksi</h2>
        <div style="max-width: 800px; margin: 0 auto; padding: 20px;">
            <?php if (empty($transactions)): ?>
                <div style="text-align: center; padding: 40px;">
                    <h3>Belum ada transaksi</h3>
                    <p style="margin-top: 10px;">
                        <a href="kursus.php" class="detail-btn">Jelajahi Tutorial</a>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                    <div class="transaction-card">
                        <img src="<?php echo htmlspecialchars($transaction['image_path'] ?? 'assets/default-course.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($transaction['course_title']); ?>">
                        
                        <div class="transaction-details">
                            <h3 class="transaction-title"><?php echo htmlspecialchars($transaction['course_title']); ?></h3>
                            <p class="transaction-info">
                                ID Transaksi: #<?php echo htmlspecialchars($transaction['id']); ?>
                            </p>
                            <p class="transaction-amount">
                                Rp <?php echo number_format($transaction['amount'], 0, ',', '.'); ?>
                            </p>
                            <?php if ($transaction['payment_proof']): ?>
                                <a href="<?php echo htmlspecialchars($transaction['payment_proof']); ?>" 
                                   target="_blank" 
                                   class="payment-proof">
                                    <i class="fas fa-receipt"></i> Lihat Bukti Pembayaran
                                </a>
                            <?php endif; ?>
                        </div>

                        <div style="text-align: right;">
                            <div class="status-badge status-<?php echo strtolower($transaction['payment_status']); ?>">
                                <?php
                                $status_labels = [
                                    'completed' => 'Selesai',
                                    'pending' => 'Menunggu Verifikasi',
                                    'failed' => 'Gagal',
                                    'refunded' => 'Dikembalikan'
                                ];
                                echo $status_labels[$transaction['payment_status']] ?? $transaction['payment_status'];
                                ?>
                            </div>
                            <div class="transaction-date">
                                <?php echo date('d M Y H:i', strtotime($transaction['transaction_date'])); ?>
                            </div>
                            <?php if ($transaction['payment_status'] === 'completed'): ?>
                                <a href="course-content.php?id=<?php echo $transaction['course_id']; ?>" 
                                   class="detail-btn" style="margin-top: 10px; display: inline-block;">
                                    Akses Tutorial
                                </a>
                            <?php endif; ?>
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