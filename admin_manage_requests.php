<?php
include 'config.php';
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Handle quantity/status updates from Edit modal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        // Handle delete request
        $id = intval($_POST['delete_id']);
        $stmt = $conn->prepare("DELETE FROM blood_donation_requests WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Request deleted successfully'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Error deleting request'];
        }
        $stmt->close();
        
        header("Location: admin_manage_requests.php");
        exit;
    }
    
    // Existing update handling
    $id = intval($_POST['id']);
    $quantity = floatval($_POST['quantity']);
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE blood_donation_requests SET quantity = ?, status = ? WHERE id = ?");
    $stmt->bind_param("dsi", $quantity, $status, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_manage_requests.php");
    exit;
}

// Handle Approve/Reject actions
if (isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);

    if ($_GET['action'] === 'approve') {
        // Validate required fields
        if (empty($_GET['stock_id']) || empty($_GET['request_date']) || empty($_GET['expire_date']) || empty($_GET['stock_status'])) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'All approval fields are required'];
            header("Location: admin_manage_requests.php");
            exit;
        }

        $stock_id = intval($_GET['stock_id']);
        $request_date = $_GET['request_date'];
        $expire_date = $_GET['expire_date'];
        $stock_status = $_GET['stock_status'];
        $description = isset($_GET['description']) ? $_GET['description'] : '';
        $status = 'Approved';
        
        // Update request status
        $stmt = $conn->prepare("UPDATE blood_donation_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();

        // Fetch donor's blood group and quantity
        $stmt = $conn->prepare(
            "SELECT d.blood_group_id, r.quantity
             FROM blood_donation_requests r
             JOIN donors d ON r.donor_id = d.id
             WHERE r.id = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($blood_group_id, $quantity);

        if ($stmt->fetch()) {
            $stmt->close();

            // Check if the stock ID exists
            $check = $conn->prepare("SELECT id FROM blood_stock WHERE id = ?");
            $check->bind_param("i", $stock_id);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                // Update existing stock
                $update = $conn->prepare(
                    "UPDATE blood_stock SET 
                     total_quantity = total_quantity + ?, 
                     request_date = ?, 
                     expire_date = ?,
                     description = ?,
                     status = ?
                     WHERE id = ?"
                );
                $update->bind_param("dssssi", $quantity, $request_date, $expire_date, $description, $stock_status, $stock_id);
                $update->execute();
                $update->close();
            } else {
                // Create new stock entry
                $insert = $conn->prepare(
                    "INSERT INTO blood_stock (id, blood_group_id, total_quantity, request_date, expire_date, description, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $insert->bind_param("iidssss", $stock_id, $blood_group_id, $quantity, $request_date, $expire_date, $description, $stock_status);
                $insert->execute();
                $insert->close();
            }

            $check->close();
        } else {
            $stmt->close();
        }

        $_SESSION['message'] = ['type' => 'success', 'text' => 'Request approved successfully'];
    } elseif ($_GET['action'] === 'reject') {
        // Reject: delete the request
        $stmt = $conn->prepare("DELETE FROM blood_donation_requests WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Request rejected successfully'];
    }

    header("Location: admin_manage_requests.php");
    exit;
}

// Build search query
$search = '';
$whereConditions = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $whereConditions = " WHERE d.full_name LIKE ? OR bg.group_name LIKE ? OR r.status LIKE ?";
}

// Main select
$sql = "SELECT r.id, r.donor_id, d.full_name, bg.group_name AS blood_group, r.quantity, r.requested_date, r.status
        FROM blood_donation_requests r
        JOIN donors d ON r.donor_id = d.id
        JOIN blood_groups bg ON d.blood_group_id = bg.id"
       . $whereConditions . " ORDER BY r.requested_date DESC";

if ($whereConditions) {
    $searchTerm = "%{$search}%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
} else {
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Donation Requests | BloodBank</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .status-public {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .status-private {
            background-color: #ede9fe;
            color: #5b21b6;
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
            <div class="group active">
                <button class="nav-item w-full flex items-center justify-between py-3 px-4 rounded-lg bg-white bg-opacity-10">
                    <div class="flex items-center">
                        <i class="fas fa-hand-holding-heart mr-3 text-lg"></i>
                        <span class="font-medium">Requests</span>
                    </div>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-[.active]:rotate-180"></i>
                </button>
                <div class="dropdown-content ml-8 mt-1 space-y-1 show">
                    <a href="admin_manage_requests.php" class="block py-2 px-4 rounded-lg bg-white bg-opacity-20">Donor Requests</a>
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
                        <h1 class="text-2xl font-bold text-gray-800">Donation Requests Management</h1>
                        <p class="text-gray-600">Manage all blood donation requests in the system</p>
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
                </div>

                <!-- Requests Table -->
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Donor</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Blood Group</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity (ml)</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $row['id'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <?= htmlspecialchars($row['blood_group']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['quantity'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['requested_date'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php 
                                        $statusClass = '';
                                        switch($row['status']) {
                                            case 'Approved': $statusClass = 'status-approved'; break;
                                            case 'Rejected': $statusClass = 'status-rejected'; break;
                                            default: $statusClass = 'status-pending';
                                        }
                                        ?>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($row['status'] === 'Pending'): ?>
                                            <a href="#" onclick="openApproveModal(<?= $row['id'] ?>); return false;" class="text-green-600 hover:text-green-900 mr-3" title="Approve">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                            <a href="?action=reject&id=<?= $row['id'] ?>" class="text-red-600 hover:text-red-900 mr-3" title="Reject">
                                                <i class="fas fa-times-circle"></i>
                                            </a>
                                            <button type="button" onclick="openEditModal(<?= $row['id'] ?>, <?= $row['quantity'] ?>, '<?= $row['status'] ?>')" class="text-yellow-600 hover:text-yellow-900 mr-3" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="admin_send_message.php?donor_id=<?= $row['donor_id'] ?>" class="text-blue-600 hover:text-blue-900" title="Send Message">
                                                <i class="fas fa-comment"></i>
                                            </a>
                                        <?php else: ?>
                                            <button type="button" onclick="confirmDelete(<?= $row['id'] ?>)" class="text-red-600 hover:text-red-900" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        No donation requests found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center px-4 z-50 opacity-0 invisible">
    <div class="modal-content bg-white rounded-xl shadow-xl w-full max-w-md">
        <form method="POST">
            <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Edit Request</h3>
                <button type="button" id="closeEditModal" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <input type="hidden" name="id" id="editId">
                <div>
                    <label for="editQuantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity (ml)</label>
                    <input type="number" name="quantity" id="editQuantity" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                </div>
                <div>
                    <label for="editStatus" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="editStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
            </div>
            <div class="border-t border-gray-200 px-6 py-4 flex justify-end space-x-3">
                <button type="button" id="cancelEdit" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Approval Confirmation Modal -->
<div id="approveModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center px-4 z-50 opacity-0 invisible">
    <div class="modal-content bg-white rounded-xl shadow-xl w-full max-w-md">
        <form id="approveForm" method="GET">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="id" id="approveId">
            <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Confirm Approval</h3>
                <button type="button" id="closeApproveModal" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label for="stockId" class="block text-sm font-medium text-gray-700 mb-1">Blood Stock ID</label>
                    <input type="text" name="stock_id" id="stockId" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                </div>
                <div>
                    <label for="requestDate" class="block text-sm font-medium text-gray-700 mb-1">Request Date</label>
                    <input type="date" name="request_date" id="requestDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                </div>
                <div>
                    <label for="expireDate" class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                    <input type="date" name="expire_date" id="expireDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                </div>
                <div>
                    <label for="stockStatus" class="block text-sm font-medium text-gray-700 mb-1">Stock Status</label>
                    <select name="stock_status" id="stockStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                        <option value="Public">Public</option>
                        <option value="Private">Private</option>
                    </select>
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                    <textarea name="description" id="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"></textarea>
                </div>
            </div>
            <div class="border-t border-gray-200 px-6 py-4 flex justify-end space-x-3">
                <button type="button" id="cancelApprove" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Confirm Approval
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form (hidden) -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="delete_id" id="deleteId">
</form>

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
const editModal = document.getElementById('editModal');
const closeEditModalBtn = document.getElementById('closeEditModal');
const cancelEditBtn = document.getElementById('cancelEdit');

function openEditModal(id, quantity, status) {
    document.getElementById('editId').value = id;
    document.getElementById('editQuantity').value = quantity;
    document.getElementById('editStatus').value = status;
    
    editModal.classList.add('show');
    editModal.classList.remove('invisible', 'opacity-0');
}

function closeEditModal() {
    editModal.classList.remove('show');
    editModal.classList.add('invisible', 'opacity-0');
}

closeEditModalBtn.addEventListener('click', closeEditModal);
cancelEditBtn.addEventListener('click', closeEditModal);

// Approval Modal functionality
const approveModal = document.getElementById('approveModal');
const closeApproveModalBtn = document.getElementById('closeApproveModal');
const cancelApproveBtn = document.getElementById('cancelApprove');

function openApproveModal(id) {
    document.getElementById('approveId').value = id;
    
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    const expireDate = new Date();
    expireDate.setDate(expireDate.getDate() + 42); // 42 days from now
    const expireDateStr = expireDate.toISOString().split('T')[0];
    
    document.getElementById('requestDate').value = today;
    document.getElementById('expireDate').value = expireDateStr;
    document.getElementById('stockStatus').value = 'Public'; // Default to Public
    
    approveModal.classList.add('show');
    approveModal.classList.remove('invisible', 'opacity-0');
}

function closeApproveModal() {
    approveModal.classList.remove('show');
    approveModal.classList.add('invisible', 'opacity-0');
}

closeApproveModalBtn.addEventListener('click', closeApproveModal);
cancelApproveBtn.addEventListener('click', closeApproveModal);

// Close modals when clicking outside
editModal.addEventListener('click', (e) => {
    if (e.target === editModal) {
        closeEditModal();
    }
});

approveModal.addEventListener('click', (e) => {
    if (e.target === approveModal) {
        closeApproveModal();
    }
});

// Search functionality with debounce
let searchTimer;
document.getElementById('searchInput').addEventListener('input', function(e) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const searchValue = this.value;
        window.location.href = `?search=${encodeURIComponent(searchValue)}`;
    }, 500); // 500ms delay
});

function confirmDelete(id) {
    event.preventDefault();
    Swal.fire({
        title: 'Are you sure?',
        text: "You are about to delete this request record.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    });
    return false;
}

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