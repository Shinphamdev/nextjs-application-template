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

$group_id = (int)$_GET['id'];

// Get group info for logging
$query = "SELECT name FROM groups WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$group_id]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if ($group) {
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Delete group
        $delete_group = "DELETE FROM groups WHERE id = ?";
        $group_stmt = $db->prepare($delete_group);
        $group_stmt->execute([$group_id]);
        
        // Log the deletion
        $log_action = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
        $action_stmt = $db->prepare($log_action);
        $action_stmt->execute([
            $_SESSION['user_id'],
            'Deleted group: ' . $group['name'],
            $_SERVER['REMOTE_ADDR']
        ]);
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['success'] = "Group deleted successfully";
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error'] = "Error deleting group";
    }
} else {
    $_SESSION['error'] = "Group not found";
}

header("Location: index.php");
exit();
?>
