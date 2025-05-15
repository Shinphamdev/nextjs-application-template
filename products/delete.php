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

$product_id = (int)$_GET['id'];

// Get product info for logging
$query = "SELECT title FROM products WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if ($product) {
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Delete product
        $delete_product = "DELETE FROM products WHERE id = ?";
        $product_stmt = $db->prepare($delete_product);
        $product_stmt->execute([$product_id]);
        
        // Log the deletion
        $log_action = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
        $action_stmt = $db->prepare($log_action);
        $action_stmt->execute([
            $_SESSION['user_id'],
            'Deleted product: ' . $product['title'],
            $_SERVER['REMOTE_ADDR']
        ]);
        
        // Commit transaction
        $db->commit();
        
        $_SESSION['success'] = "Product deleted successfully";
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        $_SESSION['error'] = "Error deleting product";
    }
} else {
    $_SESSION['error'] = "Product not found";
}

header("Location: index.php");
exit();
?>
