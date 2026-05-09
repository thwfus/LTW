<?php 
include '../php/header.php'; 
$isAdmin = false; // Giả lập Admin đang đăng nhập
?>
<link rel="stylesheet" href="../task2/about.css" />

<main class="about-page-container">
    <?php if ($isAdmin): ?>
        <div class="admin-controls">
            <button id="saveChanges" class="btn btn-primary btn-sm">Save All Changes</button>
            <span id="statusMsg"></span>
        </div>
    <?php endif; ?>

    <section class="about-hero">
        <div class="about-hero-content">
            <h1 class="hero-title <?= $isAdmin ? 'editable' : '' ?>" 
                contenteditable="<?= $isAdmin ? 'true' : 'false' ?>" 
                data-id="about_hero_title">The Soul Behind the Wood</h1>
            <p class="section-subtitle <?= $isAdmin ? 'editable' : '' ?>" 
               contenteditable="<?= $isAdmin ? 'true' : 'false' ?>" 
               data-id="about_hero_sub">Crafting a legacy of silence, strength, and artisanal soul.</p>
        </div>
    </section>

    <section class="about-split-section">
        <div class="split-image">
            <img id="heritageImg" src="../uploads/homepage/5.jpeg" alt="Workshop">
            <?php if ($isAdmin): ?>
                <input type="file" id="uploadHeritage" hidden accept="image/*">
                <button class="edit-img-btn" onclick="document.getElementById('uploadHeritage').click()">Change Image</button>
            <?php endif; ?>
        </div>
        <div class="split-text">
            <h2 class="section-title <?= $isAdmin ? 'editable' : '' ?>" 
                contenteditable="<?= $isAdmin ? 'true' : 'false' ?>" 
                data-id="about_heritage_title">Our Heritage</h2>
            <p class="section-content <?= $isAdmin ? 'editable' : '' ?>" 
               contenteditable="<?= $isAdmin ? 'true' : 'false' ?>" 
               data-id="about_heritage_body">
                At Olivewood Atelier, we believe that furniture is more than just utility—it is an anchor for the home.
            </p>
        </div>
    </section>
</main>

<script src="../task2/about-admin.js"></script>
<?php include '../php/footer.php'; ?>