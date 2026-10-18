<?php
session_start();
include 'config.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: patient_login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];
$patientName = "Patient";

// Get patient name and blood group
$patient_query = $conn->prepare("SELECT p.full_name, bg.id, bg.group_name 
                               FROM patients p 
                               JOIN blood_groups bg ON p.blood_group_id = bg.id 
                               WHERE p.id = ?");
$patient_query->bind_param("i", $patient_id);
$patient_query->execute();
$patient_result = $patient_query->get_result();
$patient_data = $patient_result->fetch_assoc();

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_date = date('Y-m-d');
    $blood_group_id = $patient_data['id'];
    $quantity = $_POST['quantity_units'];
    $reason = $_POST['reason'];

    // Check if a request already exists for today
    $check_stmt = $conn->prepare("SELECT id FROM requests WHERE patient_id = ? AND request_date = ?");
    $check_stmt->bind_param("is", $patient_id, $request_date);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 1000) {
        $error_message = "You have already submitted a request today.";
    } else {
        // Insert the new request
        $stmt = $conn->prepare("INSERT INTO requests (patient_id, request_date, blood_group_id, quantity_units, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isiss", $patient_id, $request_date, $blood_group_id, $quantity, $reason);

        if ($stmt->execute()) {
            header("Location: patient_dashboard.php?request=submitted");
            exit();
        } else {
            $error_message = "Error submitting request. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Blood - BloodLink</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .dashboard-card {
            transition: all 0.25s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
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
                <a href="request_blood.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg bg-red-50 text-red-700">
                    <i class="fas fa-hand-holding-medical mr-3 text-red-600"></i>
                    Request Blood
                </a>
                <a href="request_history.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-history mr-3 text-gray-500"></i>
                    Request History
                </a>
                <a href="patient_inbox.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-inbox mr-3 text-gray-500"></i>
                    Messages
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
                    <h1 class="text-lg font-semibold text-gray-900">Request Blood</h1>
                    <div class="flex items-center space-x-4">
                        <button class="p-2 rounded-full text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none">
                            <i class="fas fa-bell"></i>
                        </button>
                        <div class="flex items-center">
                            <div class="h-9 w-9 rounded-full bg-red-100 flex items-center justify-center">
                                <span class="text-red-600 font-medium"><?php echo substr($patient_data['full_name'], 0, 1); ?></span>
                            </div>
                            <span class="ml-2 text-sm font-medium text-gray-700 hidden md:inline"><?php echo htmlspecialchars($patient_data['full_name']); ?></span>
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
                    <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center">
                        <span class="text-red-600 font-medium text-sm"><?php echo substr($patient_data['full_name'], 0, 1); ?></span>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden bg-white shadow-md">
                <div class="px-4 py-4 space-y-2">
                    <a href="patient_dashboard.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Dashboard</a>
                    <a href="request_blood.php" class="block px-3 py-2 rounded-md bg-red-50 text-red-700 font-medium">Request Blood</a>
                    <a href="request_history.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Request History</a>
                    <a href="patient_inbox.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Messages</a>
                    <a href="patient_profile.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Profile Settings</a>
                    <a href="patient_logout.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Sign Out</a>
                </div>
            </div>

            <!-- Main Content -->
            <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden mb-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">New Blood Request</h2>
                    </div>
                    
                    <div class="p-6">
                        <?php if (!empty($error_message)): ?>
                            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input type="text" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 bg-gray-100" 
                                           value="<?php echo htmlspecialchars($patient_data['group_name']); ?>" readonly>
                                    <input type="hidden" name="blood_group_id" value="<?php echo $patient_data['id']; ?>">
                                </div>
                            </div>

                            <div>
                                <label for="quantity_units" class="block text-sm font-medium text-gray-700 mb-1">Quantity (units)</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input type="number" name="quantity_units" id="quantity_units" 
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" 
                                           min="1" required>
                                </div>
                            </div>

                            <div>
                                <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Reason for Request</label>
                                <div class="mt-1">
                                    <textarea name="reason" id="reason" rows="3" 
                                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" required></textarea>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
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