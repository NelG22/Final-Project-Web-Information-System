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

    <main class="dashboard-container">
        <!-- Profile Sidebar -->
        <aside class="profile-sidebar">
            <div class="profile-header">
                <?php
                    // Get user information
                    $user_id = $_SESSION["user_id"];
                    $sql = "SELECT * FROM users WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();

                    // Get total contacts count
                    $sql_count = "SELECT COUNT(*) as total FROM contacts WHERE user_id = ?";
                    $stmt_count = $conn->prepare($sql_count);
                    $stmt_count->bind_param("i", $user_id);
                    $stmt_count->execute();
                    $result_count = $stmt_count->get_result();
                    $contacts_count = $result_count->fetch_assoc()['total'];
                ?>
                <div class="profile-avatar">
                    <?php if (isset($user['avatar']) && !empty($user['avatar'])): ?>
                        <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Profile Picture">
                    <?php else: ?>
                        <div class="default-avatar"><?php echo isset($user['username']) ? strtoupper(substr($user['username'], 0, 1)) : '?'; ?></div>
                    <?php endif; ?>
                </div>
                <h2><?php echo isset($user['username']) ? htmlspecialchars($user['username']) : 'User'; ?></h2>
                <p class="contacts-count"><i class="fas fa-address-book"></i> <?php echo $contacts_count; ?> Contacts</p>
                <button class="edit-profile-btn" onclick="showProfileModal()">
                    <i class="fas fa-edit"></i>
                    Edit Profile
                </button>
            </div>
            <div class="profile-stats">
                <div class="stat-item">
                    <i class="fas fa-envelope"></i>
                    <span><?php echo isset($user['email']) ? htmlspecialchars($user['email']) : 'No email set'; ?></span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-phone"></i>
                    <span><?php echo isset($user['phone']) && !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'No phone set'; ?></span>
                </div>
            </div>
        </aside>

        <div class="main-content">
            <div class="dashboard-header">
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search contacts...">
                    <i class="fas fa-search"></i>
                </div>
            </div>

            <div class="contacts-grid">
                <?php
                    $sql = "SELECT * FROM contacts WHERE user_id = ? ORDER BY name ASC";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($contact = $result->fetch_assoc()):
                ?>
                <div class="contact-card">
                    <div class="contact-info">
                        <div class="contact-avatar">
                            <?php if (isset($contact['avatar']) && !empty($contact['avatar'])): ?>
                                <img src="<?php echo htmlspecialchars($contact['avatar']); ?>" alt="Contact Picture">
                            <?php else: ?>
                                <div class="default-avatar">
                                    <?php echo strtoupper(substr($contact['name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="contact-details">
                            <h3><?php echo htmlspecialchars($contact['name']); ?></h3>
                            <?php if (!empty($contact['email'])): ?>
                                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contact['email']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($contact['phone'])): ?>
                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($contact['phone']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($contact['address'])): ?>
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($contact['address']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="contact-actions">
                        <button onclick="editContact(<?php echo $contact['id']; ?>)" class="btn-edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteContact(<?php echo $contact['id']; ?>)" class="btn-delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>

    <!-- Add Contact Modal -->
<div id="addContactModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Contact</h2>
            <span class="close" onclick="closeAddContactModal()">&times;</span>
        </div>
        <form id="addContactForm" onsubmit="handleAddContact(event)" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required class="form-control">
            </div>
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" required class="form-control">
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required class="form-control">
            </div>
            <div class="form-group">
                <label for="contact_avatar">Contact Picture:</label>
                <input type="file" id="contact_avatar" name="avatar" accept="image/*" onchange="previewImage(this, 'contact_avatar_preview')" class="form-control">
                <img id="contact_avatar_preview" style="display:none; max-width: 200px; margin-top: 10px;">
            </div>
            <button type="submit" class="btn btn-primary">Add Contact</button>
        </form>
    </div>
</div>

    <!-- Profile Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Profile</h2>
                <span class="close" onclick="closeProfileModal()">&times;</span>
            </div>
            <form id="profileForm" onsubmit="handleProfileUpdate(event)" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="profile_name">Name:</label>
                    <input type="text" id="profile_name" name="name" required class="form-control" 
                           value="<?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="profile_email">Email:</label>
                    <input type="email" id="profile_email" name="email" required class="form-control" 
                           value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="profile_phone">Phone:</label>
                    <input type="tel" id="profile_phone" name="phone" class="form-control" 
                           value="<?php echo isset($user['phone']) ? htmlspecialchars($user['phone']) : ''; ?>"
                           placeholder="Enter your phone number">
                </div>
                <div class="form-group">
                    <label for="profile_avatar">Profile Picture:</label>
                    <input type="file" id="profile_avatar" name="avatar" accept="image/*" 
                           onchange="previewImage(this, 'profile_avatar_preview')" class="form-control">
                    <img id="profile_avatar_preview" 
                         src="<?php echo isset($user['avatar']) && !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : ''; ?>" 
                         style="<?php echo isset($user['avatar']) && !empty($user['avatar']) ? 'display:block;' : 'display:none;'; ?> max-width: 200px; margin-top: 10px;">
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
<!-- Edit Contact Modal -->
<div id="editContactModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Contact</h2>
            <span class="close" onclick="closeEditContactModal()">&times;</span>
        </div>
        <form id="editContactForm" onsubmit="handleEditContact(event)" enctype="multipart/form-data">
            <input type="hidden" id="edit_contact_id" name="contact_id">
            <div class="form-group">
                <label for="edit_name">Name:</label>
                <input type="text" id="edit_name" name="name" class="form-control">
            </div>
            <div class="form-group">
                <label for="edit_phone">Phone:</label>
                <input type="tel" id="edit_phone" name="phone" class="form-control">
            </div>
            <div class="form-group">
                <label for="edit_email">Email:</label>
                <input type="email" id="edit_email" name="email" class="form-control">
            </div>
            <div class="form-group">
                <label for="edit_avatar">Contact Picture:</label>
                <input type="file" id="edit_avatar" name="avatar" accept="image/*" onchange="previewImage(this, 'edit_avatar_preview')" class="form-control">
                <img id="edit_avatar_preview" style="display:none; max-width: 200px; margin-top: 10px;">
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>
    <script src="dashboard.js"></script>
</body>
</html>
