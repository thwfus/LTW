<?php 
include '../php/header.php'; 
require_once '../task1/config_m1.php'; // Kết nối thông qua config_m1

try {
    // Thêm product_id vào câu truy vấn để làm link chi tiết
    $stmt = $pdo->query("SELECT product_id, product_name, price, stock_quantity, warranty_period FROM product");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage());
}
?>
<link rel="stylesheet" href="../task1/prices.css" />

<main class="prices-page-container">
    <header class="prices-header animate__animated animate__fadeIn">
        <h1 class="hero-title">Price Guide</h1>
        <p class="section-content">Reference pricing for our handcrafted collection. Prices include standard finishes.</p>
    </header>

    <section class="price-list-section">
        <div class="price-table-wrapper">
            <table class="price-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Warranty</th>
                        <th>Reference Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $item): 
                        // Logic xác định trạng thái tồn kho
                        $qty = (int)$item['stock_quantity'];
                        if ($qty == 0) {
                            $statusText = "Out of Stock";
                            $statusClass = "out-of-stock";
                        } elseif ($qty < 6) {
                            $statusText = "Limited";
                            $statusClass = "limited";
                        } else {
                            $statusText = "In Stock";
                            $statusClass = "in-stock";
                        }
                    ?>
                    <tr>
                        <td class="product-name-cell">
                            <a href="../task3/product-detail.php?id=<?= $item['product_id'] ?>" style="text-decoration: none; color: inherit;">
                                <?= htmlspecialchars($item['product_name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($item['warranty_period']) ?></td>
                        <td class="price-cell">
                            <?= number_format($item['price'], 0, ',', '.') ?> VNĐ
                        </td>
                        <td>
                            <span class="status-badge <?= $statusClass ?>">
                                <?= $statusText ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="price-disclaimer">
            <p>* Tất cả giá đã bao gồm VAT. Nhấn vào tên sản phẩm để xem thông tin chi tiết và các tùy chọn tùy chỉnh.</p>
        </div>
    </section>
</main>

<?php include '../php/footer.php'; ?>