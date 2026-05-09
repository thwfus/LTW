<?php
require_once __DIR__ . '/db.php';

// Sửa lỗi: hàm current_customer_id trong db.php không nhận tham số $conn
$customerId = current_customer_id(); 
$productId = (int)($_GET['id'] ?? 0);
$notice = '';
$noticeType = 'success';

// --- LOGIC THÊM VÀO GIỎ (ĐỒNG BỘ VỚI PRODUCTS.PHP) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    // 1. Kiểm tra đăng nhập
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../html/login.php"); 
        exit();
    }

    $productId = (int)($_POST['product_id'] ?? $productId);
    // Lấy số lượng từ form nhưng vẫn đảm bảo tối thiểu là 1
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    // 2. Sử dụng $_SESSION['user_id'] trực tiếp để khớp với logic nãy giờ chúng ta làm
    $ok = add_to_cart($conn, $_SESSION['user_id'], $productId, $quantity, $notice);
    $noticeType = $ok ? 'success' : 'error';
}

// ... (Đoạn code truy vấn sản phẩm và header giữ nguyên) ...
$stmt = $conn->prepare('
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
        c.category_name
    FROM product p
    JOIN category c ON p.category_id = c.category_id
    WHERE p.product_id = ?
    LIMIT 1
');
$stmt->bind_param('i', $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

$relatedProducts = [];

if ($product) {
    $stmt = $conn->prepare('
        SELECT product_id, product_name, price, url
        FROM product
        WHERE category_id = ? AND product_id <> ?
        ORDER BY product_id ASC
        LIMIT 3
    ');
    $stmt->bind_param('ii', $product['category_id'], $productId);
    $stmt->execute();
    $relatedProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

include_site_header($product ? $product['product_name'] : 'Product Detail');
?>

<link rel="stylesheet" href="../task3/product.css" />

<main class="product-detail-container">
    <?php if ($notice !== ''): ?>
        <div class="task-alert task-alert-<?= h($noticeType) ?>">
            <?= h($notice) ?>
        </div>
    <?php endif; ?>

    <?php if (!$product): ?>
        <section class="empty-state product-not-found">
            <h1>Product Not Found</h1>
            <p>The piece you are looking for does not exist or has been removed.</p>
            <a class="product-card-btn" href="../task3/products.php">
                Back to Collection
            </a>
        </section>
    <?php else: ?>
        <section class="product-essentials">
            <div class="product-gallery gallery-spacing">
                <div class="main-image-wrapper">
                    <img
                        id="mainProductImage"
                        src="<?= h(asset_path($product['url'])) ?>"
                        alt="<?= h($product['product_name']) ?>"
                    />
                </div>

                <div class="thumbnail-grid">
                    <img class="thumb active" src="<?= h(asset_path($product['url'])) ?>" alt="Thumbnail" onclick="setMainImage(this)" />
                    <img class="thumb" src="<?= h(asset_path($product['url'])) ?>" alt="Thumbnail" onclick="setMainImage(this)" />
                    <img class="thumb" src="<?= h(asset_path($product['url'])) ?>" alt="Thumbnail" onclick="setMainImage(this)" />
                </div>
            </div>

            <div class="product-info-panel">
                <p class="breadcrumb">
                    Collections / <?= h($product['category_name']) ?> / #<?= (int)$product['product_id'] ?>
                </p>

                <h1 class="product-title"><?= h($product['product_name']) ?></h1>
                <p class="product-price"><?= money_vnd($product['price']) ?></p>

                <div class="detail-status-row">
                    <span class="stock-pill <?= (int)$product['stock_quantity'] > 0 ? 'in-stock' : 'out-stock' ?>">
                        <?= (int)$product['stock_quantity'] > 0 ? ' In Stock' : 'Out Of Stock' ?>
                    </span>
                    <span>In Stock: <?= (int)$product['stock_quantity'] ?></span>
                </div>

                <div class="quick-spec-list">
                    <p><strong>Category:</strong> <?= h($product['category_name']) ?></p>
                    <p><strong>Material:</strong> <?= h($product['material'] ?: 'Unknown') ?></p>
                    <p><strong>Color:</strong> <?= h($product['color'] ?: 'Unknown') ?></p>
                    <p><strong>Warranty:</strong> <?= h($product['warranty_period'] ?: 'Unknown') ?></p>
                </div>

                <form method="post" class="detail-cart-form">
                    <input type="hidden" name="action" value="add_to_cart" />
                    <input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>" />

                    <label class="qty-label">Quantity</label>
                    <div class="qty-selector">
                        <button type="button" onclick="changeQty(-1)">−</button>
                        <input id="quantityInput" type="number" name="quantity" value="1" min="1" max="<?= max(1, (int)$product['stock_quantity']) ?>" />
                        <button type="button" onclick="changeQty(1)">+</button>
                    </div>

                    <button class="product-card-btn full-width" type="submit" <?= (int)$product['stock_quantity'] <= 0 ? 'disabled' : '' ?>>
                        Add to Collection
                    </button>

                    <a class="product-card-btn secondary full-width" href="<?= isset($_SESSION['user_id']) ? '../task3/cart.php' : '../html/login.php' ?>">
                        View Cart
                    </a>
                </form>

                <div class="service-notes">
                    <p>✓ Handcrafted from sustainable materials</p>
                    <p>✓ Warranty varies by product</p>
                    <p>✓ Support available during business hours</p>
                </div>
            </div>
        </section>

        <section class="product-specs-section">
            <div class="specs-tabs">
                <button class="tab-link active" type="button" data-tab="story">Concept & Story</button>
                <button class="tab-link" type="button" data-tab="material">Dimensions & Material</button>
            </div>

            <div id="story" class="tab-pane active">
                <div class="editorial-grid">
                    <div>
                        <h2 class="editorial-heading">Pure Form</h2>
                        <p class="editorial-body">
                            The <?= h($product['product_name']) ?> is designed with a minimalist spirit, focusing on materiality, color, and seamless integration into modern living spaces.
                        </p>
                    </div>
                    <blockquote class="editorial-quote">
                        “Design is not just what it looks like; it's how the material breathes in your space.”
                    </blockquote>
                </div>
            </div>

            <div id="material" class="tab-pane">
                <div class="specs-table">
                    <div class="specs-row"><span class="label">Product ID</span><span class="value">#<?= (int)$product['product_id'] ?></span></div>
                    <div class="specs-row"><span class="label">Category</span><span class="value"><?= h($product['category_name']) ?></span></div>
                    <div class="specs-row"><span class="label">Primary Material</span><span class="value"><?= h($product['material'] ?: 'Not updated') ?></span></div>
                    <div class="specs-row"><span class="label">Color</span><span class="value"><?= h($product['color'] ?: 'Not updated') ?></span></div>
                    <div class="specs-row"><span class="label">Warranty</span><span class="value"><?= h($product['warranty_period'] ?: 'Not updated') ?></span></div>
                </div>
            </div>
        </section>

        <?php if (!empty($relatedProducts)): ?>
            <section class="related-products-section">
                <h2>Similar Pieces</h2>
                <div class="related-grid">
                    <?php foreach ($relatedProducts as $related): ?>
                        <a class="related-card" href="../task3/product-detail.php?id=<?= (int)$related['product_id'] ?>">
                            <img src="<?= h(asset_path($related['url'])) ?>" alt="<?= h($related['product_name']) ?>" />
                            <span><?= h($related['product_name']) ?></span>
                            <strong><?= money_vnd($related['price']) ?></strong>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>

<script>
function setMainImage(img) {
    const main = document.getElementById('mainProductImage');
    if (!main) return;
    main.src = img.src;
    document.querySelectorAll('.thumb').forEach(item => item.classList.remove('active'));
    img.classList.add('active');
}

function changeQty(delta) {
    const input = document.getElementById('quantityInput');
    if (!input) return;
    const min = Number(input.min || 1);
    const max = Number(input.max || 999);
    const next = Math.min(max, Math.max(min, Number(input.value || 1) + delta));
    input.value = next;
}

document.querySelectorAll('.tab-link').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.tab-link').forEach(item => item.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(item => item.classList.remove('active'));
        button.classList.add('active');
        const tab = document.getElementById(button.dataset.tab);
        if (tab) tab.classList.add('active');
    });
});
</script>

<?php include_site_footer(); ?>