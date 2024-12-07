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
    
    if ($action === "update_profile") {
        // Add update profile code here
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
