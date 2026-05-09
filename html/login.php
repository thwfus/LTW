<!DOCTYPE html>
<html lang="en">
<head>
    <title>Log In - Olivewood Atelier</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta charset="utf-8" />
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/login.css" />
    <link rel="stylesheet" href="https://unpkg.com/animate.css@4.1.1/animate.css" />
</head>
<body>
    <div class="login-layout">
        <div class="login-media animate__animated animate__fadeIn">
            <img src="https://images.pexels.com/photos/27164972/pexels-photo-27164972.jpeg?auto=compress&cs=tinysrgb&w=1500" alt="Atelier Interior">
            <div class="login-media-overlay"></div>
            <div class="login-quote">
                <h2 class="section-title">Sculpting Space</h2>
                <p class="section-content">Welcome back to your curated luxury collection.</p>
            </div>
        </div>

        <div class="login-form-side">
            <div class="login-form-container animate__animated animate__fadeInRight">
                <header class="login-header">
                    <a href="../task1/index.php" class="login-logo-link">
                        <span class="section-title">Olivewood Atelier</span>
                    </a>
                    <h1 class="section-subtitle">Log In</h1>
                    <p class="section-content">Enter your details to access your atelier account.</p>
                </header>
                 
                <?php if (isset($_GET['error'])): ?>
                    <div style="background: rgba(255, 77, 77, 0.1); color: #ff4d4d; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 0.9rem; text-align: center;">
                        <?php 
                            if ($_GET['error'] == 'wrong_password') echo "Mật khẩu không chính xác. Vui lòng thử lại.";
                            if ($_GET['error'] == 'user_not_found') echo "Email này chưa được đăng ký thành viên.";
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['status']) && $_GET['status'] == 'registered'): ?>
                    <div style="background: rgba(90, 122, 46, 0.1); color: #5a7a2e; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 0.9rem; text-align: center;">
                        Đăng ký thành công! Vui lòng đăng nhập.
                    </div>
                <?php endif; ?>
                
                <form class="login-form" action="login_register.php" method="post">
                    <div class="input-group">
                        <label class="section-content">Email Address</label>
                        <input type="email" name="email" placeholder="mail@example.com" class="login-input" required />
                    </div>
                    
                    <div class="input-group">
                        <div class="label-row">
                            <label class="section-content">Password</label>
                            <a href="#" class="footer-link">Forgot?</a>
                        </div>
                        <input type="password" name="password" placeholder="••••••••" class="login-input" required />
                    </div>

                    <button type="submit" name="login" class="btn btn-primary btn-lg login-submit">
                        Log In
                    </button>
                </form>

                <footer class="login-footer">
                    <p class="section-content">Don't have an account? 
                        <a href="../html/register.php" class="join-link">Register now</a>
                    </p>
                </footer>
            </div>
        </div>
    </div>
</body>
</html>