<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit();
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: manage-users.php?success=Pengguna berhasil dihapus");
        exit();
    } else {
        $error = "Error menghapus pengguna: " . $conn->error;
    }
    $stmt->close();
}

// Handle user status update
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $stmt = $conn->prepare("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: manage-users.php?success=Status pengguna berhasil diubah");
        exit();
    } else {
        $error = "Error mengubah status pengguna: " . $conn->error;
    }
    $stmt->close();
}

// Handle user addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $status = 'active';

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $email, $password, $role, $status);
    
    if ($stmt->execute()) {
        header("Location: manage-users.php?success=Pengguna berhasil ditambahkan");
        exit();
    } else {
        $error = "Error menambah pengguna: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all users
$users = [];
$result = $conn->query("SELECT * FROM users ORDER BY id DESC");
if ($result) {
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
} else {
    $error = "Error mengambil data pengguna: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin Dashboard</title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
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
                    <span>Kelola Kursus</span>
                </a>
            </li>
            <li>
                <a href="manage-users.php" class="nav-item active">
                    <i class="fas fa-users"></i>
                    <span>Kelola Pengguna</span>
                </a>
            </li>
            <li>
                <a href="manage-layouts.php" class="nav-item">
                    <i class="fas fa-images"></i>
                    <span>Kelola Contoh Layout</span>
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
            <h1>Kelola Pengguna</h1>
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

        <div class="content-card">
            <h2>Tambah Pengguna Baru</h2>
            <form method="POST" action="" class="form-grid">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_user" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Pengguna
                    </button>
                </div>
            </form>
        </div>

        <div class="content-card">
            <h2>Daftar Pengguna</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="manage-users.php?toggle_status=<?php echo $user['id']; ?>" 
                                       class="btn btn-secondary"
                                       title="<?php echo $user['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                        <i class="fas fa-power-off"></i>
                                    </a>
                                    <a href="manage-users.php?delete=<?php echo $user['id']; ?>" 
                                       class="btn btn-danger"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')"
                                       title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 