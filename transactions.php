<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Handle transaction status update
if (isset($_GET['update_status'])) {
    $id = (int)$_GET['update_status'];
    $new_status = $_GET['status'];
    $allowed_statuses = ['pending', 'completed', 'failed', 'refunded'];
    
    if (in_array($new_status, $allowed_statuses)) {
        $stmt = $conn->prepare("UPDATE transactions SET payment_status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $id);
        
        if ($stmt->execute()) {
            header("Location: transactions.php?success=Status transaksi berhasil diperbarui");
            exit();
        } else {
            $error = "Error mengubah status transaksi: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all transactions with user and course details
$transactions = [];
$query = "SELECT t.*, u.username, c.title as course_title, t.payment_proof           FROM transactions t           JOIN users u ON t.user_id = u.id           JOIN courses c ON t.course_id = c.id           ORDER BY t.transaction_date DESC";

$result = $conn->query($query);
if ($result) {
    while($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    $result->free();
} else {
    $error = "Error mengambil data transaksi: " . $conn->error;
}

// Calculate summary statistics
$total_revenue = 0;
$total_transactions = count($transactions);
$completed_transactions = 0;
$pending_transactions = 0;

foreach ($transactions as $transaction) {
    if ($transaction['payment_status'] === 'completed') {
        $total_revenue += $transaction['amount'];
        $completed_transactions++;
    } else if ($transaction['payment_status'] === 'pending') {
        $pending_transactions++;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Transaksi - Admin Dashboard</title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <style>
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
        }

        .modal-content {
            position: relative;
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
            transition: color 0.3s;
        }

        .close:hover {
            color: #000;
        }

        .modal-image {
            max-width: 100%;
            max-height: 80vh;
            display: block;
            margin: 0 auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .view-proof-btn {
            background-color: #4CAF50;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .view-proof-btn:hover {
            background-color: #45a049;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .view-proof-btn i {
            font-size: 14px;
        }

        .no-proof {
            color: #999;
            font-style: italic;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .no-proof i {
            font-size: 14px;
        }

        /* Action buttons styling */
        .actions {
            position: relative;
            text-align: center;
            padding: 12px 16px !important;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-toggle {
            background: #fff;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        .dropdown-toggle:hover {
            background: #f8f9fa;
            border-color: #ccc;
        }

        .dropdown-toggle i {
            font-size: 16px;
            color: #666;
        }

        .dropdown-content {
            display: none;
            position: fixed;
            background-color: #fff;
            min-width: 200px;
            max-height: 250px;
            overflow-y: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            z-index: 1000;
            padding: 8px;
        }

        /* Scrollbar styling */
        .dropdown-content::-webkit-scrollbar {
            width: 6px;
        }

        .dropdown-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .dropdown-content::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 10px;
        }

        .dropdown-content::-webkit-scrollbar-thumb:hover {
            background: #999;
        }

        .dropdown-content a {
            color: #333;
            padding: 10px 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
            font-size: 14px;
            white-space: nowrap;
            border-radius: 6px;
            margin-bottom: 4px;
        }

        .dropdown-content a:last-child {
            margin-bottom: 0;
        }

        .dropdown-content a i {
            width: 16px;
            text-align: center;
            font-size: 14px;
        }

        .dropdown-content a:hover {
            background-color: #f8f9fa;
        }

        .dropdown-content a.active {
            background-color: #e9ecef;
            color: #000;
            font-weight: 500;
        }

        /* Status colors for dropdown items */
        .dropdown-item.completed i { color: #198754; }
        .dropdown-item.pending i { color: #ffc107; }
        .dropdown-item.failed i { color: #dc3545; }
        .dropdown-item.refunded i { color: #6c757d; }

        /* Status badge improvements */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-completed {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-failed {
            background-color: #f8d7da;
            color: #842029;
        }

        .status-refunded {
            background-color: #e2e3e5;
            color: #383d41;
        }

        /* Table improvements */
        .table-responsive table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-responsive th {
            background-color: #f8f9fa;
            padding: 12px 16px;
            font-weight: 600;
        }

        .table-responsive td {
            padding: 12px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
        }

        .table-responsive tr:hover {
            background-color: #f8f9fa;
        }

        .table-responsive tr:last-child td {
            border-bottom: none;
        }

        /* Action column width */
        .table-responsive th:last-child,
        .table-responsive td:last-child {
            width: 100px;
        }

        /* Proof column width */
        .table-responsive th:nth-child(6),
        .table-responsive td:nth-child(6) {
            width: 120px;
        }
    </style>
</head>
<body>
    <!-- Add Modal HTML -->
    <div id="proofModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <img id="proofImage" class="modal-image" src="" alt="Bukti Pembayaran">
        </div>
    </div>

    <div class="sidebar">
        <div class="sidebar-header">
            <img src="assets/logofull.svg" alt="E-Course Logo">
            <span>E-Course Admin</span>
        </div>
        <ul class="nav-menu">
            <li>
                <a href="dashboard-admin.php" class="nav-item">
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
                <a href="manage-users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Kelola Pengguna</span>
                </a>
            </li>
            <li>
                <a href="transactions.php" class="nav-item active">
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
            <h1>Transaksi</h1>
            <div class="header-right">
                <div class="admin-profile">
                    <img src="<?php echo htmlspecialchars($_SESSION['profile_photo'] ?? 'assets/default-avatar.png'); ?>" alt="Admin Avatar">
                    <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="summary-cards">
            <div class="summary-card">
                <i class="fas fa-money-bill-wave"></i>
                <div class="card-info">
                    <h3>Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></h3>
                    <p>Total Pendapatan</p>
                </div>
            </div>
            <div class="summary-card">
                <i class="fas fa-check-circle"></i>
                <div class="card-info">
                    <h3><?php echo number_format($completed_transactions); ?></h3>
                    <p>Transaksi Selesai</p>
                </div>
            </div>
            <div class="summary-card">
                <i class="fas fa-clock"></i>
                <div class="card-info">
                    <h3><?php echo number_format($pending_transactions); ?></h3>
                    <p>Transaksi Pending</p>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h2>Daftar Transaksi</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pengguna</th>
                            <th>Tutorial</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Bukti</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>#<?php echo $transaction['id']; ?></td>
                                <td><?php echo htmlspecialchars($transaction['username']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['course_title']); ?></td>
                                <td>Rp <?php echo number_format($transaction['amount'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($transaction['payment_status']); ?>">
                                        <i class="fas fa-<?php 
                                            echo match($transaction['payment_status']) {
                                                'completed' => 'check-circle',
                                                'pending' => 'clock',
                                                'failed' => 'times-circle',
                                                'refunded' => 'undo',
                                                default => 'circle'
                                            };
                                        ?>"></i>
                                        <?php 
                                            echo match($transaction['payment_status']) {
                                                'completed' => 'Selesai',
                                                'pending' => 'Pending',
                                                'failed' => 'Gagal',
                                                'refunded' => 'Refund',
                                                default => ucfirst($transaction['payment_status'])
                                            };
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($transaction['payment_proof'])): ?>
                                        <button class="view-proof-btn" onclick="showProof('<?php echo htmlspecialchars($transaction['payment_proof']); ?>')">
                                            <i class="fas fa-receipt"></i> Lihat Bukti
                                        </button>
                                    <?php else: ?>
                                        <span class="no-proof">
                                            <i class="fas fa-times-circle"></i> Belum ada
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M Y', strtotime($transaction['transaction_date'])); ?></td>
                                <td class="actions">
                                    <div class="dropdown">
                                        <button type="button" class="dropdown-toggle" onclick="toggleDropdown(this)" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-content">
                                            <a href="?update_status=<?php echo $transaction['id']; ?>&status=completed" 
                                               class="dropdown-item completed <?php echo $transaction['payment_status'] === 'completed' ? 'active' : ''; ?>">
                                                <i class="fas fa-check"></i> Selesai
                                            </a>
                                            <a href="?update_status=<?php echo $transaction['id']; ?>&status=pending"
                                               class="dropdown-item pending <?php echo $transaction['payment_status'] === 'pending' ? 'active' : ''; ?>">
                                                <i class="fas fa-clock"></i> Pending
                                            </a>
                                            <a href="?update_status=<?php echo $transaction['id']; ?>&status=failed"
                                               class="dropdown-item failed <?php echo $transaction['payment_status'] === 'failed' ? 'active' : ''; ?>">
                                                <i class="fas fa-times"></i> Gagal
                                            </a>
                                            <a href="?update_status=<?php echo $transaction['id']; ?>&status=refunded"
                                               class="dropdown-item refunded <?php echo $transaction['payment_status'] === 'refunded' ? 'active' : ''; ?>">
                                                <i class="fas fa-undo"></i> Refund
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById("proofModal");
        const modalImg = document.getElementById("proofImage");
        const span = document.getElementsByClassName("close")[0];

        function showProof(proofUrl) {
            modal.style.display = "block";
            modalImg.src = proofUrl;
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Toggle dropdown
        function toggleDropdown(button) {
            const dropdown = button.nextElementSibling;
            const rect = button.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            
            // Close other dropdowns
            document.querySelectorAll('.dropdown-content').forEach(d => {
                if (d !== dropdown) d.style.display = 'none';
            });

            if (dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
            } else {
                dropdown.style.display = 'block';
                
                // Position the dropdown
                const dropdownHeight = dropdown.offsetHeight;
                const spaceBelow = windowHeight - rect.bottom;
                const spaceAbove = rect.top;
                
                if (spaceBelow >= dropdownHeight || spaceBelow >= spaceAbove) {
                    // Show below
                    dropdown.style.top = `${rect.bottom + 5}px`;
                    dropdown.style.bottom = 'auto';
                } else {
                    // Show above
                    dropdown.style.bottom = `${windowHeight - rect.top + 5}px`;
                    dropdown.style.top = 'auto';
                }
                
                // Horizontal positioning
                if (rect.left + dropdown.offsetWidth > window.innerWidth) {
                    dropdown.style.right = '10px';
                    dropdown.style.left = 'auto';
                } else {
                    dropdown.style.left = `${rect.left}px`;
                    dropdown.style.right = 'auto';
                }
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.matches('.dropdown-toggle') && 
                !event.target.matches('.dropdown-toggle *') && 
                !event.target.matches('.dropdown-content') && 
                !event.target.matches('.dropdown-content *')) {
                document.querySelectorAll('.dropdown-content').forEach(dropdown => {
                    dropdown.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html> 