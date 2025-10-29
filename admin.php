<?php
session_start();

// For demonstration, allow access always. Replace with your login check:
// if (!isset($_SESSION['admin_logged_in'])) {
//     header('Location: login.php');
//     exit();
// }

$host = 'localhost';
$db = 'ecourse';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_course'])) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $image = $_POST['image'] ?? '';

    $stmt = $conn->prepare("INSERT INTO courses (title, description, price, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $title, $description, $price, $image);
    $stmt->execute();
    $stmt->close();

    header('Location: admin.php');
    exit();
}

// Delete course
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM courses WHERE id = $id");
    header('Location: admin.php');
    exit();
}

// Fetch courses
$result = $conn->query("SELECT * FROM courses");

// Fetch course to edit
$edit_course = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM courses WHERE id = $edit_id");
    if ($res) {
        $edit_course = $res->fetch_assoc();
    }
}

// Update course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_course'])) {
    $id = intval($_POST['id']);
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $image = $_POST['image'] ?? '';

    $stmt = $conn->prepare("UPDATE courses SET title = ?, description = ?, price = ?, image = ? WHERE id = ?");
    $stmt->bind_param("ssdsi", $title, $description, $price, $image, $id);
    $stmt->execute();
    $stmt->close();

    header('Location: admin.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Dashboard - E-Course</title>
    <link rel="icon" type="image/png" href="assets/logofull.svg" />
    <style>
        /* Reset and base */
        * {
            box-sizing: border-box; 
        }
        body {
            font-family: 'Inter', sans-serif;
            margin: 0; 
            background-color: #f5f7fa;
            color: #333;
        }
        a {
            color: #1461b6;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        header {
            background-color: #1461b6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
        }
        header .logo img {
            height: 35px;
        }
        nav a {
            margin-right: 1.5rem;
            font-weight: 600;
            color: white;
        }
        nav a.active {
            text-decoration: underline;
        }
        .container {
            max-width: 1100px;
            margin: 2rem auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        h1 {
            margin-bottom: 1.5rem;
            color: #1461b6;
        }
        /* Form styles */
        form {
            margin-bottom: 2rem;
        }
        form input[type=text],
        form input[type=number],
        form textarea {
            width: 100%;
            padding: 0.7rem;
            margin: 0.4rem 0 1rem 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
            resize: vertical;
        }
        form textarea {
            min-height: 80px;
        }
        form button {
            background-color: #1461b6;
            color: white;
            border: none;
            padding: 0.7rem 1.2rem;
            font-size: 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        form button:hover {
            background-color: #0f4a8c;
        }
        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #f0f4f8;
            color: #1461b6;
        }
        td img {
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
        }
        .actions a {
            margin-right: 0.7rem;
            color: #1461b6;
            font-weight: 600;
        }
        .actions a.delete {
            color: #d9534f;
        }
        .actions a.delete:hover {
            text-decoration: underline;
        }
        /* Responsive */
        @media (max-width: 600px) {
            nav {
                display: none;
            }
            header {
                justify-content: center;
            }
            .container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="logo">
        <a href="home.html" style="text-decoration:none;"><span style="font-family: 'Inter', sans-serif; font-weight: 700; font-size: 22px; color: #0d6efd; letter-spacing: 0.5px;">K-Tutor</span></a>
    </div>
    <nav>
        <a href="home.html">Beranda</a>
        <a href="kursus.html">Cari Tutorial</a>
        <a href="aktivitas.html">Aktivitas</a>
        <a href="admin.php" class="active">Admin</a>
    </nav>
</header>

<div class="container">
    <h1>Admin Dashboard</h1>

    <?php if ($edit_course): ?>
        <h2>Edit Course</h2>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_course['id']); ?>" />
            <label>Title</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($edit_course['title']); ?>" required />
            
            <label>Description</label>
            <textarea name="description" required><?php echo htmlspecialchars($edit_course['description']); ?></textarea>
            
            <label>Price (Rp.)</label>
            <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($edit_course['price']); ?>" required />
            
            <label>Image URL</label>
            <input type="text" name="image" value="<?php echo htmlspecialchars($edit_course['image']); ?>" required />
            
            <button type="submit" name="update_course">Update Course</button>
            <a href="admin.php" style="margin-left: 1rem;">Cancel</a>
        </form>
    <?php else: ?>
        <h2>Add New Course</h2>
        <form method="POST">
            <label>Title</label>
            <input type="text" name="title" placeholder="Course Title" required />
            
            <label>Description</label>
            <textarea name="description" placeholder="Course Description" required></textarea>
            
            <label>Price (Rp.)</label>
            <input type="number" step="0.01" name="price" placeholder="Course Price" required />
            
            <label>Image URL</label>
            <input type="text" name="image" placeholder="Image URL" required />
            
            <button type="submit" name="add_course">Add Course</button>
        </form>
    <?php endif; ?>

    <h2>Course List</h2>
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Description</th>
                <th>Price (Rp.)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><img src="<?php echo htmlspecialchars($row['image']); ?>" alt="Thumbnail" /></td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo nl2br(htmlspecialchars($row['description'])); ?></td>
                <td><?php echo number_format($row['price'], 2, ',', '.'); ?></td>
                <td class="actions">
                    <a href="admin.php?edit=<?php echo $row['id']; ?>">Edit</a>
                    <a href="admin.php?delete=<?php echo $row['id']; ?>" class="delete" onclick="return confirm('Yakin ingin menghapus kursus ini?');">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>

<?php
$conn->close();
?>

