<?php
include 'config.php';
session_start();
if (!isset($_SESSION['donor_id'])) {
    header("Location: donor_login.php");
    exit;
}
$donor_id = $_SESSION['donor_id'] ?? $_GET['donor_id'] ?? $_POST['donor_id'] ?? null;
$message = "";
$alertType = "error";

// Get donor full name
$donorName = "Donor";
$donor_blood_group = "";
$lastAcceptedDateDisplay = "N/A";

if ($donor_id) {
    // Get donor name
    $stmt = $conn->prepare("SELECT full_name FROM donors WHERE id = ?");
    $stmt->bind_param("i", $donor_id);
    $stmt->execute();
    $stmt->bind_result($donorName);
    $stmt->fetch();
    $stmt->close();

    // Get blood group
    $stmt = $conn->prepare("
        SELECT bg.group_name 
        FROM donors d 
        JOIN blood_groups bg ON d.blood_group_id = bg.id 
        WHERE d.id = ?
    ");
    $stmt->bind_param("i", $donor_id);
    $stmt->execute();
    $stmt->bind_result($donor_blood_group);
    $stmt->fetch();
    $stmt->close();
}

$eligible = true;

// 1. Check last donation and apply restriction
if ($donor_id) {
    $stmt = $conn->prepare("
        SELECT requested_date, status 
        FROM blood_donation_requests 
        WHERE donor_id = ? 
        ORDER BY requested_date DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $donor_id);
    $stmt->execute();
    $stmt->bind_result($lastDate, $lastStatus);
    $stmt->fetch();
    $stmt->close();

    if ($lastDate) {
        $lastAcceptedDateDisplay = $lastDate;
        $lastDateTime = new DateTime($lastDate);
        $now = new DateTime();
        $intervalInDays = $now->diff($lastDateTime)->days;

        if ($lastStatus === 'Accepted' && $intervalInDays < 90) {
            $eligible = false;
            $message = "You already donated in the last 3 months. Please wait until " . $lastDateTime->modify('+90 days')->format('M j, Y');
        } elseif ($lastStatus !== 'Accepted' && $intervalInDays < 30) {
            $eligible = false;
            $message = "You can only request once every 30 days. Next available date: " . $lastDateTime->modify('+30 days')->format('M j, Y');
        }
    }
}

// 2. Check same-day request
if ($eligible && $donor_id) {
    $today = date("Y-m-d");
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM blood_donation_requests 
        WHERE donor_id = ? AND requested_date = ?
    ");
    $stmt->bind_param("is", $donor_id, $today);
    $stmt->execute();
    $stmt->bind_result($countToday);
    $stmt->fetch();
    $stmt->close();

    if ($countToday > 0) {
        $eligible = false;
        $message = "You already submitted a request today. Please try again tomorrow.";
    }
}

// 3. Handle request
if ($_SERVER["REQUEST_METHOD"] === "POST" && $eligible) {
    $quantity = $_POST['quantity'] ?? null;
    $weight = $_POST['weight'] ?? null;
    $requested_date = date("Y-m-d");
    $status = "Pending";

    if ($donor_id && $quantity && $weight) {
        // Validate weight requirement (minimum 50kg to donate)
        if ($weight < 50) {
            $eligible = false;
            $message = "Minimum weight requirement for donation is 50kg.";
        } else {
            $stmt = $conn->prepare("INSERT INTO blood_donation_requests (donor_id, quantity, requested_date, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $donor_id, $quantity, $requested_date, $status);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Your donation request has been submitted successfully!";
                header("Location: donor_dashboard.php");
                exit();
            } else {
                $message = "Database Error: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $message = "Please fill all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Blood Donation | BloodLink</title>
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
        .sidebar {
            transition: all 0.3s;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 50;
                height: 100vh;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0,0,0,0.5);
                z-index: 40;
            }
            .sidebar-overlay.active {
                display: block;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen antialiased">
    <!-- Sidebar Navigation -->
    <div class="flex">
        <div id="sidebar" class="hidden md:flex md:w-64 lg:w-72 bg-white shadow-sm flex-col fixed h-full">
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
                <a href="donor_dashboard.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-tachometer-alt mr-3 text-gray-500"></i>
                    Dashboard
                </a>
                <a href="request_donation.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg bg-red-50 text-red-700">
                    <i class="fas fa-hand-holding-medical mr-3 text-red-600"></i>
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
                    <h1 class="text-lg font-semibold text-gray-900">Request Blood Donation</h1>
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
                    <a href="donor_dashboard.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Dashboard</a>
                    <a href="request_donation.php" class="block px-3 py-2 rounded-md bg-red-50 text-red-700 font-medium">Donate Blood</a>
                    <a href="view_requests.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Donation History</a>
                    <a href="donor_inbox.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Messages</a>
                    <a href="update_profile_donor.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Profile Settings</a>
                    <a href="donor_logout.php" class="block px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100">Sign Out</a>
                </div>
            </div>

            <!-- Main Content Area -->
            <main class="max-w-2xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <!-- Form Header -->
                    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-red-600 to-red-500">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-semibold text-white">Blood Donation Request</h2>
                            <div class="blood-type-badge bg-white/10 rounded-full px-4 py-1 flex items-center">
                                <span class="text-lg font-bold text-white"><?= htmlspecialchars($donor_blood_group) ?></span>
                                <span class="ml-2 text-sm text-white/80">Blood Type</span>
                            </div>
                        </div>
                    </div>

                    <!-- Eligibility Status -->
                    <?php if (!$eligible): ?>
                    <div class="p-6 border-b border-red-200 bg-red-50">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Donation Not Available</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p><?= htmlspecialchars($message) ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="donor_dashboard.php" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Return to Dashboard
                            </a>
                        </div>
                    </div>
                    <?php else: ?>

                    <!-- Donation Form -->
                    <form method="POST" action="" class="divide-y divide-gray-200">
                        <div class="px-6 py-5 space-y-6">
                            <?php if (!empty($message)): ?>
                            <div class="rounded-md bg-yellow-50 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-yellow-800">Notice</h3>
                                        <div class="mt-2 text-sm text-yellow-700">
                                            <p><?= htmlspecialchars($message) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <input type="hidden" name="donor_id" value="<?= htmlspecialchars($donor_id) ?>">

                            <div class="grid grid-cols-1 gap-6">
                                <!-- Blood Quantity -->
                                <div>
                                    <label for="quantity" class="block text-sm font-medium text-gray-700">Donation Amount (ml)</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <input type="number" name="quantity" id="quantity" required min="50" max="1000" 
                                               class="focus:ring-red-500 focus:border-red-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md" 
                                               placeholder="450">
                                        <div class="absolute inset-y-0 right-0 flex items-center">
                                            <span class="text-gray-500 sm:text-sm pr-3">ml</span>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-500">Standard donation is 450-500ml</p>
                                </div>

                                <!-- Weight Input -->
                                <div>
                                    <label for="weight" class="block text-sm font-medium text-gray-700">Your Weight (kg)</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <input type="number" name="weight" id="weight" required min="50" 
                                               class="focus:ring-red-500 focus:border-red-500 block w-full pr-12 sm:text-sm border-gray-300 rounded-md" 
                                               placeholder="65">
                                        <div class="absolute inset-y-0 right-0 flex items-center">
                                            <span class="text-gray-500 sm:text-sm pr-3">kg</span>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-500">Minimum 50kg required to donate</p>
                                </div>

                                <!-- Blood Type Display -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Your Blood Type</label>
                                    <div class="mt-1">
                                        <div class="blood-type-badge bg-red-50 rounded-full px-4 py-2 inline-flex items-center">
                                            <span class="text-lg font-bold text-red-700"><?= htmlspecialchars($donor_blood_group) ?></span>
                                            <span class="ml-2 text-sm text-red-600">Blood Type</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Last Donation Info -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Last Donation</label>
                                    <div class="mt-1">
                                        <p class="text-sm text-gray-900">
                                            <?= $lastAcceptedDateDisplay === "N/A" ? "No previous donations" : date('M j, Y', strtotime($lastAcceptedDateDisplay)) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Footer -->
                        <div class="px-6 py-4 bg-gray-50 text-right">
                            <button type="button" onclick="window.location.href='donor_dashboard.php'" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Cancel
                            </button>
                            <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Submit Request
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- JavaScript for mobile menu toggle -->
    <script>
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });

        document.getElementById('mobileMenuButton').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('hidden');
            document.getElementById('sidebar').classList.toggle('flex');
        });
    </script>

    <!-- SweetAlert for messages -->
    <?php if (!empty($message) && $alertType === 'error'): ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        Swal.fire({
            icon: 'error',
            title: '<?= addslashes($message) ?>',
            confirmButtonColor: '#EF4444',
        }).then(() => {
            window.location.href = "donor_dashboard.php";
        });
    </script>
    <?php endif; ?>
</body>
</html>