<?php
session_start();
include 'config.php';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Manual mapping: blood_group_id => blood group name
$bloodGroups = [
    1 => 'A+',
    2 => 'A-',
    3 => 'B+',
    4 => 'B-',
    5 => 'AB+',
    6 => 'AB-',
    7 => 'O+',
    8 => 'O-',
];

// Initialize variables
$bloodStocks = [];
$totalUnits = 0;
$donorCount = 0;
$requestCount = 0;
$approvedCount = 0;

// Notification counts and data
$pendingDonorRequests = 0;
$pendingPatientRequests = 0;
$expiringSoonCount = 0;
$expiringSoonDetails = [];

// Fetch blood stock data with error handling
try {
    // Blood Stock Data
    $result = $conn->query("SELECT blood_group_id, SUM(total_quantity) AS total FROM blood_stock GROUP BY blood_group_id");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $groupId = $row['blood_group_id'];
            $groupName = $bloodGroups[$groupId] ?? 'Unknown';
            $quantity = (int)$row['total'];
            $bloodStocks[$groupName] = $quantity;
            $totalUnits += $quantity;
        }
    }

    // Count Data
    $donorResult = $conn->query("SELECT COUNT(*) AS count FROM donors");
    $donorCount = $donorResult ? $donorResult->fetch_assoc()['count'] : 0;

    $requestResult = $conn->query("SELECT COUNT(*) AS count FROM blood_donation_requests");
    $requestCount = $requestResult ? $requestResult->fetch_assoc()['count'] : 0;

    $approvedResult = $conn->query("SELECT COUNT(*) AS count FROM blood_donation_requests WHERE status = 'approved'");
    $approvedCount = $approvedResult ? $approvedResult->fetch_assoc()['count'] : 0;

    // Notification counts
    $pendingDonorResult = $conn->query("SELECT COUNT(*) AS count FROM blood_donation_requests WHERE status = 'pending'");
    $pendingDonorRequests = $pendingDonorResult ? $pendingDonorResult->fetch_assoc()['count'] : 0;

    $pendingPatientResult = $conn->query("SELECT COUNT(*) AS count FROM requests WHERE status = 'pending'");
    $pendingPatientRequests = $pendingPatientResult ? $pendingPatientResult->fetch_assoc()['count'] : 0;

    // Blood expiring soon (within 7 days) with details
    $expiringResult = $conn->query("
    SELECT bs.id, bs.blood_group_id, bs.expire_date, bs.total_quantity, bg.group_name
    FROM blood_stock bs
    JOIN blood_groups bg ON bs.blood_group_id = bg.id
    WHERE bs.expire_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY bs.expire_date ASC
");

$expiringResult = $conn->query("
    SELECT bs.id, bs.blood_group_id, bs.expire_date, bs.total_quantity, bg.group_name
    FROM blood_stock bs
    JOIN blood_groups bg ON bs.blood_group_id = bg.id
    WHERE bs.expire_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY bs.expire_date ASC
");

if ($expiringResult) {
    $expiringSoonCount = $expiringResult->num_rows;
    $expiringSoonDetails = [];
    
    while ($row = $expiringResult->fetch_assoc()) {
        $expiringSoonDetails[] = [
            'id' => $row['id'],
            'group' => $row['group_name'],
            'quantity' => $row['total_quantity'],
            'expiry_date' => date('M j, Y', strtotime($row['expire_date'])),
            'days_left' => ceil((strtotime($row['expire_date']) - time()) / (60 * 60 * 24))
        ];
    }
}
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

// Calculate total notifications
$totalNotifications = $pendingDonorRequests + $pendingPatientRequests + $expiringSoonCount;
?>

<?php
// First get the count of pending requests
$pendingRequestsCount = 0;
$pendingResult = $conn->query("SELECT COUNT(*) as count FROM requests WHERE status = 'Pending'");
if ($pendingResult) {
    $row = $pendingResult->fetch_assoc();
    $pendingRequestsCount = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | BloodBank</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
        }
        
        .glass-sidebar {
            background: rgba(185, 28, 28, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }
        
        .nav-item {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .nav-item:hover {
            transform: translateX(5px);
        }
        
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: white;
            border-radius: 0 4px 4px 0;
        }
        
        .blood-card {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        
        .blood-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .dropdown-content {
            transition: all 0.3s ease;
            max-height: 0;
            overflow: hidden;
        }
        
        .dropdown-content.show {
            max-height: 500px;
        }
        .critical-stock {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }
        
        .notification-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
            width: 320px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            z-index: 50;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .notification-dropdown.show {
            max-height: 400px;
            padding: 8px 0;
        }
        
        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .notification-item:hover {
            background-color: #f9fafb;
        }
        
        .notification-item.unread {
            background-color: #fef2f2;
        }
        
        .notification-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #ef4444;
            display: inline-block;
            margin-right: 8px;
        }
        
        .expiry-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
        }
        
        .expiry-critical {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .expiry-warning {
            background-color: #fef3c7;
            color: #b45309;
        }
        
        .expiry-normal {
            background-color: #ecfdf5;
            color: #065f46;
        }
    </style>
</head>
<body class="bg-gray-50">
<div class="flex h-screen overflow-hidden">
    <!-- Glass Sidebar -->
    <div class="glass-sidebar text-white w-72 py-8 px-4 fixed inset-y-0 left-0 transform -translate-x-full md:translate-x-0 transition duration-300 z-50">
        <!-- Logo -->
        <div class="flex items-center justify-center mb-10">
            <div class="bg-white p-3 rounded-xl shadow-lg mr-3">
                <i class="fas fa-heartbeat text-2xl text-red-600"></i>
            </div>
            <h1 class="text-2xl font-bold">Blood<span class="text-white">Bank</span></h1>
        </div>
        
        <!-- Navigation -->
        <nav class="space-y-2">
            <a href="admin_dashboard.php" class="nav-item active flex items-center py-3 px-4 rounded-lg bg-white bg-opacity-10">
                <i class="fas fa-chart-pie mr-3 text-lg"></i>
                <span class="font-medium">Dashboard</span>
            </a>
            
            <a href="donors.php" class="nav-item flex items-center py-3 px-4 rounded-lg hover:bg-white hover:bg-opacity-10">
                <i class="fas fa-user-friends mr-3 text-lg"></i>
                <span class="font-medium">Donors</span>
            </a>
            
            <a href="patients.php" class="nav-item flex items-center py-3 px-4 rounded-lg hover:bg-white hover:bg-opacity-10">
                <i class="fas fa-procedures mr-3 text-lg"></i>
                <span class="font-medium">Patients</span>
            </a>
            
            <!-- Blood Management Dropdown -->
            <div class="group">
                <button class="nav-item w-full flex items-center justify-between py-3 px-4 rounded-lg hover:bg-white hover:bg-opacity-10">
                    <div class="flex items-center">
                        <i class="fas fa-hand-holding-medical mr-3 text-lg"></i>
                        <span class="font-medium">Blood Management</span>
                    </div>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-[.active]:rotate-180"></i>
                </button>
                <div class="dropdown-content ml-8 mt-1 space-y-1">
                    <a href="blood_groups.php" class="block py-2 px-4 rounded-lg hover:bg-white hover:bg-opacity-10">Blood Groups</a>
                    <a href="blood_stock.php" class="block py-2 px-4 rounded-lg hover:bg-white hover:bg-opacity-10">Blood Stock</a>
                </div>
            </div>
            
            <!-- Requests Dropdown -->
            <div class="group">
                <button class="nav-item w-full flex items-center justify-between py-3 px-4 rounded-lg hover:bg-white hover:bg-opacity-10">
                    <div class="flex items-center">
                        <i class="fas fa-hand-holding-heart mr-3 text-lg"></i>
                        <span class="font-medium">Requests</span>
                    </div>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-[.active]:rotate-180"></i>
                </button>
                <div class="dropdown-content ml-8 mt-1 space-y-1">
                    <a href="admin_manage_requests.php" class="block py-2 px-4 rounded-lg hover:bg-white hover:bg-opacity-10">Donor Requests</a>
                    <a href="patient_requests.php" class="block py-2 px-4 rounded-lg hover:bg-white hover:bg-opacity-10">Patient Requests</a>
                </div>
            </div>
            
            <a href="blood_tests.php" class="nav-item flex items-center py-3 px-4 rounded-lg ">
                <i class="fas fa-flask mr-3 text-lg"></i>
                <span class="font-medium">Blood Tests</span>
            </a>
            <a href="admin_signup.php" class="nav-item flex items-center py-3 px-4 rounded-lg hover:bg-white hover:bg-opacity-10">
                <i class="fas fa-user-shield mr-3 text-lg"></i>
                <span class="font-medium">Add Admin</span>
            </a>
            
            <div class="absolute bottom-8 left-0 right-0 px-4">
                <a href="logouta.php" class="nav-item flex items-center py-3 px-4 rounded-lg bg-red-700 hover:bg-red-800 transition-colors">
                    <i class="fas fa-sign-out-alt mr-3 text-lg"></i>
                    <span class="font-medium">Logout</span>
                </a>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-1 md:ml-72 overflow-y-auto">
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                <!-- Mobile menu button (left side) -->
                <div class="flex items-center md:hidden">
                    <button id="menu-toggle" class="text-gray-500 hover:text-red-600 focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
                
                <!-- Right-aligned items -->
                <div class="flex items-center space-x-4 ml-auto">
                    <!-- Notification button -->
                    <div class="relative">
                        <button id="notification-btn" class="text-gray-500 hover:text-red-600 relative">
                            <i class="fas fa-bell text-xl"></i>
                            <?php if ($totalNotifications > 0): ?>
                                <span class="notification-badge"><?= $totalNotifications > 9 ? '9+' : $totalNotifications ?></span>
                            <?php endif; ?>
                        </button>
                        <!-- Notification dropdown -->
                        <div id="notification-dropdown" class="notification-dropdown">
                            <div class="px-4 py-2 border-b border-gray-200">
                                <h3 class="font-medium text-gray-800">Notifications</h3>
                            </div>
                            <div class="overflow-y-auto max-h-72">
                                <?php if ($pendingDonorRequests > 0): ?>
                                <a href="admin_manage_requests.php" class="notification-item unread block">
                                    <div class="flex items-start">
                                        <div class="bg-red-100 p-2 rounded-lg mr-3">
                                            <i class="fas fa-hand-holding-heart text-red-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium">Pending Donor Requests</p>
                                            <p class="text-gray-500 text-sm">You have <?= $pendingDonorRequests ?> pending donor requests to review</p>
                                            <p class="text-gray-400 text-xs mt-1">Click to view</p>
                                        </div>
                                    </div>
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($pendingPatientRequests > 0): ?>
                                <a href="patient_requests.php" class="notification-item unread block">
                                    <div class="flex items-start">
                                        <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                            <i class="fas fa-procedures text-blue-600"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium">Pending Patient Requests</p>
                                            <p class="text-gray-500 text-sm"><?= $pendingPatientRequests ?> patient requests awaiting approval</p>
                                            <p class="text-gray-400 text-xs mt-1">Click to view</p>
                                        </div>
                                    </div>
                                </a>
                                <?php endif; ?>
                                
                              <?php if ($expiringSoonCount > 0): ?>
    <?php foreach ($expiringSoonDetails as $expiring): ?>
    <a href="blood_stock.php" class="notification-item unread block">
        <div class="flex items-start">
            <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                <i class="fas fa-exclamation-triangle text-yellow-600"></i>
            </div>
            <div>
                <p class="font-medium">Blood Expiring Soon</p>
                <p class="text-gray-500 text-sm">
                    <?= $expiring['quantity'] ?>ml of <?= $expiring['group'] ?> (ID #<?= $expiring['id'] ?>)
                </p>
                <div class="flex items-center mt-1">
                    <span class="text-xs <?= 
                        $expiring['days_left'] <= 3 ? 'text-red-600' : 
                        ($expiring['days_left'] <= 5 ? 'text-yellow-600' : 'text-gray-500') 
                    ?>">
                        <i class="far fa-clock mr-1"></i>
                        Expires in <?= $expiring['days_left'] ?> day<?= $expiring['days_left'] != 1 ? 's' : '' ?> (<?= $expiring['expiry_date'] ?>)
                    </span>
                </div>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
<?php endif; ?>
                                
                                <?php if ($totalNotifications == 0): ?>
                                <div class="notification-item">
                                    <div class="text-center py-4 text-gray-500">
                                        <i class="far fa-bell-slash text-2xl mb-2"></i>
                                        <p>No new notifications</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="px-4 py-2 border-t border-gray-200 text-center">
                                <a href="#" class="text-sm text-red-600 font-medium">Mark all as read</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Profile picture and info -->
                    <div class="flex items-center space-x-2">
                        <img class="h-9 w-9 rounded-full border-2 border-red-200" src="https://randomuser.me/api/portraits/women/44.jpg" alt="Admin">
                        <div class="hidden md:block">
                            <p class="text-sm font-medium text-gray-700">Admin</p>
                            <p class="text-xs text-gray-500">Super Admin</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Welcome Banner with Alerts -->
            <div class="bg-gradient-to-r from-red-600 to-red-800 rounded-xl shadow-lg p-6 mb-8 text-white relative overflow-hidden">
                <!-- Alert ribbons -->
                <?php if ($pendingDonorRequests > 0 || $pendingPatientRequests > 0 || $expiringSoonCount > 0): ?>
                <div class="absolute top-0 right-0 flex space-x-2 p-3">
                    <?php if ($pendingDonorRequests > 0): ?>
                    <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-xs font-medium flex items-center">
                        <i class="fas fa-hand-holding-heart mr-1"></i> <?= $pendingDonorRequests ?> donor requests
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($pendingPatientRequests > 0): ?>
                    <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-xs font-medium flex items-center">
                        <i class="fas fa-procedures mr-1"></i> <?= $pendingPatientRequests ?> patient requests
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($expiringSoonCount > 0): ?>
                    <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-xs font-medium flex items-center">
                        <i class="fas fa-exclamation-triangle mr-1"></i> <?= $expiringSoonCount ?> expiring soon
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold mb-2">Welcome back, Admin!</h1>
                        <p class="opacity-90">Here's what's happening with your blood bank today.</p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <span class="bg-white bg-opacity-20 px-4 py-2 rounded-full text-sm font-medium">Last login: <?= date('M j, Y g:i A') ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow p-6 flex items-start">
                    <div class="bg-blue-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Donors</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?= $donorCount ?></h3>
                        <p class="text-green-500 text-xs mt-1"><i class="fas fa-arrow-up mr-1"></i> 12% from last month</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow p-6 flex items-start">
                    <div class="bg-purple-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-hand-holding-medical text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Requests</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?= $requestCount ?></h3>
                        <p class="text-green-500 text-xs mt-1"><i class="fas fa-arrow-up mr-1"></i> 8% from last month</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow p-6 flex items-start">
                    <div class="bg-red-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-tint text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Blood Stock</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?= $totalUnits ?> ml</h3>
                        <p class="text-red-500 text-xs mt-1"><i class="fas fa-arrow-down mr-1"></i> 5% from last week</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow p-6 flex items-start">
                    <div class="bg-green-100 p-3 rounded-lg mr-4">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Approved Requests</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?= $approvedCount ?></h3>
                        <p class="text-green-500 text-xs mt-1"><i class="fas fa-arrow-up mr-1"></i> 15% from last month</p>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="lg:col-span-2 bg-white rounded-xl shadow p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Blood Stock Overview</h2>
                        <select class="bg-gray-100 border-0 rounded-lg px-3 py-1 text-sm focus:ring-2 focus:ring-red-500">
                            <option>This Month</option>
                            <option>Last Month</option>
                            <option>This Year</option>
                        </select>
                    </div>
                    <canvas id="bloodStockChart" height="250"></canvas>
                </div>
                
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Quick Actions</h2>
                    <div class="space-y-3">
                        <a href="admin_manage_requests.php" class="flex items-center p-3 rounded-lg bg-gray-50 hover:bg-red-50 transition-colors">
                            <div class="bg-red-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-hand-holding-heart text-red-600"></i>
                            </div>
                            <span>Review Pending Requests donor</span>
                            <?php if ($pendingDonorRequests > 0): ?>
                            <span class="ml-auto bg-red-100 text-red-800 text-xs font-medium px-2 py-0.5 rounded-full">
                                <?= $pendingDonorRequests ?> 
                            </span>
                            <?php endif; ?>
                        </a>
                        <a href="blood_stock.php" class="flex items-center p-3 rounded-lg bg-gray-50 hover:bg-red-50 transition-colors">
                            <div class="bg-red-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-tint text-red-600"></i>
                            </div>
                            <span>Review Blood Stock</span>
                            <?php if ($expiringSoonCount > 0): ?>
                            <span class="ml-auto bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-0.5 rounded-full">
                                <?= $expiringSoonCount ?> expiring
                            </span>
                            <?php endif; ?>
                        </a>
                        <a href="patient_requests.php" class="flex items-center p-3 rounded-lg bg-gray-50 hover:bg-red-50 transition-colors relative">
    
                        <div class="bg-red-100 p-2 rounded-lg mr-3">
        <i class="fas fa-user-plus text-red-600"></i>
    </div>
    <span>Review pending requests Patients</span>
    
    <?php if ($pendingRequestsCount > 0): ?>
        <span class="ml-auto bg-red-100 text-red-800 text-xs font-medium px-2 py-0.5 rounded-full">
            <?= $pendingRequestsCount ?>
        </span>
    <?php endif; ?>
</a>
                        <a href="admin_reports.php" class="flex items-center p-3 rounded-lg bg-gray-50 hover:bg-red-50 transition-colors">
                            <div class="bg-red-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-file-alt text-red-600"></i>
                            </div>
                            <span>Analytic Report</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Blood Group Cards -->
            <div class="bg-white rounded-xl shadow p-6 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Blood Group Availability</h2>
                    <span class="text-sm text-gray-500">Last updated: <?= date('M j, Y g:i A') ?></span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <?php 
                    // Ensure we show all blood groups, even if stock is zero
                    foreach ($bloodGroups as $id => $group): 
                        $amount = $bloodStocks[$group] ?? 0;
                        
                        // Determine card styling based on amount
                        $bgColor = 'bg-gray-100';
                        $textColor = 'text-gray-800';
                        $criticalClass = '';
                        
                        if ($amount < 500) {
                            $bgColor = 'bg-red-100';
                            $textColor = 'text-red-800';
                            $criticalClass = 'critical-stock';
                        } elseif ($amount < 1000) {
                            $bgColor = 'bg-red-100';
                            $textColor = 'text-red-800';
                        } elseif ($amount < 2000) {
                            $bgColor = 'bg-yellow-100';
                            $textColor = 'text-yellow-800';
                        } else {
                            $bgColor = 'bg-green-100';
                            $textColor = 'text-green-800';
                        }
                    ?>
                    <div class="blood-card <?= $criticalClass ?> bg-white rounded-lg border border-gray-100 p-4 text-center hover:border-red-200 transition-all duration-300">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-full <?= $bgColor ?> flex items-center justify-center">
                            <i class="fas fa-tint text-xl <?= $textColor ?>"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800"><?= $group ?></h3>
                        <p class="text-gray-600 text-sm">Available Stock</p>
                        <div class="mt-2 px-3 py-1 rounded-full text-sm font-medium <?= $bgColor ?> <?= $textColor ?>">
                            <?= number_format($amount) ?> ml
                        </div>
                        <?php if ($amount < 200): ?>
                        <div class="mt-2 text-xs text-red-600 font-medium">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Critical Level
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Recent Activity</h2>
                <div class="space-y-4">
                    <div class="flex items-start pb-4 border-b border-gray-100">
                        <div class="bg-red-100 p-2 rounded-lg mr-3">
                            <i class="fas fa-tint text-red-600"></i>
                        </div>
                        <div>
                            <p class="font-medium">New blood donation received</p>
                            <p class="text-gray-500 text-sm">A+ (450ml) from donor #D-1024</p>
                            <p class="text-gray-400 text-xs mt-1">2 hours ago</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start pb-4 border-b border-gray-100">
                        <div class="bg-green-100 p-2 rounded-lg mr-3">
                            <i class="fas fa-check-circle text-green-600"></i>
                        </div>
                        <div>
                            <p class="font-medium">Request approved</p>
                            <p class="text-gray-500 text-sm">Patient request #R-2048 for O+</p>
                            <p class="text-gray-400 text-xs mt-1">Yesterday</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="bg-purple-100 p-2 rounded-lg mr-3">
                            <i class="fas fa-file-alt text-purple-600"></i>
                        </div>
                        <div>
                            <p class="font-medium">Monthly report generated</p>
                            <p class="text-gray-500 text-sm">June 2023 blood bank report</p>
                            <p class="text-gray-400 text-xs mt-1">2 days ago</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Toggle mobile menu
    document.getElementById('menu-toggle').addEventListener('click', function() {
        document.querySelector('.glass-sidebar').classList.toggle('-translate-x-full');
    });

    // Toggle dropdown menus
    document.querySelectorAll('.group button').forEach(button => {
        button.addEventListener('click', function() {
            const menu = this.nextElementSibling;
            menu.classList.toggle('show');
            this.parentElement.classList.toggle('active');
        });
    });

    // Toggle notification dropdown
    const notificationBtn = document.getElementById('notification-btn');
    const notificationDropdown = document.getElementById('notification-dropdown');
    
    notificationBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown.classList.toggle('show');
    });
    
    // Close notification dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!notificationBtn.contains(e.target) && !notificationDropdown.contains(e.target)) {
            notificationDropdown.classList.remove('show');
        }
    });

    // Blood Stock Chart
    const ctx = document.getElementById('bloodStockChart').getContext('2d');
    const bloodStockChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($bloodStocks)) ?>,
            datasets: [{
                label: 'Blood Stock (ml)',
                data: <?= json_encode(array_values($bloodStocks)) ?>,
                backgroundColor: [
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(239, 68, 68, 0.7)'
                ],
                borderColor: [
                    'rgba(239, 68, 68, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(239, 68, 68, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' ml';
                        }
                    }
                }
            }
        }
    });
</script>
</body>
</html>