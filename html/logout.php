<?php
session_start();
// Xóa tất cả các biến session
session_unset();
// Hủy bỏ phiên làm việc
session_destroy();

// Chuyển hướng về trang chủ ở task1
header("Location: ../task1/index.php");
exit();
?>