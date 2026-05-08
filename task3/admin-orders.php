<?php
require_once __DIR__ . '/db.php';

ensure_order_status_column();

$notice = '';
$noticeType = 'success';

$cartStatuses = array('Đang chọn hàng', 'Đã đặt hàng', 'Đã hủy');
$orderStatuses = array('Chờ xử lý', 'Đã xác nhận', 'Đang giao', 'Hoàn thành', 'Đã hủy');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_cart_status') {
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        if (!in_array($status, $cartStatuses)) {
            $notice = 'Trạng thái giỏ hàng không hợp lệ.';
            $noticeType = 'error';
        } else {
            $ok = db_execute(
                'UPDATE cart SET status = ? WHERE cart_id = ?',
                'si',
                array($status, $cartId)
            );

            $notice = $ok ? 'Đã cập nhật trạng thái giỏ hàng.' : 'Không thể cập nhật trạng thái giỏ hàng.';
            $noticeType = $ok ? 'success' : 'error';
        }
    } elseif ($action === 'update_order_status') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        if (!in_array($status, $orderStatuses)) {
            $notice = 'Trạng thái đơn hàng không hợp lệ.';
            $noticeType = 'error';
        } else {
            $ok = db_execute(
                'UPDATE orders SET status = ? WHERE order_id = ?',
                'si',
                array($status, $orderId)
            );

            $notice = $ok ? 'Đã cập nhật trạng thái đơn hàng.' : 'Không thể cập nhật trạng thái đơn hàng.';
            $noticeType = $ok ? 'success' : 'error';
        }
    }
}

$q = trim($_GET['q'] ?? '');

$cartPage = max(1, (int)($_GET['cart_page'] ?? 1));
$orderPage = max(1, (int)($_GET['order_page'] ?? 1));
$perPage = 5;

$cartOffset = ($cartPage - 1) * $perPage;
$orderOffset = ($orderPage - 1) * $perPage;

$cartWhere = array();
$cartParams = array();
$cartTypes = '';

if ($q !== '') {
    $cartWhere[] = '(CAST(c.cart_id AS CHAR) LIKE ? OR u.user_name LIKE ? OR u.email LIKE ? OR c.status LIKE ?)';
    $like = '%' . $q . '%';

    $cartParams[] = $like;
    $cartParams[] = $like;
    $cartParams[] = $like;
    $cartParams[] = $like;

    $cartTypes .= 'ssss';
}

$cartWhereSql = count($cartWhere) > 0 ? 'WHERE ' . implode(' AND ', $cartWhere) : '';

$cartCount = db_one(
    "SELECT COUNT(*) AS total
     FROM cart c
     JOIN user u ON c.cus_id = u.user_id
     $cartWhereSql",
    $cartTypes,
    $cartParams
);

$cartTotalRows = $cartCount ? (int)$cartCount['total'] : 0;

$cartListParams = $cartParams;
$cartListTypes = $cartTypes . 'ii';
$cartListParams[] = $perPage;
$cartListParams[] = $cartOffset;

$carts = db_all(
    "SELECT 
        c.cart_id,
        c.created_at,
        c.status,
        c.cus_id,
        u.user_name,
        u.email,
        COALESCE(SUM(ci.quantity), 0) AS item_count,
        COALESCE(SUM(ci.quantity * ci.unit_price), 0) AS cart_total
     FROM cart c
     JOIN user u ON c.cus_id = u.user_id
     LEFT JOIN cartitem ci ON c.cart_id = ci.cart_id
     $cartWhereSql
     GROUP BY c.cart_id, c.created_at, c.status, c.cus_id, u.user_name, u.email
     ORDER BY c.created_at DESC, c.cart_id DESC
     LIMIT ? OFFSET ?",
    $cartListTypes,
    $cartListParams
);

$orderWhere = array();
$orderParams = array();
$orderTypes = '';

if ($q !== '') {
    $orderWhere[] = '(CAST(o.order_id AS CHAR) LIKE ? OR u.user_name LIKE ? OR u.email LIKE ? OR o.status LIKE ?)';
    $like = '%' . $q . '%';

    $orderParams[] = $like;
    $orderParams[] = $like;
    $orderParams[] = $like;
    $orderParams[] = $like;

    $orderTypes .= 'ssss';
}

$orderWhereSql = count($orderWhere) > 0 ? 'WHERE ' . implode(' AND ', $orderWhere) : '';

$orderCount = db_one(
    "SELECT COUNT(*) AS total
     FROM orders o
     JOIN user u ON o.cus_id = u.user_id
     $orderWhereSql",
    $orderTypes,
    $orderParams
);

$orderTotalRows = $orderCount ? (int)$orderCount['total'] : 0;

$orderListParams = $orderParams;
$orderListTypes = $orderTypes . 'ii';
$orderListParams[] = $perPage;
$orderListParams[] = $orderOffset;

$orders = db_all(
    "SELECT 
        o.order_id,
        o.order_date,
        o.status,
        o.total_amount,
        o.shipping_fee,
        u.user_name,
        u.email,
        a.recipient_name,
        a.phone,
        a.ward,
        a.city,
        p.payment_method
     FROM orders o
     JOIN user u ON o.cus_id = u.user_id
     JOIN address a ON o.address_id = a.address_id
     LEFT JOIN payment p ON o.order_id = p.order_id
     $orderWhereSql
     ORDER BY o.order_date DESC, o.order_id DESC
     LIMIT ? OFFSET ?",
    $orderListTypes,
    $orderListParams
);

$detailOrderId = (int)($_GET['order_id'] ?? 0);
$orderItems = array();

if ($detailOrderId > 0) {
    $orderItems = db_all(
        'SELECT 
            oi.order_id,
            oi.product_id,
            oi.quantity,
            oi.sold_price,
            oi.subtotal,
            p.product_name,
            p.url,
            c.category_name
         FROM orderitem oi
         JOIN product p ON oi.product_id = p.product_id
         JOIN category c ON p.category_id = c.category_id
         WHERE oi.order_id = ?
         ORDER BY oi.product_id ASC',
        'i',
        array($detailOrderId)
    );
}

render_srtdash_admin_start('Quản lý giỏ hàng & đơn hàng', 'orders');
?>

<?php if ($notice !== ''): ?>
    <div class="alert alert-<?php echo $noticeType === 'success' ? 'success' : 'danger'; ?> mt-4">
        <?php echo h($notice); ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-12 mt-5">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Tìm kiếm giỏ hàng / đơn hàng</h4>

                <form method="get" class="admin-search-form">
                    <input
                        class="form-control"
                        type="text"
                        name="q"
                        value="<?php echo h($q); ?>"
                        placeholder="Tìm mã, khách hàng, email, trạng thái..."
                    >

                    <button class="btn btn-primary" type="submit">Tìm kiếm</button>
                    <a class="btn btn-secondary" href="../task3/admin-orders.php">Reset</a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 mt-5">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Danh sách giỏ hàng</h4>

                <div class="table-responsive">
                    <table class="table table-hover progress-table text-center">
                        <thead class="text-uppercase">
                            <tr>
                                <th>Mã giỏ</th>
                                <th>Khách hàng</th>
                                <th>Ngày tạo</th>
                                <th>Số lượng</th>
                                <th>Tổng tạm tính</th>
                                <th>Trạng thái</th>
                                <th>Cập nhật</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($carts as $cart): ?>
                                <tr>
                                    <td>#<?php echo (int)$cart['cart_id']; ?></td>

                                    <td class="text-left">
                                        <strong><?php echo h($cart['user_name']); ?></strong>
                                        <br>
                                        <small><?php echo h($cart['email']); ?></small>
                                    </td>

                                    <td><?php echo h($cart['created_at']); ?></td>
                                    <td><?php echo (int)$cart['item_count']; ?></td>
                                    <td><?php echo money_vnd($cart['cart_total']); ?></td>

                                    <td>
                                        <span class="status-badge">
                                            <?php echo h($cart['status']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <form method="post" class="status-form">
                                            <input type="hidden" name="action" value="update_cart_status">
                                            <input type="hidden" name="cart_id" value="<?php echo (int)$cart['cart_id']; ?>">

                                            <select class="form-control" name="status">
                                                <?php foreach ($cartStatuses as $status): ?>
                                                    <option
                                                        value="<?php echo h($status); ?>"
                                                        <?php echo $status === $cart['status'] ? 'selected' : ''; ?>
                                                    >
                                                        <?php echo h($status); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>

                                            <button class="btn btn-sm btn-primary" type="submit">
                                                Lưu
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($carts)): ?>
                                <tr>
                                    <td colspan="7">Không có giỏ hàng phù hợp.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php echo render_pagination($cartTotalRows, $cartPage, $perPage, 'cart_page'); ?>
            </div>
        </div>
    </div>

    <div class="col-12 mt-5">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Danh sách đơn hàng</h4>

                <div class="table-responsive">
                    <table class="table table-hover progress-table text-center">
                        <thead class="text-uppercase">
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
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo (int)$order['order_id']; ?></td>

                                    <td class="text-left">
                                        <strong><?php echo h($order['user_name']); ?></strong>
                                        <br>
                                        <small><?php echo h($order['email']); ?></small>
                                    </td>

                                    <td class="text-left">
                                        <?php echo h($order['recipient_name']); ?>
                                        <br>
                                        <small>
                                            <?php echo h($order['phone']); ?>
                                            ·
                                            <?php echo h($order['ward']); ?>,
                                            <?php echo h($order['city']); ?>
                                        </small>
                                    </td>

                                    <td><?php echo h($order['order_date']); ?></td>
                                    <td><?php echo h($order['payment_method'] ? $order['payment_method'] : 'Chưa có'); ?></td>
                                    <td><?php echo money_vnd($order['total_amount']); ?></td>

                                    <td>
                                        <span class="status-badge">
                                            <?php echo h($order['status']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <form method="post" class="status-form">
                                            <input type="hidden" name="action" value="update_order_status">
                                            <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>">

                                            <select class="form-control" name="status">
                                                <?php foreach ($orderStatuses as $status): ?>
                                                    <option
                                                        value="<?php echo h($status); ?>"
                                                        <?php echo $status === $order['status'] ? 'selected' : ''; ?>
                                                    >
                                                        <?php echo h($status); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>

                                            <button class="btn btn-sm btn-primary" type="submit">
                                                Lưu
                                            </button>
                                        </form>

                                        <a
                                            class="btn btn-sm btn-info mt-2"
                                            href="../task3/admin-orders.php?order_id=<?php echo (int)$order['order_id']; ?>"
                                        >
                                            Chi tiết
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8">Không có đơn hàng phù hợp.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php echo render_pagination($orderTotalRows, $orderPage, $perPage, 'order_page'); ?>
            </div>
        </div>
    </div>

    <?php if ($detailOrderId > 0): ?>
        <div class="col-12 mt-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title">Chi tiết đơn hàng #<?php echo $detailOrderId; ?></h4>

                    <div class="table-responsive">
                        <table class="table table-hover progress-table text-center">
                            <thead class="text-uppercase">
                                <tr>
                                    <th>Ảnh</th>
                                    <th>Sản phẩm</th>
                                    <th>Danh mục</th>
                                    <th>Số lượng</th>
                                    <th>Giá bán</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <img
                                                class="admin-table-image"
                                                src="<?php echo h(safe_product_image($item['url'])); ?>"
                                                alt="<?php echo h($item['product_name']); ?>"
                                            >
                                        </td>

                                        <td class="text-left"><?php echo h($item['product_name']); ?></td>
                                        <td><?php echo h($item['category_name']); ?></td>
                                        <td><?php echo (int)$item['quantity']; ?></td>
                                        <td><?php echo money_vnd($item['sold_price']); ?></td>
                                        <td><?php echo money_vnd($item['subtotal']); ?></td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php if (empty($orderItems)): ?>
                                    <tr>
                                        <td colspan="6">Không có sản phẩm trong đơn hàng này.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php render_srtdash_admin_end(); ?>