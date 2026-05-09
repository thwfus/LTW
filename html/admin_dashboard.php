<?php
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }

  if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
      // Nếu chưa đăng nhập HOẶC không phải là admin -> Chuyển hướng về trang login
      header("Location: ../html/404.html"); 
      exit();
  }

  require_once '../task1/config_m1.php'; 
  
  require_once 'includes/qa_logic.php';

  // --- Logic xử lý Profile và Contact của Huy (Dùng $pdo) ---
  if (isset($_POST['update_profile'])) {
      $sql = "UPDATE Web_Info SET phone = ?, mail = ?, address = ? WHERE info_id = 1";
      $pdo->prepare($sql)->execute([$_POST['phone'], $_POST['mail'], $_POST['address']]);
  }

  if (isset($_GET['action']) && isset($_GET['msg_id'])) {
      $id = $_GET['msg_id'];
      if ($_GET['action'] == 'mark_read') {
          $pdo->prepare("UPDATE Contact_Messages SET status = 'Đã đọc' WHERE message_id = ?")->execute([$id]);
      } elseif ($_GET['action'] == 'delete') {
          $pdo->prepare("DELETE FROM Contact_Messages WHERE message_id = ?")->execute([$id]);
      }
  }

  $company = $pdo->query("SELECT * FROM Web_Info WHERE info_id = 1")->fetch();
  $messages = $pdo->query("SELECT * FROM Contact_Messages ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Olivewood Admin Dashboard</title>
  <link rel="stylesheet" href="../css/admin_dashboard.css">
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <a class="brand" href="../task1/index.php">OLIVEWOOD</a>

      <nav class="menu">
        <a href="#dashboard" class="active"><span>Tổng quan</span><small>01</small></a>
        <a href="#news"><span>Quản lý tin tức</span><small>02</small></a>
        <a href="#comments"><span>Bình luận / Đánh giá</span><small>03</small></a>
        <a href="#products"><span>Quản lý sản phẩm</span><small>04</small></a>
        <a href="#orders"><span>Giỏ hàng / Đơn hàng</span><small>05</small></a>
        <a href="#qa"><span>Q &amp; A</span><small>06</small></a>
        <a href="#pages-manager"><span>Nội dung trang</span><small>07</small></a>
        <a href="#contacts"><span>Liên hệ khách hàng</span><small>08</small></a>
        <a href="#users"><span>Quản lý thành viên</span><small>09</small></a>
      </nav>
    </aside>

    <main class="main">
      <div class="topbar" id="dashboard">
        <div>
          <h1>Admin Dashboard</h1>
          <p>Bảng quản trị Olivewood.</p>
        </div>
        <div class="topbar-actions">
          <a class="btn btn-light" href="#pages-manager">Sửa nội dung trang</a>
          <a class="btn btn-accent" href="#products">Quản lý sản phẩm</a>
          <a href="logout.php" class="btn btn-dark" style="text-decoration: none;">Đăng xuất</a>
        </div>
      </div>

      <section class="grid">
        <div class="card stat-card">
          <div class="stat-label">Tổng sản phẩm</div>
          <div class="stat-value">128</div>
          <div class="stat-change">+12 sản phẩm tháng này</div>
        </div>

        <div class="card stat-card">
          <div class="stat-label">Bài viết</div>
          <div class="stat-value">36</div>
          <div class="stat-change">5 bài cần cập nhật</div>
        </div>

        <div class="card stat-card">
          <div class="stat-label">Đơn hàng mới</div>
          <div class="stat-value">24</div>
          <div class="stat-change">+8.4% so với tuần trước</div>
        </div>

        <div class="card stat-card">
          <div class="stat-label">Liên hệ chưa đọc</div>
          <div class="stat-value">9</div>
          <div class="stat-change">3 liên hệ cần phản hồi</div>
        </div>

        <div class="card hero-preview">
          <img src="https://images.pexels.com/photos/27164972/pexels-photo-27164972.jpeg?auto=compress&cs=tinysrgb&w=1500" alt="Hero preview">
          <div class="hero-content">
            <h2>Chỉnh sửa nhanh Hero Section</h2>
            <div class="hero-fields">
              <div class="field">
                <label>Tiêu đề lớn</label>
                <input type="text" value="Sculpting Space with Timeless Elegance">
              </div>
              <div class="field">
                <label>Nút CTA</label>
                <input type="text" value="Shop Now">
              </div>
              <div class="field-full">
                <label>Mô tả</label>
                <textarea>Discover curated luxury furniture where artisanal woodcraft meets modern minimalist design. Handcrafted pieces for the discerning home.</textarea>
              </div>
              <div class="field">
                <label>Ảnh hero</label>
                <input type="file">
              </div>
              <div class="field">
                <label>Trạng thái</label>
                <select>
                  <option selected>Đang hiển thị</option>
                  <option>Bản nháp</option>
                  <option>Ẩn</option>
                </select>
              </div>
            </div>
            <div class="bottom-actions">
              <button class="btn btn-light">Xem trước</button>
              <button class="btn btn-accent">Cập nhật hero</button>
            </div>
          </div>
        </div>

        <div class="card quick-actions">
          <h2 class="panel-title">Tác vụ nhanh</h2>
          <div class="action-list">
            <div class="action-item">
              <span>Thêm bài viết mới</span>
              <a class="btn btn-dark" href="#news">Đi tới</a>
            </div>
            <div class="action-item">
              <span>Thêm sản phẩm mới</span>
              <a class="btn btn-dark" href="#products">Đi tới</a>
            </div>
            <div class="action-item">
              <span>Xử lý đơn hàng</span>
              <a class="btn btn-dark" href="#orders">Đi tới</a>
            </div>
            <div class="action-item">
              <span>Trả lời liên hệ khách</span>
              <a class="btn btn-dark" href="#contacts">Đi tới</a>
            </div>
          </div>
        </div>

        <div class="card news" id="news">
          <div class="section-header">
            <div>
              <h2 class="panel-title">Quản lý tin tức</h2>
              <div class="section-note">Xem, tìm kiếm, thêm, sửa, xoá thông tin bài viết.</div>
            </div>
            <button class="btn btn-accent">+ Thêm bài viết</button>
          </div>
          <div class="table-toolbar">
            <div class="toolbar-group">
              <input type="text" placeholder="Tìm theo tiêu đề bài viết...">
              <select>
                <option>Tất cả trạng thái</option>
                <option>Published</option>
                <option>Draft</option>
              </select>
            </div>
            <div class="toolbar-group">
              <button class="btn btn-light">Tìm kiếm</button>
              <button class="btn btn-light">Làm mới</button>
            </div>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Tiêu đề</th>
                  <th>Danh mục</th>
                  <th>Ngày đăng</th>
                  <th>Trạng thái</th>
                  <th>Lượt xem</th>
                  <th>Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Top 5 xu hướng nội thất tối giản năm 2026</td>
                  <td>Design Trends</td>
                  <td>21/03/2026</td>
                  <td><span class="badge live">Published</span></td>
                  <td>2,354</td>
                  <td><div class="action-cell"><button class="btn btn-light">Sửa</button><button class="btn btn-danger">Xoá</button></div></td>
                </tr>
                <tr>
                  <td>Cách chọn sofa cho phòng khách nhỏ</td>
                  <td>Tips</td>
                  <td>18/03/2026</td>
                  <td><span class="badge draft">Draft</span></td>
                  <td>784</td>
                  <td><div class="action-cell"><button class="btn btn-light">Sửa</button><button class="btn btn-danger">Xoá</button></div></td>
                </tr>
                <tr>
                  <td>Bộ sưu tập gỗ olive mới của Olivewood</td>
                  <td>Collection</td>
                  <td>10/03/2026</td>
                  <td><span class="badge live">Published</span></td>
                  <td>1,429</td>
                  <td><div class="action-cell"><button class="btn btn-light">Sửa</button><button class="btn btn-danger">Xoá</button></div></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card comments" id="comments">
          <div class="section-header">
            <div>
              <h2 class="panel-title">Quản lý bình luận / đánh giá</h2>
              <div class="section-note">Quản lý bình luận và đánh giá của người dùng trên bài viết.</div>
            </div>
          </div>
          <div class="table-toolbar">
            <div class="toolbar-group">
              <input type="text" placeholder="Tìm theo tên người dùng hoặc bài viết...">
              <select>
                <option>Tất cả đánh giá</option>
                <option>5 sao</option>
                <option>4 sao</option>
                <option>3 sao trở xuống</option>
              </select>
            </div>
            <button class="btn btn-light">Tìm kiếm</button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Người dùng</th>
                  <th>Bài viết</th>
                  <th>Nội dung</th>
                  <th>Đánh giá</th>
                  <th>Trạng thái</th>
                  <th>Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Ngọc Anh</td>
                  <td>Top 5 xu hướng nội thất tối giản năm 2026</td>
                  <td>Bài viết rất hữu ích và hình ảnh đẹp.</td>
                  <td>5/5</td>
                  <td><span class="badge live">Hiển thị</span></td>
                  <td><div class="action-cell"><button class="btn btn-light">Ẩn</button><button class="btn btn-danger">Xoá</button></div></td>
                </tr>
                <tr>
                  <td>Minh Quân</td>
                  <td>Cách chọn sofa cho phòng khách nhỏ</td>
                  <td>Mình muốn xem thêm ví dụ thực tế hơn.</td>
                  <td>4/5</td>
                  <td><span class="badge draft">Chờ duyệt</span></td>
                  <td><div class="action-cell"><button class="btn btn-accent">Duyệt</button><button class="btn btn-danger">Xoá</button></div></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card products" id="products">
          <div class="section-header">
            <div>
              <h2 class="panel-title">Quản lý sản phẩm</h2>
              <div class="section-note">Xem, tìm kiếm, thêm, sửa, xoá thông tin sản phẩm.</div>
            </div>
            <button class="btn btn-accent">+ Thêm sản phẩm</button>
          </div>
          <div class="table-toolbar">
            <div class="toolbar-group">
              <input type="text" placeholder="Tìm theo tên sản phẩm...">
              <select>
                <option>Tất cả danh mục</option>
                <option>Chair</option>
                <option>Table</option>
                <option>Console</option>
              </select>
            </div>
            <button class="btn btn-light">Tìm kiếm</button>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Tên sản phẩm</th>
                  <th>Danh mục</th>
                  <th>Giá</th>
                  <th>Trạng thái</th>
                  <th>Tồn kho</th>
                  <th>Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Olivewood Dining Chair</td>
                  <td>Chair</td>
                  <td>$850</td>
                  <td><span class="badge live">Published</span></td>
                  <td>18</td>
                  <td><div class="action-cell"><button class="btn btn-light">Sửa</button><button class="btn btn-danger">Xoá</button></div></td>
                </tr>
                <tr>
                  <td>Minimalist Oak Table</td>
                  <td>Table</td>
                  <td>$2,400</td>
                  <td><span class="badge live">Published</span></td>
                  <td>9</td>
                  <td><div class="action-cell"><button class="btn btn-light">Sửa</button><button class="btn btn-danger">Xoá</button></div></td>
                </tr>
                <tr>
                  <td>Marble Top Console</td>
                  <td>Console</td>
                  <td>$1,850</td>
                  <td><span class="badge low">Low stock</span></td>
                  <td>2</td>
                  <td><div class="action-cell"><button class="btn btn-light">Sửa</button><button class="btn btn-danger">Xoá</button></div></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card orders" id="orders">
          <div class="section-header">
            <div>
              <h2 class="panel-title">Quản lý giỏ hàng / đơn hàng</h2>
              <div class="section-note">Xem thông tin, đánh dấu trạng thái cho giỏ hàng và đơn hàng.</div>
            </div>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Mã</th>
                  <th>Khách hàng</th>
                  <th>Loại</th>
                  <th>Tổng tiền</th>
                  <th>Trạng thái</th>
                  <th>Cập nhật</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>#OW-1024</td>
                  <td>Nguyễn Văn A</td>
                  <td>Đơn hàng</td>
                  <td>$3,250</td>
                  <td><span class="badge processing">Đang xử lý</span></td>
                  <td><div class="action-cell"><button class="btn btn-light">Chi tiết</button><button class="btn btn-accent">Đánh dấu</button></div></td>
                </tr>
                <tr>
                  <td>#CART-381</td>
                  <td>Trần Bảo Nhi</td>
                  <td>Giỏ hàng</td>
                  <td>$1,250</td>
                  <td><span class="badge pending">Chưa thanh toán</span></td>
                  <td><div class="action-cell"><button class="btn btn-light">Chi tiết</button><button class="btn btn-accent">Đánh dấu</button></div></td>
                </tr>
                <tr>
                  <td>#OW-1027</td>
                  <td>Phạm Dũng D</td>
                  <td>Đơn hàng</td>
                  <td>$2,200</td>
                  <td><span class="badge shipping">Đang giao</span></td>
                  <td><div class="action-cell"><button class="btn btn-light">Chi tiết</button><button class="btn btn-accent">Hoàn tất</button></div></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <?php include 'includes/qa_view.php'; ?>

        <div class="card pages-manager" id="pages-manager">
            <div class="section-header">
                <div>
                    <h2 class="panel-title">Cấu hình thông tin liên hệ</h2>
                    <div class="section-note">Cập nhật thông tin hiển thị tại trang Liên hệ và chân trang (Footer).</div>
                </div>
            </div>

            <!-- Form gửi dữ liệu về logic PHP xử lý UPDATE -->
            <form method="POST" action="#pages-manager">
                <div class="page-form page-form-spacing">
                    <div class="field">
                        <label>Số điện thoại</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($company['phone'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="field">
                        <label>Email</label>
                        <input type="email" name="mail" value="<?php echo htmlspecialchars($company['mail'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="field-full">
                        <label>Địa chỉ công ty</label>
                        <textarea name="address" rows="3" required><?php echo htmlspecialchars($company['address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="bottom-actions">
                    <!-- Nút Lưu sẽ kích hoạt biến $_POST['update_profile'] trong PHP[cite: 1] -->
                    <button type="submit" name="update_profile" class="btn btn-accent">Lưu thay đổi</button>
                </div>
            </form>
        </div>

        <div class="card contacts" id="contacts">
            <div class="section-header">
                <div>
                    <h2 class="panel-title">Quản lý các liên hệ của khách hàng</h2>
                    <div class="section-note">Xem nội dung tin nhắn, đánh dấu đã đọc hoặc xóa các liên hệ cũ.</div>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Khách hàng</th>
                            <th>Email</th>
                            <th>Nội dung tin nhắn</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($messages)): ?>
                            <?php foreach ($messages as $msg): ?>
                            <tr>
                                <!-- Hiển thị tên khách hàng -->
                                <td><strong><?php echo htmlspecialchars($msg['name']); ?></strong></td>
                                
                                <!-- Hiển thị Email -->
                                <td><?php echo htmlspecialchars($msg['mail']); ?></td>
                                
                                <!-- Hiển thị nội dung tin nhắn -->
                                <td><?php echo htmlspecialchars($msg['question']); ?></td>
                                
                                <!-- Hiển thị trạng thái màu sắc theo badge -->
                                <td>
                                    <span class="badge <?php echo ($msg['status'] == 'Chưa đọc') ? 'unread' : 'read'; ?>">
                                        <?php echo $msg['status']; ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <div class="action-cell">
                                        <!-- Nút đánh dấu đã đọc - Gửi action qua URL[cite: 1] -->
                                        <?php if ($msg['status'] == 'Chưa đọc'): ?>
                                            <a href="?action=mark_read&msg_id=<?php echo $msg['message_id']; ?>#contacts" 
                                              class="btn btn-light">Đánh dấu đã đọc</a>
                                        <?php endif; ?>

                                        <!-- Nút xóa liên hệ[cite: 1] -->
                                        <a href="?action=delete&msg_id=<?php echo $msg['message_id']; ?>#contacts" 
                                          class="btn btn-danger" 
                                          onclick="return confirm('Bạn chắc chắn muốn xóa liên hệ này?')">Xoá</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px;">Hiện chưa có liên hệ nào từ khách hàng.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card users" id="users">
          <div class="section-header">
            <div>
              <h2 class="panel-title">Quản lý thành viên</h2>
              <div class="section-note">Xem thông tin chi tiết, chỉnh sửa vai trò, cấm hoặc xóa tài khoản người dùng.</div>
            </div>
            <button class="btn btn-accent">+ Thêm thành viên</button>
          </div>

          <div class="table-toolbar">
            <div class="toolbar-group">
              <input type="text" placeholder="Tìm tên, email, SĐT...">
              <select>
                <option>Tất cả vai trò</option>
                <option>Member</option>
                <option>Admin</option>
              </select>
              <select>
                <option>Trạng thái</option>
                <option>Hoạt động</option>
                <option>Bị cấm</option>
              </select>
            </div>
            <button class="btn btn-light">Lọc dữ liệu</button>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Thành viên</th>
                  <th>Liên hệ</th>
                  <th>Địa chỉ</th>
                  <th>Vai trò</th>
                  <th>Trạng thái</th>
                  <th>Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <div class="user-info-cell">
                      <img src="https://images.pexels.com/photos/220453/pexels-photo-220453.jpeg?w=50" alt="avatar" class="mini-avatar">
                      <span>Phan Quốc Huy</span>
                    </div>
                  </td>
                  <td>huy@gmail.com<br><small>0912345678</small></td>
                  <td>Dĩ An, Bình Dương</td>
                  <td>Admin</td>
                  <td><span class="badge live">Hoạt động</span></td>
                  <td>
                    <div class="action-cell">
                      <button class="btn btn-light">Sửa</button>
                      <button class="btn btn-dark">Cấm</button>
                      <button class="btn btn-danger">Xoá</button>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div class="user-info-cell">
                      <img src="https://images.pexels.com/photos/1239291/pexels-photo-1239291.jpeg?w=50" alt="avatar" class="mini-avatar">
                      <span>Lê Minh Trang</span>
                    </div>
                  </td>
                  <td>trang.le@gmail.com<br><small>0901122334</small></td>
                  <td>Quận 1, TP.HCM</td>
                  <td>Member</td>
                  <td><span class="badge low">Bị cấm</span></td>
                  <td>
                    <div class="action-cell">
                      <button class="btn btn-light">Sửa</button>
                      <button class="btn btn-accent">Bỏ cấm</button>
                      <button class="btn btn-danger">Xoá</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
