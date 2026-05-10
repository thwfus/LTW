<?php
require_once __DIR__ . '/db.php';

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user_id'])) {
    header("Location: ../html/login.php");
    exit();
}

$customerId = (int)$_SESSION['user_id'];
$notice = '';
$noticeType = 'success';

// 3. XỬ LÝ CÁC HÀNH ĐỘNG TRONG GIỎ HÀNG
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $cartId = get_active_cart_id($customerId, false);

    if (!$cartId) {
        $notice = 'Giỏ hàng đang trống hoặc không tồn tại.';
        $noticeType = 'error';
    } elseif ($action === 'update_quantity') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        $stmt = $conn->prepare('UPDATE cartitem SET quantity = ? WHERE cart_id = ? AND product_id = ?');
        $stmt->bind_param('iii', $quantity, $cartId, $productId);
        if ($stmt->execute()) { $notice = 'Đã cập nhật số lượng thành công.'; }
        $stmt->close();

    } elseif ($action === 'remove_item') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM cartitem WHERE cart_id = ? AND product_id = ?');
        $stmt->bind_param('ii', $cartId, $productId);
        if ($stmt->execute()) { $notice = 'Đã xóa sản phẩm khỏi giỏ hàng.'; }
        $stmt->close();

    } elseif ($action === 'clear_cart') {
        $stmt = $conn->prepare('DELETE FROM cartitem WHERE cart_id = ?');
        $stmt->bind_param('i', $cartId);
        if ($stmt->execute()) { $notice = 'Đã làm trống toàn bộ giỏ hàng.'; }
        $stmt->close();

    } 
    
    // --- HÀM THANH TOÁN TỐI ƯU (Dựa trên cấu trúc image_f6abbd.png) ---
    elseif ($action === 'checkout') {
        $currentCart = get_cart_items($customerId);
        
        if (!empty($currentCart['items'])) {
            $conn->begin_transaction();
            try {
                $addrReq = $conn->prepare("SELECT address_id FROM address WHERE cus_id = ? LIMIT 1");
                $addrReq->bind_param("i", $customerId);
                $addrReq->execute();
                $addrResult = $addrReq->get_result()->fetch_assoc();
                $addrReq->close();

                $addressId = $addrResult ? (int)$addrResult['address_id'] : null;

                $totalAmount = $currentCart['total'];

                $orderStmt = $conn->prepare("INSERT INTO orders (total_amount, cus_id, address_id) VALUES (?, ?, ?)");
                
                $orderStmt->bind_param("dii", $totalAmount, $customerId, $addressId);
                
                if (!$orderStmt->execute()) {
                    throw new Exception($orderStmt->error);
                }
                $orderStmt->close();

                foreach ($currentCart['items'] as $item) {
                    $pid = (int)$item['product_id'];
                    $qty = (int)$item['quantity'];
                    
                    $updateStock = $conn->prepare("UPDATE Product SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
                    $updateStock->bind_param("ii", $qty, $pid);
                    $updateStock->execute();
                    $updateStock->close();
                }

                $stmtClear = $conn->prepare('DELETE FROM cartitem WHERE cart_id = ?');
                $stmtClear->bind_param('i', $cartId);
                $stmtClear->execute();
                $stmtClear->close();

                $conn->commit();
                $notice = 'Thanh toán thành công!';
                $noticeType = 'success';

            } catch (Exception $e) {
                $conn->rollback();
                $notice = 'Lỗi hệ thống khi thanh toán: ' . $e->getMessage();
                $noticeType = 'error';
            }
        }
    }

}

$cart = get_cart_items($customerId);
$user = db_one('SELECT user_name, email FROM User WHERE user_id = ? LIMIT 1', 'i', array($customerId));

include_site_header('Your Collection - Cart');
?>

<link rel="stylesheet" href="../task3/product.css" />
<style>
    /* CSS cho Modal thanh toán */
    #checkout-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.8);
        z-index: 1000;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        color: white;
    }
    .checkout-content {
        background: #fff;
        padding: 30px;
        text-align: center;
        border-radius: 8px;
        color: #333;
    }
    .checkout-content img {
        max-width: 300px;
        margin-bottom: 20px;
        display: block;
    }
</style>

<main class="product-detail-container">
    <header class="cart-header-page">
        <p class="breadcrumb">Atelier / Shopping Cart</p>
        <h1 class="product-title">Your Cart</h1>
        <?php if ($user): ?>
            <p>Customer: <strong><?= h($user['user_name']) ?></strong> · <?= h($user['email']) ?></p>
        <?php endif; ?>
    </header>

    <?php if ($notice !== ''): ?>
        <div class="task-alert task-alert-<?= h($noticeType) ?>"><?= h($notice) ?></div>
    <?php endif; ?>

    <?php if (empty($cart['items'])): ?>
        <section class="empty-state product-not-found">
            <h2>The cart is empty</h2>
            <a class="product-card-btn" href="../task3/products.php">Ours Collections</a>
        </section>
    <?php else: ?>
        <section class="cart-page-layout">
            <div class="cart-items-list">
                <?php foreach ($cart['items'] as $item): ?>
                    <article class="cart-item-card">
                        <img src="<?= h(asset_path($item['url'])) ?>" alt="<?= h($item['product_name']) ?>" />
                        <div class="cart-item-info">
                            <p class="product-category"><?= h($item['category_name']) ?></p>
                            <h3><?= h($item['product_name']) ?></h3>
                            <p>Price: <?= money_vnd($item['unit_price']) ?></p>
                        </div>
                        <div class="cart-item-actions">
                            <form method="post" class="cart-qty-form">
                                <input type="hidden" name="action" value="update_quantity" />
                                <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>" />
                                <input class="cart-qty-input" type="number" name="quantity" min="1" max="<?= (int)$item['stock_quantity'] ?>" value="<?= (int)$item['quantity'] ?>" />
                                <button class="product-card-btn secondary" type="submit">Save</button>
                            </form>
                            <strong><?= money_vnd($item['line_total']) ?></strong>
                            <form method="post">
                                <input type="hidden" name="action" value="remove_item" />
                                <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>" />
                                <button class="danger-link" type="submit">Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <aside class="cart-summary-card">
                <h2>Total</h2>
                <div class="cart-preview-total">
                    <span>Total</span>
                    <strong><?= money_vnd($cart['total']) ?></strong>
                </div>

                <button type="button" class="product-card-btn full-width" onclick="showCheckout()" style="background-color: #27ae60; color: white; border: none; margin-bottom: 10px;">
                    Proceed to Checkout
                </button>

                <a class="product-card-btn full-width" href="../task3/products.php">Add More</a>
            </aside>
        </section>
    <?php endif; ?>
</main>

<div id="checkout-overlay">
    <div class="checkout-content">
        <h2>Scan to Pay</h2>
        <img src="../uploads/default.png" alt="Payment QR" onerror="this.src='https://placehold.co/300x300?text=Payment+Image+Not+Found'">
        
        <form method="post">
            <input type="hidden" name="action" value="checkout">
            <button type="submit" class="product-card-btn full-width">
                I have paid, complete order
            </button>
            <button type="button" onclick="hideCheckout()" style="margin-top: 10px; background: none; border: none; text-decoration: underline; cursor: pointer;">
                Cancel
            </button>
        </form>
    </div>
</div>

<script>
    function showCheckout() {
        document.getElementById('checkout-overlay').style.display = 'flex';
    }
    function hideCheckout() {
        document.getElementById('checkout-overlay').style.display = 'none';
    }
</script>

<?php include_site_footer(); ?>