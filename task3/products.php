<?php
require_once __DIR__ . '/db.php';

$customerId = current_customer_id($conn);
$notice = '';
$noticeType = 'success';

// Handle Add to Cart action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    // YÊU CẦU ĐĂNG NHẬP: Kiểm tra xem user đã đăng nhập chưa
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../html/login.php"); // Chuyển hướng nếu chưa đăng nhập
        exit();
    }

    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = 1; // Mặc định là 1 theo yêu cầu của bạn

    // Gọi hàm add_to_cart với customerId lấy từ session
    $ok = add_to_cart($conn, $_SESSION['user_id'], $productId, $quantity, $notice);
    $noticeType = $ok ? 'success' : 'error';
}

// Get filter parameters
$keyword = trim($_GET['q'] ?? '');
$categoryId = (int)($_GET['category_id'] ?? 0);
$minPrice = trim($_GET['min_price'] ?? '');
$maxPrice = trim($_GET['max_price'] ?? '');
$sort = $_GET['sort'] ?? 'featured';

// Fetch categories for filter
$categories = [];
$categoryResult = $conn->query('SELECT category_id, category_name FROM category ORDER BY category_name ASC');

if ($categoryResult) {
    $categories = $categoryResult->fetch_all(MYSQLI_ASSOC);
}

// Build SQL query with filters
$where = [];
$params = [];
$types = '';

if ($keyword !== '') {
    $where[] = 'p.product_name LIKE ?';
    $params[] = '%' . $keyword . '%';
    $types .= 's';
}

if ($categoryId > 0) {
    $where[] = 'p.category_id = ?';
    $params[] = $categoryId;
    $types .= 'i';
}

if ($minPrice !== '' && is_numeric($minPrice)) {
    $where[] = 'p.price >= ?';
    $params[] = (float)$minPrice;
    $types .= 'd';
}

if ($maxPrice !== '' && is_numeric($maxPrice)) {
    $where[] = 'p.price <= ?';
    $params[] = (float)$maxPrice;
    $types .= 'd';
}

// Handle sorting logic
$orderBy = 'p.product_id ASC';
if ($sort === 'price_asc') {
    $orderBy = 'p.price ASC';
} elseif ($sort === 'price_desc') {
    $orderBy = 'p.price DESC';
} elseif ($sort === 'name_asc') {
    $orderBy = 'p.product_name ASC';
} elseif ($sort === 'stock_desc') {
    $orderBy = 'p.stock_quantity DESC';
}

$sql = '
    SELECT
        p.product_id,
        p.product_name,
        p.price,
        p.stock_quantity,
        p.material,
        p.color,
        p.warranty_period,
        p.url,
        c.category_id,
        c.category_name,
        CASE
            WHEN p.stock_quantity > 0 THEN "In Stock"
            ELSE "Out of Stock"
        END AS stock_status
    FROM product p
    JOIN category c ON p.category_id = c.category_id
';

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY ' . $orderBy;

// Execute prepared statement
$stmt = $conn->prepare($sql);
if ($types !== '') {
    stmt_bind($stmt, $types, $params);
}
$stmt->execute();
$products = fetch_all_stmt($stmt);
$stmt->close();

$cart = get_cart_items($conn, $customerId);

include_site_header('Shop All Products');
?>

<link rel="stylesheet" href="../task3/products.css" />


<main class="products-page-container">
    <header class="shop-header">
        <p class="eyebrow">Olivewood Collection</p>
        <h1 class="hero-title">Shop All Pieces</h1>
        <p class="shop-subtitle">
            Search, filter, and add handcrafted furniture pieces to your cart.
        </p>
    </header>

    <?php if ($notice !== ''): ?>
        <div class="task-alert task-alert-<?= h($noticeType) ?>">
            <?= h($notice) ?>
        </div>
    <?php endif; ?>

    <section class="shop-layout">
        <aside class="filter-sidebar">
            <form method="get" class="filter-card">
                <div class="filter-section">
                    <h3 class="filter-title">Search</h3>
                    <input
                        class="price-input"
                        type="text"
                        name="q"
                        value="<?= h($keyword) ?>"
                        placeholder="Product name..."
                    />
                </div>

                <div class="filter-section">
                    <h3 class="filter-title">Category</h3>
                    <select class="price-input" name="category_id">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= (int)$category['category_id'] ?>"
                                <?= $categoryId === (int)$category['category_id'] ? 'selected' : '' ?>
                            >
                                <?= h($category['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-section">
                    <h3 class="filter-title">Price Range</h3>
                    <div class="price-range-inputs">
                        <input
                            class="price-input"
                            type="number"
                            name="min_price"
                            value="<?= h($minPrice) ?>"
                            placeholder="Min"
                            min="0"
                        />
                        <span>—</span>
                        <input
                            class="price-input"
                            type="number"
                            name="max_price"
                            value="<?= h($maxPrice) ?>"
                            placeholder="Max"
                            min="0"
                        />
                    </div>
                </div>

                <div class="filter-section">
                    <h3 class="filter-title">Sort By</h3>
                    <select class="price-input" name="sort">
                        <option value="featured" <?= $sort === 'featured' ? 'selected' : '' ?>>
                            Default
                        </option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>
                            Price: Low to High
                        </option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>
                            Price: High to Low
                        </option>
                        <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>
                            Name: A-Z
                        </option>
                        <option value="stock_desc" <?= $sort === 'stock_desc' ? 'selected' : '' ?>>
                            Most in Stock
                        </option>
                    </select>
                </div>

                <button class="product-card-btn full-width" type="submit">
                    Apply Filter
                </button>

                <a class="clear-filter-link" href="../task3/products.php">
                    Clear Filters
                </a>
            </form>

            <div class="mini-cart-card">
                <h3 class="filter-title">Current Cart</h3>
                <p><?= count($cart['items']) ?> products</p>
                <strong><?= money_vnd($cart['total']) ?></strong>
                <a class="clear-filter-link" href="../task3/cart.php">
                    View Cart
                </a>
            </div>
        </aside>

        <section class="products-content">
            <div class="products-toolbar">
                <p>Showing <strong><?= count($products) ?></strong> products</p>
                </div>

            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <h2>No products found</h2>
                    <p>Try changing your keyword, category, or price range.</p>
                </div>
            <?php else: ?>
                <div class="featured-products-grid">
                    <?php foreach ($products as $product): ?>
                        <article class="product-card">
                            <a
                                href="../task3/product-detail.php?id=<?= (int)$product['product_id'] ?>"
                                class="product-image-link"
                            >
                                <img
                                    src="<?= h(asset_path($product['url'])) ?>"
                                    alt="<?= h($product['product_name']) ?>"
                                />
                            </a>

                            <div class="product-card-body">
                                <p class="product-category">
                                    <?= h($product['category_name']) ?>
                                </p>

                                <h3><?= h($product['product_name']) ?></h3>

                                <p class="product-meta">
                                    <?= h($product['material'] ?: 'Material not updated') ?>
                                    <?php if (!empty($product['color'])): ?>
                                        · <?= h($product['color']) ?>
                                    <?php endif; ?>
                                </p>

                                <div class="price-stock-row">
                                    <span class="product-price-small">
                                        <?= money_vnd($product['price']) ?>
                                    </span>

                                    <span class="stock-pill <?= (int)$product['stock_quantity'] > 0 ? 'in-stock' : 'out-stock' ?>">
                                        <?= h($product['stock_status']) ?>
                                    </span>
                                </div>

                                <p class="stock-text">
                                    In Stock: <?= (int)$product['stock_quantity'] ?>
                                </p>

                                <div class="product-card-actions">
                                    <a
                                        class="product-card-btn secondary"
                                        href="../task3/product-detail.php?id=<?= (int)$product['product_id'] ?>"
                                    >
                                        View Detail
                                    </a>

                                    <form method="post">
                                        <input type="hidden" name="action" value="add_to_cart" />
                                        <input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>" />
                                        <input type="hidden" name="quantity" value="1" />

                                        <button
                                            class="product-card-btn"
                                            type="submit"
                                            <?= (int)$product['stock_quantity'] <= 0 ? 'disabled' : '' ?>
                                        >
                                            Add
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </section>
</main>

<?php
include_site_footer();
?>