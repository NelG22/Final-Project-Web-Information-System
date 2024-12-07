<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connectify - Contact Management System</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="notifications.css">
</head>
<body>
    <?php if(isset($_SESSION['success_message'])): ?>
        <div class="notification success">
            <i class="fas fa-check-circle"></i>
            <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error_message'])): ?>
        <div class="notification error">
            <i class="fas fa-exclamation-circle"></i>
            <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <header>
        <nav>
            <div class="logo">Connectify</div>
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <section id="home" class="hero">
            <div class="hero-content">
                <h1>Manage Your Contacts Smarter</h1>
                <p class="hero-subtitle">Your All-in-One Contact Management Solution</p>
                <p class="hero-description">
                    Keep your contacts organized, accessible, and up-to-date with Connectify's powerful features and intuitive interface.
                </p>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="cta-button">Go to Dashboard</a>
                <?php else: ?>
                    <div class="hero-buttons">
                        <button onclick="showLoginForm()" class="cta-button">Get Started</button>
                        <button onclick="showRegisterForm()" class="cta-button secondary">Sign Up Free</button>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section id="features" class="features">
            <div class="container">
                <h2>Powerful Features</h2>
                <p class="section-description">Everything you need to manage your contacts effectively</p>
                <div class="feature-grid">
                    <div class="feature-card">
                        <i class="fas fa-address-book"></i>
                        <h3>Smart Organization</h3>
                        <p>Keep all your contacts organized with easy categorization and instant search capabilities.</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-sync"></i>
                        <h3>Easy Updates</h3>
                        <p>Update contact information seamlessly and keep everything current with just a few clicks.</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-shield-alt"></i>
                        <h3>Secure Storage</h3>
                        <p>Your contacts are protected with industry-standard security measures and encryption.</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-mobile-alt"></i>
                        <h3>Mobile Friendly</h3>
                        <p>Access your contacts anywhere, anytime with our responsive mobile interface.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="how-it-works" class="how-it-works">
            <div class="container">
                <h2>How It Works</h2>
                <p class="section-description">Get started with Connectify in three simple steps</p>
                <div class="steps-container">
                    <div class="step">
                        <div class="step-number">1</div>
                        <h3>Create Account</h3>
                        <p>Sign up for free and set up your personal profile in minutes.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h3>Add Contacts</h3>
                        <p>Import or add your contacts with their details easily.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h3>Stay Connected</h3>
                        <p>Keep your network organized and stay in touch effortlessly.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <div class="container">
                <h2>Ready to Get Started?</h2>
                <p>Join thousands of users who trust Connectify for managing their contacts.</p>
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <div class="cta-buttons">
                        <button onclick="showRegisterForm()" class="cta-button">Create Free Account</button>
                        <button onclick="showLoginForm()" class="cta-button secondary">Sign In</button>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Login to Connectify</h2>
            <form action="login.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="cta-button">Login</button>
            </form>
            <p class="form-footer">
                Don't have an account? 
                <a href="#" onclick="showRegisterForm()">Sign up here</a>
            </p>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Create Account</h2>
            <form action="register.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="reg-email">Email</label>
                    <input type="email" id="reg-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="reg-password">Password</label>
                    <input type="password" id="reg-password" name="password" required>
                </div>
                <button type="submit" class="cta-button">Create Account</button>
            </form>
            <p class="form-footer">
                Already have an account? 
                <a href="#" onclick="showLoginForm()">Login here</a>
            </p>
        </div>
    </div>

    <script>
        // Mobile menu functionality
        function toggleMobileMenu() {
            const navLinks = document.querySelector('.nav-links');
            navLinks.classList.toggle('active');
        }

        // Modal functionality
        function showLoginForm() {
            document.getElementById('registerModal').style.display = 'none';
            document.getElementById('loginModal').style.display = 'block';
        }

        function showRegisterForm() {
            document.getElementById('loginModal').style.display = 'none';
            document.getElementById('registerModal').style.display = 'block';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Close modal when clicking close button
        document.querySelectorAll('.close').forEach(function(closeBtn) {
            closeBtn.onclick = function() {
                this.closest('.modal').style.display = 'none';
            }
        });
    </script>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>About Connectify</h4>
                    <p>Your trusted contact management solution for organizing and maintaining your professional network.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="privacy-policy.php">Privacy Policy</a></li>
                        <li><a href="terms-of-service.php">Terms of Service</a></li>
                        <li><a href="cookie-policy.php">Cookie Policy</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Connect With Us</h4>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Connectify. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
