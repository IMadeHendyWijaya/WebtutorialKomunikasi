<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Check if course_id is provided
if (!isset($_GET['course_id'])) {
    header("Location: kursus.php");
    exit();
}

$course_id = (int)$_GET['course_id'];

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

// Check if user has already purchased this course
$stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? AND course_id = ? AND payment_status = 'completed'");
$stmt->bind_param("ii", $_SESSION['user_id'], $course_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header("Location: course-content.php?id=" . $course_id);
    exit();
}

// Handle payment proof upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['payment_proof']['tmp_name'];
        $file_type = $_FILES['payment_proof']['type'];
        $file_ext = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
        
        // Validate file type
        $allowed = array('jpg', 'jpeg', 'png', 'pdf');
        if (in_array($file_ext, $allowed)) {
            // Create unique filename
            $new_file_name = 'payment_' . time() . '_' . $_SESSION['user_id'] . '_' . $course_id . '.' . $file_ext;
            $upload_path = 'assets/payment_proofs/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            
            $target_path = $upload_path . $new_file_name;
            
            // Move uploaded file
            if (move_uploaded_file($file_tmp, $target_path)) {
                // Create transaction record
                $stmt = $conn->prepare("INSERT INTO transactions (user_id, course_id, amount, payment_proof, payment_status, transaction_date) VALUES (?, ?, ?, ?, 'pending', NOW())");
                $stmt->bind_param("iids", $_SESSION['user_id'], $course_id, $course['price'], $target_path);
                
                if ($stmt->execute()) {
                    $success = "Bukti pembayaran berhasil diunggah dan sedang diverifikasi.";
                } else {
                    $error = "Gagal menyimpan data transaksi.";
                }
            } else {
                $error = "Gagal mengunggah file.";
            }
        } else {
            $error = "Tipe file tidak diizinkan. Gunakan JPG, JPEG, PNG, atau PDF.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pembayaran - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <link rel="stylesheet" href="payment.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <main class="payment-container">
        <h1>Pembayaran</h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="payment-summary">
            <h2>Ringkasan Pembayaran</h2>
            <div class="course-info">
                <img src="<?php echo htmlspecialchars($course['image_path']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                <div>
                    <p class="course-title"><?php echo htmlspecialchars($course['title']); ?></p>
                    <p class="course-price">Rp <?php echo number_format($course['price'], 0, ',', '.'); ?></p>
                </div>
            </div>
        </div>

        <div class="transfer-instructions">
            <h2>Transfer Manual</h2>
            <p>Silakan transfer ke rekening berikut:</p>
            <div class="bank-info">
                <p><strong>Bank:</strong> BCA</p>
                <p><strong>No. Rekening:</strong> 1234567890</p>
                <p><strong>Nama Penerima:</strong> PT. Edukasi Digital</p>
                <p><strong>Nominal:</strong> Rp <?php echo number_format($course['price'], 0, ',', '.'); ?></p>
            </div>
            <p>Setelah melakukan transfer, silakan unggah bukti pembayaran melalui form di bawah ini.</p>
            
            <form method="POST" enctype="multipart/form-data" id="payment-form">
                <label for="payment_proof" class="upload-label">
                    <i class="fas fa-upload"></i> Unggah Bukti Pembayaran
                    <input type="file" id="payment_proof" name="payment_proof" accept="image/*,.pdf" required hidden>
                </label>
                <p id="selected-file" class="selected-file"></p>
                <button type="submit" class="submit-button">Konfirmasi Pembayaran</button>
            </form>
        </div>
    </main>

    <?php if (isset($success)): ?>
    <div class="popup-overlay" id="popup" style="display: flex;">
        <div class="popup-content">
            <h2>Pembayaran Diproses!</h2>
            <p>Terima kasih, bukti pembayaran Anda sedang kami verifikasi.</p>
            <a href="transaction-history.php" class="profile-button">Cek Status</a>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Show selected filename
        document.getElementById('payment_proof').addEventListener('change', function() {
            const fileName = this.files[0]?.name;
            if (fileName) {
                document.getElementById('selected-file').textContent = 'File dipilih: ' + fileName;
            }
        });
    </script>
</body>

</html> 