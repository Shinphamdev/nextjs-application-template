<?php
session_start();

// Log the logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    require_once "../config/database.php";
    $database = new Database();
    $db = $database->getConnection();
    
    $log_query = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
    $log_stmt = $db->prepare($log_query);
    $log_stmt->execute([$_SESSION['user_id'], 'logout', $_SERVER['REMOTE_ADDR']]);
}

// Destroy all session data
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
