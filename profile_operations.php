<?php
session_start();
require_once "config.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit();
}

$user_id = $_SESSION["user_id"];
$response = ["success" => false, "message" => "Invalid request"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            $username = trim($_POST["username"]);
            $email = trim($_POST["email"]);
            $password = trim($_POST["password"]);
            
            // Verify unique username and email
            $check_sql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
            if ($check_stmt = mysqli_prepare($conn, $check_sql)) {
                mysqli_stmt_bind_param($check_stmt, "ssi", $username, $email, $user_id);
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_store_result($check_stmt);
                
                if (mysqli_stmt_num_rows($check_stmt) > 0) {
                    $response["message"] = "Username or email already exists.";
                    echo json_encode($response);
                    exit();
                }
                mysqli_stmt_close($check_stmt);
            }
            
            // Update profile
            if (!empty($password)) {
                $sql = "UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?";
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sssi", $username, $email, $hashed_password, $user_id);
            } else {
                $sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssi", $username, $email, $user_id);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION["username"] = $username;
                $response = [
                    "success" => true,
                    "message" => "Profile updated successfully",
                    "username" => $username
                ];
            } else {
                $response["message"] = "Error updating profile.";
            }
            mysqli_stmt_close($stmt);
            break;
            
        case 'clear_contacts':
            // Delete all contacts for the user
            $sql = "DELETE FROM contacts WHERE user_id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                if (mysqli_stmt_execute($stmt)) {
                    $response = [
                        "success" => true,
                        "message" => "All contacts have been deleted successfully"
                    ];
                } else {
                    $response = [
                        "success" => false,
                        "message" => "Error clearing contacts. Please try again."
                    ];
                }
                mysqli_stmt_close($stmt);
            }
            break;
            
        case 'delete_account':
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Delete all contacts first
                $sql = "DELETE FROM contacts WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                // Delete avatar file if exists
                $sql = "SELECT avatar FROM users WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                
                if ($user['avatar'] && file_exists($user['avatar'])) {
                    unlink($user['avatar']);
                }
                
                // Then delete the user
                $sql = "DELETE FROM users WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                // Commit transaction
                mysqli_commit($conn);
                
                // Clear session
                session_destroy();
                
                $response = [
                    "success" => true,
                    "message" => "Your account has been deleted successfully"
                ];
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                $response = [
                    "success" => false,
                    "message" => "Error deleting account. Please try again."
                ];
            }
            break;
            
        default:
            $response = [
                "success" => false,
                "message" => "Invalid action specified"
            ];
            break;
    }
}

echo json_encode($response);
?>
