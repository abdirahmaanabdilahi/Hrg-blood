<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Approve logic
if (isset($_POST['approve_id'])) {
    $request_id = $_POST['approve_id'];

    $stmt = $conn->prepare("SELECT r.*, p.full_name, bg.group_name 
                           FROM requests r 
                           JOIN patients p ON r.patient_id = p.id 
                           JOIN blood_groups bg ON r.blood_group_id = bg.id 
                           WHERE r.id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();

    if ($request) {
        $quantity_needed = $request['quantity_units'];
        $blood_group_id = $request['blood_group_id'];
        $patient_name = $request['full_name'];
        $blood_group = $request['group_name'];
        $is_private = isset($_POST['stock_status']) && $_POST['stock_status'] === 'private';

        // Start transaction
        $conn->begin_transaction();

        try {
            // Check if specific stock ID was provided
            if (isset($_POST['stock_id']) && !empty($_POST['stock_id'])) {
                $stock_id = intval($_POST['stock_id']);
                
                // Get the specific stock entry
                $stmt = $conn->prepare("SELECT id, total_quantity FROM blood_stock 
                                        WHERE id = ? AND blood_group_id = ? AND expire_date >= CURDATE()");
                $stmt->bind_param("ii", $stock_id, $blood_group_id);
                $stmt->execute();
                $stock_result = $stmt->get_result();
                
                if ($stock_result->num_rows > 0) {
                    $stock = $stock_result->fetch_assoc();
                    
                    if ($stock['total_quantity'] >= $quantity_needed) {
                        // Deduct from this stock entry
                        $new_quantity = $stock['total_quantity'] - $quantity_needed;
                        
                        // Update stock
                        $update = $conn->prepare("UPDATE blood_stock SET total_quantity = ? WHERE id = ?");
                        $update->bind_param("di", $new_quantity, $stock_id);
                        $update->execute();
                        
                        // Update request status
                        $update_req = $conn->prepare("UPDATE requests SET status = 'Approved' WHERE id = ?");
                        $update_req->bind_param("i", $request_id);
                        $update_req->execute();
                        
                        $conn->commit();
                        
                        $_SESSION['message'] = [
                            'type' => 'success', 
                            'text' => "Request approved for patient $patient_name using stock ID $stock_id."
                        ];
                    } else {
                        $conn->rollback();
                        $_SESSION['message'] = [
                            'type' => 'error', 
                            'text' => "Not enough blood in stock ID $stock_id for patient $patient_name. Only {$stock['total_quantity']} units available."
                        ];
                    }
                } else {
                    $conn->rollback();
                    $_SESSION['message'] = [
                        'type' => 'error', 
                        'text' => "Invalid stock ID $stock_id for blood group $blood_group."
                    ];
                }
            } else {
                // No specific stock ID provided - use FIFO method with status filter
                $status_condition = $is_private ? "AND status = 'private'" : "AND status = 'public'";
                $stock_query = "SELECT * FROM blood_stock 
                              WHERE blood_group_id = $blood_group_id 
                              AND total_quantity > 0 
                              AND expire_date >= CURDATE()
                              $status_condition
                              ORDER BY expire_date ASC";
                $stock_rows = $conn->query($stock_query);

                $remaining = $quantity_needed;
                $used_stocks = [];

                while (($row = $stock_rows->fetch_assoc()) && $remaining > 0) {
                    $use = min($remaining, $row['total_quantity']);
                    $remaining -= $use;
                    $used_stocks[] = ['id' => $row['id'], 'use' => $use, 'total_quantity' => $row['total_quantity']];
                }

                if ($remaining === 0) {
                    // Update all used stock entries
                    foreach ($used_stocks as $stock) {
                        $new_quantity = $stock['total_quantity'] - $stock['use'];
                        $conn->query("UPDATE blood_stock SET total_quantity = $new_quantity WHERE id = {$stock['id']}");
                    }
                    
                    // Update request status
                    $conn->query("UPDATE requests SET status = 'Approved' WHERE id = $request_id");
                    $conn->commit();
                    
                    $status_text = $is_private ? 'private' : 'public';
                    $_SESSION['message'] = [
                        'type' => 'success', 
                        'text' => "Request approved for patient $patient_name (Blood Group: $blood_group) using $status_text stock."
                    ];
                } else {
                    $conn->rollback();
                    $_SESSION['message'] = [
                        'type' => 'error', 
                        'text' => "Not enough $blood_group blood in stock for patient $patient_name. Only " . ($quantity_needed - $remaining) . " units available."
                    ];
                }
            }
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['message'] = [
                'type' => 'error', 
                'text' => "Error processing request: " . $e->getMessage()
            ];
        }
    }
}

// Reject logic
if (isset($_POST['reject_id'])) {
    $reject_id = $_POST['reject_id'];
    
    // Get patient info for message
    $stmt = $conn->prepare("SELECT p.full_name FROM requests r JOIN patients p ON r.patient_id = p.id WHERE r.id = ?");
    $stmt->bind_param("i", $reject_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $patient_name = $request['full_name'] ?? 'Unknown';
    
    $conn->query("UPDATE requests SET status = 'Rejected' WHERE id = $reject_id");
    $_SESSION['message'] = [
        'type' => 'warning', 
        'text' => "Request from $patient_name has been rejected."
    ];
}

// Update logic
if (isset($_POST['update_request'])) {
    $edit_id = $_POST['edit_id'];
    $edit_quantity = $_POST['edit_quantity'];
    $edit_status = isset($_POST['edit_status']) && $_POST['edit_status'] !== '' ? $_POST['edit_status'] : NULL;

    $stmt = $conn->prepare("UPDATE requests SET quantity_units = ?, status = ? WHERE id = ?");
    $stmt->bind_param("isi", $edit_quantity, $edit_status, $edit_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Request updated successfully.'];
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to update the request.'];
    }
}

// Build search query
$search = '';
$whereConditions = "WHERE (r.status IS NULL OR r.status = 'Pending')";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $whereConditions .= " AND (p.full_name LIKE ? OR bg.group_name LIKE ? OR r.status LIKE ?)";
}

// Main select
$sql = "SELECT r.*, p.full_name, bg.group_name 
        FROM requests r 
        JOIN patients p ON r.patient_id = p.id 
        JOIN blood_groups bg ON r.blood_group_id = bg.id 
        $whereConditions ORDER BY r.request_date DESC";

if (isset($_GET['search'])) {
    $searchTerm = "%{$search}%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
} else {
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$requests = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patient Requests | BloodBank</title>
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
                    <a href="admin_manage_requests.php" class="block py-2 px-4 rounded-lg hover:bg-white hover:bg-opacity-10">Donor Requests</a>
                    <a href="patient_requests.php" class="block py-2 px-4 rounded-lg bg-white bg-opacity-20">Patient Requests</a>
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
                        <h1 class="text-2xl font-bold text-gray-800">Patient Blood Requests</h1>
                        <p class="text-gray-600">Manage all patient blood requests in the system</p>
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
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Blood Group</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($requests->num_rows > 0): ?>
                                <?php while ($row = $requests->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <?= htmlspecialchars($row['group_name']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['quantity_units'] ?> units</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row['reason']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['request_date'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php 
                                        $statusClass = '';
                                        switch($row['status'] ?? 'Pending') {
                                            case 'Approved': $statusClass = 'status-approved'; break;
                                            case 'Rejected': $statusClass = 'status-rejected'; break;
                                            default: $statusClass = 'status-pending';
                                        }
                                        ?>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= $row['status'] ?? 'Pending' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <form method="POST" action="">
                                            <input type="hidden" name="approve_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="text-green-600 hover:text-green-900 mr-3" title="Approve">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                        </form>
                                        <form method="POST">
                                            <input type="hidden" name="reject_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900 mr-3" title="Reject">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        </form>
                                        <button 
                                            type="button" 
                                            onclick="openEditModal(<?= $row['id'] ?>, <?= $row['quantity_units'] ?>, '<?= $row['status'] ?? '' ?>')" 
                                            class="text-yellow-600 hover:text-yellow-900 mr-3" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="admin_send_message_patient.php?patient_id=<?= $row['patient_id'] ?>" class="text-blue-600 hover:text-blue-900" title="Send Message">
                                            <i class="fas fa-comment"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        No patient requests found.
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
                <input type="hidden" name="edit_id" id="editId">
                <div>
                    <label for="editQuantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity (units)</label>
                    <input type="number" name="edit_quantity" id="editQuantity" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required min="1">
                </div>
                <div>
                    <label for="editStatus" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="edit_status" id="editStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                        <option value="">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
            </div>
            <div class="border-t border-gray-200 px-6 py-4 flex justify-end space-x-3">
                <button type="button" id="cancelEdit" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Cancel
                </button>
                <button type="submit" name="update_request" class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Approval Modal -->
<div id="approveModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center px-4 z-50 opacity-0 invisible">
    <div class="modal-content bg-white rounded-xl shadow-xl w-full max-w-md">
        <form method="POST" id="approveForm">
            <input type="hidden" name="approve_id" id="approveRequestId">
            <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Approve Request</h3>
                <button type="button" id="closeApproveModal" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label for="stockIdInput" class="block text-sm font-medium text-gray-700 mb-1">Blood Stock ID (optional)</label>
                    <input type="number" name="stock_id" id="stockIdInput" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" placeholder="Leave empty for automatic allocation">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock Type</label>
                    <div class="mt-1 space-y-2">
                        <div class="flex items-center">
                            <input id="stock-public" name="stock_status" type="radio" value="public" checked class="focus:ring-red-500 h-4 w-4 text-red-600 border-gray-300">
                            <label for="stock-public" class="ml-2 block text-sm text-gray-700">Public Stock (Available to all)</label>
                        </div>
                        <div class="flex items-center">
                            <input id="stock-private" name="stock_status" type="radio" value="private" class="focus:ring-red-500 h-4 w-4 text-red-600 border-gray-300">
                            <label for="stock-private" class="ml-2 block text-sm text-gray-700">Private Stock (Reserved for specific cases)</label>
                        </div>
                    </div>
                </div>
                <div class="bg-yellow-50 p-3 rounded-lg text-sm text-yellow-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    If you specify a stock ID, the system will use that specific stock. Otherwise, it will automatically allocate from the oldest available stock of the selected type.
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

// Edit Modal functionality
const editModal = document.getElementById('editModal');
const closeEditModalBtn = document.getElementById('closeEditModal');
const cancelEditBtn = document.getElementById('cancelEdit');

function openEditModal(id, quantity, status) {
    document.getElementById('editId').value = id;
    document.getElementById('editQuantity').value = quantity;
    document.getElementById('editStatus').value = status || '';
    
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
const approveForm = document.getElementById('approveForm');

function openApproveModal(requestId) {
    document.getElementById('approveRequestId').value = requestId;
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

// Modify approve buttons to use modal
document.querySelectorAll('form[action=""] button[type="submit"]').forEach(button => {
    if (button.innerHTML.includes('fa-check-circle')) {
        button.closest('form').addEventListener('submit', function(e) {
            e.preventDefault();
            const requestId = this.querySelector('input[name="approve_id"]').value;
            openApproveModal(requestId);
        });
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

// Show SweetAlert on action
<?php if (isset($_SESSION['message'])): ?>
Swal.fire({
    icon: '<?= $_SESSION['message']['type'] === 'error' ? 'error' : ($_SESSION['message']['type'] === 'warning' ? 'warning' : 'success') ?>',
    title: '<?= $_SESSION['message']['text'] ?>',
    timer: 3000,
    showConfirmButton: false,
});
<?php unset($_SESSION['message']); endif; ?>
</script>
</body>
</html>