<?php
session_start();
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $error = "";
    
    // Validate input
    if (empty($email)) {
        $error .= "Please enter your email.<br>";
    }
    if (empty($password)) {
        $error .= "Please enter your password.<br>";
    }
    
    if (empty($error)) {
        $sql = "SELECT id, username, password FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["user_id"] = $id;
                            $_SESSION["username"] = $username;
                            
                            // Redirect to dashboard
                            header("location: dashboard.php");
                            exit();
                        } else {
                            $error = "Invalid email or password.";
                        }
                    }
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    if (!empty($error)) {
        $_SESSION['error_message'] = $error;
        header("location: index.php");
        exit();
    }
}
?>
