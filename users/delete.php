<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$user_id = (int)$_GET['id'];

// Don't allow users to delete themselves
if ($user_id === $_SESSION['user_id']) {
    $_SESSION['error'] = "You cannot delete your own account";
    header("Location: index.php");
    exit();
}

// Get user info for logging
$query = "SELECT username FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Delete related records in activity_logs
        $delete_logs = "DELETE FROM activity_logs WHERE user_id = ?";
        $log_stmt = $db->prepare($delete_logs);
        $log_stmt->execute([$user_id]);
        
        // Delete user
        $delete_user = "DELETE FROM users WHERE id = ?";
        $user_stmt = $db->prepare($delete_user);
        $user_stmt->execute([$user_id]);
        
        // Log the deletion
        $log_action = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
        $action_stmt = $db->prepare($log_action);
        $action_stmt->execute([
            $_SESSION['user_id'],
            'Deleted user: ' . $user['username'],
            $_SERVER['REMOTE_ADDR']
        ]);
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['success'] = "User deleted successfully";
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error'] = "Error deleting user";
    }
} else {
    $_SESSION['error'] = "User not found";
}

header("Location: index.php");
exit();
?>
