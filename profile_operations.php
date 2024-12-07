<?php
session_start();

// Ensure no HTML error output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');

// Set JSON content type header
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once 'config.php';

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
    $userId = $_SESSION['user_id'];

    // Handle GET request for fetching profile data
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT id, username, email, phone, avatar FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("i", $userId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $profile = $result->fetch_assoc();
        
        if (!$profile) {
            throw new Exception("Profile not found");
        }
        
        // Map username to name for frontend consistency
        $profile['name'] = $profile['username'];
        unset($profile['username']);
        
        echo json_encode([
            'success' => true,
            'data' => $profile
        ]);
        exit;
    }

    // Handle POST request for updating profile
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $conn->autocommit(FALSE);
        
        try {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';

            if (empty($name) || empty($email)) {
                throw new Exception('Name and email are required');
            }

            // Handle avatar upload if present
            $avatarPath = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = handleFileUpload($_FILES['avatar']);
                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['message']);
                }
                $avatarPath = $uploadResult['filename'];
            }

            // Build update query
            $updateFields = ['username = ?', 'email = ?'];
            $params = [$name, $email];
            $types = "ss";

            if (!empty($phone)) {
                $updateFields[] = "phone = ?";
                $params[] = $phone;
                $types .= "s";
            }

            if ($avatarPath !== null) {
                $updateFields[] = "avatar = ?";
                $params[] = $avatarPath;
                $types .= "s";
            }

            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $params[] = $userId;
            $types .= "i";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare update statement: " . $conn->error);
            }

            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update profile: " . $stmt->error);
            }

            // Fetch updated profile data
            $sql = "SELECT id, username, email, phone, avatar FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $updatedProfile = $result->fetch_assoc();

            // Map username to name for frontend consistency
            $updatedProfile['name'] = $updatedProfile['username'];
            unset($updatedProfile['username']);

            $conn->commit();
            $conn->autocommit(TRUE);

            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $updatedProfile
            ]);
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $conn->autocommit(TRUE);
            throw $e;
        }
    }

    // If we get here, it's an invalid request
    throw new Exception('Invalid request method or action');

} catch (Exception $e) {
    // Log the error for debugging
    error_log("Profile operation error: " . $e->getMessage());
    
    // Send JSON error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
