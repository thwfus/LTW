<?php
include '../php/header.php';

/*
    Kết nối DB XAMPP.
    Nếu project bạn đã có file connect riêng thì có thể thay đoạn này bằng include file đó.
*/
$conn = new mysqli("localhost", "root", "", "btl_ltw");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

/*
    Lấy user hiện tại.
    Nếu hệ thống login của bạn đã lưu session user_id thì dùng session.
    Nếu chưa làm login hoàn chỉnh, tạm dùng customer id = 11 để test.
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUserId = $_SESSION['user_id'] ?? 11;

$errors = [];
$successMessage = "";

/*
    Lấy danh sách product để hiện khi chọn Product Review
*/
$products = [];

$productSql = "
    SELECT product_id, product_name
    FROM product
    ORDER BY product_name ASC
";

$productResult = $conn->query($productSql);

if ($productResult) {
    while ($row = $productResult->fetch_assoc()) {
        $products[] = $row;
    }
}

/*
    Xử lý submit form
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $reviewType = trim($_POST["review_type"] ?? "");
    $content = trim($_POST["content"] ?? "");
    $productId = $_POST["product_id"] ?? null;

    if ($title === "") {
        $errors[] = "Article title is required.";
    }

    if ($reviewType === "") {
        $errors[] = "Review type is required.";
    }

    if ($content === "") {
        $errors[] = "Article content is required.";
    }

    $allowedReviewTypes = [
        "product",
        "website",
        "service",
        "customer_experience",
        "delivery",
        "buying_guide"
    ];

    if ($reviewType !== "" && !in_array($reviewType, $allowedReviewTypes)) {
        $errors[] = "Invalid review type.";
    }

    if ($reviewType === "product") {
        if (empty($productId)) {
            $errors[] = "Please select a product for Product Review.";
        }
    } else {
        $productId = null;
    }

    /*
        Upload ảnh.
        DB chỉ lưu đường dẫn ảnh, không lưu file ảnh trực tiếp.
    */
    $imagePath = "uploads/reviews/default.jpg";

    if (isset($_FILES["image"]) && $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
            $errors[] = "Image upload failed.";
        } else {
            $uploadDir = "../uploads/reviews/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileTmpPath = $_FILES["image"]["tmp_name"];
            $fileName = $_FILES["image"]["name"];
            $fileSize = $_FILES["image"]["size"];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExts = ["jpg", "jpeg", "png", "webp"];

            if (!in_array($fileExt, $allowedExts)) {
                $errors[] = "Only JPG, JPEG, PNG, and WEBP images are allowed.";
            }

            if ($fileSize > 5 * 1024 * 1024) {
                $errors[] = "Image size must be less than 5MB.";
            }

            if (empty($errors)) {
                $newFileName = "review_" . time() . "_" . uniqid() . "." . $fileExt;
                $destination = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destination)) {
                    /*
                        Đường dẫn lưu trong DB.
                        Tùy cấu trúc project, bạn có thể dùng:
                        uploads/reviews/filename.jpg
                    */
                    $imagePath = "uploads/reviews/" . $newFileName;
                } else {
                    $errors[] = "Could not save uploaded image.";
                }
            }
        }
    }

    /*
        Insert vào DB nếu không có lỗi
    */
    if (empty($errors)) {
        $status = "pending";
        $adminNote = null;
        $adminId = null;

        $insertSql = "
            INSERT INTO review_post
            (
                title,
                content,
                image,
                status,
                review_type,
                admin_note,
                created_at,
                user_id,
                product_id,
                admin_id
            )
            VALUES
            (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)
        ";

        $stmt = $conn->prepare($insertSql);

        if (!$stmt) {
            $errors[] = "Prepare failed: " . $conn->error;
        } else {
            /*
                product_id và admin_id có thể NULL.
            */
            if ($productId === null || $productId === "") {
                $productId = null;
            } else {
                $productId = (int)$productId;
            }

            $stmt->bind_param(
                "ssssssiii",
                $title,
                $content,
                $imagePath,
                $status,
                $reviewType,
                $adminNote,
                $currentUserId,
                $productId,
                $adminId
            );

            if ($stmt->execute()) {
                $successMessage = "Your article has been submitted successfully and is waiting for admin approval.";

                /*
                    Reset form after successful submit
                */
                $title = "";
                $reviewType = "";
                $content = "";
                $productId = null;
            } else {
                $errors[] = "Insert failed: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}
?>

<link rel="stylesheet" href="../task4/news_create.css" />

<main class="news-page-container">
    <header class="news-header animate__animated animate__fadeIn">
        <h1 class="hero-title">Create New Article</h1>
        <p class="section-content">
            Write a new product review, website feedback, or customer story.
        </p>
    </header>

    <section class="article-form-wrapper">
        <?php if (!empty($successMessage)): ?>
            <div class="form-notice success">
                <h2>Article Submitted</h2>
                <p><?php echo htmlspecialchars($successMessage); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="form-notice error">
                <h2>Please Check Your Input</h2>
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="article-form">
            <div class="form-group">
                <label for="title">Article Title</label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    placeholder="Enter article title..."
                    value="<?php echo htmlspecialchars($title ?? ""); ?>"
                    required
                >
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="review_type">Review Type</label>
                    <select id="review_type" name="review_type" required>
                        <option value="">Select review type</option>
                        <option value="product" <?php echo (($reviewType ?? "") === "product") ? "selected" : ""; ?>>
                            Product Review
                        </option>
                        <option value="website" <?php echo (($reviewType ?? "") === "website") ? "selected" : ""; ?>>
                            Website Review
                        </option>
                        <option value="service" <?php echo (($reviewType ?? "") === "service") ? "selected" : ""; ?>>
                            Service Review
                        </option>
                        <option value="customer_experience" <?php echo (($reviewType ?? "") === "customer_experience") ? "selected" : ""; ?>>
                            Customer Experience
                        </option>
                        <option value="delivery" <?php echo (($reviewType ?? "") === "delivery") ? "selected" : ""; ?>>
                            Delivery Review
                        </option>
                        <option value="buying_guide" <?php echo (($reviewType ?? "") === "buying_guide") ? "selected" : ""; ?>>
                            Buying Guide
                        </option>
                    </select>
                </div>

                <div class="form-group product-select-group" id="productSelectGroup">
                    <label for="product_id">Related Product</label>
                    <select id="product_id" name="product_id">
                        <option value="">Select product</option>

                        <?php foreach ($products as $product): ?>
                            <option 
                                value="<?php echo (int)$product['product_id']; ?>"
                                <?php echo ((string)($productId ?? "") === (string)$product['product_id']) ? "selected" : ""; ?>
                            >
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="image">Cover Image</label>
                <input 
                    type="file" 
                    id="image" 
                    name="image" 
                    accept="image/jpeg,image/png,image/webp"
                >
                <small class="form-help">
                    Accepted formats: JPG, PNG, WEBP. Maximum size: 5MB.
                </small>
            </div>

            <div class="form-group">
                <label for="content">Article Content</label>
                <textarea 
                    id="content" 
                    name="content" 
                    rows="10" 
                    placeholder="Write the full article content here..."
                    required
                ><?php echo htmlspecialchars($content ?? ""); ?></textarea>
            </div>

            <div class="form-actions">
                <a href="../task4/news_all.php" class="cancel-article-btn">
                    Cancel
                </a>

                <button type="submit" class="submit-article-btn">
                    Publish Article
                </button>
            </div>
        </form>
    </section>
</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const reviewTypeSelect = document.getElementById("review_type");
    const productSelectGroup = document.getElementById("productSelectGroup");
    const productSelect = document.getElementById("product_id");

    function toggleProductSelect() {
        if (reviewTypeSelect.value === "product") {
            productSelectGroup.style.display = "flex";
            productSelect.setAttribute("required", "required");
        } else {
            productSelectGroup.style.display = "none";
            productSelect.removeAttribute("required");
            productSelect.value = "";
        }
    }

    reviewTypeSelect.addEventListener("change", toggleProductSelect);
    toggleProductSelect();
});
</script>

<?php include '../php/footer.php'; ?>