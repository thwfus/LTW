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
        // $field đã qua whitelist nên an toàn để dùng trong tên cột
        $stmt = $conn->prepare("UPDATE `profile` SET `{$field}` = ? WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $value, $currentUserId);
            if ($stmt->execute()) {
                header('Location: user.php?updated=' . urlencode($field));
                exit;
            } else {
                $userMessage     = 'Không thể cập nhật. Vui lòng thử lại.';
                $userMessageType = 'error';
            }
            $stmt->close();
        }
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
