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

$task_id = (int)$_GET['id'];

// Get task info for logging
$query = "SELECT title FROM tasks WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if ($task) {
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Delete task
        $delete_task = "DELETE FROM tasks WHERE id = ?";
        $task_stmt = $db->prepare($delete_task);
        $task_stmt->execute([$task_id]);
        
        // Log the deletion
        $log_action = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
        $action_stmt = $db->prepare($log_action);
        $action_stmt->execute([
            $_SESSION['user_id'],
            'Deleted task: ' . $task['title'],
            $_SERVER['REMOTE_ADDR']
        ]);
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['success'] = "Task deleted successfully";
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error'] = "Error deleting task";
    }
} else {
    $_SESSION['error'] = "Task not found";
}

header("Location: index.php");
exit();
?>
