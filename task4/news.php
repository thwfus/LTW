<?php 
include '../php/header.php'; 
$isAdmin = false; 
?>
<link rel="stylesheet" href="../task4/news.css" />

<main class="news-page-container">
    <header class="news-header animate__animated animate__fadeIn">
        <h1 class="hero-title">Atelier Journal</h1>
        <p class="section-content">Stories of craftsmanship and studio updates.</p>
        
        <?php if ($isAdmin): ?>
            <button id="openModal" class="btn btn-primary btn-sm admin-create-btn">+ Create New Entry</button>
        <?php endif; ?>
    </header>

    <?php if ($isAdmin): ?>
        <div id="postModal" class="admin-modal">
            <div class="modal-content animate__animated animate__zoomIn">
                <div class="modal-header">
                    <h3 class="section-subtitle">New Journal Entry</h3>
                    <span class="close-modal">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="info-label">Title</label>
                        <input type="text" id="newPostTitle" class="form-input" placeholder="Enter title...">
                    </div>
                    <div class="form-group">
                        <label class="info-label">Cover Image</label>
                        <input type="file" id="newPostImg" class="form-input" accept="image/*">
                        <div id="imagePreviewContainer" style="margin-top: 10px; display: none;">
                            <img id="imagePreview" src="#" alt="Preview" style="width: 100%; border-radius: 8px;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="info-label">Excerpt Summary</label>
                        <textarea id="newPostExcerpt" class="form-input" rows="4" placeholder="Brief description..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="submitPost" class="btn btn-primary full-width">Publish to Journal</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <section class="news-grid" id="newsGrid">
        <article class="news-card">
            <?php if ($isAdmin): ?>
                <button class="delete-post-btn" onclick="deletePost(this)" title="Delete Post">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            <?php endif; ?>
            <div class="news-card-media">
                <img src="https://images.pexels.com/photos/20337842/pexels-photo-20337842.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Journal Image">
            </div>
            <div class="news-card-content">
                <span class="news-date">March 25, 2026</span>
                <h2 class="news-title">The Art of Joinery</h2>
                <p class="section-content">Exploring the traditional techniques that anchor our latest collection.</p>
                <a href="#" class="btn btn-link">Read Full Story</a>
            </div>
        </article>
    </section>
</main>

<script src="../task2/news-admin.js"></script>
<?php include '../php/footer.php'; ?>