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

// Fetch group data
$query = "SELECT * FROM groups WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$group_id]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    header("Location: index.php");
    exit();
}

// Get all groups for parent selection (excluding current group and its children)
$groups_query = "SELECT id, name FROM groups WHERE id != ? ORDER BY name";
$groups_stmt = $db->prepare($groups_query);
$groups_stmt->execute([$group_id]);
$available_groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
    $status = $_POST['status'];

    $errors = [];

    if (empty($name)) {
        $errors[] = "Name is required";
    }

    // Check if group name already exists for other groups
    $check_query = "SELECT id FROM groups WHERE name = ? AND id != ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$name, $group_id]);
    if ($check_stmt->rowCount() > 0) {
        $errors[] = "Group name already exists";
    }

    // Prevent circular reference
    if ($parent_id == $group_id) {
        $errors[] = "A group cannot be its own parent";
    }

    if (empty($errors)) {
        $update_query = "UPDATE groups SET name = ?, parent_id = ?, status = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if ($update_stmt->execute([$name, $parent_id, $status, $group_id])) {
            // Log activity
            $log_query = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
            $log_stmt = $db->prepare($log_query);
            $log_stmt->execute([$_SESSION['user_id'], 'Updated group: ' . $name, $_SERVER['REMOTE_ADDR']]);
            
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Error updating group";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Group</title>
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
                <a href="index.php" class="flex items-center px-6 py-3 bg-gray-900">
                    <span>Groups</span>
                </a>
                <a href="../products/index.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                    <span>Products</span>
                </a>
                <a href="../tasks/index.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
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
                    <h2 class="text-2xl font-semibold text-gray-900">Edit Group</h2>
                    <a href="index.php" class="text-gray-500 hover:text-gray-600">
                        Back to Groups
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
                            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="name" id="name" required
                                value="<?php echo htmlspecialchars($group['name']); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="parent_id" class="block text-sm font-medium text-gray-700">Parent Group</label>
                            <select name="parent_id" id="parent_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">None</option>
                                <?php foreach ($available_groups as $available_group): ?>
                                    <option value="<?php echo $available_group['id']; ?>"
                                            <?php if ($group['parent_id'] == $available_group['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($available_group['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="active" <?php if ($group['status'] === 'active') echo 'selected'; ?>>Active</option>
                                <option value="inactive" <?php if ($group['status'] === 'inactive') echo 'selected'; ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                                Update Group
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
