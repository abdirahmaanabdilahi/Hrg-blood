<?php
session_start();
include 'config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['donor_id'])) {
    header("Location: donor_login.php");
    exit();
}

// Get donor full name
$donor_id = $_SESSION['donor_id'];
$donorName = "Donor";

$stmt = $conn->prepare("SELECT full_name FROM donors WHERE id = ?");
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$stmt->bind_result($donorName);
$stmt->fetch();
$stmt->close();

// Get last donation date
$daysSinceDonation = "Never donated"; // Default value
$lastDonationDate = null;
$totalDonations = 0;

$stmt = $conn->prepare("SELECT requested_date FROM blood_donation_requests 
                        WHERE donor_id = ? AND status = 'Approved' 
                        ORDER BY requested_date DESC LIMIT 1");
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$stmt->bind_result($lastDonationDate);
$stmt->fetch();
$stmt->close();

if ($lastDonationDate) {
    $now = new DateTime();
    $lastDate = new DateTime($lastDonationDate);
    $interval = $now->diff($lastDate);
    $daysSinceDonation = $interval->days;
}

// Get total donations count
$stmt = $conn->prepare("SELECT COUNT(*) FROM blood_donation_requests 
                       WHERE donor_id = ? AND status = 'Approved'");
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$stmt->bind_result($totalDonations);
$stmt->fetch();
$stmt->close();

// Get donor information including blood type
$donor_info = [];
$stmt = $conn->prepare("SELECT d.*, bg.group_name AS blood_type 
                       FROM donors d 
                       LEFT JOIN blood_groups bg ON d.blood_group_id = bg.id 
                       WHERE d.id = ?");
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();
$donor_info = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Portal - BloodLink</title>
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
        .function-card {
            transition: all 0.3s ease;
        }
        .function-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
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
                <a href="request_donation.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-hand-holding-medical mr-3 text-gray-500"></i>
                    Donate Blood
                </a>
                <a href="view_requests.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-history mr-3 text-gray-500"></i>
                    Donation History
                </a>
                <a href="donor_inbox.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-inbox mr-3 text-gray-500"></i>
                    Messages
                    <span class="ml-auto bg-red-100 text-red-800 text-xs font-semibold px-2 py-0.5 rounded-full">3</span>
                </a>
                <a href="update_profile_donor.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-user-cog mr-3 text-gray-500"></i>
                    Profile Settings
                </a>
            </nav>
            <div class="p-4 border-t border-gray-200 mt-auto">
                <a href="donor_logout.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100">
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
                    <h1 class="text-lg font-semibold text-gray-900">Donor Dashboard</h1>
                    <div class="flex items-center space-x-4">
                        <button class="p-2 rounded-full text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none">
                            <i class="fas fa-bell"></i>
                        </button>
                        <div class="flex items-center">
                            <div class="h-9 w-9 rounded-full bg-red-100 flex items-center justify-center">
                                <span class="text-red-600 font-medium"><?php echo substr($donorName, 0, 1); ?></span>
                            </div>
                            <span class="ml-2 text-sm font-medium text-gray-700 hidden md:inline"><?php echo htmlspecialchars($donorName); ?></span>
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
                        <span class="text-red-600 font-medium text-sm"><?php echo substr($donorName, 0, 1); ?></span>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden bg-white shadow-md">
                <div class="px-4 py-4 space-y-2">
                    <a href="#" class="block px-3 py-2 rounded-md bg-red-50 text-red-700 font-medium">Dashboard</a>
                    <a href="request_donation.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Donate Blood</a>
                    <a href="view_requests.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Donation History</a>
                    <a href="donor_inbox.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Messages</a>
                    <a href="update_profile_donor.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Profile Settings</a>
                    <a href="donor_logout.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Sign Out</a>
                </div>
            </div>

            <!-- Dashboard Content -->
            <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
                <!-- Welcome Banner -->
                <div class="bg-gradient-to-r from-red-600 to-red-500 rounded-xl shadow-sm overflow-hidden mb-8">
                    <div class="p-6 md:p-8 text-white">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 class="text-2xl font-bold">Welcome back, <?php echo htmlspecialchars($donorName); ?>!</h2>
                                <p class="mt-1 text-red-100">Thank you for being a life-saving donor</p>
                            </div>
                            <div class="mt-4 md:mt-0 flex items-center">
                                <div class="blood-type-badge bg-white/10 rounded-full px-4 py-2 flex items-center">
                                    <span class="text-lg font-bold text-red-700">
                                        <?php echo htmlspecialchars($donor_info['blood_type'] ?? 'Not set'); ?>
                                    </span>
                                    <span class="ml-2 text-sm">Blood Type</span>
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
                                <h3 class="text-sm font-medium text-gray-500">Total Donations</h3>
                                <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo $totalDonations; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-card bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-50 text-blue-600">
                                <i class="fas fa-calendar-check text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Last Donation</h3>
                                <p class="mt-1 text-2xl font-semibold text-gray-900">
                                    <?php 
                                    if ($lastDonationDate) {
                                        if ($daysSinceDonation == 0) {
                                            echo 'Today';
                                        } elseif ($daysSinceDonation == 1) {
                                            echo 'Yesterday';
                                        } else {
                                            echo $daysSinceDonation . ' days ago';
                                        }
                                    } else {
                                        echo 'Never donated';
                                    }
                                    ?>
                                </p>
                                <?php if ($lastDonationDate): ?>
                                <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, Y', strtotime($lastDonationDate)); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-card bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-50 text-green-600">
                                <i class="fas fa-heart text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Lives Saved</h3>
                                <p class="mt-1 text-2xl font-semibold text-gray-900"><?php echo $totalDonations * 3; ?>+</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Function Cards -->
                <div class="mb-8">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="request_donation.php" class="function-card bg-white rounded-lg shadow-sm border border-gray-100 p-5 text-center hover:border-red-200 hover:bg-red-50 transition-colors">
                            <div class="mx-auto h-12 w-12 rounded-full bg-red-100 flex items-center justify-center text-red-600 mb-3">
                                <i class="fas fa-hand-holding-medical text-xl"></i>
                            </div>
                            <h3 class="text-sm font-medium text-gray-900">Donate Blood</h3>
                            <p class="mt-1 text-xs text-gray-500">Request a new donation</p>
                        </a>
                        <a href="view_requests.php" class="function-card bg-white rounded-lg shadow-sm border border-gray-100 p-5 text-center hover:border-blue-200 hover:bg-blue-50 transition-colors">
                            <div class="mx-auto h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mb-3">
                                <i class="fas fa-history text-xl"></i>
                            </div>
                            <h3 class="text-sm font-medium text-gray-900">Donation History</h3>
                            <p class="mt-1 text-xs text-gray-500">View your donation records</p>
                        </a>
                        <a href="update_profile_donor.php" class="function-card bg-white rounded-lg shadow-sm border border-gray-100 p-5 text-center hover:border-green-200 hover:bg-green-50 transition-colors">
                            <div class="mx-auto h-12 w-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 mb-3">
                                <i class="fas fa-user-edit text-xl"></i>
                            </div>
                            <h3 class="text-sm font-medium text-gray-900">Update Profile</h3>
                            <p class="mt-1 text-xs text-gray-500">Edit your information</p>
                        </a>
                        <a href="donor_inbox.php" class="function-card bg-white rounded-lg shadow-sm border border-gray-100 p-5 text-center hover:border-purple-200 hover:bg-purple-50 transition-colors">
                            <div class="mx-auto h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mb-3">
                                <i class="fas fa-envelope text-xl"></i>
                            </div>
                            <h3 class="text-sm font-medium text-gray-900">Messages</h3>
                            <p class="mt-1 text-xs text-gray-500">Check your inbox</p>
                        </a>
                    </div>
                </div>

                <!-- Donation Eligibility Section -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden mb-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Donation Eligibility</h2>
                    </div>
                    <div class="p-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-900">Next Eligible Donation Date</h3>
                                <?php if ($lastDonationDate): ?>
                                    <?php
                                    $nextDonationDate = new DateTime($lastDonationDate);
                                    $nextDonationDate->add(new DateInterval('P56D')); // Add 56 days (8 weeks)
                                    $today = new DateTime();
                                    $isEligible = $today >= $nextDonationDate;
                                    ?>
                                    <p class="mt-1 text-sm text-gray-500">
                                        <?php if ($isEligible): ?>
                                            <span class="text-green-600 font-medium">You are eligible to donate now!</span>
                                        <?php else: ?>
                                            You can donate again on <span class="font-medium"><?php echo $nextDonationDate->format('F j, Y'); ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="mt-2 text-xs text-gray-400">
                                        Minimum interval between donations is 8 weeks (56 days).
                                    </p>
                                <?php else: ?>
                                    <p class="mt-1 text-sm text-gray-500">
                                        <span class="text-green-600 font-medium">You are eligible to donate!</span> You haven't donated before.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Blood Donation Tips -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Blood Donation Tips</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-8 w-8 rounded-full bg-red-100 flex items-center justify-center text-red-600 mt-1">
                                    <i class="fas fa-check text-xs"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-gray-900">Before Donation</h3>
                                    <ul class="mt-2 text-sm text-gray-500 list-disc list-inside space-y-1">
                                        <li>Get a good night's sleep</li>
                                        <li>Eat iron-rich foods (red meat, fish, poultry)</li>
                                        <li>Drink extra 16 oz of water before donating</li>
                                        <li>Avoid alcohol 24 hours before donation</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mt-1">
                                    <i class="fas fa-check text-xs"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-gray-900">After Donation</h3>
                                    <ul class="mt-2 text-sm text-gray-500 list-disc list-inside space-y-1">
                                        <li>Drink extra fluids for 24-48 hours</li>
                                        <li>Avoid strenuous activity for 5 hours</li>
                                        <li>Eat iron-rich meals for 2 weeks</li>
                                        <li>Keep bandage on for several hours</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
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