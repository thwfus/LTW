<?php
// User profile logic — $conn (mysqli) must be available from dbacc.php

if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

$currentUserId   = $_SESSION['user_id'];
$userMessage     = '';
$userMessageType = 'success';
$allowedFields   = ['name', 'email', 'phone', 'address'];

// Xử lý POST cập nhật một trường
// --- XỬ LÝ CẬP NHẬT ĐỒNG BỘ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['field'])) {
    $field = $_POST['field'] ?? '';
    $value = trim($_POST['value'] ?? '');

    if (!in_array($field, $allowedFields, true)) {
        $userMessage     = 'Trường dữ liệu không hợp lệ.';
        $userMessageType = 'error';
    } elseif ($value === '') {
        $userMessage     = 'Giá trị không được để trống.';
        $userMessageType = 'error';
    } else {
        try {
            // Bắt đầu giao dịch để đảm bảo an toàn dữ liệu
            $conn->begin_transaction();

            // 1. Cập nhật bảng profile (luôn thực hiện)
            $stmt1 = $conn->prepare("UPDATE `profile` SET `{$field}` = ? WHERE user_id = ?");
            $stmt1->bind_param('si', $value, $currentUserId);
            $stmt1->execute();

            // 2. Cập nhật bảng User nếu là name hoặc email
            if ($field === 'name') {
                $stmt2 = $conn->prepare("UPDATE `User` SET `user_name` = ? WHERE user_id = ?");
                $stmt2->bind_param('si', $value, $currentUserId);
                $stmt2->execute();
                $_SESSION['user_name'] = $value; // Cập nhật session để hiển thị tên mới trên header
            } 
            elseif ($field === 'email') {
                $stmt2 = $conn->prepare("UPDATE `User` SET `email` = ? WHERE user_id = ?");
                $stmt2->bind_param('si', $value, $currentUserId);
                $stmt2->execute();
            }
            elseif ($field === 'phone') {
                $stmt2 = $conn->prepare("UPDATE `User` SET `phone` = ? WHERE user_id = ?");
                $stmt2->bind_param('si', $value, $currentUserId);
                $stmt2->execute();
            }


            // Hoàn tất cập nhật
            $conn->commit();
            header('Location: user.php?updated=' . urlencode($field));
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $userMessage     = 'Lỗi hệ thống: ' . $e->getMessage();
            $userMessageType = 'error';
        }
    }
}

// --- XỬ LÝ ĐỔI MẬT KHẨU ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPwd = $_POST['current_password'] ?? '';
    $newPwd     = $_POST['new_password'] ?? '';
    $confirmPwd = $_POST['confirm_password'] ?? '';

    try {
        // 1. Lấy mật khẩu hiện tại từ bảng User
        $stmt = $conn->prepare("SELECT password FROM User WHERE user_id = ?");
        $stmt->bind_param('i', $currentUserId);
        $stmt->execute();
        $res = $stmt->get_result();
        $userData = $res->fetch_assoc();

        if (!$userData || !password_verify($currentPwd, $userData['password'])) {
            $userMessage = "Mật khẩu hiện tại không chính xác.";
            $userMessageType = "error";
        } elseif ($newPwd !== $confirmPwd) {
            $userMessage = "Mật khẩu mới và xác nhận không khớp.";
            $userMessageType = "error";
        } elseif (strlen($newPwd) < 6) {
            $userMessage = "Mật khẩu mới phải có ít nhất 6 ký tự.";
            $userMessageType = "error";
        } else {
            // 2. Mã hóa mật khẩu mới và cập nhật
            $hashedPwd = password_hash($newPwd, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE User SET password = ? WHERE user_id = ?");
            $updateStmt->bind_param('si', $hashedPwd, $currentUserId);
            
            if ($updateStmt->execute()) {
                $userMessage = "Đổi mật khẩu thành công!";
                $userMessageType = "success";
            }
        }
    } catch (Exception $e) {
        $userMessage = "Lỗi hệ thống: " . $e->getMessage();
        $userMessageType = "error";
    }
}

// Đọc dữ liệu người dùng
$userProfile = [
    'user_id'      => $currentUserId,
    'name'         => '',
    'email'        => '',
    'phone'        => '',
    'address'      => '',
    'avatar_url'   => 'https://images.pexels.com/photos/220453/pexels-photo-220453.jpeg?auto=compress&cs=tinysrgb&w=300',
    'member_since' => 2026,
];

$stmt = $conn->prepare("SELECT * FROM `profile` WHERE user_id = ? LIMIT 1");
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $userProfile = $row;
}
$stmt->close();

// Thông báo sau redirect
if ($userMessage === '' && isset($_GET['updated'])) {
    $fieldLabels = [
        'name'    => 'Full Name',
        'email'   => 'Email Address',
        'phone'   => 'Phone Number',
        'address' => 'Shipping Address',
    ];
    $updated        = $_GET['updated'];
    $label          = $fieldLabels[$updated] ?? $updated;
    $userMessage    = $label . ' đã được cập nhật thành công.';
    $userMessageType = 'success';
}
