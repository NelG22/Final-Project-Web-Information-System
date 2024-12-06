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
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contacts">Contacts</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <section class="hero">
            <h1>Welcome to Connectify</h1>
            <p>Your Smart Contact Management Solution</p>
            <?php if(isset($_SESSION['user_id'])): ?>
                <p class="welcome-msg">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
                <a href="dashboard.php" class="cta-button">Go to Dashboard</a>
            <?php else: ?>
                <div class="auth-buttons">
                    <button onclick="showLoginForm()" class="cta-button">Login</button>
                    <button onclick="showRegisterForm()" class="cta-button secondary">Register</button>
                </div>
            <?php endif; ?>
        </section>

        <section class="features">
            <h2>Key Features</h2>
            <div class="feature-grid">
                <div class="feature-card">
                    <i class="fas fa-user-plus"></i>
                    <h3>Add Contacts</h3>
                    <p>Easily add and organize your contacts</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-search"></i>
                    <h3>Quick Search</h3>
                    <p>Find contacts instantly with smart search</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-edit"></i>
                    <h3>Easy Edit</h3>
                    <p>Update contact information seamlessly</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Secure Storage</h3>
                    <p>Your contacts are safe with us</p>
                </div>
            </div>
        </section>

        <!-- Login Modal -->
        <div id="loginModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Login</h2>
                <form action="login.php" method="POST">
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="cta-button">Login</button>
                </form>
            </div>
        </div>

        <!-- Register Modal -->
        <div id="registerModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Register</h2>
                <form action="register.php" method="POST">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-email">Email:</label>
                        <input type="email" id="reg-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="reg-password">Password:</label>
                        <input type="password" id="reg-password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirm Password:</label>
                        <input type="password" id="confirm-password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="cta-button">Register</button>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4>About Connectify</h4>
                <p>Your modern solution for contact management</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="#privacy">Privacy Policy</a></li>
                    <li><a href="#terms">Terms of Service</a></li>
                    <li><a href="#contact">Contact Us</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Connectify. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Modal functionality
        const loginModal = document.getElementById('loginModal');
        const registerModal = document.getElementById('registerModal');
        const spans = document.getElementsByClassName('close');

        function showLoginForm() {
            loginModal.style.display = 'block';
        }

        function showRegisterForm() {
            registerModal.style.display = 'block';
        }

        // Close button functionality
        for (let span of spans) {
            span.onclick = function() {
                loginModal.style.display = 'none';
                registerModal.style.display = 'none';
            }
        }

        // Click outside modal to close
        window.onclick = function(event) {
            if (event.target == loginModal) {
                loginModal.style.display = 'none';
            }
            if (event.target == registerModal) {
                registerModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
