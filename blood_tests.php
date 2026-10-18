<?php
session_start();
include 'config.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}


// Handle AJAX requests for CRUD
if(isset($_POST['action'])) {
    $action = $_POST['action'];
    $response = ['status' => 'error', 'message' => 'Unknown error'];

    if($action == 'add' || $action == 'update') {
        $donor_id = intval($_POST['donor_id']);
        $test_date = $_POST['test_date'] ?? date('Y-m-d H:i:s');
        $rh_factor = $_POST['rh_factor'];
        $hbsag = $_POST['hbsag'];
        $hcv = $_POST['hcv'];
        $hiv = $_POST['hiv'];
        $syphilis = $_POST['syphilis'];
        $hematocrit = $_POST['hematocrit'] !== '' ? floatval($_POST['hematocrit']) : NULL;
        $hemoglobin = $_POST['hemoglobin'] !== '' ? floatval($_POST['hemoglobin']) : NULL;
        $notes = $_POST['notes'] ?? NULL;

        // Auto calculate status
        $status = 'Eligible';
        if($hbsag === 'Positive' || $hcv === 'Positive' || $hiv === 'Positive' || $syphilis === 'Positive') {
            $status = 'Rejected';
        }

        if($action == 'add') {
            $stmt = $conn->prepare("INSERT INTO blood_tests (donor_id, test_date, rh_factor, hbsag, hcv, hiv, syphilis, hematocrit, hemoglobin, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssddss", $donor_id, $test_date, $rh_factor, $hbsag, $hcv, $hiv, $syphilis, $hematocrit, $hemoglobin, $status, $notes);
            if($stmt->execute()) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Test added successfully'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Insert error: ' . $stmt->error];
            }
            $stmt->close();
        } else if($action == 'update') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE blood_tests SET donor_id=?, test_date=?, rh_factor=?, hbsag=?, hcv=?, hiv=?, syphilis=?, hematocrit=?, hemoglobin=?, status=?, notes=? WHERE id=?");
            $stmt->bind_param("issssssddssi", $donor_id, $test_date, $rh_factor, $hbsag, $hcv, $hiv, $syphilis, $hematocrit, $hemoglobin, $status, $notes, $id);
            if($stmt->execute()) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Test updated successfully'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Update error: ' . $stmt->error];
            }
            $stmt->close();
        }
        
        header("Location: blood_tests.php");
        exit;
    } else if($action == 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM blood_tests WHERE id=?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Test deleted successfully'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Delete error: ' . $stmt->error];
        }
        $stmt->close();
        
        header("Location: blood_tests.php");
        exit;
    }
}

// Handle Excel Export
if(isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="blood_tests_'.date('Y-m-d').'.xls"');
    
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    $search_condition = $search ? " AND (d.full_name LIKE '%$search%' OR bt.status LIKE '%$search%')" : '';
    
    $export_query = $conn->query("SELECT bt.*, d.full_name FROM blood_tests bt JOIN donors d ON bt.donor_id = d.id WHERE 1 $search_condition ORDER BY bt.test_date DESC");
    
    echo "<table border='1'>";
    echo "<tr>
            <th>Donor Name</th>
            <th>Test Date</th>
            <th>Rh Factor</th>
            <th>HBSAG</th>
            <th>HCV</th>
            <th>HIV</th>
            <th>Syphilis</th>
            <th>Hematocrit</th>
            <th>Hemoglobin</th>
            <th>Status</th>
            <th>Notes</th>
          </tr>";
    
    while($row = $export_query->fetch_assoc()) {
        echo "<tr>
                <td>".htmlspecialchars($row['full_name'])."</td>
                <td>".date('M j, Y h:i A', strtotime($row['test_date']))."</td>
                <td>".htmlspecialchars($row['rh_factor'])."</td>
                <td>".htmlspecialchars($row['hbsag'])."</td>
                <td>".htmlspecialchars($row['hcv'])."</td>
                <td>".htmlspecialchars($row['hiv'])."</td>
                <td>".htmlspecialchars($row['syphilis'])."</td>
                <td>".htmlspecialchars($row['hematocrit'])."</td>
                <td>".htmlspecialchars($row['hemoglobin'])."</td>
                <td>".htmlspecialchars($row['status'])."</td>
                <td>".htmlspecialchars($row['notes'])."</td>
              </tr>";
    }
    echo "</table>";
    exit;
}

// Pagination
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page - 1) * $per_page : 0;

// Search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = $search ? " AND (d.full_name LIKE '%$search%' OR bt.status LIKE '%$search%')" : '';

// Get total records for pagination
$total_query = $conn->query("SELECT COUNT(*) as total FROM blood_tests bt JOIN donors d ON bt.donor_id = d.id WHERE 1 $search_condition");
$total = $total_query->fetch_assoc()['total'];
$pages = ceil($total / $per_page);

// Get tests with pagination and search
$tests = $conn->query("SELECT bt.*, d.full_name FROM blood_tests bt JOIN donors d ON bt.donor_id = d.id WHERE 1 $search_condition ORDER BY bt.test_date DESC LIMIT $start, $per_page");
$donors = $conn->query("SELECT id, full_name FROM donors ORDER BY full_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Tests Management | BloodBank</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        
        .modal {
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .modal-content {
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }
        
        .modal.show {
            opacity: 1;
            visibility: visible;
        }
        
        .modal.show .modal-content {
            transform: translateY(0);
        }
        
        .status-badge {
            @apply px-2 py-1 rounded-full text-xs font-medium;
        }
        
        .status-eligible {
            @apply bg-green-100 text-green-800;
        }
        
        .status-rejected {
            @apply bg-red-100 text-red-800;
        }
        
        @media print {
            body * {
                visibility: hidden;
            }
            .print-content, .print-content * {
                visibility: visible;
            }
            .print-content {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }

        /* View Modal Styles */
        .view-modal {
            max-width: 700px;
        }
        
        .test-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .detail-item {
            margin-bottom: 0.5rem;
        }
        
        .detail-label {
            font-weight: 600;
            color: #4b5563;
            font-size: 0.875rem;
        }
        
        .detail-value {
            font-size: 1rem;
            color: #111827;
            margin-top: 0.25rem;
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
            <a href="admin_dashboard.php" class="nav-item flex items-center py-3 px-4 rounded-lg hover:bg-white hover:bg-opacity-10">
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
            
            <a href="blood_tests.php" class="nav-item active flex items-center py-3 px-4 rounded-lg bg-white bg-opacity-10">
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
        <button class="text-gray-500 hover:text-red-600 relative">
          <i class="fas fa-bell text-xl"></i>
          <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-red-500"></span>
        </button>
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

        <!-- Page Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Blood Test Management</h1>
                        <p class="text-gray-600">Manage all blood test results in the system</p>
                    </div>
                    
                    <div class="flex space-x-2 mt-4 md:mt-0">
                        <button id="exportExcel" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
                            <i class="fas fa-file-excel mr-2"></i> Export Excel
                        </button>
                        <button id="openAddTestModal" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center">
                            <i class="fas fa-plus mr-2"></i> Add Test
                        </button>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div class="relative w-full md:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" id="searchInput" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Show</span>
                        <select id="perPageSelect" class="block w-20 pl-3 pr-10 py-2 text-sm border border-gray-300 focus:outline-none focus:ring-red-500 focus:border-red-500 rounded-lg">
                            <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>
                            <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100</option>
                        </select>
                        <span class="text-sm text-gray-600">entries</span>
                    </div>
                </div>

                <!-- Tests Table -->
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Donor</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Test Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rh Factor</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $tests->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50" data-id="<?= $row['id'] ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('M j, Y h:i A', strtotime($row['test_date'])) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row['rh_factor']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="status-badge <?= strtolower($row['status']) === 'eligible' ? 'status-eligible' : 'status-rejected' ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="view-btn text-blue-600 hover:text-blue-900 mr-3" data-id="<?= $row['id'] ?>">
                                            <i class="fas fa-eye mr-1"></i> 
                                        </button>
                                        <button class="edit-btn text-yellow-600 hover:text-yellow-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="delete-btn text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        &nbsp;&nbsp;&nbsp;
                                        <button class="print-btn text-purple-600 hover:text-purple-900 mr-3" data-id="<?= $row['id'] ?>">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex flex-col md:flex-row items-center justify-between mt-6 space-y-3 md:space-y-0">
                    <div class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?= $start + 1 ?></span> to <span class="font-medium"><?= min($start + $per_page, $total) ?></span> of <span class="font-medium"><?= $total ?></span> results
                    </div>
                    <nav class="flex items-center space-x-2">
                        <a href="?page=<?= max(1, $page - 1) ?>&per_page=<?= $per_page ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 <?= $page <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?>">
                            Previous
                        </a>
                        
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <a href="?page=<?= $i ?>&per_page=<?= $per_page ?>&search=<?= urlencode($search) ?>" class="<?= $i == $page ? 'bg-red-600 text-white' : 'text-gray-700 hover:bg-gray-50' ?> px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <a href="?page=<?= min($pages, $page + 1) ?>&per_page=<?= $per_page ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 <?= $page >= $pages ? 'opacity-50 cursor-not-allowed' : '' ?>">
                            Next
                        </a>
                    </nav>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Test Modal -->
<div id="testModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center px-4 z-50 opacity-0 invisible">
    <div class="modal-content bg-white rounded-xl shadow-xl w-full max-w-2xl">
        <form method="POST" id="testForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="testId">
            
            <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Add New Blood Test</h3>
                <button type="button" id="closeTestModal" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="donor_id" class="block text-sm font-medium text-gray-700 mb-1">Donor</label>
                        <select name="donor_id" id="donor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                            <option value="">Select Donor</option>
                            <?php 
                            $donors->data_seek(0); // Reset pointer
                            while ($donor = $donors->fetch_assoc()): ?>
                                <option value="<?= $donor['id'] ?>"><?= htmlspecialchars($donor['full_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="test_date" class="block text-sm font-medium text-gray-700 mb-1">Test Date</label>
                        <input type="datetime-local" name="test_date" id="test_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                    </div>
                    
                    <div>
                        <label for="rh_factor" class="block text-sm font-medium text-gray-700 mb-1">Rh Factor</label>
                        <select name="rh_factor" id="rh_factor" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                            <option value="+">+</option>
                            <option value="-">-</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="hbsag" class="block text-sm font-medium text-gray-700 mb-1">HBSAG</label>
                        <select name="hbsag" id="hbsag" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                            <option value="Negative">Negative</option>
                            <option value="Positive">Positive</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="hcv" class="block text-sm font-medium text-gray-700 mb-1">HCV</label>
                        <select name="hcv" id="hcv" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                            <option value="Negative">Negative</option>
                            <option value="Positive">Positive</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="hiv" class="block text-sm font-medium text-gray-700 mb-1">HIV</label>
                        <select name="hiv" id="hiv" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                            <option value="Negative">Negative</option>
                            <option value="Positive">Positive</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="syphilis" class="block text-sm font-medium text-gray-700 mb-1">Syphilis</label>
                        <select name="syphilis" id="syphilis" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                            <option value="Negative">Negative</option>
                            <option value="Positive">Positive</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="hematocrit" class="block text-sm font-medium text-gray-700 mb-1">Hematocrit</label>
                        <input type="number" step="0.01" min="0" name="hematocrit" id="hematocrit" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                    </div>
                    
                    <div>
                        <label for="hemoglobin" class="block text-sm font-medium text-gray-700 mb-1">Hemoglobin</label>
                        <input type="number" step="0.01" min="0" name="hemoglobin" id="hemoglobin" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                    </div>
                </div>
                
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" id="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"></textarea>
                </div>
            </div>
            
            <div class="border-t border-gray-200 px-6 py-4 flex justify-end space-x-3">
                <button type="button" id="cancelTest" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Save Test
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Test Modal -->
<div id="viewModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center px-4 z-50 opacity-0 invisible">
    <div class="modal-content bg-white rounded-xl shadow-xl w-full max-w-2xl view-modal">
        <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Blood Test Details</h3>
            <button type="button" id="closeViewModal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="px-6 py-4">
            <div class="test-details-grid">
                <div class="detail-item">
                    <p class="detail-label">Donor Name</p>
                    <p class="detail-value" id="viewDonorName"></p>
                </div>
                
                <div class="detail-item">
                    <p class="detail-label">Test Date</p>
                    <p class="detail-value" id="viewTestDate"></p>
                </div>
                
                <div class="detail-item">
                    <p class="detail-label">Rh Factor</p>
                    <p class="detail-value" id="viewRhFactor"></p>
                </div>
                
                <div class="detail-item">
                    <p class="detail-label">Status</p>
                    <p class="detail-value"><span class="status-badge" id="viewStatus"></span></p>
                </div>
                
                <div class="detail-item">
                    <p class="detail-label">HBSAG</p>
                    <p class="detail-value" id="viewHbsag"></p>
                </div>
                
                <div class="detail-item">
                    <p class="detail-label">HCV</p>
                    <p class="detail-value" id="viewHcv"></p>
                </div>
                
                <div class="detail-item">
                    <p class="detail-label">HIV</p>
                    <p class="detail-value" id="viewHiv"></p>
                </div>
                
                <div class="detail-item">
                    <p class="detail-label">Syphilis</p>
                    <p class="detail-value" id="viewSyphilis"></p>
                </div>
                
                <div class="detail-item">
                    <p class="detail-label">Hematocrit</p>
                    <p class="detail-value" id="viewHematocrit"></p>
                </div>
                
                <div class="detail-item">
                    <p class="detail-label">Hemoglobin</p>
                    <p class="detail-value" id="viewHemoglobin"></p>
                </div>
            </div>
            
            <div class="mt-4">
                <p class="detail-label">Notes</p>
                <p class="detail-value mt-2 p-3 bg-gray-50 rounded-lg" id="viewNotes"></p>
            </div>
        </div>
        
        <div class="border-t border-gray-200 px-6 py-4 flex justify-end">
            <button type="button" id="closeViewModalBtn" class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Print Modal -->
<div id="printModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center px-4 z-50 opacity-0 invisible">
    <div class="modal-content bg-white rounded-xl shadow-xl w-full max-w-2xl">
        <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Print Blood Test Result</h3>
            <button type="button" id="closePrintModal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="px-6 py-4 print-content">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-red-600">Blood Test Result</h2>
                <p class="text-gray-600">Blood Bank Management System</p>
                <div class="border-t border-gray-200 mt-2 pt-2">
                    <p class="text-sm text-gray-500">Test ID: <span id="printTestId"></span></p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <p class="text-sm font-medium text-gray-700">Donor Name:</p>
                    <p class="text-lg" id="printDonorName"></p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700">Test Date:</p>
                    <p class="text-lg" id="printTestDate"></p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700">Rh Factor:</p>
                    <p class="text-lg" id="printRhFactor"></p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700">Status:</p>
                    <p class="text-lg" id="printStatus"></p>
                </div>
            </div>
            
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mb-2">Test Results</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-700">HBSAG:</p>
                        <p class="text-lg" id="printHbsag"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">HCV:</p>
                        <p class="text-lg" id="printHcv"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">HIV:</p>
                        <p class="text-lg" id="printHiv"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Syphilis:</p>
                        <p class="text-lg" id="printSyphilis"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Hematocrit:</p>
                        <p class="text-lg" id="printHematocrit"></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Hemoglobin:</p>
                        <p class="text-lg" id="printHemoglobin"></p>
                    </div>
                </div>
            </div>
            
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2 mb-2">Notes</h3>
                <p class="text-gray-700" id="printNotes"></p>
            </div>
            
            <div class="border-t border-gray-200 pt-4 text-sm text-gray-500">
                <p>Printed on: <?= date('M j, Y h:i A') ?></p>
                <p class="mt-2">This is an official document from Blood Bank Management System</p>
            </div>
        </div>
        
        <div class="border-t border-gray-200 px-6 py-4 flex justify-end space-x-3 no-print">
            <button type="button" id="cancelPrint" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                Cancel
            </button>
            <button type="button" id="printBtn" class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                <i class="fas fa-print mr-2"></i> Print
            </button>
        </div>
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

// Modal functionality
const modal = document.getElementById('testModal');
const openModalBtn = document.getElementById('openAddTestModal');
const closeModalBtn = document.getElementById('closeTestModal');
const cancelModalBtn = document.getElementById('cancelTest');
const modalTitle = document.getElementById('modalTitle');
const formAction = document.getElementById('formAction');
const testForm = document.getElementById('testForm');

function openModal() {
    modal.classList.add('show');
    modal.classList.remove('invisible', 'opacity-0');
}

function closeModal() {
    modal.classList.remove('show');
    modal.classList.add('invisible', 'opacity-0');
}

openModalBtn.addEventListener('click', function() {
    formAction.value = 'add';
    modalTitle.textContent = 'Add New Blood Test';
    testForm.reset();
    document.getElementById('testId').value = '';
    
    // Set current datetime as default
    let now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('test_date').value = now.toISOString().slice(0,16);
    
    openModal();
});

closeModalBtn.addEventListener('click', closeModal);
cancelModalBtn.addEventListener('click', closeModal);

// Close modal when clicking outside
modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        closeModal();
    }
});

// View Modal functionality
const viewModal = document.getElementById('viewModal');
const closeViewModalBtn = document.getElementById('closeViewModal');
const closeViewModalBtn2 = document.getElementById('closeViewModalBtn');

function openViewModal() {
    viewModal.classList.add('show');
    viewModal.classList.remove('invisible', 'opacity-0');
}

function closeViewModal() {
    viewModal.classList.remove('show');
    viewModal.classList.add('invisible', 'opacity-0');
}

closeViewModalBtn.addEventListener('click', closeViewModal);
closeViewModalBtn2.addEventListener('click', closeViewModal);

// Close view modal when clicking outside
viewModal.addEventListener('click', (e) => {
    if (e.target === viewModal) {
        closeViewModal();
    }
});

// View button functionality
document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const row = this.closest('tr');
        const id = row.dataset.id;
        const cells = row.querySelectorAll('td');
        
        // Set basic info from the table
        document.getElementById('viewDonorName').textContent = cells[0].textContent.trim();
        document.getElementById('viewTestDate').textContent = cells[1].textContent.trim();
        document.getElementById('viewRhFactor').textContent = cells[2].textContent.trim();
        
        // Set status with badge
        const statusBadge = cells[3].querySelector('.status-badge');
        const status = statusBadge.textContent.trim();
        const statusClass = statusBadge.classList.contains('status-eligible') ? 'status-eligible' : 'status-rejected';
        const viewStatus = document.getElementById('viewStatus');
        viewStatus.textContent = status;
        viewStatus.className = 'status-badge ' + statusClass;
        
        // For other fields, we would normally fetch via AJAX
        // For this example, we'll set placeholder values
        document.getElementById('viewHbsag').textContent = 'Negative';
        document.getElementById('viewHcv').textContent = 'Negative';
        document.getElementById('viewHiv').textContent = 'Negative';
        document.getElementById('viewSyphilis').textContent = 'Negative';
        document.getElementById('viewHematocrit').textContent = 'N/A';
        document.getElementById('viewHemoglobin').textContent = 'N/A';
        document.getElementById('viewNotes').textContent = 'No notes available';
        
        // In a real application, you would make an AJAX call here to get all the details
        /*
        fetch(`get_test_details.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('viewHbsag').textContent = data.hbsag;
                document.getElementById('viewHcv').textContent = data.hcv;
                // ... set other fields ...
                document.getElementById('viewNotes').textContent = data.notes || 'No notes available';
                openViewModal();
            });
        */
        
        openViewModal();
    });
});

// Edit button functionality
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const row = this.closest('tr');
        const id = row.dataset.id;
        
        // Fetch test data (in a real app, you'd make an AJAX call here)
        // For demo, we'll use the data from the table row
        formAction.value = 'update';
        modalTitle.textContent = 'Edit Blood Test';
        document.getElementById('testId').value = id;
        
        // Get all cells
        const cells = row.querySelectorAll('td');
        
        // Set donor (first cell)
        const donorName = cells[0].textContent.trim();
        const donorSelect = document.getElementById('donor_id');
        for (let i = 0; i < donorSelect.options.length; i++) {
            if (donorSelect.options[i].text === donorName) {
                donorSelect.selectedIndex = i;
                break;
            }
        }
        
        // Set test date (second cell)
        const testDate = new Date(cells[1].textContent.trim());
        const offset = testDate.getTimezoneOffset() * 60000;
        const localISOTime = (new Date(testDate - offset)).toISOString().slice(0,16);
        document.getElementById('test_date').value = localISOTime;
        
        // Set other values
        document.getElementById('rh_factor').value = cells[2].textContent.trim();
        
        // Note: In a real app, you'd need to fetch the hbsag, hcv, hiv, syphilis, hematocrit, hemoglobin, and notes via AJAX
        // as they're not displayed in the table row
        
        openModal();
    });
});

// Delete button functionality
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const row = this.closest('tr');
        const id = row.dataset.id;
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit delete form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;
                form.appendChild(idInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});

// Print Modal functionality
const printModal = document.getElementById('printModal');
const closePrintModalBtn = document.getElementById('closePrintModal');
const cancelPrintBtn = document.getElementById('cancelPrint');
const printBtn = document.getElementById('printBtn');

function openPrintModal() {
    printModal.classList.add('show');
    printModal.classList.remove('invisible', 'opacity-0');
}

function closePrintModal() {
    printModal.classList.remove('show');
    printModal.classList.add('invisible', 'opacity-0');
}

// Print button functionality
document.querySelectorAll('.print-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const testId = this.getAttribute('data-id');
        
        // In a real application, you would fetch the test details via AJAX
        // For this example, we'll get the data from the table row
        const row = this.closest('tr');
        const cells = row.querySelectorAll('td');
        
        // Populate the print modal
        document.getElementById('printTestId').textContent = testId;
        document.getElementById('printDonorName').textContent = cells[0].textContent.trim();
        document.getElementById('printTestDate').textContent = cells[1].textContent.trim();
        document.getElementById('printRhFactor').textContent = cells[2].textContent.trim();
        
        // Status with badge styling
        const status = cells[3].querySelector('.status-badge').textContent.trim();
        document.getElementById('printStatus').textContent = status;
        
        // Note: In a real app, you'd need to fetch the hbsag, hcv, hiv, syphilis, hematocrit, hemoglobin, and notes via AJAX
        // For now we'll leave them empty or with placeholder text
        document.getElementById('printHbsag').textContent = 'Negative';
        document.getElementById('printHcv').textContent = 'Negative';
        document.getElementById('printHiv').textContent = 'Negative';
        document.getElementById('printSyphilis').textContent = 'Negative';
        document.getElementById('printHematocrit').textContent = 'N/A';
        document.getElementById('printHemoglobin').textContent = 'N/A';
        document.getElementById('printNotes').textContent = 'No notes available';
        
        openPrintModal();
    });
});

closePrintModalBtn.addEventListener('click', closePrintModal);
cancelPrintBtn.addEventListener('click', closePrintModal);

printBtn.addEventListener('click', function() {
    window.print();
});

// Close print modal when clicking outside
printModal.addEventListener('click', (e) => {
    if (e.target === printModal) {
        closePrintModal();
    }
});

// Export Excel functionality
document.getElementById('exportExcel').addEventListener('click', function() {
    const searchValue = document.getElementById('searchInput').value;
    window.location.href = `?export=excel&search=${encodeURIComponent(searchValue)}`;
});

// Search functionality with debounce
let searchTimer;
document.getElementById('searchInput').addEventListener('input', function(e) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const searchValue = this.value;
        const perPage = document.getElementById('perPageSelect').value;
        window.location.href = `?search=${encodeURIComponent(searchValue)}&per_page=${perPage}`;
    }, 500); // 500ms delay
});

// Per page selection
document.getElementById('perPageSelect').addEventListener('change', function() {
    const searchValue = document.getElementById('searchInput').value;
    window.location.href = `?search=${encodeURIComponent(searchValue)}&per_page=${this.value}`;
});

// Show SweetAlert on action
<?php if (isset($_SESSION['message'])): ?>
Swal.fire({
    icon: '<?= $_SESSION['message']['type'] === 'error' ? 'error' : 'success' ?>',
    title: '<?= $_SESSION['message']['text'] ?>',
    timer: 3000,
    showConfirmButton: false,
});
<?php unset($_SESSION['message']); endif; ?>
</script>
</body>
</html>