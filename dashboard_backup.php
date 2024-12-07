<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("location: index.php");
    exit();
}

require_once "config.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Connectify</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="notifications.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="#" onclick="showAddContactModal()">Add Contact</a></li>
                <li><a href="#" onclick="showProfileModal()">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search contacts...">
                <i class="fas fa-search"></i>
            </div>
        </div>

        <div class="contacts-container">
            <?php
            $user_id = $_SESSION["user_id"];
            $sql = "SELECT * FROM contacts WHERE user_id = ? ORDER BY name ASC";
            
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if (mysqli_num_rows($result) > 0) {
                        while ($contact = mysqli_fetch_assoc($result)) {
                            ?>
                            <div class="contact-card" data-contact-id="<?php echo $contact['id']; ?>">
                                <div class="contact-avatar">
                                    <?php if (isset($contact['avatar']) && $contact['avatar']): ?>
                                        <img src="<?php echo htmlspecialchars($contact['avatar']); ?>" alt="<?php echo htmlspecialchars($contact['name']); ?>">
                                    <?php else: ?>
                                        <div class="default-avatar"><?php echo strtoupper(substr($contact['name'], 0, 1)); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="contact-info">
                                    <h3><?php echo htmlspecialchars($contact['name']); ?></h3>
                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($contact['phone']); ?></p>
                                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contact['email']); ?></p>
                                </div>
                                <div class="contact-actions">
                                    <button onclick="editContact(<?php echo $contact['id']; ?>)" class="edit-btn">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteContact(<?php echo $contact['id']; ?>)" class="delete-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="no-contacts">No contacts found. Add your first contact!</p>';
                    }
                }
                mysqli_stmt_close($stmt);
            }
            ?>
        </div>
    </main>

    <!-- Add Contact Modal -->
    <div id="addContactModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddContactModal()">&times;</span>
            <h2>Add New Contact</h2>
            <form id="addContactForm" onsubmit="return handleAddContact(event)">
                <div class="avatar-container">
                    <div class="default-avatar" id="newContactAvatar"></div>
                    <button type="button" onclick="document.getElementById('newContactAvatarInput').click()" class="change-avatar-btn">
                        <i class="fas fa-camera"></i>
                    </button>
                    <input type="file" id="newContactAvatarInput" accept="image/*" style="display: none" onchange="previewContactAvatar(this.files[0])">
                </div>
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required oninput="updateNewContactAvatar(this.value)">
                </div>
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" class="cta-button">Add Contact</button>
            </form>
        </div>
    </div>

    <!-- Profile Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeProfileModal()">&times;</span>
            <h2>Profile Management</h2>
            <form id="profileForm" onsubmit="return updateProfile(event)">
                <div class="avatar-container">
                    <?php
                    $user_query = "SELECT * FROM users WHERE id = ?";
                    $stmt = $conn->prepare($user_query);
                    $stmt->bind_param("i", $_SESSION["user_id"]);
                    $stmt->execute();
                    $user = $stmt->get_result()->fetch_assoc();
                    
                    if (isset($user['avatar']) && $user['avatar']) {
                        echo '<img src="' . htmlspecialchars($user['avatar']) . '" alt="Profile Avatar" class="profile-avatar" id="userAvatar">';
                    } else {
                        echo '<div class="default-avatar" id="userAvatar">' . strtoupper(substr($user['username'], 0, 1)) . '</div>';
                    }
                    ?>
                    <button type="button" onclick="document.getElementById('avatarInput').click()" class="change-avatar-btn">
                        <i class="fas fa-camera"></i>
                    </button>
                    <input type="file" id="avatarInput" accept="image/*" style="display: none" onchange="updateAvatar(this.files[0])">
                </div>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="profile_email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password (leave blank to keep current):</label>
                    <input type="password" id="new_password" name="new_password">
                </div>
                <button type="submit" class="cta-button">Update Profile</button>
                <button type="button" onclick="deleteAccount()" class="cta-button" style="background-color: #dc3545; margin-top: 10px;">Delete Account</button>
            </form>
        </div>
    </div>
<!-- Edit Contact Modal -->
<div id="editContactModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditContactModal()">&times;</span>
        <h2>Edit Contact</h2>
        <form id="editContactForm" enctype="multipart/form-data" onsubmit="handleEditContact(event)">
            <input type="hidden" id="edit_contact_id" name="contact_id">
            <div class="avatar-upload">
                <div id="editAvatarPreview" class="avatar-preview"></div>
                <div class="avatar-edit">
                    <input type="file" id="editContactAvatarInput" name="avatar" accept="image/*" onchange="previewEditContactAvatar(this.files[0])">
                    <label for="editContactAvatarInput">
                        <i class="fas fa-camera"></i> Change Photo
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label for="edit_name">Name:</label>
                <input type="text" id="edit_name" name="name" required>
            </div>
            <div class="form-group">
                <label for="edit_phone">Phone:</label>
                <input type="tel" id="edit_phone" name="phone" required>
            </div>
            <div class="form-group">
                <label for="edit_email">Email:</label>
                <input type="email" id="edit_email" name="email" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="save-btn">Save Changes</button>
                <button type="button" onclick="closeEditContactModal()" class="cancel-btn">Cancel</button>
            </div>
        </form>
    </div>
</div>
    <script src="dashboard.js"></script>
</body>
</html>
