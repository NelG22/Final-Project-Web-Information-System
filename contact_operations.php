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

function handleFileUpload($file) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file parameters'];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error: ' . $file['error']];
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'];
    }

    if ($file['size'] > 5242880) { // 5MB limit
        return ['success' => false, 'message' => 'File is too large. Maximum size is 5MB.'];
    }

    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = uniqid() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => false, 'message' => 'Failed to save file'];
    }

    return ['success' => true, 'filename' => $uploadPath];
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
    $action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
    error_log("Action received: " . $action);

    switch($action) {
        case 'get':
            if (!isset($_GET['id'])) {
                throw new Exception('Contact ID is required');
            }

            $contact_id = $_GET['id'];
            $user_id = $_SESSION['user_id'];

            // Get contact details
            $sql = "SELECT * FROM contacts WHERE id = ? AND user_id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $contact_id, $user_id);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($contact = $result->fetch_assoc()) {
                    echo json_encode([
                        'success' => true,
                        'contact' => $contact
                    ]);
                } else {
                    throw new Exception('Contact not found');
                }
            } else {
                throw new Exception('Failed to fetch contact details');
            }
            break;

        case 'add':
            if (!isset($_POST['name']) || !isset($_POST['phone']) || !isset($_POST['email'])) {
                throw new Exception('Missing required fields');
            }

            $name = $_POST['name'];
            $phone = $_POST['phone'];
            $email = $_POST['email'];
            $user_id = $_SESSION['user_id'];

            try {
                $conn->begin_transaction();
                
                $sql = "INSERT INTO contacts (user_id, name, phone, email) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isss", $user_id, $name, $phone, $email);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to add contact");
                }
                
                $contactId = $stmt->insert_id;
                
                // Handle avatar upload if present
                if (isset($_FILES['avatar'])) {
                    $uploadResult = handleFileUpload($_FILES['avatar']);
                    if (!$uploadResult['success'] && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                        throw new Exception($uploadResult['message']);
                    }
                    if ($uploadResult['success'] && $uploadResult['filename'] !== null) {
                        $sql = "UPDATE contacts SET avatar = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $uploadResult['filename'], $contactId);
                        if (!$stmt->execute()) {
                            throw new Exception("Failed to update contact avatar");
                        }
                    }
                }
                
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Contact added successfully']);
                
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
            
        case 'edit_contact':
            if (!isset($_POST['contact_id']) || !isset($_POST['name']) || !isset($_POST['phone']) || !isset($_POST['email'])) {
                throw new Exception('Missing required fields for editing contact');
            }

            $contact_id = $_POST['contact_id'];
            $name = $_POST['name'];
            $phone = $_POST['phone'];
            $email = $_POST['email'];
            $user_id = $_SESSION['user_id'];

            try {
                $conn->begin_transaction();
                
                // First verify the contact belongs to the user
                $stmt = $conn->prepare("SELECT id FROM contacts WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $contact_id, $user_id);
                $stmt->execute();
                if (!$stmt->get_result()->fetch_assoc()) {
                    throw new Exception("Contact not found or access denied");
                }
                
                $updateFields = [];
                $params = [];
                $types = '';
                
                if (!empty($name)) {
                    $updateFields[] = 'name = ?';
                    $params[] = $name;
                    $types .= 's';
                }
                if (!empty($phone)) {
                    $updateFields[] = 'phone = ?';
                    $params[] = $phone;
                    $types .= 's';
                }
                if (!empty($email)) {
                    $updateFields[] = 'email = ?';
                    $params[] = $email;
                    $types .= 's';
                }
                
                // Handle avatar upload if present
                if (isset($_FILES['avatar'])) {
                    $uploadResult = handleFileUpload($_FILES['avatar']);
                    if (!$uploadResult['success'] && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                        throw new Exception($uploadResult['message']);
                    }
                    if ($uploadResult['success'] && $uploadResult['filename'] !== null) {
                        $updateFields[] = 'avatar = ?';
                        $params[] = $uploadResult['filename'];
                        $types .= 's';
                    }
                }
                
                if (!empty($updateFields)) {
                    $params[] = $contact_id;
                    $types .= 'i';
                    
                    $sql = "UPDATE contacts SET " . implode(', ', $updateFields) . " WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param($types, ...$params);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update contact");
                    }
                }
                
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Contact updated successfully']);
                
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'delete':
            if (!isset($_POST['contact_id'])) {
                throw new Exception('Contact ID is required');
            }

            $contact_id = $_POST['contact_id'];
            $user_id = $_SESSION['user_id'];

            try {
                $conn->begin_transaction();
                
                // First get the avatar path if exists
                $stmt = $conn->prepare("SELECT avatar FROM contacts WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $contact_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $contact = $result->fetch_assoc();
                
                // Delete the contact
                $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $contact_id, $user_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to delete contact");
                }
                
                // Delete avatar file if exists
                if ($contact && $contact['avatar'] && file_exists($contact['avatar'])) {
                    unlink($contact['avatar']);
                }
                
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Contact deleted successfully']);
                
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
            
        default:
            throw new Exception('Invalid action: ' . $action);
    }
} catch (Exception $e) {
    error_log("Error in contact_operations.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
