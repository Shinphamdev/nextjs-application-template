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

$area_id = (int)$_GET['id'];

// Get area info for logging
$query = "SELECT name FROM areas WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$area_id]);
$area = $stmt->fetch(PDO::FETCH_ASSOC);

if ($area) {
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Delete area
        $delete_area = "DELETE FROM areas WHERE id = ?";
        $area_stmt = $db->prepare($delete_area);
        $area_stmt->execute([$area_id]);
        
        // Log the deletion
        $log_action = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
        $action_stmt = $db->prepare($log_action);
        $action_stmt->execute([
            $_SESSION['user_id'],
            'Deleted area: ' . $area['name'],
            $_SERVER['REMOTE_ADDR']
        ]);
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['success'] = "Area deleted successfully";
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error'] = "Error deleting area";
    }
} else {
    $_SESSION['error'] = "Area not found";
}

header("Location: index.php");
exit();
?>
