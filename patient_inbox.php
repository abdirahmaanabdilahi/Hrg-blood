<?php
include 'config.php';
session_start();

if (!isset($_SESSION['patient_id'])) {
    header("Location: patient_login.php");
    exit;
}

$patient_id = $_SESSION['patient_id'];

// Handle message deletion
if (isset($_POST['delete_message'])) {
    $message_id = $_POST['message_id'];
    $stmt = $conn->prepare("DELETE FROM patient_messages WHERE id = ? AND receiver_id = ?");
    $stmt->bind_param("ii", $message_id, $patient_id);
    $stmt->execute();
    $stmt->close();
    
    // Refresh the page to show updated messages
    header("Location: patient_inbox.php");
    exit;
}

// Get patient information
$patient_info = [];
$stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient_info = $result->fetch_assoc();
$stmt->close();

// Get patient name for display
$patientName = $patient_info['full_name'] ?? 'Patient';

// Get messages
$sql = "SELECT pm.*, a.username as admin_name 
        FROM patient_messages pm
        LEFT JOIN admins a ON pm.sender_id = a.id AND pm.sender_type = 'admin'
        WHERE pm.receiver_id = ? 
        ORDER BY pm.sent_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();

// Mark unread messages as read
$conn->query("UPDATE patient_messages SET is_read = 1 WHERE receiver_id = $patient_id AND is_read = 0");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | BloodLink</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7fafc;
        }
        .message-card {
            transition: all 0.2s ease;
            position: relative;
        }
        .message-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .unread {
            background-color: #f0f9ff;
            border-left: 4px solid #3b82f6;
        }
        .delete-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .message-card:hover .delete-btn {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen antialiased">
    <!-- Sidebar Navigation -->
    <div class="flex">
        <div class="hidden md:flex md:w-64 lg:w-72 bg-white shadow-sm flex-col fixed h-full">
            <div class="p-6">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6.625 2.655A9 9 0 0119 11a1 1 0 11-2 0 7 7 0 00-9.625-6.492 1 1 0 11-.75-1.853zM4.662 4.959A1 1 0 014.75 6.37 6.97 6.97 0 003 11a1 1 0 11-2 0 8.97 8.97 0 012.25-5.953 1 1 0 011.412-.088z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M5 11a5 5 0 1110 0 1 1 0 11-2 0 3 3 0 10-6 0c0 1.677-.345 3.276-.968 4.729a1 1 0 11-1.838-.789A9.964 9.964 0 005 11zm8.921 2.012a1 1 0 01.831 1.145 19.86 19.86 0 01-.545 2.436 1 1 0 11-1.92-.558c.207-.713.371-1.445.49-2.192a1 1 0 011.144-.83z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M10 10a1 1 0 011 1c0 2.236-.46 4.368-1.29 6.304a1 1 0 01-1.838-.789A13.952 13.952 0 009 11a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    <span class="ml-2 text-xl font-bold text-gray-800">BloodLink</span>
                </div>
            </div>
            <nav class="flex-1 px-4 space-y-1 mt-6">
                <a href="patient_dashboard.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-tachometer-alt mr-3 text-gray-500"></i>
                    Dashboard
                </a>
                <a href="request_blood.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-hand-holding-water mr-3 text-gray-500"></i>
                    Request Blood
                </a>
                <a href="patient_history.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-history mr-3 text-gray-500"></i>
                    Request History
                </a>
                <a href="patient_inbox.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg bg-blue-50 text-blue-700">
                    <i class="fas fa-inbox mr-3 text-blue-600"></i>
                    Messages
                    <?php 
                    // Count unread messages
                    $unread_stmt = $conn->prepare("SELECT COUNT(*) as unread FROM patient_messages WHERE receiver_id = ? AND is_read = 0");
                    $unread_stmt->bind_param("i", $patient_id);
                    $unread_stmt->execute();
                    $unread_result = $unread_stmt->get_result();
                    $unread_count = $unread_result->fetch_assoc()['unread'];
                    $unread_stmt->close();
                    
                    if ($unread_count > 0): ?>
                    <span class="ml-auto bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-0.5 rounded-full"><?= $unread_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="patient_profile.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-user-cog mr-3 text-gray-500"></i>
                    Profile Settings
                </a>
            </nav>
            <div class="p-4 border-t border-gray-200 mt-auto">
                <a href="patient_logout.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-sign-out-alt mr-3 text-gray-500"></i>
                    Sign Out
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 md:ml-64 lg:ml-72">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm">
                <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <h1 class="text-lg font-semibold text-gray-900">Messages</h1>
                    <div class="flex items-center space-x-4">
                        <button class="p-2 rounded-full text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none">
                            <i class="fas fa-bell"></i>
                        </button>
                        <div class="flex items-center">
                            <div class="h-9 w-9 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-blue-600 font-medium"><?php echo substr($patientName, 0, 1); ?></span>
                            </div>
                            <span class="ml-2 text-sm font-medium text-gray-700 hidden md:inline"><?php echo htmlspecialchars($patientName); ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Mobile Menu Button -->
            <div class="md:hidden bg-white shadow-sm p-4 flex justify-between items-center">
                <button id="mobile-menu-button" class="text-gray-500 hover:text-gray-600 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="flex items-center">
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                        <span class="text-blue-600 font-medium text-sm"><?php echo substr($patientName, 0, 1); ?></span>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden bg-white shadow-md">
                <div class="px-4 py-4 space-y-2">
                    <a href="patient_dashboard.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Dashboard</a>
                    <a href="request_blood.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Request Blood</a>
                    <a href="patient_history.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Request History</a>
                    <a href="patient_inbox.php" class="block px-3 py-2 rounded-md bg-blue-50 text-blue-700 font-medium">Messages</a>
                    <a href="patient_profile.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Profile Settings</a>
                    <a href="patient_logout.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Sign Out</a>
                </div>
            </div>

            <!-- Main Content Area -->
            <main class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <?php if ($result->num_rows > 0): ?>
                        <div class="divide-y divide-gray-200">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <div class="message-card p-6 hover:bg-gray-50 <?= $row['is_read'] ? '' : 'unread' ?>">
                                    <form method="post" action="patient_inbox.php" class="delete-btn">
                                        <input type="hidden" name="message_id" value="<?= $row['id'] ?>">
                                        <button type="submit" name="delete_message" class="text-gray-400 hover:text-red-500 focus:outline-none" onclick="return confirm('Are you sure you want to delete this message?');">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                            <i class="fas fa-user-shield text-blue-600"></i>
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <div class="flex items-center justify-between">
                                                <h3 class="text-lg font-medium text-gray-900">
                                                    <?= $row['admin_name'] ? htmlspecialchars($row['admin_name']) : 'BloodLink Admin' ?>
                                                </h3>
                                                <span class="text-sm text-gray-500"><?= date('M j, Y g:i a', strtotime($row['sent_at'])) ?></span>
                                            </div>
                                            <?php if ($row['subject']): ?>
                                            <h4 class="text-md font-semibold text-gray-800 mt-1"><?= htmlspecialchars($row['subject']) ?></h4>
                                            <?php endif; ?>
                                            <div class="mt-2 text-sm text-gray-700">
                                                <p><?= htmlspecialchars($row['message']) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100">
                                <i class="fas fa-envelope-open-text text-gray-400"></i>
                            </div>
                            <h3 class="mt-2 text-lg font-medium text-gray-900">No messages yet</h3>
                            <p class="mt-1 text-sm text-gray-500">You don't have any messages from the admin yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>