<?php
// Bật session để lưu trạng thái đăng nhập
session_start();
include 'db.php';

// -----------------------------------------
// 1. XỬ LÝ ĐĂNG KÝ (Register)
// -----------------------------------------
if (isset($_POST['action']) && $_POST['action'] == 'register') {
    $name = trim($_POST['regName']);
    $email = trim($_POST['regEmail']);
    $password = $_POST['regPassword'];

    // Kiểm tra xem email đã tồn tại trong CSDL chưa
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        // Email đã tồn tại
        echo json_encode(['status' => 'error', 'message' => 'Email này đã được đăng ký!']);
    } else {
        // Mã hóa mật khẩu trước khi lưu (Bảo mật cơ bản)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Lưu user mới vào CSDL
        $insert_stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("sss", $name, $email, $hashed_password);
        
        if($insert_stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Đăng ký thành công! Vui lòng đăng nhập.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Có lỗi xảy ra khi lưu dữ liệu.']);
        }
    }
    exit();
}

// -----------------------------------------
// 2. XỬ LÝ ĐĂNG NHẬP (Login)
// -----------------------------------------
if (isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = trim($_POST['loginEmail']);
    $password = $_POST['loginPassword'];

    // Tìm user theo email
    $stmt = $conn->prepare("SELECT id, full_name, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Kiểm tra mật khẩu (so sánh mật khẩu gõ vào với mật khẩu đã mã hóa trong CSDL)
        if (password_verify($password, $user['password'])) {
            // Đăng nhập thành công -> Lưu thông tin vào Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $email;
            
            echo json_encode(['status' => 'success', 'message' => 'Đăng nhập thành công!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Mật khẩu không chính xác!']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tài khoản không tồn tại!']);
    }
    exit();
}

// -----------------------------------------
// 3. XỬ LÝ ĐĂNG XUẤT (Logout)
// -----------------------------------------
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy(); // Hủy toàn bộ session
    header("Location: index.php"); // Đẩy về trang chủ
    exit();
}
?>