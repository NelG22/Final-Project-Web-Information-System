<?php
session_start();
require_once "config.php";

header('Content-Type: application/json');

// Log the request for debugging
error_log("Request Method: " . $_SERVER["REQUEST_METHOD"]);
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("POST data: " . print_r($_POST, true));
}

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST["action"]) ? $_POST["action"] : "";
    error_log("Action received: " . $action);
    
    if ($action === "delete_account") {
        $user_id = $_SESSION["user_id"];
        error_log("Attempting to delete user account: " . $user_id);
        
        try {
            // Start transaction
            mysqli_begin_transaction($conn);
            error_log("Transaction started");
            
            // First, get user data for avatar deletion
            $get_user = "SELECT avatar FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $get_user);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $user_result = mysqli_stmt_get_result($stmt);
            $user_data = mysqli_fetch_assoc($user_result);
            
            // Get contacts data for avatar deletion
            $get_contacts = "SELECT avatar FROM contacts WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $get_contacts);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $contacts_result = mysqli_stmt_get_result($stmt);
            
            // Delete contact avatars
            while ($contact = mysqli_fetch_assoc($contacts_result)) {
                if (!empty($contact['avatar']) && file_exists($contact['avatar'])) {
                    unlink($contact['avatar']);
                }
            }
            
            // Delete user avatar
            if (!empty($user_data['avatar']) && file_exists($user_data['avatar'])) {
                unlink($user_data['avatar']);
            }
            
            // Delete all contacts
            $delete_contacts = "DELETE FROM contacts WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $delete_contacts);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            $contacts_deleted = mysqli_stmt_execute($stmt);
            error_log("Contacts deleted: " . ($contacts_deleted ? "true" : "false"));
            
            // Delete user account
            $delete_user = "DELETE FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $delete_user);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            $user_deleted = mysqli_stmt_execute($stmt);
            error_log("User deleted: " . ($user_deleted ? "true" : "false"));
            
            // If both operations are successful, commit the transaction
            if ($contacts_deleted && $user_deleted) {
                mysqli_commit($conn);
                error_log("Transaction committed");
                
                // Clear session
                session_destroy();
                error_log("Session destroyed");
                
                echo json_encode(["success" => true, "message" => "Account deleted successfully"]);
            } else {
                throw new Exception("Failed to delete account");
            }
        } catch (Exception $e) {
            // If any operation fails, rollback the transaction
            mysqli_rollback($conn);
            error_log("Error in user_operations.php: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode(["success" => false, "message" => "Failed to delete account: " . $e->getMessage()]);
        }
    } else {
        error_log("Invalid action: " . $action);
        echo json_encode(["success" => false, "message" => "Invalid action"]);
    }
} else {
    error_log("Invalid request method: " . $_SERVER["REQUEST_METHOD"]);
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}

mysqli_close($conn);
?>
