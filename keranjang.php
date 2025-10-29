<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle add to cart
if (isset($_GET['action']) && $_GET['action'] == 'add' && isset($_GET['id'])) {
    $course_id = $_GET['id'];
    
    // Check if course exists
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Add to cart session
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }
        if (!in_array($course_id, $_SESSION['cart'])) {
            $_SESSION['cart'][] = $course_id;
        }
    }
    header("Location: keranjang.php");
    exit();
}

// Handle remove from cart
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['id'])) {
    $course_id = $_GET['id'];
    if (isset($_SESSION['cart'])) {
        $key = array_search($course_id, $_SESSION['cart']);
        if ($key !== false) {
            unset($_SESSION['cart'][$key]);
        }
    }
    header("Location: keranjang.php");
    exit();
}

// Get cart items
$cart_items = array();
$total_price = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $cart_ids = implode(',', array_map('intval', $_SESSION['cart']));
    $query = "SELECT * FROM courses WHERE id IN ($cart_ids)";
    $result = $conn->query($query);
    if ($result) {
        while ($course = $result->fetch_assoc()) {
            $cart_items[] = $course;
            $total_price += $course['price'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang - E-Course</title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <link rel="stylesheet" type="text/css" href="fontawesome/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #ffffff;
            color: #1a1a1a;
            padding: 32px;
            max-width: 800px;
            margin: 0 auto;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #1a1a1a;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            margin-bottom: 24px;
            background: #f5f5f5;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .back-button:hover {
            background: #e8e8e8;
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 40px;
            color: #1a1a1a;
        }

        .cart-item {
            display: flex;
            align-items: flex-start;
            gap: 24px;
            padding: 24px 0;
            border-bottom: 1px solid #eee;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
        }

        .item-details {
            flex: 1;
        }

        .item-details h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .item-details p {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .item-price {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .remove-btn {
            background: none;
            border: none;
            padding: 8px;
            color: #ff4444;
            cursor: pointer;
            font-size: 20px;
            transition: color 0.2s ease;
            margin-top: 4px;
        }

        .remove-btn:hover {
            color: #cc0000;
        }

        .empty-cart {
            text-align: center;
            padding: 60px 0;
        }

        .empty-cart i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 16px;
        }

        .empty-cart p {
            font-size: 16px;
            color: #666;
            margin-bottom: 24px;
        }

        .browse-courses-btn {
            display: inline-block;
            background: #007bff;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.2s ease;
        }

        .browse-courses-btn:hover {
            background: #0056b3;
        }

        @media (max-width: 600px) {
            body {
                padding: 16px;
            }

            .cart-item {
                gap: 16px;
            }

            .cart-item img {
                width: 60px;
                height: 60px;
            }

            .item-details h3 {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <a href="kursus.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Kembali
    </a>

    <h1>Keranjang kamu</h1>

    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-basket"></i>
            <p>Keranjang belanja Anda masih kosong</p>
            <a href="kursus.php" class="browse-courses-btn">Cari Tutorial</a>
        </div>
    <?php else: ?>
        <?php foreach ($cart_items as $item): ?>
            <div class="cart-item">
                <img src="<?php echo htmlspecialchars($item['image_path'] ?? 'assets/default-course.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($item['title']); ?>">
                <div class="item-details">
                    <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                    <p>Kelas online | Akses selamanya</p>
                    <span class="item-price">Rp. <?php echo number_format($item['price'], 0, ',', '.'); ?></span>
                </div>
                <a href="keranjang.php?action=remove&id=<?php echo $item['id']; ?>" class="remove-btn">
                    <i class="fas fa-trash-alt"></i>
                </a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>