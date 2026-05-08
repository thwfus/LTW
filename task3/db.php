<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'btl_ltw';

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    die('Không thể kết nối database: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money_vnd($value)
{
    return number_format((float)$value, 0, ',', '.') . ' đ';
}

function bind_stmt_params($stmt, $types, $params)
{
    if ($types === '' || empty($params)) {
        return;
    }

    $bind = array();
    $bind[] = $types;

    foreach ($params as $key => $value) {
        $bind[] = &$params[$key];
    }

    call_user_func_array(array($stmt, 'bind_param'), $bind);
}

function stmt_bind($stmt, $types, &$params = array())
{
    bind_stmt_params($stmt, $types, $params);
}

function fetch_all_stmt($stmt)
{
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        return array();
    }

    $rows = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}

function db_all($sql, $types = '', $params = array())
{
    global $conn;

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return array();
    }

    bind_stmt_params($stmt, $types, $params);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $rows = array();

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }

    mysqli_stmt_close($stmt);

    return $rows;
}

function db_one($sql, $types = '', $params = array())
{
    $rows = db_all($sql, $types, $params);

    if (count($rows) > 0) {
        return $rows[0];
    }

    return null;
}

function db_execute($sql, $types = '', $params = array())
{
    global $conn;

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return false;
    }

    bind_stmt_params($stmt, $types, $params);
    $ok = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    return $ok;
}

function include_site_header($title = 'Olivewood')
{
    $headerFile = __DIR__ . '/../php/header.php';

    if (file_exists($headerFile)) {
        include $headerFile;
        return;
    }

    echo '<!doctype html>';
    echo '<html lang="vi">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . h($title) . '</title>';
    echo '</head>';
    echo '<body>';
}

function include_site_footer()
{
    $footerFile = __DIR__ . '/../php/footer.php';

    if (file_exists($footerFile)) {
        include $footerFile;
        return;
    }

    echo '</body>';
    echo '</html>';
}

function current_customer_id()
{
    if (isset($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];

        $customer = db_one(
            'SELECT user_id FROM customer WHERE user_id = ? LIMIT 1',
            'i',
            array($userId)
        );

        if ($customer) {
            return $userId;
        }
    }

    $customer = db_one('SELECT user_id FROM customer ORDER BY user_id ASC LIMIT 1');

    if ($customer) {
        return (int)$customer['user_id'];
    }

    return 11;
}

function safe_product_image($path)
{
    $path = trim((string)$path);

    if ($path === '') {
        return '../public/uploads/products/no-image.png';
    }

    if (preg_match('/^https?:\/\//i', $path)) {
        return '../public/uploads/products/no-image.png';
    }

    if (strpos($path, '../') === 0 || strpos($path, '/') === 0) {
        return $path;
    }

    return '../' . $path;
}

function save_product_upload($fieldName, $oldPath, &$error)
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $oldPath;
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload ảnh thất bại.';
        return false;
    }

    if ($file['size'] > 3 * 1024 * 1024) {
        $error = 'Ảnh không được vượt quá 3MB.';
        return false;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = array('jpg', 'jpeg', 'png', 'webp', 'gif');

    if (!in_array($ext, $allowed)) {
        $error = 'Chỉ cho phép upload ảnh JPG, JPEG, PNG, WEBP hoặc GIF.';
        return false;
    }

    if (@getimagesize($file['tmp_name']) === false) {
        $error = 'File upload không phải là ảnh hợp lệ.';
        return false;
    }

    $rootDir = realpath(__DIR__ . '/..');
    $uploadDir = $rootDir . '/public/uploads/products';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $newName = 'product_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $targetPath = $uploadDir . '/' . $newName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $error = 'Không thể lưu ảnh lên server.';
        return false;
    }

    return 'public/uploads/products/' . $newName;
}

function get_active_cart_id($customerId, $createIfMissing = false)
{
    global $conn;

    $cart = db_one(
        "SELECT cart_id 
         FROM cart 
         WHERE cus_id = ? AND status = 'Đang chọn hàng'
         ORDER BY cart_id DESC
         LIMIT 1",
        'i',
        array($customerId)
    );

    if ($cart) {
        return (int)$cart['cart_id'];
    }

    if (!$createIfMissing) {
        return null;
    }

    $ok = db_execute(
        "INSERT INTO cart (created_at, status, cus_id)
         VALUES (NOW(), 'Đang chọn hàng', ?)",
        'i',
        array($customerId)
    );

    if (!$ok) {
        return null;
    }

    return mysqli_insert_id($conn);
}

function add_product_to_cart($customerId, $productId, $quantity, &$message)
{
    $productId = (int)$productId;
    $quantity = (int)$quantity;

    if ($productId <= 0) {
        $message = 'Sản phẩm không hợp lệ.';
        return false;
    }

    if ($quantity <= 0) {
        $message = 'Số lượng phải lớn hơn 0.';
        return false;
    }

    $product = db_one(
        'SELECT product_id, price, stock_quantity
         FROM product
         WHERE product_id = ?
         LIMIT 1',
        'i',
        array($productId)
    );

    if (!$product) {
        $message = 'Sản phẩm không tồn tại.';
        return false;
    }

    if ((int)$product['stock_quantity'] < $quantity) {
        $message = 'Số lượng vượt quá tồn kho.';
        return false;
    }

    $cartId = get_active_cart_id($customerId, true);

    if (!$cartId) {
        $message = 'Không thể tạo giỏ hàng.';
        return false;
    }

    $cartItem = db_one(
        'SELECT quantity
         FROM cartitem
         WHERE cart_id = ? AND product_id = ?
         LIMIT 1',
        'ii',
        array($cartId, $productId)
    );

    if ($cartItem) {
        $newQuantity = (int)$cartItem['quantity'] + $quantity;

        if ($newQuantity > (int)$product['stock_quantity']) {
            $message = 'Số lượng trong giỏ vượt quá tồn kho.';
            return false;
        }

        $ok = db_execute(
            'UPDATE cartitem
             SET quantity = ?, unit_price = ?
             WHERE cart_id = ? AND product_id = ?',
            'idii',
            array($newQuantity, (float)$product['price'], $cartId, $productId)
        );
    } else {
        $ok = db_execute(
            'INSERT INTO cartitem (cart_id, product_id, quantity, unit_price)
             VALUES (?, ?, ?, ?)',
            'iiid',
            array($cartId, $productId, $quantity, (float)$product['price'])
        );
    }

    if (!$ok) {
        $message = 'Không thể thêm sản phẩm vào giỏ hàng.';
        return false;
    }

    $message = 'Đã thêm sản phẩm vào giỏ hàng.';
    return true;
}

function get_cart_data($customerId)
{
    $cartId = get_active_cart_id($customerId, false);

    if (!$cartId) {
        return array(
            'cart_id' => null,
            'items' => array(),
            'total' => 0
        );
    }

    $items = db_all(
        'SELECT 
            ci.cart_id,
            ci.product_id,
            ci.quantity,
            ci.unit_price,
            p.product_name,
            p.price,
            p.stock_quantity,
            p.url,
            c.category_name,
            ci.quantity * ci.unit_price AS line_total
         FROM cartitem ci
         JOIN product p ON ci.product_id = p.product_id
         JOIN category c ON p.category_id = c.category_id
         WHERE ci.cart_id = ?
         ORDER BY p.product_name ASC',
        'i',
        array($cartId)
    );

    $total = 0;

    foreach ($items as $item) {
        $total += (float)$item['line_total'];
    }

    return array(
        'cart_id' => $cartId,
        'items' => $items,
        'total' => $total
    );
}

function get_cart_items($arg1, $arg2 = null)
{
    /*
        Hỗ trợ cả 2 kiểu gọi:

        Kiểu cũ:
        get_cart_items($conn, $customerId)

        Kiểu mới:
        get_cart_items($customerId)
    */

    if ($arg2 === null) {
        $customerId = (int)$arg1;
    } else {
        $customerId = (int)$arg2;
    }

    return get_cart_data($customerId);
}

function add_to_cart($ignoredConn, $customerId, $productId, $quantity, &$message)
{
    /*
        Hỗ trợ kiểu gọi cũ trong products.php / product-detail.php:

        add_to_cart($conn, $customerId, $productId, $quantity, $notice)
    */

    return add_product_to_cart(
        (int)$customerId,
        (int)$productId,
        (int)$quantity,
        $message
    );
}

function asset_path($path, $fallback = 'public/uploads/products/no-image.png')
{
    /*
        Hỗ trợ code cũ đang gọi asset_path($product['url'])
    */

    $path = trim((string)$path);

    if ($path === '') {
        $path = $fallback;
    }

    return safe_product_image($path);
}

function render_pagination($totalRows, $page, $perPage, $pageKey = 'page')
{
    if ($totalRows <= $perPage) {
        return '';
    }

    $totalPages = (int)ceil($totalRows / $perPage);
    $html = '<nav class="task3-pagination">';

    for ($i = 1; $i <= $totalPages; $i++) {
        $params = $_GET;
        $params[$pageKey] = $i;

        $class = $i === $page ? 'active' : '';
        $html .= '<a class="' . $class . '" href="?' . h(http_build_query($params)) . '">' . $i . '</a>';
    }

    $html .= '</nav>';

    return $html;
}

function ensure_order_status_column()
{
    $column = db_one("SHOW COLUMNS FROM orders LIKE 'status'");

    if (!$column) {
        db_execute(
            "ALTER TABLE orders 
             ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Chờ xử lý' AFTER order_date"
        );
    }
}

function srtdash_asset($path)
{
    return '../public/srtdash/' . ltrim($path, '/');
}

function render_srtdash_admin_start($title, $activePage)
{
    ?>
    <!doctype html>
    <html lang="vi">

    <head>
        <meta charset="utf-8">
        <title><?php echo h($title); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="icon" type="image/png" href="<?php echo srtdash_asset('assets/images/icon/logo.png'); ?>">

        <link rel="stylesheet" href="<?php echo srtdash_asset('assets/css/bootstrap.min.css'); ?>">
        <link rel="stylesheet" href="<?php echo srtdash_asset('assets/css/fontawesome.min.css'); ?>">
        <link rel="stylesheet" href="<?php echo srtdash_asset('assets/css/themify-icons.css'); ?>">
        <link rel="stylesheet" href="<?php echo srtdash_asset('assets/css/metismenujs.min.css'); ?>">
        <link rel="stylesheet" href="<?php echo srtdash_asset('assets/css/swiper-bundle.min.css'); ?>">
        <link rel="stylesheet" href="<?php echo srtdash_asset('assets/css/typography.css'); ?>">
        <link rel="stylesheet" href="<?php echo srtdash_asset('assets/css/default-css.css'); ?>">
        <link rel="stylesheet" href="<?php echo srtdash_asset('assets/css/styles.css'); ?>">
        <link rel="stylesheet" href="<?php echo srtdash_asset('assets/css/responsive.css'); ?>">
        <link rel="stylesheet" href="../task3/admin.css">
    </head>

    <body>
        <div id="preloader">
            <div class="loader"></div>
        </div>

        <div class="page-container">
            <div class="sidebar-menu">
                <div class="sidebar-header">
                    <div class="logo">
                        <a href="../task3/admin-products.php">
                            <span class="task3-admin-logo">TASK 3 ADMIN</span>
                        </a>
                    </div>
                </div>

                <div class="main-menu">
                    <div class="menu-inner">
                        <nav>
                            <ul class="metismenu" id="menu">
                                <li class="<?php echo $activePage === 'products' ? 'active' : ''; ?>">
                                    <a href="../task3/admin-products.php">
                                        <i class="ti-package"></i>
                                        <span>Quản lý sản phẩm</span>
                                    </a>
                                </li>

                                <li class="<?php echo $activePage === 'orders' ? 'active' : ''; ?>">
                                    <a href="../task3/admin-orders.php">
                                        <i class="ti-shopping-cart"></i>
                                        <span>Giỏ hàng / đơn hàng</span>
                                    </a>
                                </li>

                                <li>
                                    <a href="../task3/products.php">
                                        <i class="ti-layout-grid2"></i>
                                        <span>Trang sản phẩm</span>
                                    </a>
                                </li>

                                <li>
                                    <a href="../task3/cart.php">
                                        <i class="ti-bag"></i>
                                        <span>Giỏ hàng khách</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="main-content">
                <div class="header-area">
                    <div class="row align-items-center">
                        <div class="col-md-6 col-sm-8 clearfix">
                            <div class="nav-btn float-start">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>

                            <div class="search-box float-start task3-admin-heading">
                                <?php echo h($title); ?>
                            </div>
                        </div>

                        <div class="col-md-6 col-sm-4 clearfix">
                            <ul class="notification-area float-end">
                                <li>
                                    <a href="../task3/products.php" title="Xem trang sản phẩm">
                                        <i class="ti-home"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="../task3/cart.php" title="Xem giỏ hàng">
                                        <i class="ti-bag"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="page-title-area">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <div class="breadcrumbs-area clearfix">
                                <h1 class="page-title float-start"><?php echo h($title); ?></h1>

                                <ul class="breadcrumbs float-start">
                                    <li><a href="../task3/admin-products.php">Dashboard</a></li>
                                    <li><span><?php echo h($title); ?></span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-sm-6 clearfix">
                            <div class="user-profile float-end task3-user-profile">
                                <h4 class="user-name dropdown-toggle" data-bs-toggle="dropdown">
                                    Admin <i class="fa-solid fa-angle-down"></i>
                                </h4>

                                <div class="dropdown-menu user-dropdown">
                                    <a class="dropdown-item" href="../task3/products.php">Trang sản phẩm</a>
                                    <a class="dropdown-item" href="../task3/cart.php">Giỏ hàng khách</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-content-inner">
    <?php
}

function render_srtdash_admin_end()
{
    ?>
                </div>
            </div>

            <footer>
                <div class="footer-area">
                    <p>© 2026 Task 3 Admin. Template by Srtdash / Colorlib.</p>
                </div>
            </footer>
        </div>

        <script src="<?php echo srtdash_asset('assets/js/bootstrap.bundle.min.js'); ?>"></script>
        <script src="<?php echo srtdash_asset('assets/js/swiper-bundle.min.js'); ?>"></script>
        <script src="<?php echo srtdash_asset('assets/js/metismenujs.min.js'); ?>"></script>
        <script src="<?php echo srtdash_asset('assets/js/scripts.js'); ?>"></script>
    </body>

    </html>
    <?php
}
?>