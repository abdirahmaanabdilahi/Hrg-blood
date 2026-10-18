<?php
session_start();
include 'config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['patient_id'])) {
    header("Location: patient_login.php");
    exit();
}

// Get patient details
$patient_id = $_SESSION['patient_id'];
$patientName = "Patient";
$bloodType = '';
$requestCount = 0;
$completedCount = 0;
$donorCount = 0;

// Get patient name and blood type
$stmt = $conn->prepare("SELECT p.full_name, bg.group_name 
                       FROM patients p
                       LEFT JOIN blood_groups bg ON p.blood_group_id = bg.id
                       WHERE p.id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stmt->bind_result($patientName, $bloodType);
$stmt->fetch();
$stmt->close();

// Get request statistics
$stmt = $conn->prepare("SELECT 
                        COUNT(*) as total_requests,
                        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as completed_requests
                        FROM requests 
                        WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $requestCount = $row['total_requests'];
    $completedCount = $row['completed_requests'];
}
$stmt->close();

// Get donor count (simplified example - in a real app this would be more complex)
$stmt = $conn->prepare("SELECT COUNT(DISTINCT d.id) as donor_count
                       FROM donors d
                       JOIN blood_groups bg ON d.blood_group_id = bg.id
                       JOIN patients p ON p.id = ?
                       WHERE bg.id = p.blood_group_id");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $donorCount = $row['donor_count'];
}
$stmt->close();



// Activity icons based on type
function getActivityIcon($type) {
    switch ($type) {
        case 'request_completed':
            return ['fas fa-check-circle', 'bg-green-100', 'text-green-600'];
        case 'new_donor':
            return ['fas fa-user-plus', 'bg-blue-100', 'text-blue-600'];
        case 'request_pending':
            return ['fas fa-exclamation-circle', 'bg-yellow-100', 'text-yellow-600'];
        case 'message_received':
            return ['fas fa-envelope', 'bg-purple-100', 'text-purple-600'];
        default:
            return ['fas fa-info-circle', 'bg-gray-100', 'text-gray-600'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Portal - BloodLink</title>
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
        .blood-type-badge {
            box-shadow: inset 0 0 0 2px rgba(255,255,255,0.4);
        }
        .unread-count {
            font-size: 0.65rem;
            min-width: 1.25rem;
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
                <a href="#" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg bg-red-50 text-red-700">
                    <i class="fas fa-tachometer-alt mr-3 text-red-600"></i>
                    Dashboard
                </a>
                <a href="request_blood.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-hand-holding-medical mr-3 text-gray-500"></i>
                    Request Blood
                </a>
                <a href="request_history.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-history mr-3 text-gray-500"></i>
                    Request History
                </a>
                <a href="patient_inbox.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-inbox mr-3 text-gray-500"></i>
                    Messages
                    <?php if (rand(0, 3) > 0): ?>
                    <span class="ml-auto bg-red-100 text-red-800 text-xs font-semibold px-2 py-0.5 rounded-full unread-count"><?php echo rand(1, 5); ?></span>
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
                    <h1 class="text-lg font-semibold text-gray-900">Patient Dashboard</h1>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button id="notification-button" class="p-2 rounded-full text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none">
                                <i class="fas fa-bell"></i>
                                <?php if (rand(0, 3) > 0): ?>
                                <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-red-500"></span>
                                <?php endif; ?>
                            </button>
                            <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                
                                <div class="max-h-60 overflow-y-auto">
                                    <a href="#" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 border-b border-gray-100">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 bg-red-100 rounded-full p-1 text-red-600">
                                               
                                            </div>
                                           
                                        </div>
                                    </a>
                                    <a href="#" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 border-b border-gray-100">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 bg-green-100 rounded-full p-1 text-green-600">
                                               
                                            </div>
                                           
                                        </div>
                                    </a>
                                    
                                    </a>
                                </div>
                               
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="h-9 w-9 rounded-full bg-red-100 flex items-center justify-center">
                                <span class="text-red-600 font-medium"><?php echo substr($patientName, 0, 1); ?></span>
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
                <div class="flex items-center space-x-4">
                    <button id="mobile-notification-button" class="p-2 rounded-full text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none">
                        <i class="fas fa-bell"></i>
                        <?php if (rand(0, 3) > 0): ?>
                        <span class="absolute top-3 right-3 h-2 w-2 rounded-full bg-red-500"></span>
                        <?php endif; ?>
                    </button>
                    <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center">
                        <span class="text-red-600 font-medium text-sm"><?php echo substr($patientName, 0, 1); ?></span>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden bg-white shadow-md">
                <div class="px-4 py-4 space-y-2">
                    <a href="#" class="block px-3 py-2 rounded-md bg-red-50 text-red-700 font-medium">Dashboard</a>
                    <a href="request_blood.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Request Blood</a>
                    <a href="request_history.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Request History</a>
                    <a href="patient_inbox.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Messages</a>
                    <a href="patient_profile.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Profile Settings</a>
                    <a href="patient_logout.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Sign Out</a>
                </div>
            </div>

            <!-- Dashboard Content -->
            <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
                <!-- Welcome Banner -->
                <div class="bg-gradient-to-r from-red-600 to-red-500 rounded-xl shadow-sm overflow-hidden mb-8">
                    <div class="p-6 md:p-8 text-white">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 class="text-2xl font-bold">Welcome, <?php echo htmlspecialchars($patientName); ?>!</h2>
                                <p class="mt-1 text-red-100">We're here to help you find the blood you need</p>
                            </div>
                            <div class="mt-4 md:mt-0 flex items-center">
                                <div class="blood-type-badge bg-white/10 rounded-full px-4 py-2 flex items-center">
                                    <span class="text-lg font-bold text-red-700"><?php echo htmlspecialchars($bloodType ?: 'Not set'); ?></span>
                                    <span class="ml-2 text-sm">Blood Type</span>
                                    <?php if (!$bloodType): ?>
                                    <a href="patient_profile.php" class="ml-2 text-xs text-white underline">Set your blood type</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="dashboard-card bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-50 text-red-600">
                                <i class="fas fa-tint text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Blood Requests</h3>
                                <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo $requestCount; ?></p>
                                <p class="mt-1 text-xs text-gray-500"><?php echo $requestCount == 1 ? '1 active request' : $requestCount . ' active requests'; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-card bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-50 text-blue-600">
                                <i class="fas fa-check-circle text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Completed Requests</h3>
                                <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo $completedCount; ?></p>
                                <p class="mt-1 text-xs text-gray-500"><?php echo $completedCount == 1 ? '1 successful match' : $completedCount . ' successful matches'; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-card bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-50 text-green-600">
                                <i class="fas fa-heart text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Potential Donors</h3>
                                <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo $donorCount; ?></p>
                                <p class="mt-1 text-xs text-gray-500">Compatible with your blood type</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mb-8">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="request_blood.php" class="dashboard-card bg-white rounded-lg shadow-sm border border-gray-100 p-5 text-center hover:border-red-200 hover:bg-red-50 transition-colors">
                            <div class="mx-auto h-12 w-12 rounded-full bg-red-100 flex items-center justify-center text-red-600 mb-3">
                                <i class="fas fa-hand-holding-medical text-xl"></i>
                            </div>
                            <h3 class="text-sm font-medium text-gray-900">Request Blood</h3>
                            <p class="mt-1 text-xs text-gray-500">Submit a new blood request</p>
                        </a>
                        
                        <a href="request_history.php" class="dashboard-card bg-white rounded-lg shadow-sm border border-gray-100 p-5 text-center hover:border-purple-200 hover:bg-purple-50 transition-colors">
                            <div class="mx-auto h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mb-3">
                                <i class="fas fa-history text-xl"></i>
                            </div>
                            <h3 class="text-sm font-medium text-gray-900">Request History</h3>
                            <p class="mt-1 text-xs text-gray-500">View past requests</p>
                        </a>
                        <a href="edit_patient_profile.php" class="function-card bg-white rounded-lg shadow-sm border border-gray-100 p-5 text-center hover:border-green-200 hover:bg-green-50 transition-colors">
                            <div class="mx-auto h-12 w-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 mb-3">
                                <i class="fas fa-user-edit text-xl"></i>
                            </div>
                            <h3 class="text-sm font-medium text-gray-900">Update Profile</h3>
                            <p class="mt-1 text-xs text-gray-500">Edit your information</p>
                        </a>
                         <a href="patient_inbox.php" class="function-card bg-white rounded-lg shadow-sm border border-gray-100 p-5 text-center hover:border-purple-200 hover:bg-purple-50 transition-colors">
                            <div class="mx-auto h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mb-3">
                                <i class="fas fa-envelope text-xl"></i>
                            </div>
                            <h3 class="text-sm font-medium text-gray-900">Messages</h3>
                            <p class="mt-1 text-xs text-gray-500">Check your inbox</p>
                        </a>
                    </div>
                </div>

               

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });

        // Notification dropdown toggle
        document.getElementById('notification-button').addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('notification-dropdown');
            dropdown.classList.toggle('hidden');
        });

        // Mobile notification button
        document.getElementById('mobile-notification-button').addEventListener('click', function() {
            alert('You have new notifications. Please check the desktop view for details.');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            const dropdown = document.getElementById('notification-dropdown');
            if (!dropdown.classList.contains('hidden')) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
<?php
// Helper function to display time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>