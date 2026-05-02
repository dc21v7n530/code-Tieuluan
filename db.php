<?php
// db.php - File cấu hình kết nối Database
$servername = "localhost";
$username = "root";     // Mặc định của XAMPP là root
$password = "";         // Mặc định của XAMPP là rỗng (không có pass)
$dbname = "09homestay_db"; // Tên Database bạn đã tạo trong phpMyAdmin

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Bật font tiếng Việt (UTF-8)
mysqli_set_charset($conn, 'utf8mb4');

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Lỗi kết nối CSDL: " . $conn->connect_error);
}
?>