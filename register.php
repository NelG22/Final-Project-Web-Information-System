<?php
session_start();
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    
    // Validate input
    $error = "";
    if (empty($username)) {
        $error .= "Please enter a username.<br>";
    }
    if (empty($email)) {
        $error .= "Please enter an email.<br>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error .= "Please enter a valid email address.<br>";
    }
    if (empty($password)) {
        $error .= "Please enter a password.<br>";
    } elseif (strlen($password) < 6) {
        $error .= "Password must have at least 6 characters.<br>";
    }
    if ($password != $confirm_password) {
        $error .= "Passwords do not match.<br>";
    }
    
    if (empty($error)) {
        // Check if username exists
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error = "This username is already taken.";
            }
            mysqli_stmt_close($stmt);
        }
        
        // Check if email exists
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error = "This email is already registered.";
            }
            mysqli_stmt_close($stmt);
        }
        
        if (empty($error)) {
            // Insert new user
            $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashed_password);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Registration successful
                    $_SESSION['success_message'] = "Registration successful! Please login.";
                    header("location: index.php");
                    exit();
                } else {
                    $error = "Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    if (!empty($error)) {
        $_SESSION['error_message'] = $error;
        header("location: index.php");
        exit();
    }
}
?>
