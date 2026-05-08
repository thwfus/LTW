<?php
require_once __DIR__ . '/db.php';

$customerId = current_customer_id($conn);
$notice = '';
$noticeType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cartId = get_active_cart_id($conn, $customerId, false);

    if (!$cartId) {
        $notice = 'Giỏ hàng đang trống.';
        $noticeType = 'error';
    } elseif ($action === 'update_quantity') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        $stmt = $conn->prepare('UPDATE cartitem SET quantity = ? WHERE cart_id = ? AND product_id = ?');
        $stmt->bind_param('iii', $quantity, $cartId, $productId);

        if ($stmt->execute()) {
            $notice = 'Đã cập nhật số lượng.';
        } else {
            $notice = $stmt->error ?: 'Không thể cập nhật số lượng.';
            $noticeType = 'error';
        }

        $stmt->close();
    } elseif ($action === 'remove_item') {
        $productId = (int)($_POST['product_id'] ?? 0);

        $stmt = $conn->prepare('DELETE FROM cartitem WHERE cart_id = ? AND product_id = ?');
        $stmt->bind_param('ii', $cartId, $productId);

        if ($stmt->execute()) {
            $notice = 'Đã xóa sản phẩm khỏi giỏ hàng.';
        } else {
            $notice = $stmt->error ?: 'Không thể xóa sản phẩm.';
            $noticeType = 'error';
        }

        $stmt->close();
    } elseif ($action === 'clear_cart') {
        $stmt = $conn->prepare('DELETE FROM cartitem WHERE cart_id = ?');
        $stmt->bind_param('i', $cartId);

        if ($stmt->execute()) {
            $notice = 'Đã làm trống giỏ hàng.';
        } else {
            $notice = $stmt->error ?: 'Không thể làm trống giỏ hàng.';
            $noticeType = 'error';
        }

        $stmt->close();
    }
}

$cart = get_cart_items($conn, $customerId);

$user = null;

$stmt = $conn->prepare('SELECT user_id, user_name, email, phone FROM user WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $customerId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

include_site_header('Cart');
?>

<link rel="stylesheet" href="../task3/product.css" />

<main class="product-detail-container">
    <header class="cart-header-page">
        <p class="breadcrumb">Task 3 / Cart</p>
        <h1 class="product-title">Giỏ hàng</h1>

        <?php if ($user): ?>
            <p>
                Khách hàng:
                <strong><?= h($user['user_name']) ?></strong>
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
            <h2>Giỏ hàng đang trống</h2>
            <p>Hãy quay lại trang sản phẩm để chọn thêm món nội thất bạn yêu thích.</p>
            <a class="product-card-btn" href="../task3/products.php">
                Tiếp tục mua sắm
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
                            <p>Đơn giá: <?= money_vnd($item['unit_price']) ?></p>
                            <p>Tồn kho hiện tại: <?= (int)$item['stock_quantity'] ?></p>
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
                                    Cập nhật
                                </button>
                            </form>

                            <strong><?= money_vnd($item['line_total']) ?></strong>

                            <form method="post" onsubmit="return confirm('Xóa sản phẩm này khỏi giỏ hàng?')">
                                <input type="hidden" name="action" value="remove_item" />
                                <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>" />

                                <button class="danger-link" type="submit">
                                    Xóa
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <aside class="cart-summary-card">
                <h2>Tóm tắt giỏ hàng</h2>

                <div class="cart-preview-row">
                    <span>Mã giỏ hàng</span>
                    <strong>#<?= (int)$cart['cart_id'] ?></strong>
                </div>

                <div class="cart-preview-row">
                    <span>Số dòng sản phẩm</span>
                    <strong><?= count($cart['items']) ?></strong>
                </div>

                <div class="cart-preview-total">
                    <span>Tổng tạm tính</span>
                    <strong><?= money_vnd($cart['total']) ?></strong>
                </div>

                <a class="product-card-btn full-width" href="../task3/products.php">
                    Tiếp tục mua sắm
                </a>

                <a class="product-card-btn secondary full-width" href="../task3/admin-orders.php">
                    Admin: quản lý giỏ hàng/đơn hàng
                </a>

                <form method="post" onsubmit="return confirm('Làm trống toàn bộ giỏ hàng?')">
                    <input type="hidden" name="action" value="clear_cart" />

                    <button class="product-card-btn danger full-width" type="submit">
                        Làm trống giỏ hàng
                    </button>
                </form>
            </aside>
        </section>
    <?php endif; ?>
</main>

<?php
include_site_footer();
?>