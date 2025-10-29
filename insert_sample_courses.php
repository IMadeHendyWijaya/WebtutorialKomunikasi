<?php
require_once 'config.php';

$sample_courses = [
    [
        'title' => 'Machine Learning',
        'description' => 'Pelajari dasar-dasar machine learning dan implementasinya dalam dunia nyata.',
        'price' => 100000,
        'image_path' => 'assets/2.svg'
    ],
    [
        'title' => 'Web Development',
        'description' => 'Kuasai pengembangan web dari dasar hingga mahir.',
        'price' => 60000,
        'image_path' => 'assets/1.svg'
    ],
    [
        'title' => 'Game Development',
        'description' => 'Belajar membuat game dari konsep hingga publikasi.',
        'price' => 50000,
        'image_path' => 'assets/3.svg'
    ],
    [
        'title' => 'Digital Marketing',
        'description' => 'Pelajari strategi pemasaran digital yang efektif.',
        'price' => 80000,
        'image_path' => 'assets/6.svg'
    ],
    [
        'title' => 'Internet of Things',
        'description' => 'Eksplorasi dunia IoT dan implementasinya.',
        'price' => 120000,
        'image_path' => 'assets/5.svg'
    ],
    [
        'title' => 'UI/UX Design',
        'description' => 'Pelajari prinsip desain UI/UX dan implementasinya.',
        'price' => 38000,
        'image_path' => 'assets/4.svg'
    ]
];

$stmt = $conn->prepare("INSERT INTO courses (title, description, price, image_path) VALUES (?, ?, ?, ?)");

foreach ($sample_courses as $course) {
    $stmt->bind_param("ssds", 
        $course['title'],
        $course['description'],
        $course['price'],
        $course['image_path']
    );
    
    if ($stmt->execute()) {
        echo "Berhasil menambahkan kursus: " . $course['title'] . "\n";
    } else {
        echo "Gagal menambahkan kursus: " . $course['title'] . " - " . $stmt->error . "\n";
    }
}

$stmt->close();
$conn->close();

echo "Selesai menambahkan data kursus contoh!";
?> 