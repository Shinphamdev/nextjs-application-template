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

// Get all active users for assignment
$users_query = "SELECT id, username FROM users WHERE status = 'active' ORDER BY username";
$users_stmt = $db->query($users_query);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch task data
$query = "SELECT * FROM tasks WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $user_id = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
    $status = $_POST['status'];
    $bonus_ratio = $_POST['bonus_ratio'];
    $amount = $_POST['amount'];

    $errors = [];

    if (empty($title)) {
        $errors[] = "Title is required";
    }

    if (!is_numeric($bonus_ratio) || $bonus_ratio < 0) {
        $errors[] = "Bonus ratio must be a positive number";
    }

    if (!is_numeric($amount) || $amount < 0) {
        $errors[] = "Amount must be a positive number";
    }

    if (empty($errors)) {
        $update_query = "UPDATE tasks SET title = ?, user_id = ?, status = ?, bonus_ratio = ?, amount = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if ($update_stmt->execute([$title, $user_id, $status, $bonus_ratio, $amount, $task_id])) {
            // Log activity
            $log_query = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
            $log_stmt = $db->prepare($log_query);
            $log_stmt->execute([$_SESSION['user_id'], 'Updated task: ' . $title, $_SERVER['REMOTE_ADDR']]);
            
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Error updating task";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-gray-800 text-white w-64 py-6 flex flex-col">
            <div class="px-6 mb-8">
                <h1 class="text-2xl font-bold">Admin Panel</h1>
            </div>
            <nav class="flex-1">
                <a href="../dashboard.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                    <span>Dashboard</span>
                </a>
                <a href="../users/index.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                    <span>Users</span>
                </a>
                <a href="../groups/index.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                    <span>Groups</span>
                </a>
                <a href="../products/index.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                    <span>Products</span>
                </a>
                <a href="index.php" class="flex items-center px-6 py-3 bg-gray-900">
                    <span>Tasks</span>
                </a>
                <a href="../areas/index.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                    <span>Areas</span>
                </a>
            </nav>
            <div class="px-6 py-4">
                <div class="flex items-center">
                    <span class="text-sm"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="../auth/logout.php" class="ml-auto text-sm text-red-400 hover:text-red-300">Logout</a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-x-hidden overflow-y-auto">
            <div class="container mx-auto px-6 py-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold text-gray-900">Edit Task</h2>
                    <a href="index.php" class="text-gray-500 hover:text-gray-600">
                        Back to Tasks
                    </a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <form method="POST" action="" class="p-6 space-y-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" name="title" id="title" required
                                value="<?php echo htmlspecialchars($task['title']); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700">Assign To</label>
                            <select name="user_id" id="user_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select User</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            <?php if ($task['user_id'] == $user['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="pending" <?php if ($task['status'] === 'pending') echo 'selected'; ?>>Pending</option>
                                <option value="in_progress" <?php if ($task['status'] === 'in_progress') echo 'selected'; ?>>In Progress</option>
                                <option value="completed" <?php if ($task['status'] === 'completed') echo 'selected'; ?>>Completed</option>
                            </select>
                        </div>

                        <div>
                            <label for="bonus_ratio" class="block text-sm font-medium text-gray-700">Bonus Ratio (%)</label>
                            <input type="number" name="bonus_ratio" id="bonus_ratio" step="0.01" required
                                value="<?php echo htmlspecialchars($task['bonus_ratio']); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                            <input type="number" name="amount" id="amount" step="0.01" required
                                value="<?php echo htmlspecialchars($task['amount']); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                                Update Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
