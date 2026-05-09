<?php
// QA CRUD logic — requires $conn (mysqli) to already be available via dbacc.php

$qaMessage = '';
$qaMessageType = 'success';
$qaRows = [];
$qaTable = null;
$qaCategories = ['product', 'order', 'shipping', 'warranty'];
$qaStatuses = ['pub', 'draft'];

if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

function detectQaTable(mysqli $conn): ?string {
    $requiredColumns = ['q_id', 'title', 'content', 'category', 'status', 'role'];
    $tablesResult = $conn->query('SHOW TABLES');
    if (!$tablesResult) {
        return null;
    }

    while ($tableRow = $tablesResult->fetch_array()) {
        $tableName = $tableRow[0] ?? null;
        if (!$tableName || !preg_match('/^[A-Za-z0-9_]+$/', $tableName)) {
            continue;
        }

        $descResult = $conn->query("SHOW COLUMNS FROM `{$tableName}`");
        if (!$descResult) {
            continue;
        }

        $columns = [];
        while ($column = $descResult->fetch_assoc()) {
            $columns[] = $column['Field'];
        }

        if (empty(array_diff($requiredColumns, $columns))) {
            return $tableName;
        }
    }

    return null;
}

$qaTable = detectQaTable($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qa_action']) && $qaTable !== null) {
    $action = $_POST['qa_action'];

    if ($action === 'add') {
        $title    = trim($_POST['title'] ?? '');
        $content  = trim($_POST['content'] ?? '');
        $category = $_POST['category'] ?? '';
        $status   = $_POST['status'] ?? '';
        $role     = 'admin';

        if ($title === '' || $content === '') {
            $qaMessage = 'Vui lòng nhập đầy đủ title và content.';
            $qaMessageType = 'error';
        } elseif (!in_array($category, $qaCategories, true) || !in_array($status, $qaStatuses, true)) {
            $qaMessage = 'Category hoặc status không hợp lệ.';
            $qaMessageType = 'error';
        } else {
            $stmt = $conn->prepare("INSERT INTO `{$qaTable}` (title, content, category, status, role) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('sssss', $title, $content, $category, $status, $role);
    
                if ($stmt->execute()) {
                    $qaMessage = 'Đã thêm câu hỏi mới vào database.';
                    $qaMessageType = 'success';
                    
                    // --- GIẢI PHÁP BỔ SUNG: CHỐNG F5 (REFRESH) ---
                    // Sau khi thêm thành công, điều hướng lại trang để xóa dữ liệu POST
                    header("Location: admin_dashboard.php?status=success#qa");
                    exit(); 
                }
                else
                {
                    $qaMessage = 'Không thể thêm câu hỏi mới.';
                    $qaMessageType = 'error';
                }
                $stmt->close();
            }
        }
    }

    if ($action === 'update') {
        $qId     = (int)($_POST['q_id'] ?? 0);
        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category = $_POST['category'] ?? '';
        $status   = $_POST['status'] ?? '';

        if ($qId <= 0 || $title === '' || $content === '') {
            $qaMessage = 'Dữ liệu cập nhật chưa hợp lệ.';
            $qaMessageType = 'error';
        } elseif (!in_array($category, $qaCategories, true) || !in_array($status, $qaStatuses, true)) {
            $qaMessage = 'Category hoặc status không hợp lệ.';
            $qaMessageType = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE `{$qaTable}` SET title = ?, content = ?, category = ?, status = ? WHERE q_id = ?");
            if ($stmt) {
                $stmt->bind_param('ssssi', $title, $content, $category, $status, $qId);
                if ($stmt->execute()) {
                    $qaMessage = 'Đã cập nhật câu hỏi #' . $qId . '.';
                } else {
                    $qaMessage = 'Không thể cập nhật câu hỏi #' . $qId . '.';
                    $qaMessageType = 'error';
                }
                $stmt->close();
            } else {
                $qaMessage = 'Không thể tạo câu lệnh cập nhật dữ liệu.';
                $qaMessageType = 'error';
            }
        }
    }

    if ($action === 'delete') {
        $qId = (int)($_POST['q_id'] ?? 0);
        if ($qId <= 0) {
            $qaMessage = 'ID câu hỏi không hợp lệ.';
            $qaMessageType = 'error';
        } else {
            $stmt = $conn->prepare("DELETE FROM `{$qaTable}` WHERE q_id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $qId);
                if ($stmt->execute()) {
                    $qaMessage = 'Đã xoá câu hỏi #' . $qId . '.';
                } else {
                    $qaMessage = 'Không thể xoá câu hỏi #' . $qId . '.';
                    $qaMessageType = 'error';
                }
                $stmt->close();
            } else {
                $qaMessage = 'Không thể tạo câu lệnh xoá dữ liệu.';
                $qaMessageType = 'error';
            }
        }
    }
}

if ($qaTable !== null) {
    $result = $conn->query("SELECT q_id, title, content, category, status, role FROM `{$qaTable}` ORDER BY q_id DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $qaRows[] = $row;
        }
        $result->free();
    } else {
        $qaMessage = 'Không thể đọc dữ liệu câu hỏi từ database.';
        $qaMessageType = 'error';
    }
} else {
    $qaMessage = 'Không tìm thấy bảng Q&A có đủ các cột q_id, title, content, category, status, role.';
    $qaMessageType = 'error';
}
