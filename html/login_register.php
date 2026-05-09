<?php
session_start();
// Sử dụng file config chung để kết nối PDO
require_once '../task1/config_m1.php'; 

// --- XỬ LÝ ĐĂNG KÝ ---
if (isset($_POST['register'])) {
    $name = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Kiểm tra mật khẩu khớp nhau
    if ($password !== $confirm_password) {
        header("Location: register.php?error=password_mismatch"); // Chuyển hướng kèm mã lỗi
        exit();
    }

    try {
        // 2. Kiểm tra email đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT * FROM User WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            header("Location: register.php?error=email_exists"); // Chuyển hướng khi mail tồn tại
            exit();
        } else {
            // 3. Mã hóa mật khẩu để bảo mật
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 4. Lưu vào bảng User
            $insert = $pdo->prepare("INSERT INTO User (user_name, email, password, role) VALUES (?, ?, ?, 'member')");
            if ($insert->execute([$name, $email, $hashed_password])) {
                // 5. Tạo profile cho user mới với tên và email, còn lại để trống
                $newUserId = $pdo->lastInsertId();
                $year = (int)date('Y');
                $profileInsert = $pdo->prepare("INSERT INTO profile (user_id, name, email, phone, address, avatar_url, member_since) VALUES (?, ?, ?, '', '', '', ?)");
                $profileInsert->execute([$newUserId, $name, $email, $year]);

                header("Location: login.php?status=registered");
                exit();
            }
        }
    } catch (PDOException $e) {
        die("Lỗi hệ thống: " . $e->getMessage());
    }
}

// --- XỬ LÝ ĐĂNG NHẬP ---
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM User WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['user_name'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: ../html/admin_dashboard.php");
            } else {
                header("Location: ../task1/index.php");
            }
            exit();
        } else {
            // Đăng nhập thất bại
            header("Location: login.php?error=wrong_password");
            exit();
        }
    } catch (PDOException $e) {
        die("Lỗi hệ thống: " . $e->getMessage());
    }
}
?>