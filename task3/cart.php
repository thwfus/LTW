<?php
require_once __DIR__ . '/db.php';

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user_id'])) {
    header("Location: ../html/login.php");
    exit();
}

// 2. LẤY ID NGƯỜI DÙNG TỪ SESSION
$customerId = (int)$_SESSION['user_id'];
$notice = '';
$noticeType = 'success';

// 3. XỬ LÝ CÁC HÀNH ĐỘNG TRONG GIỎ HÀNG
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // Tìm mã giỏ hàng 'active' của khách hàng này
    $cartId = get_active_cart_id($customerId, false);

    if (!$cartId) {
        $notice = 'Giỏ hàng đang trống hoặc không tồn tại.';
        $noticeType = 'error';
    } elseif ($action === 'update_quantity') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        $stmt = $conn->prepare('UPDATE cartitem SET quantity = ? WHERE cart_id = ? AND product_id = ?');
        $stmt->bind_param('iii', $quantity, $cartId, $productId);

        if ($stmt->execute()) {
            $notice = 'Đã cập nhật số lượng thành công.';
        } else {
            $notice = 'Không thể cập nhật số lượng.';
            $noticeType = 'error';
        }
        $stmt->close();
    } elseif ($action === 'remove_item') {
        $productId = (int)($_POST['product_id'] ?? 0);

        $stmt = $conn->prepare('DELETE FROM cartitem WHERE cart_id = ? AND product_id = ?');
        $stmt->bind_param('ii', $cartId, $productId);

        if ($stmt->execute()) {
            $notice = 'Item successfully removed from cart.';
        } else {
            $notice = 'Error removing item from cart.';
            $noticeType = 'error';
        }
        $stmt->close();
    } elseif ($action === 'clear_cart') {
        $stmt = $conn->prepare('DELETE FROM cartitem WHERE cart_id = ?');
        $stmt->bind_param('i', $cartId);

        if ($stmt->execute()) {
            $notice = 'Đã làm trống toàn bộ giỏ hàng.';
        } else {
            $notice = 'Lỗi khi làm trống giỏ hàng.';
            $noticeType = 'error';
        }
        $stmt->close();
    }
}

// 4. LẤY DỮ LIỆU GIỎ HÀNG ĐỂ HIỂN THỊ
// Hàm get_cart_items sẽ tự động JOIN bảng cartitem và product để lấy tên, giá, ảnh...
$cart = get_cart_items($customerId);

// Lấy thông tin khách hàng để hiển thị tiêu đề
$user = db_one('SELECT user_name, email FROM User WHERE user_id = ? LIMIT 1', 'i', array($customerId));

include_site_header('Your Collection - Cart');
?>

<link rel="stylesheet" href="../task3/product.css" />

<main class="product-detail-container">
    <header class="cart-header-page">
        <p class="breadcrumb">Atelier / Shopping Cart</p>
        <h1 class="product-title">Your Cart</h1>

        <?php if ($user): ?>
            <p>
                Customer: <strong><?= h($user['user_name']) ?></strong>
                · <?= h($user['email']) ?>
            </p>
        <?php endif; ?>
    </header>

    <?php if ($notice !== ''): ?>
        <div class="task-alert task-alert-<?= h($noticeType) ?>">
            <?= h($notice) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cart['items'])): ?>
        <section class="empty-state product-not-found">
            <h2>The cart is empty</h2>
            <p>View our best collections.</p>
            <a class="product-card-btn" href="../task3/products.php">
                Ours Collections
            </a>
        </section>
    <?php else: ?>
        <section class="cart-page-layout">
            <div class="cart-items-list">
                <?php foreach ($cart['items'] as $item): ?>
                    <article class="cart-item-card">
                        <img
                            src="<?= h(asset_path($item['url'])) ?>"
                            alt="<?= h($item['product_name']) ?>"
                        />

                        <div class="cart-item-info">
                            <p class="product-category"><?= h($item['category_name']) ?></p>
                            <h3><?= h($item['product_name']) ?></h3>
                            <p>Price: <?= money_vnd($item['unit_price']) ?></p>
                        </div>

                        <div class="cart-item-actions">
                            <form method="post" class="cart-qty-form">
                                <input type="hidden" name="action" value="update_quantity" />
                                <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>" />

                                <input
                                    class="cart-qty-input"
                                    type="number"
                                    name="quantity"
                                    min="1"
                                    max="<?= max(1, (int)$item['stock_quantity']) ?>"
                                    value="<?= (int)$item['quantity'] ?>"
                                />

                                <button class="product-card-btn secondary" type="submit">
                                    Save    
                                </button>
                            </form>

                            <strong><?= money_vnd($item['line_total']) ?></strong>

                            <form method="post" onsubmit="return confirm('Delete this item from cart?')">
                                <input type="hidden" name="action" value="remove_item" />
                                <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>" />
                                <button class="danger-link" type="submit">Delete<button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <aside class="cart-summary-card">
                <h2>Total</h2>

                <div class="cart-preview-row">
                    <span>Cart ID   </span>
                    <strong>#<?= (int)$cart['cart_id'] ?></strong>
                </div>

                <div class="cart-preview-row">
                    <span>Number of Products</span>
                    <strong><?= count($cart['items']) ?></strong>
                </div>

                <div class="cart-preview-total">
                    <span>Total</span>
                    <strong><?= money_vnd($cart['total']) ?></strong>
                </div>

                <a class="product-card-btn full-width" href="../task3/products.php">
                    Add More Products
                </a>

                <form method="post" onsubmit="return confirm('Do you want to empty cart?')">
                    <input type="hidden" name="action" value="clear_cart" />
                    <button class="product-card-btn danger full-width" type="submit">
                        Empty Cart
                    </button>
                </form>
            </aside>
        </section>
    <?php endif; ?>
</main>

<?php include_site_footer(); ?>