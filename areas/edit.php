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

// Fetch area data
$query = "SELECT * FROM areas WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$area_id]);
$area = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$area) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $currency = $_POST['currency'];
    $area_code = $_POST['area_code'];
    $domain = $_POST['domain'];
    $lang = $_POST['lang'];

    $errors = [];

    if (empty($name)) {
        $errors[] = "Name is required";
    }

    if (empty($currency)) {
        $errors[] = "Currency is required";
    }

    if (empty($area_code)) {
        $errors[] = "Area code is required";
    }

    // Check if area name already exists for other areas
    $check_query = "SELECT id FROM areas WHERE name = ? AND id != ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$name, $area_id]);
    if ($check_stmt->rowCount() > 0) {
        $errors[] = "Area name already exists";
    }

    if (empty($errors)) {
        $update_query = "UPDATE areas SET name = ?, currency = ?, area_code = ?, domain = ?, lang = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if ($update_stmt->execute([$name, $currency, $area_code, $domain, $lang, $area_id])) {
            // Log activity
            $log_query = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
            $log_stmt = $db->prepare($log_query);
            $log_stmt->execute([$_SESSION['user_id'], 'Updated area: ' . $name, $_SERVER['REMOTE_ADDR']]);
            
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Error updating area";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Area</title>
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
                <a href="../tasks/index.php" class="flex items-center px-6 py-3 hover:bg-gray-700">
                    <span>Tasks</span>
                </a>
                <a href="index.php" class="flex items-center px-6 py-3 bg-gray-900">
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
                    <h2 class="text-2xl font-semibold text-gray-900">Edit Area</h2>
                    <a href="index.php" class="text-gray-500 hover:text-gray-600">
                        Back to Areas
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
                                value="<?php echo htmlspecialchars($area['name']); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700">Currency</label>
                            <input type="text" name="currency" id="currency" required
                                value="<?php echo htmlspecialchars($area['currency']); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="area_code" class="block text-sm font-medium text-gray-700">Area Code</label>
                            <input type="text" name="area_code" id="area_code" required
                                value="<?php echo htmlspecialchars($area['area_code']); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="domain" class="block text-sm font-medium text-gray-700">Domain</label>
                            <input type="text" name="domain" id="domain"
                                value="<?php echo htmlspecialchars($area['domain']); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="lang" class="block text-sm font-medium text-gray-700">Language</label>
                            <select name="lang" id="lang" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="en" <?php if ($area['lang'] === 'en') echo 'selected'; ?>>English</option>
                                <option value="fr" <?php if ($area['lang'] === 'fr') echo 'selected'; ?>>French</option>
                                <option value="es" <?php if ($area['lang'] === 'es') echo 'selected'; ?>>Spanish</option>
                                <option value="de" <?php if ($area['lang'] === 'de') echo 'selected'; ?>>German</option>
                                <option value="it" <?php if ($area['lang'] === 'it') echo 'selected'; ?>>Italian</option>
                                <option value="pt" <?php if ($area['lang'] === 'pt') echo 'selected'; ?>>Portuguese</option>
                                <option value="ru" <?php if ($area['lang'] === 'ru') echo 'selected'; ?>>Russian</option>
                                <option value="zh" <?php if ($area['lang'] === 'zh') echo 'selected'; ?>>Chinese</option>
                                <option value="ja" <?php if ($area['lang'] === 'ja') echo 'selected'; ?>>Japanese</option>
                                <option value="ko" <?php if ($area['lang'] === 'ko') echo 'selected'; ?>>Korean</option>
                            </select>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                                Update Area
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
