<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'total_tasks' => 0,
    'completed_tasks' => 0
];

// Get total users
$query = "SELECT COUNT(*) as total FROM users";
$stmt = $db->query($query);
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get active users
$query = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
$stmt = $db->query($query);
$stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get total tasks
$query = "SELECT COUNT(*) as total FROM tasks";
$stmt = $db->query($query);
$stats['total_tasks'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get completed tasks
$query = "SELECT COUNT(*) as total FROM tasks WHERE status = 'completed'";
$stmt = $db->query($query);
$stats['completed_tasks'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get recent activity logs
$query = "SELECT al.*, u.username FROM activity_logs al 
          LEFT JOIN users u ON al.user_id = u.id 
          ORDER BY al.created_at DESC LIMIT 10";
$stmt = $db->query($query);
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
                <a href="dashboard.php" class="flex items-center px-6 py-3 bg-gray-900">
                    <span>Dashboard</span>
                </a>
                <a href="users/index.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                    <span>Users</span>
                </a>
                <a href="groups/index.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                    <span>Groups</span>
                </a>
                <a href="products/index.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                    <span>Products</span>
                </a>
                <a href="tasks/index.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                    <span>Tasks</span>
                </a>
                <a href="areas/index.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                    <span>Areas</span>
                </a>
            </nav>
            <div class="px-6 py-4">
                <div class="flex items-center">
                    <span class="text-sm"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="auth/logout.php" class="ml-auto text-sm text-red-400 hover:text-red-300">Logout</a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-x-hidden overflow-y-auto">
            <div class="container mx-auto px-6 py-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-8">Dashboard Overview</h2>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-gray-500 text-sm font-medium">Total Users</h3>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['total_users']; ?></p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-gray-500 text-sm font-medium">Active Users</h3>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['active_users']; ?></p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-gray-500 text-sm font-medium">Total Tasks</h3>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['total_tasks']; ?></p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-gray-500 text-sm font-medium">Completed Tasks</h3>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['completed_tasks']; ?></p>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <th class="px-6 py-3">User</th>
                                        <th class="px-6 py-3">Action</th>
                                        <th class="px-6 py-3">IP Address</th>
                                        <th class="px-6 py-3">Time</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($recent_activities as $activity): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php echo htmlspecialchars($activity['username']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php echo htmlspecialchars($activity['action']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php echo htmlspecialchars($activity['ip_address']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php echo date('Y-m-d H:i:s', strtotime($activity['created_at'])); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
