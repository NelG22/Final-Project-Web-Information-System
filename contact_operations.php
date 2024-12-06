<?php
// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');

// Turn off error reporting for display
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start session and set JSON header
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

require_once "config.php";

// Function to resize and save image
function resizeAndSaveImage($source_path, $target_path, $max_dimension = 200) {
    list($width, $height, $type) = getimagesize($source_path);
    
    // Calculate new dimensions while maintaining aspect ratio
    if ($width > $height) {
        if ($width > $max_dimension) {
            $new_width = $max_dimension;
            $new_height = floor($height * ($max_dimension / $width));
        } else {
            $new_width = $width;
            $new_height = $height;
        }
    } else {
        if ($height > $max_dimension) {
            $new_height = $max_dimension;
            $new_width = floor($width * ($max_dimension / $height));
        } else {
            $new_width = $width;
            $new_height = $height;
        }
    }
    
    // Create new image
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // Handle transparency for PNG images
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Load source image
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($source_path);
            break;
        default:
            throw new Exception('Unsupported image type');
    }
    
    // Resize image
    imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save image
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($new_image, $target_path, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($new_image, $target_path, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($new_image, $target_path);
            break;
    }
    
    // Clean up
    imagedestroy($new_image);
    imagedestroy($source);
    
    return true;
}

try {
    // First, let's check if the avatar column exists and add it if it doesn't
    $check_column = "SHOW COLUMNS FROM contacts LIKE 'avatar'";
    $result = $conn->query($check_column);

    if ($result->num_rows === 0) {
        $add_column = "ALTER TABLE contacts ADD avatar VARCHAR(255)";
        $conn->query($add_column);
    }

    // Get the action from POST or GET
    $action = $_POST['action'] ?? ($_GET['action'] ?? '');
    error_log("Action received: " . $action);

    if ($action === 'add') {
        if (!isset($_POST['name']) || !isset($_POST['phone']) || !isset($_POST['email'])) {
            throw new Exception('Missing required fields');
        }

        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $user_id = $_SESSION['user_id'];
        $avatar_path = null;

        // Handle avatar upload if present
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/avatars/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception('Invalid file type. Only JPG, PNG and GIF allowed.');
            }

            $avatar_filename = uniqid() . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $avatar_filename;

            if (resizeAndSaveImage($_FILES['avatar']['tmp_name'], $target_path)) {
                $avatar_path = $target_path;
            } else {
                throw new Exception('Failed to process avatar image');
            }
        }

        // Insert contact
        if ($avatar_path !== null) {
            $sql = "INSERT INTO contacts (user_id, name, phone, email, avatar) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issss", $user_id, $name, $phone, $email, $avatar_path);
        } else {
            $sql = "INSERT INTO contacts (user_id, name, phone, email) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $user_id, $name, $phone, $email);
        }

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Contact added successfully'
            ]);
        } else {
            throw new Exception('Failed to add contact: ' . $stmt->error);
        }
        exit;
    }

    if ($action === 'edit_contact') {
        if (!isset($_POST['contact_id']) || !isset($_POST['name']) || !isset($_POST['phone']) || !isset($_POST['email'])) {
            throw new Exception('Missing required fields for editing contact');
        }

        $contact_id = $_POST['contact_id'];
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $user_id = $_SESSION['user_id'];

        // Verify the contact belongs to the user
        $check_sql = "SELECT * FROM contacts WHERE id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $contact_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            throw new Exception('Contact not found or unauthorized');
        }

        // Handle avatar upload if present
        $avatar_update = "";
        $avatar_params = [];
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/avatars/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception('Invalid file type. Only JPG, PNG and GIF allowed.');
            }

            $avatar_filename = uniqid() . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $avatar_filename;

            if (resizeAndSaveImage($_FILES['avatar']['tmp_name'], $target_path)) {
                $avatar_update = ", avatar = ?";
                $avatar_params[] = $target_path;

                // Delete old avatar if it exists
                $old_avatar_sql = "SELECT avatar FROM contacts WHERE id = ? AND user_id = ?";
                $old_avatar_stmt = $conn->prepare($old_avatar_sql);
                $old_avatar_stmt->bind_param("ii", $contact_id, $user_id);
                $old_avatar_stmt->execute();
                $old_avatar_result = $old_avatar_stmt->get_result();
                if ($old_avatar_row = $old_avatar_result->fetch_assoc()) {
                    if ($old_avatar_row['avatar'] && file_exists($old_avatar_row['avatar'])) {
                        unlink($old_avatar_row['avatar']);
                    }
                }
            } else {
                throw new Exception('Failed to process avatar image');
            }
        }

        // Update contact
        $sql = "UPDATE contacts SET name = ?, phone = ?, email = ?" . $avatar_update . " WHERE id = ? AND user_id = ?";
        $params = array_merge(["ssss"], [$name, $phone, $email], $avatar_params, [$contact_id, $user_id]);
        $stmt = $conn->prepare($sql);
        call_user_func_array([$stmt, 'bind_param'], $params);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Contact updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update contact: ' . $stmt->error);
        }
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        error_log("POST Action: " . $action);

        if ($action === 'add') {
            try {
                $name = $_POST['name'];
                $phone = $_POST['phone'];
                $email = $_POST['email'];
                $user_id = $_SESSION['user_id'];
                $avatar_path = null;

                // Handle avatar upload if present
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/avatars/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

                    if (!in_array($file_extension, $allowed_types)) {
                        throw new Exception('Invalid file type. Only JPG, PNG and GIF allowed.');
                    }

                    $avatar_filename = uniqid() . '_' . time() . '.' . $file_extension;
                    $target_path = $upload_dir . $avatar_filename;

                    // Resize and save avatar
                    if (resizeAndSaveImage($_FILES['avatar']['tmp_name'], $target_path)) {
                        $avatar_path = $target_path;
                    } else {
                        throw new Exception('Failed to process avatar image');
                    }
                }

                // Insert contact
                if ($avatar_path !== null) {
                    $sql = "INSERT INTO contacts (user_id, name, phone, email, avatar) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("issss", $user_id, $name, $phone, $email, $avatar_path);
                } else {
                    $sql = "INSERT INTO contacts (user_id, name, phone, email) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("isss", $user_id, $name, $phone, $email);
                }

                if ($stmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Contact added successfully'
                    ]);
                } else {
                    throw new Exception('Failed to add contact');
                }
            } catch (Exception $e) {
                error_log("Error in add contact: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
            exit;
        }
        elseif ($_POST['action'] === 'edit') {
            if (!isset($_POST['contact_id'])) {
                throw new Exception('Contact ID is required for edit operation');
            }

            $contact_id = intval($_POST['contact_id']);
            error_log("Attempting to edit contact ID: " . $contact_id);

            // Get existing contact data
            $query = "SELECT * FROM contacts WHERE id = ? AND user_id = ? LIMIT 1";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Failed to prepare select statement: ' . $conn->error);
            }
            
            $stmt->bind_param("ii", $contact_id, $_SESSION['user_id']);
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute select statement: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $existing_contact = $result->fetch_assoc();
            
            if (!$existing_contact) {
                throw new Exception('Contact not found or does not belong to current user');
            }

            error_log("Existing contact data: " . print_r($existing_contact, true));

            // Handle avatar
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                // Delete old avatar if it exists
                if ($existing_contact['avatar'] && file_exists($existing_contact['avatar'])) {
                    unlink($existing_contact['avatar']);
                }

                // Process and save new avatar
                $upload_dir = 'uploads/avatars/contacts/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $new_filename = uniqid() . '.' . strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $avatar_path = $upload_dir . $new_filename;

                try {
                    resizeAndSaveImage($_FILES['avatar']['tmp_name'], $avatar_path);
                    error_log("New avatar saved: " . $avatar_path);
                } catch (Exception $e) {
                    error_log("Failed to process avatar: " . $e->getMessage());
                    throw new Exception('Failed to process avatar: ' . $e->getMessage());
                }
            }

            // Prepare update query
            $set_clauses = [];
            $params = [];
            $types = "";

            // Always update these fields
            $set_clauses[] = "name = ?";
            $set_clauses[] = "phone = ?";
            $set_clauses[] = "email = ?";
            $params[] = &$name;
            $params[] = &$phone;
            $params[] = &$email;
            $types .= "sss";

            // Add avatar if we have a new one
            if (isset($avatar_path)) {
                $set_clauses[] = "avatar = ?";
                $params[] = &$avatar_path;
                $types .= "s";
            }

            // Add contact_id and user_id
            $params[] = &$contact_id;
            $params[] = &$_SESSION['user_id'];
            $types .= "ii";

            $query = "UPDATE contacts SET " . implode(", ", $set_clauses) . 
                     " WHERE id = ? AND user_id = ?";

            error_log("Update query: " . $query);
            error_log("Params types: " . $types);
            error_log("Params values: " . print_r($params, true));

            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Failed to prepare update statement: ' . $conn->error);
            }

            // Bind parameters dynamically
            array_unshift($params, $types);
            call_user_func_array([$stmt, 'bind_param'], $params);

            if (!$stmt->execute()) {
                throw new Exception('Failed to execute update statement: ' . $stmt->error);
            }

            if ($stmt->affected_rows === 0) {
                throw new Exception('No changes were made to the contact');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Contact updated successfully',
                'contact' => [
                    'id' => $contact_id,
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'avatar' => isset($avatar_path) ? $avatar_path : $existing_contact['avatar']
                ]
            ]);
            exit;
        }
        elseif ($_POST['action'] === 'delete') {
            if (!isset($_POST['contact_id'])) {
                throw new Exception('Contact ID is required');
            }

            $contact_id = intval($_POST['contact_id']);
            error_log("Attempting to delete contact ID: " . $contact_id);
            
            // First verify the contact exists and belongs to the user
            $query = "SELECT id FROM contacts WHERE id = ? AND user_id = ? LIMIT 1";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Failed to prepare verify statement: ' . $conn->error);
            }
            
            $stmt->bind_param("ii", $contact_id, $_SESSION['user_id']);
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute verify statement: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            if (!$result->fetch_assoc()) {
                throw new Exception('Contact not found or does not belong to current user');
            }
            
            // Now delete the contact
            $query = "DELETE FROM contacts WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Failed to prepare delete statement: ' . $conn->error);
            }
            
            $stmt->bind_param("ii", $contact_id, $_SESSION['user_id']);
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute delete statement: ' . $stmt->error);
            }
            
            if ($stmt->affected_rows > 0) {
                error_log("Successfully deleted contact ID: " . $contact_id);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Contact deleted successfully',
                    'contact_id' => $contact_id
                ]);
            } else {
                throw new Exception('No rows were affected by the delete operation');
            }
            exit;
        }
    }
    elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
        if ($_GET['action'] === 'get') {
            $contact_id = $_GET['id'];
            
            $query = "SELECT * FROM contacts WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Failed to prepare statement');
            }
            
            $stmt->bind_param("ii", $contact_id, $_SESSION['user_id']);
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute statement');
            }
            
            $result = $stmt->get_result();
            if ($contact = $result->fetch_assoc()) {
                echo json_encode(['success' => true, 'contact' => $contact]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Contact not found']);
            }
            exit;
        }
    }

    // If we get here, no valid action was specified
    throw new Exception('Invalid action');

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
