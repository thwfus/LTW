<?php
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
 

  // 1. KIỂM TRA QUYỀN ADMIN (Dùng strtolower để tránh lỗi chữ hoa/thường)
  $isLoggedIn = isset($_SESSION['user_id']);
  $userRole = strtolower($_SESSION['role'] ?? 'guest');

  if (!$isLoggedIn || $userRole !== 'admin') {
      header("Location: ../html/login.php"); 
      exit();
  }

  // 2. NHÚNG CÁC FILE LOGIC
  require_once '../task1/config_m1.php'; 
  require_once '../task3/db.php'; 
  if (file_exists('includes/qa_logic.php')) {
    require_once 'includes/qa_logic.php';
}
  $qaMessage = "";


  // --- LOGIC XỬ LÝ DỮ LIỆU CỦA HUY ---
  $notice = $_SESSION['admin_notice'] ?? "";
  unset($_SESSION['admin_notice']);

  // Helper chống lỗi XSS
  if (!function_exists('h')) {
      function h($value) {
          return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
      }
  }

  // Cập nhật thông tin Website Info
  if (isset($_POST['update_profile'])) {
      $sql = "UPDATE Web_Info SET phone = ?, mail = ?, address = ? WHERE info_id = 1";
      $pdo->prepare($sql)->execute([$_POST['phone'], $_POST['mail'], $_POST['address']]);

      $_SESSION['admin_notice'] = "Đã cập nhật thông tin Website thành công!";
      header("Location: admin_dashboard.php#pages-manager");
      exit();
  }

  // Xử lý trạng thái tin nhắn Liên hệ
  if (isset($_GET['action']) && isset($_GET['msg_id'])) {
      $id = (int)$_GET['msg_id'];

      if ($_GET['action'] == 'mark_read') {
          $pdo->prepare("UPDATE Contact_Messages SET status = 'Đã đọc' WHERE message_id = ?")->execute([$id]);
          $_SESSION['admin_notice'] = "Đã đánh dấu liên hệ là đã đọc.";
      } elseif ($_GET['action'] == 'delete') {
          $pdo->prepare("DELETE FROM Contact_Messages WHERE message_id = ?")->execute([$id]);
          $_SESSION['admin_notice'] = "Đã xóa liên hệ.";
      }

      header("Location: admin_dashboard.php#contacts");
      exit();
  }

  // =================PHÚ==============
  // XỬ LÝ THÊM / SỬA / DUYỆT / GỠ DUYỆT / XÓA BÀI REVIEW
  // ===============================
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_action'])) {
      $action = $_POST['review_action'];
      $adminId = (int)$_SESSION['user_id'];

      // Kiểm tra adminId có tồn tại trong bảng admin không để tránh lỗi khóa ngoại
      $checkAdmin = $pdo->prepare("
          SELECT user_id 
          FROM admin 
          WHERE user_id = ? 
          LIMIT 1
      ");
      $checkAdmin->execute([$adminId]);
      $validAdminId = $checkAdmin->fetchColumn() ? $adminId : null;

      $title = trim($_POST['title'] ?? '');
      $content = trim($_POST['content'] ?? '');
      $image = trim($_POST['image'] ?? '');
      $reviewType = trim($_POST['review_type'] ?? 'website');
      $adminNote = trim($_POST['admin_note'] ?? '');

      $status = $_POST['status'] ?? 'pending';
      if (!in_array($status, ['pending', 'approved'])) {
          $status = 'pending';
      }

      $productIdRaw = trim($_POST['product_id'] ?? '');
      $productId = ($productIdRaw === '') ? null : (int)$productIdRaw;

      // Admin thêm bài review mới
      if ($action === 'add') {
          if ($title === '' || $content === '') {
              $_SESSION['admin_notice'] = "Vui lòng nhập tiêu đề và nội dung bài review.";
              header("Location: admin_dashboard.php#reviews");
              exit();
          }

          $stmt = $pdo->prepare("
      INSERT INTO review_post
          (title, content, image, status, review_type, admin_note, created_at, user_id, product_id, admin_id)
      VALUES
          (?, ?, ?, ?, ?, ?, NOW(), NULL, ?, ?)
  ");

  $stmt->execute([
      $title,
      $content,
      $image,
      $status,
      $reviewType,
      $adminNote,
      $productId,
      $validAdminId
  ]);

          $_SESSION['admin_notice'] = "Đã thêm bài review mới.";
          header("Location: admin_dashboard.php#reviews");
          exit();
      }

      // Admin sửa bài review
      if ($action === 'edit' && isset($_POST['review_id'])) {
          $reviewId = (int)$_POST['review_id'];

          if ($title === '' || $content === '') {
              $_SESSION['admin_notice'] = "Vui lòng nhập tiêu đề và nội dung bài review.";
              header("Location: admin_dashboard.php#reviews");
              exit();
          }

          $stmt = $pdo->prepare("
              UPDATE review_post
              SET title = ?,
                  content = ?,
                  image = ?,
                  status = ?,
                  review_type = ?,
                  admin_note = ?,
                  product_id = ?,
                  admin_id = ?
              WHERE review_id = ?
          ");

          $stmt->execute([
              $title,
              $content,
              $image,
              $status,
              $reviewType,
              $adminNote,
              $productId,
              $validAdminId,
              $reviewId
          ]);

          $_SESSION['admin_notice'] = "Đã cập nhật bài review.";
          header("Location: admin_dashboard.php#reviews");
          exit();
      }

      // Duyệt bài
      if ($action === 'approve' && isset($_POST['review_id'])) {
          $reviewId = (int)$_POST['review_id'];

          $stmt = $pdo->prepare("
              UPDATE review_post
              SET status = 'approved',
                  admin_note = ?,
                  admin_id = ?
              WHERE review_id = ?
          ");

          $stmt->execute([
              $adminNote !== '' ? $adminNote : 'Approved by admin.',
              $validAdminId,
              $reviewId
          ]);

          $_SESSION['admin_notice'] = "Đã duyệt bài review.";
          header("Location: admin_dashboard.php#reviews");
          exit();
      }

      // Gỡ duyệt bài
      if ($action === 'pending' && isset($_POST['review_id'])) {
          $reviewId = (int)$_POST['review_id'];

          $stmt = $pdo->prepare("
              UPDATE review_post
              SET status = 'pending',
                  admin_note = ?,
                  admin_id = ?
              WHERE review_id = ?
          ");

          $stmt->execute([
              $adminNote !== '' ? $adminNote : 'Moved back to pending.',
              $validAdminId,
              $reviewId
          ]);

          $_SESSION['admin_notice'] = "Đã gỡ duyệt bài review.";
          header("Location: admin_dashboard.php#reviews");
          exit();
      }

      // Xóa bài
      if ($action === 'delete' && isset($_POST['review_id'])) {
          $reviewId = (int)$_POST['review_id'];

          $pdo->prepare("DELETE FROM review_cmt WHERE review_id = ?")->execute([$reviewId]);
          $pdo->prepare("DELETE FROM review_post WHERE review_id = ?")->execute([$reviewId]);

          $_SESSION['admin_notice'] = "Đã xóa bài review.";
          header("Location: admin_dashboard.php#reviews");
          exit();
      }
  }

  // =================PHÚ============
  // XỬ LÝ DUYỆT / ẨN / XÓA BÌNH LUẬN REVIEW
  // ===============================
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_action'], $_POST['review_cmt_id'])) {
      $commentId = (int)$_POST['review_cmt_id'];
      $action = $_POST['comment_action'];
      $adminId = (int)$_SESSION['user_id'];
      $adminNote = trim($_POST['admin_note'] ?? '');

      $checkAdmin = $pdo->prepare("SELECT user_id FROM admin WHERE user_id = ?");
      $checkAdmin->execute([$adminId]);

      if (!$checkAdmin->fetch()) {
          // Nếu chưa có trong bảng admin, thì chèn vào luôn
          $insertAdmin = $pdo->prepare("INSERT INTO admin (user_id) VALUES (?)");
          $insertAdmin->execute([$adminId]);
      }

      if ($action === 'approve') {
          $stmt = $pdo->prepare("
              UPDATE review_cmt
              SET status = 'approved',
                  admin_note = ?,
                  admin_id = ?
              WHERE review_cmt_id = ?
          ");
          $stmt->execute([
              $adminNote !== '' ? $adminNote : 'Approved by admin.',
              $adminId,
              $commentId
          ]);

          $_SESSION['admin_notice'] = "Đã duyệt bình luận.";
      }

      if ($action === 'pending') {
          $stmt = $pdo->prepare("
              UPDATE review_cmt
              SET status = 'pending',
                  admin_note = ?,
                  admin_id = ?
              WHERE review_cmt_id = ?
          ");
          $stmt->execute([
              $adminNote !== '' ? $adminNote : 'Hidden by admin.',
              $adminId,
              $commentId
          ]);

          $_SESSION['admin_notice'] = "Đã ẩn bình luận.";
      }

      if ($action === 'delete') {
          $pdo->prepare("DELETE FROM review_cmt WHERE review_cmt_id = ?")->execute([$commentId]);

          $_SESSION['admin_notice'] = "Đã xóa bình luận.";
      }

      header("Location: admin_dashboard.php#reviews");
      exit();
  }

  // HẾT PHÚ

  // ===============================
  // XỬ LÝ QUẢN LÝ SẢN PHẨM
  // ===============================
  $productNotice = '';
  $productNoticeType = 'success';

  // Lấy danh sách danh mục
  $categories = $pdo->query("SELECT category_id, category_name FROM category ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_action'])) {
      $prodAction = $_POST['product_action'];
      $productId = (int)($_POST['product_id'] ?? 0);
      $prodName = trim($_POST['product_name'] ?? '');
      $prodPrice = (float)($_POST['price'] ?? 0);
      $prodStock = (int)($_POST['stock_quantity'] ?? 0);
      $prodMaterial = trim($_POST['material'] ?? '');
      $prodColor = trim($_POST['color'] ?? '');
      $prodWarranty = trim($_POST['warranty_period'] ?? '');
      $prodCategoryId = (int)($_POST['category_id'] ?? 0);
      $prodOldUrl = trim($_POST['old_url'] ?? '');

      if ($prodAction === 'create' || $prodAction === 'update') {
          if ($prodName === '' || strlen($prodName) > 150) {
              $productNotice = 'Tên sản phẩm không được rỗng và không vượt quá 150 ký tự.';
              $productNoticeType = 'error';
          } elseif ($prodPrice <= 0) {
              $productNotice = 'Giá sản phẩm phải lớn hơn 0.';
              $productNoticeType = 'error';
          } elseif ($prodStock < 0) {
              $productNotice = 'Tồn kho không được âm.';
              $productNoticeType = 'error';
          } elseif ($prodCategoryId <= 0) {
              $productNotice = 'Vui lòng chọn danh mục.';
              $productNoticeType = 'error';
          } else {
              // Xử lý upload ảnh
              $imagePath = $prodOldUrl;
              $uploadOk = true;
              if (!empty($_FILES['product_image']['name'])) {
                  $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
                  $fileType = $_FILES['product_image']['type'];
                  $fileSize = $_FILES['product_image']['size'];
                  if (!in_array($fileType, $allowed)) {
                      $productNotice = 'Định dạng ảnh không hợp lệ (JPG, PNG, WEBP, GIF).';
                      $productNoticeType = 'error';
                      $uploadOk = false;
                  } elseif ($fileSize > 3 * 1024 * 1024) {
                      $productNotice = 'Ảnh không được vượt quá 3MB.';
                      $productNoticeType = 'error';
                      $uploadOk = false;
                  } else {
                      $uploadDir = '../uploads/products/';
                      if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                      $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
                      $newName = 'prod_' . time() . '_' . rand(100,999) . '.' . $ext;
                      if (move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadDir . $newName)) {
                          $imagePath = '../uploads/products/' . $newName;
                      } else {
                          $productNotice = 'Upload ảnh thất bại.';
                          $productNoticeType = 'error';
                          $uploadOk = false;
                      }
                  }
              }
              if ($imagePath === '') $imagePath = '../uploads/products/default.jpg';

              if ($uploadOk) {
                  if ($prodAction === 'create') {
                      $stmt = $pdo->prepare('INSERT INTO product (product_name, price, stock_quantity, material, color, warranty_period, category_id, url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                      $ok = $stmt->execute([$prodName, $prodPrice, $prodStock, $prodMaterial, $prodColor, $prodWarranty, $prodCategoryId, $imagePath]);
                      $productNotice = $ok ? 'Đã thêm sản phẩm mới.' : 'Không thể thêm sản phẩm.';
                      $productNoticeType = $ok ? 'success' : 'error';
                  } else {
                      $stmt = $pdo->prepare('UPDATE product SET product_name=?, price=?, stock_quantity=?, material=?, color=?, warranty_period=?, category_id=?, url=? WHERE product_id=?');
                      $ok = $stmt->execute([$prodName, $prodPrice, $prodStock, $prodMaterial, $prodColor, $prodWarranty, $prodCategoryId, $imagePath, $productId]);
                      $productNotice = $ok ? 'Đã cập nhật sản phẩm.' : 'Không thể cập nhật sản phẩm.';
                      $productNoticeType = $ok ? 'success' : 'error';
                  }
                  $_SESSION['admin_notice'] = $productNotice;
                  header("Location: admin_dashboard.php#products");
                  exit();
              }
          }
      } elseif ($prodAction === 'delete') {
          $stmt = $pdo->prepare('DELETE FROM product WHERE product_id = ?');
          $ok = $stmt->execute([$productId]);
          $productNotice = $ok ? 'Đã xóa sản phẩm.' : 'Không thể xóa vì sản phẩm có thể đang nằm trong giỏ hàng hoặc đơn hàng.';
          $_SESSION['admin_notice'] = $productNotice;
          header("Location: admin_dashboard.php#products");
          exit();
      }
  }

  // Lấy sản phẩm đang sửa (nếu có)
  $prodEditId = (int)($_GET['edit_product'] ?? 0);
  $editProduct = null;
  if ($prodEditId > 0) {
      $stmt = $pdo->prepare('SELECT * FROM product WHERE product_id = ? LIMIT 1');
      $stmt->execute([$prodEditId]);
      $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Tìm kiếm & phân trang sản phẩm
  $prodQ = trim($_GET['prod_q'] ?? '');
  $prodCatFilter = (int)($_GET['prod_category'] ?? 0);
  $prodPage = max(1, (int)($_GET['prod_page'] ?? 1));
  $prodPerPage = 8;
  $prodOffset = ($prodPage - 1) * $prodPerPage;

  $prodWhere = []; $prodParams = [];
  if ($prodQ !== '') {
      $prodWhere[] = '(p.product_name LIKE ? OR p.material LIKE ? OR p.color LIKE ?)';
      $like = '%' . $prodQ . '%';
      $prodParams[] = $like; $prodParams[] = $like; $prodParams[] = $like;
  }
  if ($prodCatFilter > 0) {
      $prodWhere[] = 'p.category_id = ?';
      $prodParams[] = $prodCatFilter;
  }
  $prodWhereSql = !empty($prodWhere) ? 'WHERE ' . implode(' AND ', $prodWhere) : '';

  $countStmt = $pdo->prepare("SELECT COUNT(*) FROM product p JOIN category c ON p.category_id = c.category_id $prodWhereSql");
  $countStmt->execute($prodParams);
  $prodTotalRows = (int)$countStmt->fetchColumn();
  $prodTotalPages = max(1, ceil($prodTotalRows / $prodPerPage));

  $listStmt = $pdo->prepare("SELECT p.*, c.category_name FROM product p JOIN category c ON p.category_id = c.category_id $prodWhereSql ORDER BY p.product_id DESC LIMIT $prodPerPage OFFSET $prodOffset");
  $listStmt->execute($prodParams);
  $products = $listStmt->fetchAll(PDO::FETCH_ASSOC);

  // Helper format tiền VND
  if (!function_exists('money_vnd')) {
      function money_vnd($amount) {
          return number_format((float)$amount, 0, ',', '.') . ' ₫';
      }
  }
  // Helper ảnh sản phẩm an toàn
  if (!function_exists('safe_product_image')) {
      function safe_product_image($url) {
          if (empty($url)) return '../uploads/products/default.jpg';
          if (str_starts_with($url, 'http')) return $url;
          return '../' . ltrim($url, '/');
      }
  }

  // Cập nhật $totalProducts từ DB
  $totalProducts = $pdo->query("SELECT COUNT(*) FROM product")->fetchColumn();

  // ===============================
  // XỬ LÝ QUẢN LÝ ĐƠN HÀNG & GIỎ HÀNG
  // ===============================
  $cartStatuses  = ['Đang chọn hàng', 'Đã đặt hàng', 'Đã hủy'];
  $orderStatuses = ['Chờ xử lý', 'Đã xác nhận', 'Đang giao', 'Hoàn thành', 'Đã hủy'];

  // Đảm bảo cột status tồn tại (migrate an toàn)
  try {
      $pdo->query("SELECT status FROM orders LIMIT 1");
  } catch (Exception $e) {
      try { $pdo->exec("ALTER TABLE orders ADD COLUMN status VARCHAR(50) DEFAULT 'Chờ xử lý'"); } catch (Exception $e2) {}
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_action'])) {
      $oAction = $_POST['order_action'];

      if ($oAction === 'update_cart_status') {
          $cartId  = (int)($_POST['cart_id'] ?? 0);
          $cStatus = trim($_POST['status'] ?? '');
          if (in_array($cStatus, $cartStatuses)) {
              $pdo->prepare('UPDATE cart SET status = ? WHERE cart_id = ?')->execute([$cStatus, $cartId]);
              $_SESSION['admin_notice'] = 'Đã cập nhật trạng thái giỏ hàng.';
          }
          header('Location: admin_dashboard.php#orders'); exit();
      }

      if ($oAction === 'update_order_status') {
          $orderId = (int)($_POST['order_id'] ?? 0);
          $oStatus = trim($_POST['status'] ?? '');
          if (in_array($oStatus, $orderStatuses)) {
              $pdo->prepare('UPDATE orders SET status = ? WHERE order_id = ?')->execute([$oStatus, $orderId]);
              $_SESSION['admin_notice'] = 'Đã cập nhật trạng thái đơn hàng.';
          }
          header('Location: admin_dashboard.php#orders'); exit();
      }
  }

  // Tìm kiếm & phân trang
  $orderQ    = trim($_GET['order_q'] ?? '');
  $cartPage  = max(1, (int)($_GET['cart_page']  ?? 1));
  $orderPage = max(1, (int)($_GET['order_page'] ?? 1));
  $ordPerPage = 6;

  // --- Giỏ hàng ---
  $cartWhere = []; $cartParams = [];
  if ($orderQ !== '') {
      $cartWhere[] = '(CAST(c.cart_id AS CHAR) LIKE ? OR u.user_name LIKE ? OR u.email LIKE ? OR c.status LIKE ?)';
      $like = '%' . $orderQ . '%';
      $cartParams = [$like, $like, $like, $like];
  }
  $cartWhereSql = $cartWhere ? 'WHERE ' . implode(' AND ', $cartWhere) : '';

  $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM cart c JOIN `User` u ON c.cus_id = u.user_id $cartWhereSql");
  $stmt2->execute($cartParams);
  $cartTotal = (int)$stmt2->fetchColumn();
  $cartTotalPages = max(1, ceil($cartTotal / $ordPerPage));
  $cartOffset = ($cartPage - 1) * $ordPerPage;

  $cartStmt = $pdo->prepare("SELECT c.cart_id, c.created_at, c.status, c.cus_id, u.user_name, u.email,
      COALESCE(SUM(ci.quantity),0) AS item_count,
      COALESCE(SUM(ci.quantity * ci.unit_price),0) AS cart_total
      FROM cart c JOIN `User` u ON c.cus_id = u.user_id
      LEFT JOIN cartitem ci ON c.cart_id = ci.cart_id
      $cartWhereSql
      GROUP BY c.cart_id, c.created_at, c.status, c.cus_id, u.user_name, u.email
      ORDER BY c.created_at DESC, c.cart_id DESC LIMIT $ordPerPage OFFSET $cartOffset");
  $cartStmt->execute($cartParams);
  $carts = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

  // --- Đơn hàng ---
  $ordWhere = []; $ordParams = [];
  if ($orderQ !== '') {
      $ordWhere[] = '(CAST(o.order_id AS CHAR) LIKE ? OR u.user_name LIKE ? OR u.email LIKE ? OR o.status LIKE ?)';
      $like = '%' . $orderQ . '%';
      $ordParams = [$like, $like, $like, $like];
  }
  $ordWhereSql = $ordWhere ? 'WHERE ' . implode(' AND ', $ordWhere) : '';
  $ordCountStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN `User` u ON o.cus_id = u.user_id $ordWhereSql");
  $ordCountStmt->execute($ordParams);
  $ordTotal = (int)$ordCountStmt->fetchColumn();
  $ordTotalPages = max(1, ceil($ordTotal / $ordPerPage));
  $ordOffset = ($orderPage - 1) * $ordPerPage;

  $ordStmt = $pdo->prepare("SELECT o.order_id, o.order_date, o.status, o.total_amount, o.shipping_fee,
      u.user_name, u.email,
      a.recipient_name, a.phone, a.ward, a.city,
      p.payment_method
      FROM orders o JOIN `User` u ON o.cus_id = u.user_id
      JOIN address a ON o.address_id = a.address_id
      LEFT JOIN payment p ON o.order_id = p.order_id
      $ordWhereSql
      ORDER BY o.order_date DESC, o.order_id DESC LIMIT $ordPerPage OFFSET $ordOffset");
  $ordStmt->execute($ordParams);
  $orders = $ordStmt->fetchAll(PDO::FETCH_ASSOC);

  // Chi tiết đơn hàng
  $detailOrderId = (int)($_GET['order_detail'] ?? 0);
  $orderItems = [];
  if ($detailOrderId > 0) {
      $diStmt = $pdo->prepare('SELECT oi.*, p.product_name, p.url, c.category_name
          FROM orderitem oi JOIN product p ON oi.product_id = p.product_id
          JOIN category c ON p.category_id = c.category_id
          WHERE oi.order_id = ? ORDER BY oi.product_id ASC');
      $diStmt->execute([$detailOrderId]);
      $orderItems = $diStmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Cập nhật $totalOrders từ DB thực
  $totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

  // ===============================
  // XỬ LÝ QUẢN LÝ THÀNH VIÊN
  // ===============================
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_action'])) {
      $uAction = $_POST['user_action'];
      $uId     = (int)($_POST['user_id'] ?? 0);

      if ($uAction === 'delete' && $uId > 0) {
          if ($uId === (int)$_SESSION['user_id']) {
              $_SESSION['admin_notice'] = 'Không thể xóa tài khoản đang đăng nhập.';
          } else {
              $pdo->prepare("DELETE FROM `User` WHERE user_id = ?")->execute([$uId]);
              $_SESSION['admin_notice'] = 'Đã xóa thành viên.';
          }
          header('Location: admin_dashboard.php#users'); exit();
      }

      if ($uAction === 'change_role' && $uId > 0) {
          $newRole = strtolower(trim($_POST['new_role'] ?? ''));
          if (in_array($newRole, ['admin', 'customer'])) {
              if ($uId === (int)$_SESSION['user_id'] && $newRole === 'customer') {
                  $_SESSION['admin_notice'] = 'Không thể tự hạ quyền tài khoản đang đăng nhập.';
              } else {
                  $pdo->prepare("UPDATE `User` SET role = ? WHERE user_id = ?")->execute([$newRole, $uId]);
                  if ($newRole === 'admin') {
                      try { $pdo->prepare("INSERT IGNORE INTO admin (user_id) VALUES (?)")->execute([$uId]); } catch (Exception $e) {}
                  } else {
                      try { $pdo->prepare("DELETE FROM admin WHERE user_id = ?")->execute([$uId]); } catch (Exception $e) {}
                  }
                  $_SESSION['admin_notice'] = 'Đã cập nhật quyền thành viên.';
              }
          }
          header('Location: admin_dashboard.php#users'); exit();
      }
  }

  // Lấy danh sách thành viên
  $userQ          = trim($_GET['user_q'] ?? '');
  $userRoleFilter = trim($_GET['user_role'] ?? '');
  $userPage       = max(1, (int)($_GET['user_page'] ?? 1));
  $userPerPage    = 10;

  $userWhere = []; $userParams = [];
  if ($userQ !== '') {
      $userWhere[] = '(user_name LIKE ? OR email LIKE ? OR phone LIKE ?)';
      $like = '%' . $userQ . '%';
      $userParams = [$like, $like, $like];
  }
  if (in_array(strtolower($userRoleFilter), ['admin', 'customer'])) {
      $userWhere[] = 'LOWER(role) = ?';
      $userParams[] = strtolower($userRoleFilter);
  }
  $userWhereSql = $userWhere ? 'WHERE ' . implode(' AND ', $userWhere) : '';

  $uCountStmt = $pdo->prepare("SELECT COUNT(*) FROM `User` $userWhereSql");
  $uCountStmt->execute($userParams);
  $userTotal      = (int)$uCountStmt->fetchColumn();
  $userTotalPages = max(1, ceil($userTotal / $userPerPage));
  $userOffset     = ($userPage - 1) * $userPerPage;

  $uStmt = $pdo->prepare("SELECT user_id, user_name, email, phone, password, role FROM `User` $userWhereSql ORDER BY user_id DESC LIMIT $userPerPage OFFSET $userOffset");
  $uStmt->execute($userParams);
  $users = $uStmt->fetchAll(PDO::FETCH_ASSOC);

  // 3. LẤY DỮ LIỆU HIỂN THỊ
  $company = $pdo->query("SELECT * FROM Web_Info WHERE info_id = 1")->fetch();
  $messages = $pdo->query("SELECT * FROM Contact_Messages ORDER BY created_at DESC")->fetchAll();
  $unreadCount = count(array_filter($messages, fn($m) => $m['status'] == 'Chưa đọc'));

  // $totalProducts và $totalOrders được lấy từ DB thực ở các section logic bên trên

  // Lấy danh sách bài review PHÚ
  // Lấy danh sách bài review, có tìm kiếm
  $reviewKeyword = trim($_GET['review_keyword'] ?? '');
  $reviewStatus = trim($_GET['review_status'] ?? '');

  $whereReview = [];
  $paramsReview = [];

  if ($reviewKeyword !== '') {
      $whereReview[] = "
          (
              rp.title LIKE ?
              OR rp.content LIKE ?
              OR rp.review_type LIKE ?
              OR rp.admin_note LIKE ?
          )
      ";

      $likeKeyword = '%' . $reviewKeyword . '%';
      $paramsReview[] = $likeKeyword;
      $paramsReview[] = $likeKeyword;
      $paramsReview[] = $likeKeyword;
      $paramsReview[] = $likeKeyword;
  }

  if (in_array($reviewStatus, ['pending', 'approved'])) {
      $whereReview[] = "rp.status = ?";
      $paramsReview[] = $reviewStatus;
  }

  $whereReviewSql = "";
  if (!empty($whereReview)) {
      $whereReviewSql = "WHERE " . implode(" AND ", $whereReview);
  }

  $stmtReviews = $pdo->prepare("
      SELECT 
          rp.*,
          u.user_name,
          COALESCE(cmt.total_comments, 0) AS total_comments
      FROM review_post rp
      LEFT JOIN `User` u ON u.user_id = rp.user_id
      LEFT JOIN (
          SELECT review_id, COUNT(*) AS total_comments
          FROM review_cmt
          GROUP BY review_id
      ) cmt ON cmt.review_id = rp.review_id
      $whereReviewSql
      ORDER BY 
          FIELD(rp.status, 'pending', 'approved'),
          rp.created_at DESC
  ");

  $stmtReviews->execute($paramsReview);
  $reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);

  // Lấy danh sách bình luận review PHÚ
  $reviewComments = $pdo->query("
      SELECT 
          rc.*,
          rp.title AS review_title,
          u.user_name
      FROM review_cmt rc
      LEFT JOIN review_post rp ON rp.review_id = rc.review_id
      LEFT JOIN `User` u ON u.user_id = rc.user_id
      ORDER BY 
          FIELD(rc.status, 'pending', 'approved'),
          rc.created_at DESC
  ")->fetchAll(PDO::FETCH_ASSOC);

  $pendingReviewCount = count(array_filter($reviews, fn($r) => $r['status'] === 'pending'));
  $pendingCommentCount = count(array_filter($reviewComments, fn($c) => $c['status'] === 'pending'));

// HẾT PHÚ

  // ===============================
  // QUẢN LÝ TRANG ABOUT
  // ===============================
  try {
      $pdo->exec("CREATE TABLE IF NOT EXISTS about_images (
          image_id    INT AUTO_INCREMENT PRIMARY KEY,
          filename    VARCHAR(255) NOT NULL,
          path        VARCHAR(500) NOT NULL,
          alt_text    VARCHAR(255) NOT NULL DEFAULT '',
          uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

      $pdo->exec("CREATE TABLE IF NOT EXISTS about_content (
          id         INT AUTO_INCREMENT PRIMARY KEY,
          content    LONGTEXT NOT NULL,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

      if ((int)$pdo->query("SELECT COUNT(*) FROM about_content")->fetchColumn() === 0) {
          $pdo->exec("INSERT INTO about_content (content) VALUES ('')");
      }
  } catch (Exception $e) {}

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['about_action'])) {
      $aAction = $_POST['about_action'];

      if ($aAction === 'upload_image') {
          $altText = trim($_POST['about_alt'] ?? '');
          if (!empty($_FILES['about_img']['name']) && $_FILES['about_img']['error'] === UPLOAD_ERR_OK) {
              $fType = mime_content_type($_FILES['about_img']['tmp_name']);
              $fSize = $_FILES['about_img']['size'];
              $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
              if (!in_array($fType, $allowedMime)) {
                  $_SESSION['admin_notice'] = 'Định dạng ảnh không hợp lệ (JPG, PNG, WEBP, GIF).';
              } elseif ($fSize > 5 * 1024 * 1024) {
                  $_SESSION['admin_notice'] = 'Ảnh không được vượt quá 5MB.';
              } else {
                  $uploadDir = realpath(__DIR__ . '/..') . '/uploads/about/';
                  if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                  $ext     = strtolower(pathinfo($_FILES['about_img']['name'], PATHINFO_EXTENSION));
                  $newName = 'about_' . date('YmdHis') . '_' . mt_rand(100, 999) . '.' . $ext;
                  if (move_uploaded_file($_FILES['about_img']['tmp_name'], $uploadDir . $newName)) {
                      $imgPath = 'uploads/about/' . $newName;
                      $stmt = $pdo->prepare("INSERT INTO about_images (filename, path, alt_text) VALUES (?, ?, ?)");
                      $stmt->execute([$newName, $imgPath, $altText]);
                      $newId = $pdo->lastInsertId();
                      $_SESSION['admin_notice'] = "Upload thành công! ID ảnh: $newId — dùng /image($newId) trong editor.";
                  } else {
                      $_SESSION['admin_notice'] = 'Không thể lưu ảnh lên server.';
                  }
              }
          } else {
              $_SESSION['admin_notice'] = 'Vui lòng chọn file ảnh.';
          }
          header('Location: admin_dashboard.php#about');
          exit();
      }

      if ($aAction === 'save_content') {
          $rawContent = $_POST['about_content'] ?? '';
          $pdo->prepare("UPDATE about_content SET content = ?, updated_at = NOW() WHERE id = 1")->execute([$rawContent]);
          $_SESSION['admin_notice'] = 'Đã lưu nội dung trang About.';
          header('Location: admin_dashboard.php#about');
          exit();
      }

      if ($aAction === 'delete_image') {
          $imgId  = (int)($_POST['image_id'] ?? 0);
          $imgStmt = $pdo->prepare("SELECT path FROM about_images WHERE image_id = ? LIMIT 1");
          $imgStmt->execute([$imgId]);
          $imgData = $imgStmt->fetch(PDO::FETCH_ASSOC);
          if ($imgData) {
              $fullPath = realpath(__DIR__ . '/..') . '/' . ltrim($imgData['path'], '/');
              if (file_exists($fullPath)) @unlink($fullPath);
              $pdo->prepare("DELETE FROM about_images WHERE image_id = ?")->execute([$imgId]);
              $_SESSION['admin_notice'] = 'Đã xóa ảnh ID ' . $imgId . '.';
          }
          header('Location: admin_dashboard.php#about');
          exit();
      }
  }

  try {
      $aboutContent = $pdo->query("SELECT content FROM about_content WHERE id = 1 LIMIT 1")->fetchColumn();
      if ($aboutContent === false) $aboutContent = '';
      $aboutImages  = $pdo->query("SELECT * FROM about_images ORDER BY uploaded_at DESC")->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) {
      $aboutContent = '';
      $aboutImages  = [];
  }
?>


<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Olivewood Atelier | Hệ thống Quản trị</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="../css/admin_dashboard.css?v=review_fix_1">
</head>
<body class="admin-body">

  <div class="admin-wrapper">
    
    <aside class="admin-sidebar">
      <div class="sidebar-brand">
        <a href="../task1/index.php" class="brand-logo">OLIVEWOOD<span>.</span></a>
        <p class="brand-sub">Management System</p>
      </div>

      <nav class="sidebar-menu">
        <div class="menu-group">Tổng quan</div>
        <a href="#overview" class="menu-link active" data-tab="overview">
          <span class="menu-icon">📊</span> Tổng quan hệ thống
        </a>
        <a href="#pages-manager" class="menu-link" data-tab="pages-manager">
          <span class="menu-icon">⚙️</span> Cấu hình Website
        </a>
        <a href="#about" class="menu-link" data-tab="about">
          <span class="menu-icon">📄</span> Trang About
        </a>

        <div class="menu-group">Kinh doanh</div>
        <a href="#products" class="menu-link" data-tab="products">
          <span class="menu-icon">🪑</span> Quản lý Sản phẩm
        </a>
        <a href="#orders" class="menu-link" data-tab="orders">
          <span class="menu-icon">📦</span> Đơn hàng & Giỏ hàng
        </a>

        <div class="menu-group">Tương tác</div>
        <a href="#contacts" class="menu-link" data-tab="contacts">
          <span class="menu-icon">✉️</span> Liên hệ khách hàng
          <?php if($unreadCount > 0): ?>
            <span class="menu-badge"><?= $unreadCount ?></span>
          <?php endif; ?>
        </a>
        <a href="#qa" class="menu-link" data-tab="qa">
          <span class="menu-icon">❓</span> Hỏi đáp (Q&A)
        </a>
        <a href="#reviews" class="menu-link" data-tab="reviews">
          <span class="menu-icon">📝</span> Bài review
          <?php if(($pendingReviewCount + $pendingCommentCount) > 0): ?>
            <span class="menu-badge"><?= $pendingReviewCount + $pendingCommentCount ?></span>
          <?php endif; ?>
        </a>
        <a href="#users" class="menu-link" data-tab="users">
          <span class="menu-icon">👥</span> Thành viên
        </a>
        
      </nav>

      <div class="sidebar-footer">
        <a href="logout.php" class="logout-link">Đăng xuất</a>
      </div>
    </aside>

    <main class="admin-main">
      
      <header class="admin-topbar">
        <div class="topbar-left">
            <span class="breadcrumb">Admin / <strong id="active-tab-name">Tổng quan</strong></span>
        </div>
        <div class="topbar-right">
          <div class="admin-info">
            <p class="admin-name">Chào <strong>Admin</strong></p>
            <img src="https://ui-avatars.com/api/?name=Admin&background=1e4e36&color=fff" alt="Avatar" class="admin-avatar">
          </div>
        </div>
      </header>

      <div class="admin-container">
        
        <?php if ($notice): ?>
            <div class="admin-alert animate__animated animate__fadeInDown">
                <?= h($notice) ?>
            </div>
        <?php endif; ?>

        <section id="overview" class="admin-section active">
          <div class="section-header-wrap">
            <h1 class="section-title">Bảng tin hệ thống</h1>
            <p class="section-desc">Dữ liệu tổng hợp từ các hoạt động của Atelier.</p>
          </div>

          <div class="stat-grid">
            <div class="stat-item">
              <span class="stat-label">Sản phẩm</span>
              <div class="stat-main">
                <span class="stat-number"><?= $totalProducts ?></span>
                <span class="stat-tag">Active</span>
              </div>
            </div>
            <div class="stat-item">
              <span class="stat-label">Đơn hàng mới</span>
              <div class="stat-main">
                <span class="stat-number"><?= $totalOrders ?></span>
                <span class="stat-tag positive">+5%</span>
              </div>
            </div>
            <div class="stat-item">
              <span class="stat-label">Liên hệ chờ</span>
              <div class="stat-main">
                <span class="stat-number"><?= $unreadCount ?></span>
                <span class="stat-tag <?= $unreadCount > 0 ? 'warning' : '' ?>">
                    <?= $unreadCount > 0 ? 'Cần xử lý' : 'Đã xong' ?>
                </span>
              </div>
            </div>
          </div>
        </section>

        <section id="pages-manager" class="admin-section">
          <div class="admin-card">
            <div class="card-header">
              <h2>Cấu hình thông tin Website</h2>
              <p>Các thông tin này hiển thị tại Footer và trang Liên hệ.</p>
            </div>
            <form method="POST" action="#pages-manager" class="admin-form">
              <div class="form-grid">
                <div class="form-group">
                  <label>Số điện thoại hotline</label>
                  <input type="text" name="phone" value="<?= h($company['phone'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                  <label>Email liên hệ</label>
                  <input type="email" name="mail" value="<?= h($company['mail'] ?? ''); ?>" required>
                </div>
              </div>
              <div class="form-group">
                <label>Địa chỉ văn phòng / Showroom</label>
                <textarea name="address" rows="3" required><?= h($company['address'] ?? ''); ?></textarea>
              </div>
              <div class="form-footer">
                <button type="submit" name="update_profile" class="btn-submit">Lưu cấu hình</button>
              </div>
            </form>
          </div>
        </section>

        <section id="contacts" class="admin-section">
          <div class="admin-card">
            <div class="card-header">
                <h2>Danh sách khách hàng liên hệ</h2>
            </div>
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Khách hàng</th>
                    <th>Nội dung tin nhắn</th>
                    <th>Ngày gửi</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $msg): ?>
                    <tr>
                      <td>
                        <strong><?= h($msg['name']); ?></strong><br>
                        <small><?= h($msg['mail']); ?></small>
                      </td>
                      <td>
                        <div class="text-truncate" style="max-width: 300px;"><?= h($msg['question']); ?></div>
                      </td>
                      <td><?= date('d/m/Y', strtotime($msg['created_at'])); ?></td>
                      <td>
                        <span class="status-badge <?= ($msg['status'] == 'Chưa đọc') ? 'status-unread' : 'status-read' ?>">
                          <?= $msg['status']; ?>
                        </span>
                      </td>
                      <td>
                        <div class="action-btns">
                          <?php if ($msg['status'] == 'Chưa đọc'): ?>
                            <a href="?action=mark_read&msg_id=<?= $msg['message_id']; ?>#contacts" class="btn-table">Đọc</a>
                          <?php endif; ?>
                          <a href="?action=delete&msg_id=<?= $msg['message_id']; ?>#contacts" 
                             class="btn-table btn-danger" 
                             onclick="return confirm('Xác nhận xóa liên hệ này?')">Xóa</a>
                        </div>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="5" class="empty-row">Hiện chưa có liên hệ nào.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section id="qa" class="admin-section">
            <div class="admin-card">
                <?php include 'includes/qa_view.php'; ?>
            </div>
        </section>

        <!-- PHÚ -->
        <section id="reviews" class="admin-section">
          <div class="admin-card">
            <div class="card-header">
              <h2>Quản lý bài review</h2>
              <p>Admin duyệt bài review trước khi bài được hiển thị ngoài trang người dùng.</p>
            </div>
            <div class="review-admin-toolbar">
              <form method="GET" action="admin_dashboard.php#reviews" class="review-search-form">
                <div class="review-search-left">
                  <label>Tìm kiếm bài review</label>
                  <input 
                    type="text" 
                    name="review_keyword" 
                    value="<?= h($reviewKeyword ?? ''); ?>" 
                    placeholder="Nhập tiêu đề, nội dung, loại review..."
                  >
                </div>

                <div class="review-search-status">
                  <label>Trạng thái</label>
                  <select name="review_status">
                    <option value="">Tất cả</option>
                    <option value="pending" <?= ($reviewStatus ?? '') === 'pending' ? 'selected' : ''; ?>>
                      Chờ duyệt
                    </option>
                    <option value="approved" <?= ($reviewStatus ?? '') === 'approved' ? 'selected' : ''; ?>>
                      Đã duyệt
                    </option>
                  </select>
                </div>

                <div class="review-search-actions">
                  <button type="submit" class="review-btn review-btn-primary">
                    Tìm kiếm
                  </button>

                  <a href="admin_dashboard.php#reviews" class="review-btn review-btn-light">
                    Làm mới
                  </a>
                </div>
              </form>
            </div>

            <details class="review-create-panel">
              <summary>
                <span class="review-plus-icon">+</span>
                Thêm bài review mới
              </summary>

              <div class="review-create-body">
                <form method="POST" action="admin_dashboard.php#reviews">
                  <input type="hidden" name="review_action" value="add">

                  <div class="review-create-grid">
                    <div class="review-form-group review-form-wide">
                      <label>Tiêu đề bài review</label>
                      <input 
                        type="text" 
                        name="title" 
                        placeholder="Ví dụ: Trải nghiệm mua bàn gỗ rất tốt" 
                        required
                      >
                    </div>

                    <div class="review-form-group">
                      <label>Loại review</label>
                      <select name="review_type">
                        <option value="website">Website</option>
                        <option value="product">Sản phẩm</option>
                        <option value="service">Dịch vụ</option>
                        <option value="delivery">Giao hàng</option>
                      </select>
                    </div>

                    <div class="review-form-group">
                      <label>ID sản phẩm nếu có</label>
                      <input 
                        type="number" 
                        name="product_id" 
                        placeholder="Có thể bỏ trống"
                      >
                    </div>

                    <div class="review-form-group">
                      <label>Trạng thái</label>
                      <select name="status">
                        <option value="approved">Đăng ngay</option>
                        <option value="pending">Lưu chờ duyệt</option>
                      </select>
                    </div>

                    <div class="review-form-group review-form-full">
                      <label>Ảnh bài review</label>
                      <input 
                        type="text" 
                        name="image" 
                        placeholder="Ví dụ: uploads/reviews/my-review.jpg"
                      >
                    </div>

                    <div class="review-form-group review-form-full">
                      <label>Nội dung bài review</label>
                      <textarea 
                        name="content" 
                        rows="6" 
                        placeholder="Nhập nội dung bài review..."
                        required
                      ></textarea>
                    </div>

                    <div class="review-form-group review-form-full">
                      <label>Ghi chú admin</label>
                      <textarea 
                        name="admin_note" 
                        rows="2" 
                        placeholder="Ghi chú nội bộ nếu có..."
                      ></textarea>
                    </div>
                  </div>

                  <div class="review-create-actions">
                    <button type="submit" class="review-btn review-btn-primary">
                      Thêm bài review
                    </button>
                  </div>
                </form>
              </div>
            </details>
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Bài review</th>
                    <th>Người viết</th>
                    <th>Loại</th>
                    <th>Sản phẩm</th>
                    <th>Bình luận</th>
                    <th>Ngày gửi</th>
                    <th>Trạng thái</th>
                    <th>Quản lý</th>
                  </tr>
                </thead>

                <tbody>
                  <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                      <tr>
                        <td>
                          <div class="review-main-title">
                            <?= h($review['title']); ?>
                          </div>

                          <div class="review-short-content">
                            <?= h(mb_substr($review['content'], 0, 120)); ?>
                            <?= mb_strlen($review['content']) > 120 ? '...' : ''; ?>
                          </div>
                        </td>

                        <td>
                          <?= h($review['user_name'] ?? 'Admin đăng'); ?>
                        </td>

                        <td>
                          <?= h($review['review_type']); ?>
                        </td>

                        <td>
                          <?= !empty($review['product_id']) ? '#' . (int)$review['product_id'] : 'Không có'; ?>
                        </td>

                        <td>
                          <?= (int)$review['total_comments']; ?>
                        </td>

                        <td>
                          <?= date('d/m/Y H:i', strtotime($review['created_at'])); ?>
                        </td>

                        <td>
                          <?php if ($review['status'] === 'approved'): ?>
                            <span class="status-badge status-read">Đã duyệt</span>
                          <?php else: ?>
                            <span class="status-badge status-unread">Chờ duyệt</span>
                          <?php endif; ?>
                        </td>

                        <td class="review-actions-cell">
                          <form method="POST" action="admin_dashboard.php#reviews">
                            <input type="hidden" name="review_id" value="<?= (int)$review['review_id']; ?>">
                            <input type="hidden" name="admin_note" value="<?= h($review['admin_note'] ?? ''); ?>">

                            <div class="review-inline-actions">
                              <?php if ($review['status'] !== 'approved'): ?>
                                <button type="submit" name="review_action" value="approve" class="review-btn review-btn-primary">
                                  Duyệt
                                </button>
                              <?php else: ?>
                                <button type="submit" name="review_action" value="pending" class="review-btn review-btn-light">
                                  Gỡ duyệt
                                </button>
                              <?php endif; ?>

                              <button 
                                type="submit" 
                                name="review_action" 
                                value="delete" 
                                class="review-btn review-btn-danger"
                                onclick="return confirm('Xóa bài review này?')"
                              >
                                Xóa
                              </button>
                            </div>
                          </form>
                        </td>
                      </tr>

                      <tr class="review-detail-row">
                        <td colspan="8">
                          <details class="review-detail-panel">
                  <summary>Đọc chi tiết / Sửa bài</summary>

                  <div class="review-detail-body">

                    <div class="review-full-content-box">
                      <div class="review-full-content-title">
                        Nội dung đầy đủ
                      </div>

                      <div class="review-full-content-text">
                        <?= nl2br(h($review['content'])); ?>
                      </div>

                      <?php if (!empty($review['admin_note'])): ?>
                        <div class="review-admin-note">
                          <strong>Ghi chú admin:</strong>
                          <?= h($review['admin_note']); ?>
                        </div>
                      <?php endif; ?>
                    </div>

                    <div class="review-edit-card">
                      <div class="review-edit-title">
                        Sửa thông tin bài review
                      </div>

                      <form method="POST" action="admin_dashboard.php#reviews">
                        <input type="hidden" name="review_action" value="edit">
                        <input type="hidden" name="review_id" value="<?= (int)$review['review_id']; ?>">

                        <div class="review-edit-grid">
                          <div class="review-edit-group">
                            <label>Tiêu đề</label>
                            <input 
                              type="text" 
                              name="title" 
                              value="<?= h($review['title']); ?>" 
                              required
                            >
                          </div>

                          <div class="review-edit-group">
                            <label>Loại review</label>
                            <select name="review_type">
                              <option value="website" <?= $review['review_type'] === 'website' ? 'selected' : ''; ?>>
                                Website
                              </option>
                              <option value="product" <?= $review['review_type'] === 'product' ? 'selected' : ''; ?>>
                                Sản phẩm
                              </option>
                              <option value="service" <?= $review['review_type'] === 'service' ? 'selected' : ''; ?>>
                                Dịch vụ
                              </option>
                              <option value="delivery" <?= $review['review_type'] === 'delivery' ? 'selected' : ''; ?>>
                                Giao hàng
                              </option>
                            </select>
                          </div>

                          <div class="review-edit-group">
                            <label>ID sản phẩm</label>
                            <input 
                              type="number" 
                              name="product_id" 
                              value="<?= h($review['product_id'] ?? ''); ?>"
                              placeholder="Bỏ trống"
                            >
                          </div>

                          <div class="review-edit-group">
                            <label>Trạng thái</label>
                            <select name="status">
                              <option value="pending" <?= $review['status'] === 'pending' ? 'selected' : ''; ?>>
                                Chờ duyệt
                              </option>
                              <option value="approved" <?= $review['status'] === 'approved' ? 'selected' : ''; ?>>
                                Đã duyệt
                              </option>
                            </select>
                          </div>

                          <div class="review-edit-group review-edit-full">
                            <label>Ảnh</label>
                            <input 
                              type="text" 
                              name="image" 
                              value="<?= h($review['image'] ?? ''); ?>"
                              placeholder="Ví dụ: uploads/reviews/review-1.jpg"
                            >
                          </div>

                          <div class="review-edit-group review-edit-full">
                            <label>Nội dung</label>
                            <textarea name="content" rows="6" required><?= h($review['content']); ?></textarea>
                          </div>

                          <div class="review-edit-group review-edit-full">
                            <label>Ghi chú admin</label>
                            <textarea name="admin_note" rows="2"><?= h($review['admin_note'] ?? ''); ?></textarea>
                          </div>
                        </div>

                        <div class="review-save-row">
                          <button type="submit" class="review-btn review-btn-primary">
                            Lưu sửa bài review
                          </button>
                        </div>
                      </form>
                    </div>

                  </div>
                </details>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="8" class="empty-row">
                        Chưa có bài review nào.
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>


          <div class="admin-card" style="margin-top: 24px;">
            <div class="card-header">
              <h2>Kiểm soát bình luận review</h2>
              <p>Admin có thể duyệt, ẩn hoặc xóa bình luận trong các bài review.</p>
            </div>

            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Bài review</th>
                    <th>Người bình luận</th>
                    <th>Nội dung</th>
                    <th>Ngày gửi</th>
                    <th>Trạng thái</th>
                    <th>Quản lý</th>
                  </tr>
                </thead>

                <tbody>
                  <?php if (!empty($reviewComments)): ?>
                    <?php foreach ($reviewComments as $comment): ?>
                      <tr>
                        <td style="min-width: 220px;">
                          <strong><?= h($comment['review_title'] ?? 'Bài review đã bị xóa'); ?></strong>
                        </td>

                        <td>
                          <?= h($comment['user_name'] ?? ('User #' . $comment['user_id'])); ?>
                        </td>

                        <td style="min-width: 300px;">
                          <details>
                            <summary style="cursor:pointer; color:#1e4e36; font-weight:600;">
                              Đọc bình luận
                            </summary>

                            <p style="margin-top:8px; line-height:1.6;">
                              <?= nl2br(h($comment['content'])); ?>
                            </p>

                            <?php if (!empty($comment['admin_note'])): ?>
                              <p>
                                <strong>Ghi chú admin:</strong>
                                <?= h($comment['admin_note']); ?>
                              </p>
                            <?php endif; ?>
                          </details>
                        </td>

                        <td>
                          <?= date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                        </td>

                        <td>
                          <?php if ($comment['status'] === 'approved'): ?>
                            <span class="status-badge status-read">Hiển thị</span>
                          <?php else: ?>
                            <span class="status-badge status-unread">Chờ duyệt / Đang ẩn</span>
                          <?php endif; ?>
                        </td>

                        <td style="min-width: 230px;">
                          <form method="POST" action="admin_dashboard.php#reviews" style="max-width: 200px; display: flex; flex-direction: column; gap: 6px;">
                              <input type="hidden" name="review_cmt_id" value="<?= (int)$comment['review_cmt_id']; ?>">

                              <textarea 
                                  name="admin_note" 
                                  rows="2" 
                                  placeholder="Ghi chú admin..."
                                  style="width: 100%; padding: 6px; font-size: 12px; border: 1px solid #ccc; border-radius: 4px; resize: none; box-sizing: border-box; font-family: sans-serif;"
                              ><?= htmlspecialchars($comment['admin_note'] ?? ''); ?></textarea>

                              <div style="display: flex; gap: 5px;">
                                  <?php if ($comment['status'] !== 'approved'): ?>
                                      <button type="submit" name="comment_action" value="approve" 
                                          style="flex: 1; padding: 6px 0; font-size: 12px; font-weight: bold; cursor: pointer; background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; border-radius: 4px;">
                                          Duyệt
                                      </button>
                                  <?php else: ?>
                                      <button type="submit" name="comment_action" value="pending" 
                                          style="flex: 1; padding: 6px 0; font-size: 12px; font-weight: bold; cursor: pointer; background: #fff3e0; color: #ef6c00; border: 1px solid #ffcc80; border-radius: 4px;">
                                          Ẩn
                                      </button>
                                  <?php endif; ?>

                                  <button type="submit" name="comment_action" value="delete" 
                                      style="flex: 1; padding: 6px 0; font-size: 12px; font-weight: bold; cursor: pointer; background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; border-radius: 4px;"
                                      onclick="return confirm('Xóa?')">
                                      Xóa
                                  </button>
                              </div>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="6" class="empty-row">
                        Chưa có bình luận review nào.
                      </td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section id="products" class="admin-section">
          <style>
            .products-layout {
              display: grid;
              grid-template-columns: minmax(0, 1fr) minmax(0, 1.6fr);
              gap: 24px;
              align-items: start;
            }
            @media (max-width: 1100px) {
              .products-layout {
                grid-template-columns: 1fr;
              }
            }
            /* Bảng sản phẩm cuộn ngang trên màn hình hẹp */
            .products-layout .table-container {
              overflow-x: auto;
              -webkit-overflow-scrolling: touch;
            }
            /* Thu gọn form khi stack */
            .products-layout .admin-form .form-grid {
              grid-template-columns: 1fr 1fr;
            }
            @media (max-width: 600px) {
              .products-layout .admin-form .form-grid {
                grid-template-columns: 1fr;
              }
            }
          </style>

          <!-- Thông báo sản phẩm -->
          <?php if (!empty($productNotice)): ?>
            <div class="admin-alert <?= $productNoticeType === 'error' ? 'admin-alert-error' : '' ?>" style="margin-bottom:16px;">
              <?= h($productNotice) ?>
            </div>
          <?php endif; ?>

          <div class="products-layout">

            <!-- Form thêm / sửa sản phẩm -->
            <div class="admin-card">
              <div class="card-header">
                <h2><?= $editProduct ? 'Sửa sản phẩm' : 'Thêm sản phẩm mới' ?></h2>
              </div>
              <form method="POST" action="admin_dashboard.php#products" enctype="multipart/form-data" class="admin-form" id="productForm">
                <input type="hidden" name="product_action" value="<?= $editProduct ? 'update' : 'create' ?>">
                <input type="hidden" name="product_id" value="<?= $editProduct ? (int)$editProduct['product_id'] : 0 ?>">
                <input type="hidden" name="old_url" value="<?= h($editProduct ? $editProduct['url'] : '') ?>">

                <div class="form-group">
                  <label>Tên sản phẩm</label>
                  <input type="text" name="product_name" class="form-control" maxlength="150" required
                    value="<?= h($editProduct ? $editProduct['product_name'] : '') ?>">
                </div>

                <div class="form-grid" style="grid-template-columns:1fr 1fr;">
                  <div class="form-group">
                    <label>Giá (₫)</label>
                    <input type="number" name="price" min="1" step="1000" required
                      value="<?= h($editProduct ? $editProduct['price'] : '') ?>">
                  </div>
                  <div class="form-group">
                    <label>Tồn kho</label>
                    <input type="number" name="stock_quantity" min="0" required
                      value="<?= h($editProduct ? $editProduct['stock_quantity'] : 0) ?>">
                  </div>
                  <div class="form-group">
                    <label>Vật liệu</label>
                    <input type="text" name="material" value="<?= h($editProduct ? $editProduct['material'] : '') ?>">
                  </div>
                  <div class="form-group">
                    <label>Màu sắc</label>
                    <input type="text" name="color" value="<?= h($editProduct ? $editProduct['color'] : '') ?>">
                  </div>
                </div>

                <div class="form-group">
                  <label>Bảo hành</label>
                  <input type="text" name="warranty_period" value="<?= h($editProduct ? $editProduct['warranty_period'] : '') ?>">
                </div>

                <div class="form-group">
                  <label>Danh mục</label>
                  <select name="category_id" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($categories as $cat): ?>
                      <option value="<?= (int)$cat['category_id'] ?>"
                        <?= ($editProduct && (int)$editProduct['category_id'] === (int)$cat['category_id']) ? 'selected' : '' ?>>
                        <?= h($cat['category_name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="form-group">
                  <label>Ảnh sản phẩm</label>
                  <input type="file" name="product_image" accept="image/*" style="padding:6px;">
                  <small style="color:#888; font-size:12px;">JPG, PNG, WEBP, GIF. Tối đa 3MB.</small>
                </div>

                <?php if ($editProduct && !empty($editProduct['url'])): ?>
                  <div class="form-group">
                    <img src="<?= h(safe_product_image($editProduct['url'])) ?>" alt="Ảnh hiện tại"
                      style="max-width:120px; border-radius:8px; border:1px solid #e0d5c5;">
                  </div>
                <?php endif; ?>

                <div class="form-footer" style="gap:10px; display:flex;">
                  <button type="submit" class="btn-submit">
                    <?= $editProduct ? 'Lưu thay đổi' : 'Thêm sản phẩm' ?>
                  </button>
                  <?php if ($editProduct): ?>
                    <a href="admin_dashboard.php#products" class="btn-submit" style="background:#888; text-decoration:none; display:inline-flex; align-items:center;">Hủy</a>
                  <?php endif; ?>
                </div>
              </form>
            </div>

            <!-- Danh sách sản phẩm -->
            <div class="admin-card">
              <div class="card-header">
                <h2>Danh sách sản phẩm <span style="color:#888; font-size:14px; font-weight:400;">(<?= $prodTotalRows ?> sản phẩm)</span></h2>
              </div>

              <!-- Tìm kiếm -->
              <form method="GET" action="admin_dashboard.php#products" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px; align-items:center;">
                <input type="text" name="prod_q" value="<?= h($prodQ) ?>" placeholder="Tên, vật liệu, màu sắc..."
                  style="flex:1; min-width:140px; padding:8px 12px; border:1px solid #d9cfc2; border-radius:6px; font-size:14px;">
                <select name="prod_category" style="flex:1; min-width:130px; max-width:200px; padding:8px 12px; border:1px solid #d9cfc2; border-radius:6px; font-size:14px;">
                  <option value="0">Tất cả danh mục</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['category_id'] ?>" <?= $prodCatFilter === (int)$cat['category_id'] ? 'selected' : '' ?>>
                      <?= h($cat['category_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div style="display:flex; gap:6px; flex-shrink:0;">
                  <button type="submit" class="btn-submit" style="padding:8px 16px; font-size:14px;">Tìm</button>
                  <a href="admin_dashboard.php#products" class="btn-submit" style="background:#888; text-decoration:none; display:inline-flex; align-items:center; padding:8px 14px; font-size:14px;">Reset</a>
                </div>
              </form>

              <div class="table-container">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Ảnh</th>
                      <th>Tên sản phẩm</th>
                      <th>Danh mục</th>
                      <th>Giá</th>
                      <th>Tồn</th>
                      <th>Thao tác</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($products)): ?>
                      <?php foreach ($products as $prod): ?>
                        <tr>
                          <td>#<?= (int)$prod['product_id'] ?></td>
                          <td>
                            <img src="<?= h(safe_product_image($prod['url'])) ?>"
                              alt="<?= h($prod['product_name']) ?>"
                              style="width:48px; height:48px; object-fit:cover; border-radius:6px; border:1px solid #e0d5c5;">
                          </td>
                          <td>
                            <strong><?= h($prod['product_name']) ?></strong><br>
                            <small style="color:#888;"><?= h($prod['material']) ?> · <?= h($prod['color']) ?></small>
                          </td>
                          <td><?= h($prod['category_name']) ?></td>
                          <td style="white-space:nowrap;"><?= money_vnd($prod['price']) ?></td>
                          <td><?= (int)$prod['stock_quantity'] ?></td>
                          <td>
                            <div class="action-btns">
                              <a href="admin_dashboard.php?edit_product=<?= (int)$prod['product_id'] ?>#products"
                                class="btn-table">Sửa</a>
                              <form method="POST" action="admin_dashboard.php#products" class="d-inline"
                                onsubmit="return confirm('Xác nhận xóa sản phẩm này?')">
                                <input type="hidden" name="product_action" value="delete">
                                <input type="hidden" name="product_id" value="<?= (int)$prod['product_id'] ?>">
                                <button type="submit" class="btn-table btn-danger">Xóa</button>
                              </form>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr><td colspan="7" class="empty-row">Không có sản phẩm phù hợp.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <!-- Phân trang -->
              <?php if ($prodTotalPages > 1): ?>
                <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:16px; align-items:center;">
                  <?php for ($i = 1; $i <= $prodTotalPages; $i++): ?>
                    <?php
                      $pageUrl = 'admin_dashboard.php?prod_page=' . $i
                        . ($prodQ !== '' ? '&prod_q=' . urlencode($prodQ) : '')
                        . ($prodCatFilter > 0 ? '&prod_category=' . $prodCatFilter : '')
                        . '#products';
                    ?>
                    <a href="<?= $pageUrl ?>"
                      style="padding:6px 12px; border-radius:6px; border:1px solid #d9cfc2; text-decoration:none; font-size:13px;
                        <?= $i === $prodPage ? 'background:#1e4e36; color:#fff;' : 'color:#1e4e36;' ?>">
                      <?= $i ?>
                    </a>
                  <?php endfor; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <script>
          document.getElementById('productForm')?.addEventListener('submit', function(e) {
            const name = this.querySelector('[name=product_name]').value.trim();
            const price = Number(this.querySelector('[name=price]').value);
            const stock = Number(this.querySelector('[name=stock_quantity]').value);
            const cat = this.querySelector('[name=category_id]').value;
            const img = this.querySelector('[name=product_image]');
            if (!name || name.length > 150) { alert('Tên sản phẩm không hợp lệ.'); e.preventDefault(); return; }
            if (!price || price <= 0) { alert('Giá phải lớn hơn 0.'); e.preventDefault(); return; }
            if (stock < 0) { alert('Tồn kho không được âm.'); e.preventDefault(); return; }
            if (!cat) { alert('Vui lòng chọn danh mục.'); e.preventDefault(); return; }
            if (img.files.length > 0 && img.files[0].size > 3 * 1024 * 1024) { alert('Ảnh không được vượt quá 3MB.'); e.preventDefault(); }
          });
          </script>
        </section>

        <section id="orders" class="admin-section">
          <!-- Thanh tìm kiếm -->
          <div class="admin-card" style="margin-bottom:20px;">
            <div class="card-header"><h2>Tìm kiếm đơn hàng & Giỏ hàng</h2></div>
            <form method="GET" action="admin_dashboard.php#orders" style="display:flex; gap:10px; flex-wrap:wrap;">
              <input type="text" name="order_q" value="<?= h($orderQ) ?>" placeholder="Mã, tên khách, email, trạng thái..."
                style="flex:1; min-width:200px; padding:8px 12px; border:1px solid #d9cfc2; border-radius:6px; font-size:14px;">
              <button type="submit" class="btn-submit" style="padding:8px 18px; font-size:14px;">Tìm</button>
              <a href="admin_dashboard.php#orders" class="btn-submit" style="background:#888; text-decoration:none; display:inline-flex; align-items:center; padding:8px 14px; font-size:14px;">Reset</a>
            </form>
          </div>

          <!-- Giỏ hàng -->
          <div class="admin-card" style="margin-bottom:20px;">
            <div class="card-header">
              <h2>Danh sách Giỏ hàng <span style="color:#888; font-size:14px; font-weight:400;">(<?= $cartTotal ?> giỏ)</span></h2>
            </div>
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Mã giỏ</th>
                    <th>Khách hàng</th>
                    <th>Ngày tạo</th>
                    <th>Số SP</th>
                    <th>Tổng tạm tính</th>
                    <th>Trạng thái</th>
                    <th>Cập nhật</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($carts)): ?>
                    <?php foreach ($carts as $cart): ?>
                      <tr>
                        <td>#<?= (int)$cart['cart_id'] ?></td>
                        <td><strong><?= h($cart['user_name']) ?></strong><br><small><?= h($cart['email']) ?></small></td>
                        <td><?= h($cart['created_at']) ?></td>
                        <td><?= (int)$cart['item_count'] ?></td>
                        <td style="white-space:nowrap;"><?= money_vnd($cart['cart_total']) ?></td>
                        <td><span class="status-badge"><?= h($cart['status']) ?></span></td>
                        <td>
                          <form method="POST" action="admin_dashboard.php#orders" style="display:flex; gap:6px; align-items:center;">
                            <input type="hidden" name="order_action" value="update_cart_status">
                            <input type="hidden" name="cart_id" value="<?= (int)$cart['cart_id'] ?>">
                            <select name="status" style="padding:5px 8px; border:1px solid #d9cfc2; border-radius:6px; font-size:13px;">
                              <?php foreach ($cartStatuses as $cs): ?>
                                <option value="<?= h($cs) ?>" <?= $cs === $cart['status'] ? 'selected' : '' ?>><?= h($cs) ?></option>
                              <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-table">Lưu</button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="7" class="empty-row">Không có giỏ hàng phù hợp.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <?php if ($cartTotalPages > 1): ?>
              <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:14px;">
                <?php for ($i = 1; $i <= $cartTotalPages; $i++): ?>
                  <a href="admin_dashboard.php?cart_page=<?= $i ?><?= $orderQ ? '&order_q=' . urlencode($orderQ) : '' ?>#orders"
                    style="padding:5px 11px; border-radius:6px; border:1px solid #d9cfc2; text-decoration:none; font-size:13px; <?= $i === $cartPage ? 'background:#1e4e36; color:#fff;' : 'color:#1e4e36;' ?>"><?= $i ?></a>
                <?php endfor; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Đơn hàng -->
          <div class="admin-card" style="margin-bottom:20px;">
            <div class="card-header">
              <h2>Danh sách Đơn hàng <span style="color:#888; font-size:14px; font-weight:400;">(<?= $ordTotal ?> đơn)</span></h2>
            </div>
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Người nhận</th>
                    <th>Ngày đặt</th>
                    <th>Thanh toán</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                      <tr>
                        <td>#<?= (int)$order['order_id'] ?></td>
                        <td><strong><?= h($order['user_name']) ?></strong><br><small><?= h($order['email']) ?></small></td>
                        <td>
                          <?= h($order['recipient_name']) ?><br>
                          <small><?= h($order['phone']) ?> · <?= h($order['ward']) ?>, <?= h($order['city']) ?></small>
                        </td>
                        <td><?= h($order['order_date']) ?></td>
                        <td><?= h($order['payment_method'] ?: 'Chưa có') ?></td>
                        <td style="white-space:nowrap;"><?= money_vnd($order['total_amount']) ?></td>
                        <td><span class="status-badge"><?= h($order['status']) ?></span></td>
                        <td>
                          <form method="POST" action="admin_dashboard.php#orders" style="display:flex; flex-direction:column; gap:6px;">
                            <input type="hidden" name="order_action" value="update_order_status">
                            <input type="hidden" name="order_id" value="<?= (int)$order['order_id'] ?>">
                            <div style="display:flex; gap:6px; align-items:center;">
                              <select name="status" style="padding:5px 8px; border:1px solid #d9cfc2; border-radius:6px; font-size:13px;">
                                <?php foreach ($orderStatuses as $os): ?>
                                  <option value="<?= h($os) ?>" <?= $os === $order['status'] ? 'selected' : '' ?>><?= h($os) ?></option>
                                <?php endforeach; ?>
                              </select>
                              <button type="submit" class="btn-table">Lưu</button>
                            </div>
                          </form>
                          <a href="admin_dashboard.php?order_detail=<?= (int)$order['order_id'] ?>#orders"
                            class="btn-table" style="margin-top:6px; display:inline-block; text-decoration:none;">Chi tiết</a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="8" class="empty-row">Không có đơn hàng phù hợp.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <?php if ($ordTotalPages > 1): ?>
              <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:14px;">
                <?php for ($i = 1; $i <= $ordTotalPages; $i++): ?>
                  <a href="admin_dashboard.php?order_page=<?= $i ?><?= $orderQ ? '&order_q=' . urlencode($orderQ) : '' ?>#orders"
                    style="padding:5px 11px; border-radius:6px; border:1px solid #d9cfc2; text-decoration:none; font-size:13px; <?= $i === $orderPage ? 'background:#1e4e36; color:#fff;' : 'color:#1e4e36;' ?>"><?= $i ?></a>
                <?php endfor; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Chi tiết đơn hàng -->
          <?php if ($detailOrderId > 0): ?>
          <div class="admin-card">
            <div class="card-header">
              <h2>Chi tiết đơn hàng #<?= $detailOrderId ?></h2>
              <a href="admin_dashboard.php#orders" style="font-size:13px; color:#888;">&larr; Đóng</a>
            </div>
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr><th>Ảnh</th><th>Sản phẩm</th><th>Danh mục</th><th>Số lượng</th><th>Giá bán</th><th>Thành tiền</th></tr>
                </thead>
                <tbody>
                  <?php if (!empty($orderItems)): ?>
                    <?php foreach ($orderItems as $item): ?>
                      <tr>
                        <td><img src="<?= h(safe_product_image($item['url'])) ?>" alt="<?= h($item['product_name']) ?>"
                          style="width:48px; height:48px; object-fit:cover; border-radius:6px; border:1px solid #e0d5c5;"></td>
                        <td><?= h($item['product_name']) ?></td>
                        <td><?= h($item['category_name']) ?></td>
                        <td><?= (int)$item['quantity'] ?></td>
                        <td><?= money_vnd($item['sold_price']) ?></td>
                        <td><?= money_vnd($item['subtotal']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="6" class="empty-row">Không có sản phẩm trong đơn hàng này.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
          <?php endif; ?>
        </section>

        

        <section id="users" class="admin-section">
          <div class="admin-card">
            <div class="card-header">
              <h2>Quản lý Thành viên <span style="color:#888; font-size:14px; font-weight:400;">(<?= $userTotal ?> tài khoản)</span></h2>
              <p>Xem thông tin, đổi quyền (Admin ↔ Customer) hoặc xóa tài khoản. Không thể thêm tài khoản mới từ đây.</p>
            </div>

            <!-- Tìm kiếm & lọc -->
            <form method="GET" action="admin_dashboard.php#users" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px;">
              <input type="text" name="user_q" value="<?= h($userQ) ?>" placeholder="Tên, email, số điện thoại..."
                style="flex:1; min-width:200px; padding:8px 12px; border:1px solid #d9cfc2; border-radius:6px; font-size:14px;">
              <select name="user_role" style="padding:8px 12px; border:1px solid #d9cfc2; border-radius:6px; font-size:14px;">
                <option value="">Tất cả quyền</option>
                <option value="admin"    <?= strtolower($userRoleFilter) === 'admin'    ? 'selected' : '' ?>>Admin</option>
                <option value="customer" <?= strtolower($userRoleFilter) === 'customer' ? 'selected' : '' ?>>Customer</option>
              </select>
              <button type="submit" class="btn-submit" style="padding:8px 18px; font-size:14px;">Tìm</button>
              <a href="admin_dashboard.php#users" class="btn-submit" style="background:#888; text-decoration:none; display:inline-flex; align-items:center; padding:8px 14px; font-size:14px;">Reset</a>
            </form>

            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên tài khoản</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Mật khẩu (hash)</th>
                    <th>Quyền</th>
                    <th>Đổi quyền</th>
                    <th>Xóa</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($users)): ?>
                    <?php foreach ($users as $u): ?>
                      <?php
                        $isSelf  = (int)$u['user_id'] === (int)$_SESSION['user_id'];
                        $isAdmin = strtolower($u['role']) === 'admin';
                      ?>
                      <tr style="<?= $isSelf ? 'background:#f0f7f3;' : '' ?>">
                        <td>#<?= (int)$u['user_id'] ?></td>
                        <td>
                          <strong><?= h($u['user_name']) ?></strong>
                          <?php if ($isSelf): ?><span style="font-size:11px; background:#1e4e36; color:#fff; border-radius:10px; padding:1px 7px; margin-left:4px;">Bạn</span><?php endif; ?>
                        </td>
                        <td><?= h($u['email']) ?></td>
                        <td><?= h($u['phone'] ?: '—') ?></td>
                        <td>
                          <details style="cursor:pointer;">
                            <summary style="color:#1e4e36; font-size:12px; font-weight:600;">Xem hash</summary>
                            <div style="font-size:11px; color:#888; word-break:break-all; margin-top:4px; max-width:220px;"><?= h($u['password']) ?></div>
                          </details>
                        </td>
                        <td>
                          <span class="status-badge <?= $isAdmin ? 'status-read' : '' ?>" style="<?= !$isAdmin ? 'background:#f0e6d3; color:#8b5e3c;' : '' ?>">
                            <?= h(ucfirst($u['role'])) ?>
                          </span>
                        </td>
                        <td>
                          <?php if (!$isSelf): ?>
                            <form method="POST" action="admin_dashboard.php#users" style="display:flex; gap:6px; align-items:center;">
                              <input type="hidden" name="user_action" value="change_role">
                              <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                              <select name="new_role" style="padding:5px 8px; border:1px solid #d9cfc2; border-radius:6px; font-size:13px;">
                                <option value="customer" <?= !$isAdmin ? 'selected' : '' ?>>Customer</option>
                                <option value="admin"    <?= $isAdmin  ? 'selected' : '' ?>>Admin</option>
                              </select>
                              <button type="submit" class="btn-table">Lưu</button>
                            </form>
                          <?php else: ?>
                            <span style="font-size:12px; color:#aaa;">—</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if (!$isSelf): ?>
                            <form method="POST" action="admin_dashboard.php#users"
                              onsubmit="return confirm('Xóa tài khoản <?= h(addslashes($u['user_name'])) ?>? Hành động này không thể hoàn tác.')">
                              <input type="hidden" name="user_action" value="delete">
                              <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                              <button type="submit" class="btn-table btn-danger">Xóa</button>
                            </form>
                          <?php else: ?>
                            <span style="font-size:12px; color:#aaa;">—</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="8" class="empty-row">Không tìm thấy thành viên nào.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Phân trang -->
            <?php if ($userTotalPages > 1): ?>
              <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:16px; align-items:center;">
                <?php for ($i = 1; $i <= $userTotalPages; $i++): ?>
                  <?php
                    $uPageUrl = 'admin_dashboard.php?user_page=' . $i
                      . ($userQ !== '' ? '&user_q=' . urlencode($userQ) : '')
                      . ($userRoleFilter !== '' ? '&user_role=' . urlencode($userRoleFilter) : '')
                      . '#users';
                  ?>
                  <a href="<?= $uPageUrl ?>" style="padding:6px 12px; border-radius:6px; border:1px solid #d9cfc2; text-decoration:none; font-size:13px;
                    <?= $i === $userPage ? 'background:#1e4e36; color:#fff;' : 'color:#1e4e36;' ?>">
                    <?= $i ?>
                  </a>
                <?php endfor; ?>
              </div>
            <?php endif; ?>
          </div>
        </section>

        <!-- ======================== -->
        <!-- SECTION: TRANG ABOUT     -->
        <!-- ======================== -->
        <section id="about" class="admin-section">
          <div class="section-header-wrap">
            <h1 class="section-title">Quản lý trang About</h1>
            <p class="section-desc">
              Soạn nội dung giới thiệu. Dùng <code style="background:#f0f0f0;padding:2px 6px;border-radius:4px;font-size:13px;">/image(ID)</code>
              trên một dòng riêng để chèn ảnh dạng block — tương tự <code style="background:#f0f0f0;padding:2px 6px;border-radius:4px;font-size:13px;">\includegraphics</code> trong LaTeX.
            </p>
          </div>

          <!-- Upload ảnh mới -->
          <div class="admin-card" style="margin-bottom:20px;">
            <div class="card-header">
              <h2>Upload ảnh vào thư viện About</h2>
            </div>
            <form method="POST" enctype="multipart/form-data" action="admin_dashboard.php#about">
              <input type="hidden" name="about_action" value="upload_image">
              <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;padding:16px 20px;">
                <div class="form-group" style="flex:2;min-width:200px;margin:0;">
                  <label>Chọn ảnh (JPG, PNG, WEBP, GIF — tối đa 5MB)</label>
                  <input type="file" name="about_img" accept="image/*" required style="padding:8px;width:100%;">
                </div>
                <div class="form-group" style="flex:2;min-width:180px;margin:0;">
                  <label>Chú thích ảnh (alt text)</label>
                  <input type="text" name="about_alt" placeholder="Mô tả ngắn về ảnh..." style="width:100%;">
                </div>
                <div style="flex-shrink:0;">
                  <button type="submit" class="btn-submit">Upload ảnh</button>
                </div>
              </div>
            </form>
          </div>

          <!-- Editor + Image Library -->
          <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">

            <!-- Editor -->
            <div class="admin-card">
              <div class="card-header">
                <h2>Editor nội dung trang About</h2>
                <p style="font-size:13px;color:#888;margin-top:6px;line-height:1.6;">
                  Gõ văn bản bình thường cho các đoạn giới thiệu.
                  Gõ <strong>/image(ID)</strong> trên một dòng riêng (hoặc click ảnh bên phải) để chèn ảnh dạng block.
                  Dòng trống tạo khoảng cách giữa các đoạn.
                </p>
              </div>
              <form method="POST" action="admin_dashboard.php#about" id="aboutEditorForm">
                <input type="hidden" name="about_action" value="save_content">
                <div style="padding:16px 20px;">
                  <div id="aboutEditorWrap" style="position:relative;border:1px solid #d1c9bb;border-radius:8px;overflow:hidden;">
                    <textarea
                      name="about_content"
                      id="aboutEditor"
                      rows="28"
                      style="width:100%;box-sizing:border-box;font-family:'Fira Code','Cascadia Code','Courier New',monospace;
                             font-size:14px;line-height:1.9;padding:18px 20px;
                             background:#1c1c28;color:#e2ddd6;border:none;outline:none;
                             resize:vertical;tab-size:2;white-space:pre-wrap;"
                      placeholder="Nhập nội dung trang About...&#10;&#10;Ví dụ:&#10;The Soul Behind the Wood&#10;&#10;Crafting a legacy of silence, strength, and artisanal soul.&#10;&#10;/image(1)&#10;&#10;Our Heritage&#10;&#10;At Olivewood Atelier, we believe that furniture is more than just utility."
                    ><?= h($aboutContent) ?></textarea>
                  </div>
                  <div style="margin-top:10px;font-size:12px;color:#666;line-height:2;padding:12px 14px;background:#f9f8f6;border-radius:6px;border:1px solid #ede9e3;">
                    <strong>Cú pháp:</strong><br>
                    <code style="background:#eee;padding:1px 5px;border-radius:3px;">header(Tiêu đề lớn)</code> → chữ rất to &nbsp;|&nbsp;
                    <code style="background:#eee;padding:1px 5px;border-radius:3px;">subheader(Đề mục)</code> → chữ to vừa<br>
                    Văn bản thường → đoạn văn &nbsp;|&nbsp;
                    <code style="background:#eee;padding:1px 5px;border-radius:3px;">/image(ID)</code> trên dòng riêng → ảnh block &nbsp;|&nbsp;
                    Dòng trống → ngắt đoạn
                  </div>
                </div>
                <div class="form-footer" style="padding:0 20px 20px;">
                  <button type="submit" class="btn-submit">Lưu nội dung About</button>
                  <button type="button" onclick="previewAboutContent()"
                          style="margin-left:10px;padding:10px 20px;border:1px solid #1e4e36;
                                 background:transparent;color:#1e4e36;border-radius:6px;cursor:pointer;font-size:14px;">
                    Xem trước
                  </button>
                </div>
              </form>

              <!-- Preview modal -->
              <div id="aboutPreviewModal" style="display:none;position:fixed;inset:0;z-index:9999;
                   background:rgba(0,0,0,0.6);overflow-y:auto;padding:40px 20px;">
                <div style="max-width:760px;margin:0 auto;background:#fff;border-radius:12px;
                            padding:40px;position:relative;">
                  <button onclick="document.getElementById('aboutPreviewModal').style.display='none'"
                          style="position:absolute;top:16px;right:20px;background:none;border:none;
                                 font-size:24px;cursor:pointer;color:#666;">&times;</button>
                  <h3 style="margin-bottom:20px;color:#1e4e36;">Xem trước nội dung About</h3>
                  <div id="aboutPreviewBody" style="font-family:Georgia,serif;color:#333;line-height:1.9;"></div>
                </div>
              </div>
            </div>

            <!-- Thư viện ảnh -->
            <div class="admin-card" style="position:sticky;top:80px;">
              <div class="card-header">
                <h2>Thư viện ảnh</h2>
                <p style="font-size:12px;color:#999;margin-top:4px;">Click ảnh → tự chèn vào editor</p>
              </div>
              <div style="padding:12px 16px;max-height:620px;overflow-y:auto;">
                <?php if (empty($aboutImages)): ?>
                  <p style="color:#aaa;font-size:13px;text-align:center;padding:24px 0;">
                    Chưa có ảnh nào.<br>Upload ảnh ở trên để bắt đầu.
                  </p>
                <?php else: ?>
                  <div style="display:flex;flex-direction:column;gap:10px;">
                    <?php foreach ($aboutImages as $aImg): ?>
                      <div class="about-lib-card"
                           onclick="insertImageTag(<?= (int)$aImg['image_id'] ?>)"
                           title="Click để chèn /image(<?= (int)$aImg['image_id'] ?>) vào editor"
                           style="display:flex;align-items:center;gap:10px;padding:10px 12px;
                                  border:1px solid #ddd8cf;border-radius:8px;cursor:pointer;
                                  transition:all 0.15s;background:#faf9f7;user-select:none;">
                        <img src="../<?= h($aImg['path']) ?>"
                             alt="<?= h($aImg['alt_text']) ?>"
                             style="width:60px;height:60px;object-fit:cover;border-radius:6px;flex-shrink:0;border:1px solid #e8e3db;">
                        <div style="flex:1;min-width:0;">
                          <div style="font-family:monospace;font-size:12px;font-weight:700;color:#1e4e36;
                                      background:#e8f0eb;display:inline-block;padding:2px 7px;border-radius:4px;">
                            /image(<?= (int)$aImg['image_id'] ?>)
                          </div>
                          <?php if ($aImg['alt_text']): ?>
                            <div style="font-size:11px;color:#777;margin-top:4px;
                                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                              <?= h($aImg['alt_text']) ?>
                            </div>
                          <?php endif; ?>
                          <div style="font-size:10px;color:#bbb;margin-top:2px;">
                            <?= date('d/m/Y H:i', strtotime($aImg['uploaded_at'])) ?>
                          </div>
                        </div>
                        <form method="POST" action="admin_dashboard.php#about"
                              onsubmit="return confirm('Xóa ảnh ID <?= (int)$aImg['image_id'] ?>?')"
                              onclick="event.stopPropagation()" style="flex-shrink:0;">
                          <input type="hidden" name="about_action" value="delete_image">
                          <input type="hidden" name="image_id" value="<?= (int)$aImg['image_id'] ?>">
                          <button type="submit"
                                  style="background:none;border:none;color:#c0392b;cursor:pointer;
                                         font-size:18px;line-height:1;padding:4px;"
                                  title="Xóa ảnh">&times;</button>
                        </form>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>

          </div>
        </section>

      </div>
    </main>
  </div>

  <script>
    // Logic Tab Switching (Chuyển trang mượt mà không cần load lại trang)
    document.addEventListener('DOMContentLoaded', function() {
        const links = document.querySelectorAll('.menu-link');
        const sections = document.querySelectorAll('.admin-section');
        const tabName = document.getElementById('active-tab-name');

        function showSection(id) {
            sections.forEach(s => s.classList.remove('active'));
            const target = document.querySelector(id);
            if(target) target.classList.add('active');
            
            // Cập nhật Breadcrumb
            const activeLink = document.querySelector(`.menu-link[href="${id}"]`);
            if(activeLink) tabName.innerText = activeLink.innerText.replace(/[^\w\sàáảãạâầấẩẫậăằắẳẵặèéẻẽẹêềếểễệìíỉĩịòóỏõọôồốổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵđ]/gi, '').trim();
        }

        links.forEach(link => {
            link.addEventListener('click', function(e) {
                links.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                showSection(this.getAttribute('href'));
            });
        });

        // Xử lý khi load trang có sẵn Hash (ví dụ: admin_dashboard.php#contacts)
        const currentHash = window.location.hash || '#overview';
        const initialLink = document.querySelector(`.menu-link[href="${currentHash}"]`);
        if(initialLink) {
            links.forEach(l => l.classList.remove('active'));
            initialLink.classList.add('active');
            showSection(currentHash);
        }
    });

    // ========================
    // ABOUT EDITOR FUNCTIONS
    // ========================
    function insertImageTag(imageId) {
        const editor = document.getElementById('aboutEditor');
        if (!editor) return;
        const tag = '/image(' + imageId + ')';
        const start = editor.selectionStart;
        const end   = editor.selectionEnd;
        const val   = editor.value;

        // Đảm bảo tag nằm trên dòng riêng
        const prefix = (start > 0 && val[start - 1] !== '\n') ? '\n' : '';
        const suffix = (end < val.length && val[end] !== '\n') ? '\n' : '';
        const insertion = prefix + tag + suffix;

        editor.value = val.substring(0, start) + insertion + val.substring(end);
        const newCursor = start + insertion.length;
        editor.setSelectionRange(newCursor, newCursor);
        editor.focus();

        // Highlight card vừa click
        const card = event.currentTarget;
        card.style.borderColor = '#1e4e36';
        card.style.background  = '#e8f0eb';
        setTimeout(() => {
            card.style.borderColor = '#ddd8cf';
            card.style.background  = '#faf9f7';
        }, 600);
    }

    // Hover effect cho image library cards
    document.querySelectorAll('.about-lib-card').forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            this.style.borderColor = '#1e4e36';
            this.style.background  = '#f0f5f1';
            this.style.transform   = 'translateX(2px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.borderColor = '#ddd8cf';
            this.style.background  = '#faf9f7';
            this.style.transform   = '';
        });
    });

    function previewAboutContent() {
        const raw = document.getElementById('aboutEditor').value;
        const lines = raw.split('\n');
        let html = '';
        let paraLines = [];

        function flushPara() {
            const text = paraLines.join(' ').trim();
            if (text) {
                html += '<p style="margin:0 0 16px;font-size:16px;color:#333;line-height:1.9;">'
                      + text.replace(/</g,'&lt;').replace(/>/g,'&gt;')
                      + '</p>';
            }
            paraLines = [];
        }

        lines.forEach(function(line) {
            const trimmed = line.trimEnd();
            const mImg    = trimmed.match(/^\/image\((\d+)\)\s*$/);
            const mHead   = trimmed.match(/^header\((.+)\)\s*$/);
            const mSub    = trimmed.match(/^subheader\((.+)\)\s*$/);

            if (mImg) {
                flushPara();
                const imgEl = document.querySelector('.about-lib-card[onclick*="(' + mImg[1] + ')"] img');
                if (imgEl) {
                    html += '<figure style="margin:32px 0;text-align:center;">'
                          + '<img src="' + imgEl.src + '" style="max-width:100%;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,0.15);">'
                          + '</figure>';
                } else {
                    html += '<div style="text-align:center;padding:20px;background:#f5f5f5;border-radius:8px;margin:20px 0;color:#999;font-family:monospace;">'
                          + '[Ảnh ID ' + mImg[1] + ' chưa có trong thư viện]</div>';
                }
            } else if (mHead) {
                flushPara();
                html += '<h1 style="font-size:2.8rem;font-weight:700;margin:40px 0 12px;line-height:1.2;color:#1e4e36;">'
                      + mHead[1].replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</h1>';
            } else if (mSub) {
                flushPara();
                html += '<h2 style="font-size:1.6rem;font-weight:600;margin:28px 0 8px;line-height:1.35;color:#2d6b4a;">'
                      + mSub[1].replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</h2>';
            } else if (trimmed.trim() === '') {
                flushPara();
            } else {
                paraLines.push(line);
            }
        });
        flushPara();

        document.getElementById('aboutPreviewBody').innerHTML = html || '<p style="color:#aaa;">Chưa có nội dung.</p>';
        document.getElementById('aboutPreviewModal').style.display = 'block';
    }
  </script>
</body>
</html>