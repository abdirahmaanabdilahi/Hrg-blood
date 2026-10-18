<?php
session_start();
include 'config.php'; // Include your database connection

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}


// ADD STOCK
if (isset($_POST['add_stock'])) {
    $id = $_POST['id'];
    $blood_group_id = $_POST['blood_group_id'];
    $total_quantity = $_POST['total_quantity'];
    $request_date = $_POST['request_date'];
    $expire_date = $_POST['expire_date'];
    $description = $_POST['description'];
    $status = $_POST['status']; // New status field

    $stmt = $conn->prepare("INSERT INTO blood_stock (id, blood_group_id, total_quantity, request_date, expire_date, description, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $id, $blood_group_id, $total_quantity, $request_date, $expire_date, $description, $status);
    $stmt->execute();
    $_SESSION['message'] = ['type' => 'success', 'text' => 'Blood stock added successfully.'];
}

// UPDATE STOCK
if (isset($_POST['update_stock'])) {
    $id = $_POST['stock_id'];
    $blood_group_id = $_POST['blood_group_id'];
    $total_quantity = $_POST['total_quantity'];
    $request_date = $_POST['request_date'];
    $expire_date = $_POST['expire_date'];
    $description = $_POST['description'];
    $status = $_POST['status']; // New status field

    $stmt = $conn->prepare("UPDATE blood_stock SET blood_group_id=?, total_quantity=?, request_date=?, expire_date=?, description=?, status=? WHERE id=?");
    $stmt->bind_param("iissssi", $blood_group_id, $total_quantity, $request_date, $expire_date, $description, $status, $id);
    $stmt->execute();
    $_SESSION['message'] = ['type' => 'success', 'text' => 'Blood stock updated successfully.'];
}

// DELETE STOCK
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM blood_stock WHERE id=$id");
    $_SESSION['message'] = ['type' => 'success', 'text' => 'Blood stock deleted successfully.'];
}

// Pagination
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page - 1) * $per_page : 0;

// Search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = $search ? " AND (bg.group_name LIKE '%$search%' OR bs.id LIKE '%$search%' OR bs.description LIKE '%$search%')" : '';

// Get total records for pagination
$total_query = $conn->query("SELECT COUNT(*) as total FROM blood_stock bs JOIN blood_groups bg ON bs.blood_group_id = bg.id WHERE 1 $search_condition");
$total = $total_query->fetch_assoc()['total'];
$pages = ceil($total / $per_page);

// Get stocks with pagination and search
$stocks = $conn->query("SELECT bs.*, bg.group_name FROM blood_stock bs JOIN blood_groups bg ON bs.blood_group_id = bg.id WHERE 1 $search_condition ORDER BY bs.request_date DESC LIMIT $start, $per_page");
$blood_groups = $conn->query("SELECT * FROM blood_groups");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Stock | BloodBank</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .group.active .fa-chevron-down {
            transform: rotate(180deg);
        }
        
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .status-public {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-private {
            background-color: #fee2e2;
            color: #b91c1c;
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
                    <i class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                </button>
                <div class="dropdown-content ml-8 mt-1 space-y-1">
                    <a href="blood_groups.php" class="block py-2 px-4 rounded-lg hover:bg-white hover:bg-opacity-10">Blood Groups</a>
                    <a href="blood_stock.php" class="block py-2 px-4 rounded-lg bg-white bg-opacity-20">Blood Stock</a>
                </div>
            </div>
            
            <!-- Requests Dropdown -->
            <div class="group">
                <button class="nav-item w-full flex items-center justify-between py-3 px-4 rounded-lg hover:bg-white hover:bg-opacity-10">
                    <div class="flex items-center">
                        <i class="fas fa-hand-holding-heart mr-3 text-lg"></i>
                        <span class="font-medium">Requests</span>
                    </div>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                </button>
                <div class="dropdown-content ml-8 mt-1 space-y-1">
                    <a href="admin_manage_requests.php" class="block py-2 px-4 rounded-lg hover:bg-white hover:bg-opacity-10">Donor Requests</a>
                    <a href="patient_requests.php" class="block py-2 px-4 rounded-lg hover:bg-white hover:bg-opacity-10">Patient Requests</a>
                </div>
            </div>
            
            <a href="blood_tests.php" class="nav-item flex items-center py-3 px-4 rounded-lg hover:bg-white hover:bg-opacity-10">
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
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">🩸 Blood Stock</h1>
                    
                    <!-- Button to Open Add Blood Stock Modal -->
                    <button type="button" onclick="openStockModal()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add Blood Stock
                    </button>
                </div>

                <!-- Search and Filter -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 space-y-3 md:space-y-0">
                    <div class="relative w-full md:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" id="searchInput" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Show</span>
                        <select id="perPageSelect" class="block w-20 pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm rounded-md">
                            <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>
                            <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100</option>
                        </select>
                        <span class="text-sm text-gray-600">entries</span>
                    </div>
                </div>

                <!-- Blood Stock Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Blood Group</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity (ml)</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expire Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $stocks->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $row['id'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            <?= htmlspecialchars($row['group_name']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $row['total_quantity'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $row['request_date'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $row['expire_date'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $row['status'] == 'public' ? 'status-public' : 'status-private' ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($row['description']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="editStock(<?= htmlspecialchars(json_encode($row)) ?>)" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?= $row['id'] ?>" onclick="return confirmDelete(event)" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex flex-col md:flex-row items-center justify-between mt-4 space-y-3 md:space-y-0">
                    <div class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?= $start + 1 ?></span> to <span class="font-medium"><?= min($start + $per_page, $total) ?></span> of <span class="font-medium"><?= $total ?></span> results
                    </div>
                    <nav class="flex items-center space-x-2">
                        <a href="?page=<?= max(1, $page - 1) ?>&per_page=<?= $per_page ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 <?= $page <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?>">
                            Previous
                        </a>
                        
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <a href="?page=<?= $i ?>&per_page=<?= $per_page ?>&search=<?= urlencode($search) ?>" class="<?= $i == $page ? 'bg-red-600 text-white' : 'text-gray-700 hover:bg-gray-50' ?> px-3 py-1 border border-gray-300 rounded-md text-sm font-medium">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <a href="?page=<?= min($pages, $page + 1) ?>&per_page=<?= $per_page ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 <?= $page >= $pages ? 'opacity-50 cursor-not-allowed' : '' ?>">
                            Next
                        </a>
                    </nav>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Blood Stock Modal -->
<div id="stockModal" class="modal">
    <div class="modal-backdrop" onclick="closeStockModal()"></div>
    <div class="modal-content">
        <form method="POST">
            <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Add Blood Stock</h3>
                <button type="button" onclick="closeStockModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-6 py-4">
                <div class="mb-4">
                    <label for="id" class="block text-sm font-medium text-gray-700 mb-1">Blood Stock ID</label>
                    <input type="number" name="id" id="id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required>
                </div>
                <div class="mb-4">
                    <label for="blood_group_id" class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                    <select name="blood_group_id" id="blood_group_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required>
                        <option value="">Select Blood Group</option>
                        <?php while ($bg = $blood_groups->fetch_assoc()): ?>
                            <option value="<?= $bg['id'] ?>"><?= $bg['group_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="total_quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity (ml)</label>
                    <input type="number" name="total_quantity" id="total_quantity" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required>
                </div>
                <div class="mb-4">
                    <label for="request_date" class="block text-sm font-medium text-gray-700 mb-1">Request Date</label>
                    <input type="date" name="request_date" id="request_date" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required>
                </div>
                <div class="mb-4">
                    <label for="expire_date" class="block text-sm font-medium text-gray-700 mb-1">Expire Date</label>
                    <input type="date" name="expire_date" id="expire_date" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required>
                </div>
                <div class="mb-4">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required>
                        <option value="public">Public</option>
                        <option value="private">Private</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="description" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"></textarea>
                </div>
            </div>
            <div class="border-t border-gray-200 px-6 py-4 flex justify-end space-x-3">
                <button type="button" onclick="closeStockModal()" class="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Cancel
                </button>
                <button type="submit" name="add_stock" class="inline-flex justify-center rounded-md border border-transparent bg-red-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Add Blood Stock
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Blood Stock Modal -->
<div id="editModal" class="modal">
    <div class="modal-backdrop" onclick="closeEditModal()"></div>
    <div class="modal-content">
        <form method="POST">
            <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Edit Blood Stock</h3>
                <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-6 py-4">
                <input type="hidden" name="stock_id" id="stock_id">
                <div class="mb-4">
                    <label for="edit_blood_group_id" class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                    <select name="blood_group_id" id="edit_blood_group_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required>
                        <?php
                        $bg_result = $conn->query("SELECT * FROM blood_groups");
                        while ($bg = $bg_result->fetch_assoc()):
                        ?>
                            <option value="<?= $bg['id'] ?>"><?= $bg['group_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="edit_quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity (ml)</label>
                    <input type="number" name="total_quantity" id="edit_quantity" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required>
                </div>
                <div class="mb-4">
                    <label for="edit_request_date" class="block text-sm font-medium text-gray-700 mb-1">Request Date</label>
                    <input type="date" name="request_date" id="edit_request_date" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required>
                </div>
                <div class="mb-4">
                    <label for="edit_expire_date" class="block text-sm font-medium text-gray-700 mb-1">Expire Date</label>
                    <input type="date" name="expire_date" id="edit_expire_date" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required>
                </div>
                <div class="mb-4">
                    <label for="edit_status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="edit_status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required>
                        <option value="public">Public</option>
                        <option value="private">Private</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="edit_description" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"></textarea>
                </div>
            </div>
            <div class="border-t border-gray-200 px-6 py-4 flex justify-end space-x-3">
                <button type="button" onclick="closeEditModal()" class="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Cancel
                </button>
                <button type="submit" name="update_stock" class="inline-flex justify-center rounded-md border border-transparent bg-red-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Update Blood Stock
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

// Dropdown functionality
document.querySelectorAll('.group button').forEach(button => {
    button.addEventListener('click', function() {
        const group = this.closest('.group');
        const dropdown = group.querySelector('.dropdown-content');
        
        // Close all other dropdowns first
        document.querySelectorAll('.group').forEach(g => {
            if (g !== group) {
                g.classList.remove('active');
                g.querySelector('.dropdown-content').classList.remove('show');
            }
        });
        
        // Toggle current dropdown
        group.classList.toggle('active');
        dropdown.classList.toggle('show');
    });
});

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.group')) {
        document.querySelectorAll('.group').forEach(group => {
            group.classList.remove('active');
            group.querySelector('.dropdown-content').classList.remove('show');
        });
    }
});

// Search functionality with debounce
let searchTimer;
document.getElementById('searchInput').addEventListener('input', function(e) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const searchValue = this.value;
        const perPage = document.getElementById('perPageSelect').value;
        window.location.href = `?search=${encodeURIComponent(searchValue)}&per_page=${perPage}`;
    }, 500);
});

// Per page selection
document.getElementById('perPageSelect').addEventListener('change', function() {
    const searchValue = document.getElementById('searchInput').value;
    window.location.href = `?search=${encodeURIComponent(searchValue)}&per_page=${this.value}`;
});

function confirmDelete(event) {
    event.preventDefault();
    const url = event.currentTarget.href;
    Swal.fire({
        title: 'Are you sure?',
        text: "You are about to delete this blood stock record.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
    return false;
}

// Stock Modal functions
function openStockModal() {
    document.getElementById('stockModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeStockModal() {
    document.getElementById('stockModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Edit Modal functions
function editStock(data) {
    document.getElementById('stock_id').value = data.id;
    document.getElementById('edit_blood_group_id').value = data.blood_group_id;
    document.getElementById('edit_quantity').value = data.total_quantity;
    document.getElementById('edit_request_date').value = data.request_date;
    document.getElementById('edit_expire_date').value = data.expire_date;
    document.getElementById('edit_status').value = data.status;
    document.getElementById('edit_description').value = data.description;
    document.getElementById('editModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
    document.body.style.overflow = 'auto';
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