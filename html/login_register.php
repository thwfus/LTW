<?php
session_start();

require_once '../task1/config_m1.php';

/**
 * Tạo customer và cart cho user nếu chưa có.
 * Dùng cả khi đăng ký mới và khi đăng nhập tài khoản cũ trong DB.
 */
function ensureCustomerAndCart(PDO $pdo, int $userId): void
{
    $activeCartStatus = 'Đang chọn hàng';

    // 1. Kiểm tra customer đã tồn tại chưa
    $checkCustomer = $pdo->prepare("
        SELECT user_id 
        FROM customer 
        WHERE user_id = ?
        LIMIT 1
    ");
    $checkCustomer->execute([$userId]);

    if (!$checkCustomer->fetch(PDO::FETCH_ASSOC)) {
        $insertCustomer = $pdo->prepare("
            INSERT INTO customer (user_id)
            VALUES (?)
        ");
        $insertCustomer->execute([$userId]);
    }

    // 2. Kiểm tra user đã có cart đang chọn hàng chưa
    $checkCart = $pdo->prepare("
        SELECT cart_id 
        FROM cart 
        WHERE cus_id = ? 
          AND status = ?
        LIMIT 1
    ");
    $checkCart->execute([$userId, $activeCartStatus]);

    if (!$checkCart->fetch(PDO::FETCH_ASSOC)) {
        $insertCart = $pdo->prepare("
            INSERT INTO cart (created_at, status, cus_id)
            VALUES (NOW(), ?, ?)
        ");
        $insertCart->execute([$activeCartStatus, $userId]);
    }
}


// =======================
// XỬ LÝ ĐĂNG KÝ
// =======================
if (isset($_POST['register'])) {
    $name = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        header("Location: register.php?error=password_mismatch");
        exit();
    }

    try {
        // Kiểm tra email đã tồn tại chưa
        $stmt = $pdo->prepare("
            SELECT user_id 
            FROM `User` 
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);

        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            header("Location: register.php?error=email_exists");
            exit();
        }

        $pdo->beginTransaction();

        // Mã hóa mật khẩu
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Tạo user
        // Nếu bảng User của bạn không có cột phone/status/created_at thì báo tôi sửa lại.
        $insertUser = $pdo->prepare("
            INSERT INTO `User` 
                (user_name, email, phone, created_at, status, password, role)
            VALUES 
                (?, ?, '', NOW(), 'Hoạt động', ?, 'customer')
        ");
        $insertUser->execute([$name, $email, $hashed_password]);

        $newUserId = (int)$pdo->lastInsertId();

        // Tạo profile
        $year = (int)date('Y');

        $profileInsert = $pdo->prepare("
            INSERT INTO profile 
                (user_id, name, email, phone, address, avatar_url, member_since) 
            VALUES 
                (?, ?, ?, '', '', '', ?)
        ");
        $profileInsert->execute([$newUserId, $name, $email, $year]);

        // Tạo customer + cart
        ensureCustomerAndCart($pdo, $newUserId);

        $pdo->commit();

        header("Location: login.php?status=registered");
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        die("Lỗi hệ thống khi đăng ký: " . $e->getMessage());
    }
}


// =======================
// XỬ LÝ ĐĂNG NHẬP
// =======================
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("
            SELECT * 
            FROM `User` 
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $userId = (int)$user['user_id'];

            // Nếu là customer thì đảm bảo có customer + cart.
            // Dòng này giúp tài khoản cũ trong DB không bị lỗi thiếu cart/customer.
            if ($user['role'] === 'customer') {
                ensureCustomerAndCart($pdo, $userId);
            }

            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $user['user_name'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: ../html/admin_dashboard.php");
            } else {
                header("Location: ../task1/index.php");
            }

            exit();

        } else {
            header("Location: login.php?error=wrong_password");
            exit();
        }

    } catch (PDOException $e) {
        die("Lỗi hệ thống khi đăng nhập: " . $e->getMessage());
    }
}
?>