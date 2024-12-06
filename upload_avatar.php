<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit();
}

$response = ["success" => false, "message" => "Invalid request"];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["avatar"])) {
    $target_dir = "uploads/avatars/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file = $_FILES["avatar"];
    $fileName = basename($file["name"]);
    $targetFile = $target_dir . uniqid() . '_' . $fileName;
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    
    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        $response["message"] = "File is not an image.";
        $uploadOk = 0;
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        $response["message"] = "File is too large. Maximum size is 5MB.";
        $uploadOk = 0;
    }
    
    // Allow certain file formats
    if (!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
        $response["message"] = "Only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }
    
    if ($uploadOk == 1) {
        if (move_uploaded_file($file["tmp_name"], $targetFile)) {
            // Update database with new avatar path
            $sql = "UPDATE users SET avatar = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "si", $targetFile, $_SESSION["user_id"]);
                if (mysqli_stmt_execute($stmt)) {
                    $response["success"] = true;
                    $response["message"] = "Avatar updated successfully.";
                    $response["avatar_path"] = $targetFile;
                } else {
                    $response["message"] = "Error updating avatar in database.";
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $response["message"] = "Error uploading file.";
        }
    }
}

echo json_encode($response);
?>
