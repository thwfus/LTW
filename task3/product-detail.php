<?php include '../php/header.php'; ?>
<link rel="stylesheet" href="../task3/product.css" />

<main class="product-detail-container">
    <section class="product-essentials">
        <div class="product-gallery">
            <div class="main-image-wrapper gallery-spacing">
                <img id="mainImage" src="https://images.pexels.com/photos/3773579/pexels-photo-3773579.png?auto=compress&cs=tinysrgb&w=1500" alt="Minimalist Oak Table">
            </div>
            <div class="thumbnail-grid">
                <img class="thumb active" src="https://images.pexels.com/photos/3773579/pexels-photo-3773579.png?auto=compress&cs=tinysrgb&w=300" onclick="changeImage(this.src, this)">
                <img class="thumb" src="https://images.pexels.com/photos/447592/pexels-photo-447592.jpeg?auto=compress&cs=tinysrgb&w=300" onclick="changeImage(this.src, this)">
                <img class="thumb" src="https://images.pexels.com/photos/20337842/pexels-photo-20337842.jpeg?auto=compress&cs=tinysrgb&w=300" onclick="changeImage(this.src, this)">
            </div>
        </div>

        <div class="product-info-box">
            <nav class="breadcrumb">Collections / Dining / Tables</nav>
            <h1 class="product-title section-title">Minimalist Oak Table</h1>
            <p class="product-price">$2,400.00</p>
            
            <div class="product-selection">
                <div class="select-group">
                    <label class="section-content">Wood Finish: <span>Natural Oak</span></label>
                    <div class="swatch-picker">
                        <button class="swatch active" style="background-color: #d2b48c;" title="Natural Oak"></button>
                        <button class="swatch" style="background-color: #4a3728;" title="Smoked Oak"></button>
                    </div>
                </div>

                <div class="select-group">
                    <label class="section-content">Quantity</label>
                    <div class="qty-selector">
                        <button onclick="updateQty(-1)">−</button>
                        <input type="number" id="quantity" value="1" min="1">
                        <button onclick="updateQty(1)">+</button>
                    </div>
                </div>
            </div>

            <div class="product-actions">
                <button class="btn btn-primary btn-lg full-width">Add to Collection</button>
                <button class="btn btn-outline btn-lg full-width">Custom Inquiry</button>
            </div>

            <div class="product-meta-highlights">
                <p class="section-content">✓ Handcrafted from sustainable European Oak</p>
                <p class="section-content">✓ Lifetime structural warranty</p>
            </div>
        </div>
    </section>

    <section class="product-editorial-section">
        <div class="specs-tabs">
            <button class="tab-link active" onclick="openTab(event, 'Story')">Concept & Story</button>
            <button class="tab-link" onclick="openTab(event, 'Specs')">Dimensions & Material</button>
        </div>

        <div class="tab-content-container">
            <div id="Story" class="tab-pane active">
                <div class="editorial-grid">
                    <div class="editorial-text">
                        <h2 class="editorial-heading">Pure Form</h2>
                        <p class="editorial-body">
                            The Minimalist Oak Table is a celebration of restraint and material honesty. 
                            Using traditional mortise and tenon joinery, each piece is assembled with surgical precision to ensure a lifetime of stability.
                        </p>
                    </div>
                    <div class="editorial-quote">
                        <p>"Design is not just what it looks like; it's how the material breathes in your space."</p>
                    </div>
                </div>
            </div>

            <div id="Specs" class="tab-pane">
                <div class="specs-table">
                    <div class="specs-row">
                        <span class="label">Overall Length</span>
                        <span class="value">2200 mm / 86.6"</span>
                    </div>
                    <div class="specs-row">
                        <span class="label">Overall Width</span>
                        <span class="value">1000 mm / 39.4"</span>
                    </div>
                    <div class="specs-row">
                        <span class="label">Primary Material</span>
                        <span class="value">Solid European Oak</span>
                    </div>
                    <div class="specs-row">
                        <span class="label">Protective Finish</span>
                        <span class="value">Matte Natural Oil</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
    function changeImage(src, el) {
        const mainImg = document.getElementById('mainImage');
        mainImg.style.opacity = '0'; // Smooth transition
        setTimeout(() => {
            mainImg.src = src;
            mainImg.style.opacity = '1';
        }, 200);
        
        document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
        el.classList.add('active');
    }

    function updateQty(val) {
        let input = document.getElementById('quantity');
        let current = parseInt(input.value);
        if (current + val >= 1) input.value = current + val;
    }

    function openTab(evt, tabName) {
        document.querySelectorAll('.tab-pane').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
        document.getElementById(tabName).classList.add('active');
        evt.currentTarget.classList.add('active');
    }
</script>

<?php include '../php/footer.php'; ?>