<?php
// config_m1.php
$host = 'localhost';
$db   = 'btl_ltw'; // Đảm bảo tên database đúng là btl_ltw
$user = 'root';
$pass = '';

// 1. Kết nối kiểu PDO (Dành cho phần của Huy)
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lỗi kết nối PDO: " . $e->getMessage());
}

// 2. Kết nối kiểu mysqli (Dành cho phần Q&A của bạn Huy)
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Lỗi kết nối mysqli: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>