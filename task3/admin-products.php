<?php
require_once __DIR__ . '/db.php';

$notice = '';
$noticeType = 'success';

$categories = db_all('SELECT category_id, category_name FROM category ORDER BY category_name ASC');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    $productId = (int)($_POST['product_id'] ?? 0);
    $name = trim($_POST['product_name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock_quantity'] ?? 0);
    $material = trim($_POST['material'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $warranty = trim($_POST['warranty_period'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $oldUrl = trim($_POST['old_url'] ?? '');

    if ($action === 'create' || $action === 'update') {
        if ($name === '' || strlen($name) > 150) {
            $notice = 'Tên sản phẩm không được rỗng và không vượt quá 150 ký tự.';
            $noticeType = 'error';
        } elseif ($price <= 0) {
            $notice = 'Giá sản phẩm phải lớn hơn 0.';
            $noticeType = 'error';
        } elseif ($stock < 0) {
            $notice = 'Tồn kho không được âm.';
            $noticeType = 'error';
        } elseif ($categoryId <= 0) {
            $notice = 'Vui lòng chọn danh mục.';
            $noticeType = 'error';
        } else {
            $uploadError = '';
            $imagePath = save_product_upload('product_image', $oldUrl, $uploadError);

            if ($imagePath === false) {
                $notice = $uploadError;
                $noticeType = 'error';
            } else {
                if ($imagePath === '') {
                    $imagePath = 'public/uploads/products/no-image.png';
                }

                if ($action === 'create') {
                    $ok = db_execute(
                        'INSERT INTO product
                            (product_name, price, stock_quantity, material, color, warranty_period, category_id, url)
                         VALUES
                            (?, ?, ?, ?, ?, ?, ?, ?)',
                        'sdisssis',
                        array($name, $price, $stock, $material, $color, $warranty, $categoryId, $imagePath)
                    );

                    $notice = $ok ? 'Đã thêm sản phẩm mới.' : 'Không thể thêm sản phẩm.';
                    $noticeType = $ok ? 'success' : 'error';
                } else {
                    $ok = db_execute(
                        'UPDATE product
                         SET product_name = ?,
                             price = ?,
                             stock_quantity = ?,
                             material = ?,
                             color = ?,
                             warranty_period = ?,
                             category_id = ?,
                             url = ?
                         WHERE product_id = ?',
                        'sdisssisi',
                        array($name, $price, $stock, $material, $color, $warranty, $categoryId, $imagePath, $productId)
                    );

                    $notice = $ok ? 'Đã cập nhật sản phẩm.' : 'Không thể cập nhật sản phẩm.';
                    $noticeType = $ok ? 'success' : 'error';
                }
            }
        }
    } elseif ($action === 'delete') {
        $ok = db_execute(
            'DELETE FROM product WHERE product_id = ?',
            'i',
            array($productId)
        );

        $notice = $ok ? 'Đã xóa sản phẩm.' : 'Không thể xóa vì sản phẩm có thể đang nằm trong giỏ hàng hoặc đơn hàng.';
        $noticeType = $ok ? 'success' : 'error';
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editProduct = null;

if ($editId > 0) {
    $editProduct = db_one(
        'SELECT * FROM product WHERE product_id = ? LIMIT 1',
        'i',
        array($editId)
    );
}

$q = trim($_GET['q'] ?? '');
$categoryId = (int)($_GET['category_id'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;

$where = array();
$params = array();
$types = '';

if ($q !== '') {
    $where[] = '(p.product_name LIKE ? OR p.material LIKE ? OR p.color LIKE ?)';
    $like = '%' . $q . '%';

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($categoryId > 0) {
    $where[] = 'p.category_id = ?';
    $params[] = $categoryId;
    $types .= 'i';
}

$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$countRow = db_one(
    "SELECT COUNT(*) AS total
     FROM product p
     JOIN category c ON p.category_id = c.category_id
     $whereSql",
    $types,
    $params
);

$totalRows = $countRow ? (int)$countRow['total'] : 0;

$listParams = $params;
$listTypes = $types . 'ii';
$listParams[] = $perPage;
$listParams[] = $offset;

$products = db_all(
    "SELECT p.*, c.category_name
     FROM product p
     JOIN category c ON p.category_id = c.category_id
     $whereSql
     ORDER BY p.product_id DESC
     LIMIT ? OFFSET ?",
    $listTypes,
    $listParams
);

render_srtdash_admin_start('Quản lý sản phẩm', 'products');
?>

<?php if ($notice !== ''): ?>
    <div class="alert alert-<?php echo $noticeType === 'success' ? 'success' : 'danger'; ?> mt-4">
        <?php echo h($notice); ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-5 mt-5">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">
                    <?php echo $editProduct ? 'Sửa sản phẩm' : 'Thêm sản phẩm'; ?>
                </h4>

                <form method="post" enctype="multipart/form-data" id="productForm">
                    <input type="hidden" name="action" value="<?php echo $editProduct ? 'update' : 'create'; ?>">
                    <input type="hidden" name="product_id" value="<?php echo $editProduct ? (int)$editProduct['product_id'] : 0; ?>">
                    <input type="hidden" name="old_url" value="<?php echo h($editProduct ? $editProduct['url'] : ''); ?>">

                    <div class="form-group">
                        <label for="product_name">Tên sản phẩm</label>
                        <input
                            id="product_name"
                            class="form-control"
                            type="text"
                            name="product_name"
                            maxlength="150"
                            required
                            value="<?php echo h($editProduct ? $editProduct['product_name'] : ''); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="price">Giá</label>
                        <input
                            id="price"
                            class="form-control"
                            type="number"
                            name="price"
                            min="1"
                            step="1000"
                            required
                            value="<?php echo h($editProduct ? $editProduct['price'] : ''); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="stock_quantity">Tồn kho</label>
                        <input
                            id="stock_quantity"
                            class="form-control"
                            type="number"
                            name="stock_quantity"
                            min="0"
                            required
                            value="<?php echo h($editProduct ? $editProduct['stock_quantity'] : 0); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="material">Vật liệu</label>
                        <input
                            id="material"
                            class="form-control"
                            type="text"
                            name="material"
                            value="<?php echo h($editProduct ? $editProduct['material'] : ''); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="color">Màu sắc</label>
                        <input
                            id="color"
                            class="form-control"
                            type="text"
                            name="color"
                            value="<?php echo h($editProduct ? $editProduct['color'] : ''); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="warranty_period">Bảo hành</label>
                        <input
                            id="warranty_period"
                            class="form-control"
                            type="text"
                            name="warranty_period"
                            value="<?php echo h($editProduct ? $editProduct['warranty_period'] : ''); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="category_id">Danh mục</label>
                        <select id="category_id" class="form-control" name="category_id" required>
                            <option value="">-- Chọn danh mục --</option>

                            <?php foreach ($categories as $category): ?>
                                <?php $selectedId = $editProduct ? (int)$editProduct['category_id'] : 0; ?>

                                <option
                                    value="<?php echo (int)$category['category_id']; ?>"
                                    <?php echo $selectedId === (int)$category['category_id'] ? 'selected' : ''; ?>
                                >
                                    <?php echo h($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="product_image">Ảnh sản phẩm</label>
                        <input
                            id="product_image"
                            class="form-control"
                            type="file"
                            name="product_image"
                            accept="image/*"
                        >
                        <small class="form-text text-muted">
                            Chỉ upload ảnh local JPG, JPEG, PNG, WEBP hoặc GIF. Tối đa 3MB.
                        </small>
                    </div>

                    <?php if ($editProduct && $editProduct['url'] !== ''): ?>
                        <div class="form-group">
                            <img
                                class="admin-preview-image"
                                src="<?php echo h(safe_product_image($editProduct['url'])); ?>"
                                alt="Ảnh hiện tại"
                            >
                        </div>
                    <?php endif; ?>

                    <button class="btn btn-primary mt-3" type="submit">
                        <?php echo $editProduct ? 'Lưu thay đổi' : 'Thêm sản phẩm'; ?>
                    </button>

                    <?php if ($editProduct): ?>
                        <a class="btn btn-secondary mt-3" href="../task3/admin-products.php">
                            Hủy sửa
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7 mt-5">
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Danh sách sản phẩm</h4>

                <form method="get" class="admin-search-form mb-4">
                    <input
                        class="form-control"
                        type="text"
                        name="q"
                        value="<?php echo h($q); ?>"
                        placeholder="Tìm tên, vật liệu, màu sắc..."
                    >

                    <select class="form-control" name="category_id">
                        <option value="0">Tất cả danh mục</option>

                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?php echo (int)$category['category_id']; ?>"
                                <?php echo $categoryId === (int)$category['category_id'] ? 'selected' : ''; ?>
                            >
                                <?php echo h($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button class="btn btn-primary" type="submit">Tìm kiếm</button>
                    <a class="btn btn-secondary" href="../task3/admin-products.php">Reset</a>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover progress-table text-center">
                        <thead class="text-uppercase">
                            <tr>
                                <th>ID</th>
                                <th>Ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Giá</th>
                                <th>Tồn kho</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>#<?php echo (int)$product['product_id']; ?></td>

                                    <td>
                                        <img
                                            class="admin-table-image"
                                            src="<?php echo h(safe_product_image($product['url'])); ?>"
                                            alt="<?php echo h($product['product_name']); ?>"
                                        >
                                    </td>

                                    <td class="text-left">
                                        <strong><?php echo h($product['product_name']); ?></strong>
                                        <br>
                                        <small>
                                            <?php echo h($product['material']); ?>
                                            ·
                                            <?php echo h($product['color']); ?>
                                        </small>
                                    </td>

                                    <td><?php echo h($product['category_name']); ?></td>
                                    <td><?php echo money_vnd($product['price']); ?></td>
                                    <td><?php echo (int)$product['stock_quantity']; ?></td>

                                    <td>
                                        <a
                                            class="btn btn-sm btn-info"
                                            href="../task3/admin-products.php?edit=<?php echo (int)$product['product_id']; ?>"
                                        >
                                            Sửa
                                        </a>

                                        <form
                                            method="post"
                                            class="d-inline"
                                            onsubmit="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?')"
                                        >
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="product_id" value="<?php echo (int)$product['product_id']; ?>">

                                            <button class="btn btn-sm btn-danger" type="submit">
                                                Xóa
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="7">Không có sản phẩm phù hợp.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php echo render_pagination($totalRows, $page, $perPage, 'page'); ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('productForm').addEventListener('submit', function (event) {
    const name = document.getElementById('product_name').value.trim();
    const price = Number(document.getElementById('price').value);
    const stock = Number(document.getElementById('stock_quantity').value);
    const category = document.getElementById('category_id').value;
    const image = document.getElementById('product_image');

    if (name === '' || name.length > 150) {
        alert('Tên sản phẩm không được rỗng và không vượt quá 150 ký tự.');
        event.preventDefault();
        return;
    }

    if (!price || price <= 0) {
        alert('Giá sản phẩm phải lớn hơn 0.');
        event.preventDefault();
        return;
    }

    if (stock < 0) {
        alert('Tồn kho không được âm.');
        event.preventDefault();
        return;
    }

    if (!category) {
        alert('Vui lòng chọn danh mục.');
        event.preventDefault();
        return;
    }

    if (image.files.length > 0 && image.files[0].size > 3 * 1024 * 1024) {
        alert('Ảnh không được vượt quá 3MB.');
        event.preventDefault();
    }
});
</script>

<?php render_srtdash_admin_end(); ?>