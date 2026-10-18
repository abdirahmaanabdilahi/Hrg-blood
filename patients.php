<?php
session_start();
include 'config.php';

// Insert or update patient
if (isset($_POST['save'])) {
    $name = trim($_POST["full_name"]);
    $gender = $_POST["gender"];
    $age = (int)$_POST["age"];
    $status = $_POST["marriage_status"];
    $blood = $_POST["blood_group_id"];
    $diagnosis = trim($_POST["diagnosis"]);

    if (empty($name) || empty($gender) || empty($blood)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Please fill all required fields.'];
    } else {
        $stmt = $conn->prepare("INSERT INTO patients (full_name, gender, age, marriage_status, blood_group_id, diagnosis) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisis", $name, $gender, $age, $status, $blood, $diagnosis);

        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Patient added successfully.'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Error adding patient.'];
        }
    }

    header("Location: patients.php");
    exit;
}

// Handle Update
if (isset($_POST['update'])) {
    $id = $_POST["id"];
    $name = trim($_POST["full_name"]);
    $gender = $_POST["gender"];
    $age = (int)$_POST["age"];
    $status = $_POST["marriage_status"];
    $blood = $_POST["blood_group_id"];
    $diagnosis = trim($_POST["diagnosis"]);

    if (empty($name) || empty($gender) || empty($blood)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Please fill all required fields.'];
    } else {
        $stmt = $conn->prepare("UPDATE patients SET full_name=?, gender=?, age=?, marriage_status=?, blood_group_id=?, diagnosis=? WHERE id=?");
        $stmt->bind_param("ssisisi", $name, $gender, $age, $status, $blood, $diagnosis, $id);

        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Patient updated successfully.'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Error updating patient.'];
        }
    }

    header("Location: patients.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Patient deleted successfully.'];
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to delete patient.'];
    }
    header("Location: patients.php");
    exit;
}

// Pagination
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page - 1) * $per_page : 0;

// Search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = $search ? " AND (p.full_name LIKE '%$search%' OR p.diagnosis LIKE '%$search%' OR bg.group_name LIKE '%$search%')" : '';

// Get total records for pagination
$total_query = $conn->query("SELECT COUNT(*) as total FROM patients p LEFT JOIN blood_groups bg ON p.blood_group_id = bg.id WHERE 1 $search_condition");
$total = $total_query->fetch_assoc()['total'];
$pages = ceil($total / $per_page);

// Get patients with pagination and search
$patients = $conn->query("SELECT p.*, bg.group_name FROM patients p LEFT JOIN blood_groups bg ON p.blood_group_id = bg.id WHERE 1 $search_condition ORDER BY p.full_name ASC LIMIT $start, $per_page");
$blood_groups = $conn->query("SELECT * FROM blood_groups");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients | BloodBank</title>
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
        
        .dropdown-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        
        .dropdown-content.show {
            max-height: 500px;
            transition: max-height 0.3s ease-in;
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
        
        .rotate-180 {
            transform: rotate(180deg);
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
            
            <a href="patients.php" class="nav-item flex items-center py-3 px-4 rounded-lg bg-white bg-opacity-20">
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
                    <i class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
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
                    <h1 class="text-2xl font-bold text-gray-800">🏥 Patients</h1>
                    
                    <!-- Button to Open Add Patient Modal -->
                    <button type="button" onclick="openPatientModal()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add Patient
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

                <!-- Patients Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marriage Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Blood Group</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnosis</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $patients->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($row['gender']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= (int)$row['age'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($row['marriage_status']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            <?= htmlspecialchars($row['group_name']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($row['diagnosis']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                       
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

<!-- Add/Edit Patient Modal -->
<div id="patientModal" class="modal">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closePatientModal()"></div>
    <div class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-full p-4 text-center">
            <div class="relative bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full sm:p-6">
                <div class="absolute top-0 right-0 pt-4 pr-4">
                    <button type="button" onclick="closePatientModal()" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Add Patient</h3>
                        <div class="mt-6">
                            <input type="hidden" name="id" id="patientId">
                            <div class="mb-4">
                                <label for="fullName" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" name="full_name" id="fullName" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required>
                            </div>
                            <div class="mb-4">
                                <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                <select name="gender" id="gender" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required>
                                    <option value="">Select Gender</option>
                                    <option>Male</option>
                                    <option>Female</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="age" class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                                <input type="number" name="age" id="age" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required min="0">
                            </div>
                            <div class="mb-4">
                                <label for="marriageStatus" class="block text-sm font-medium text-gray-700 mb-1">Marriage Status</label>
                                <input type="text" name="marriage_status" id="marriageStatus" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                            </div>
                            <div class="mb-4">
                                <label for="bloodGroup" class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                                <select name="blood_group_id" id="bloodGroup" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" required>
                                    <option value="">Select Blood Group</option>
                                    <?php while ($bg = $blood_groups->fetch_assoc()): ?>
                                        <option value="<?= $bg['id'] ?>"><?= $bg['group_name'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="diagnosis" class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
                                <textarea name="diagnosis" id="diagnosis" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="button" onclick="closePatientModal()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-red-500 sm:col-start-1 sm:text-sm">
                            Cancel
                        </button>
                        <button type="submit" name="save" id="saveBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 sm:mt-0 sm:col-start-2 sm:text-sm">
                            Save
                        </button>
                        <button type="submit" name="update" id="updateBtn" class="hidden mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 sm:mt-0 sm:col-start-2 sm:text-sm">
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle mobile menu
document.getElementById('menu-toggle').addEventListener('click', function() {
    document.querySelector('.glass-sidebar').classList.toggle('-translate-x-full');
});

// Dropdown functionality
document.querySelectorAll('.group button').forEach(button => {
    button.addEventListener('click', function(e) {
        e.stopPropagation();
        const dropdown = this.nextElementSibling;
        const icon = this.querySelector('.fa-chevron-down');
        
        // Close all other dropdowns
        document.querySelectorAll('.dropdown-content').forEach(d => {
            if (d !== dropdown) {
                d.classList.remove('show');
            }
        });
        
        // Close all other icons
        document.querySelectorAll('.group .fa-chevron-down').forEach(i => {
            if (i !== icon) {
                i.classList.remove('rotate-180');
            }
        });
        
        // Toggle current dropdown
        dropdown.classList.toggle('show');
        icon.classList.toggle('rotate-180');
    });
});

// Close dropdowns when clicking outside
document.addEventListener('click', function() {
    document.querySelectorAll('.dropdown-content').forEach(dropdown => {
        dropdown.classList.remove('show');
    });
    document.querySelectorAll('.group .fa-chevron-down').forEach(icon => {
        icon.classList.remove('rotate-180');
    });
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
        text: "You are about to delete this patient record.",
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

// Patient Modal functions
function openPatientModal() {
    clearForm();
    document.getElementById('patientModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closePatientModal() {
    document.getElementById('patientModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

function clearForm() {
    document.getElementById('patientId').value = '';
    document.getElementById('fullName').value = '';
    document.getElementById('gender').value = '';
    document.getElementById('age').value = '';
    document.getElementById('marriageStatus').value = '';
    document.getElementById('bloodGroup').value = '';
    document.getElementById('diagnosis').value = '';
    document.getElementById('modalTitle').textContent = 'Add Patient';
    document.getElementById('saveBtn').classList.remove('hidden');
    document.getElementById('updateBtn').classList.add('hidden');
}

function editPatient(data) {
    document.getElementById('patientId').value = data.id;
    document.getElementById('fullName').value = data.full_name;
    document.getElementById('gender').value = data.gender;
    document.getElementById('age').value = data.age;
    document.getElementById('marriageStatus').value = data.marriage_status;
    document.getElementById('bloodGroup').value = data.blood_group_id;
    document.getElementById('diagnosis').value = data.diagnosis;
    document.getElementById('modalTitle').textContent = 'Edit Patient';
    document.getElementById('saveBtn').classList.add('hidden');
    document.getElementById('updateBtn').classList.remove('hidden');
    document.getElementById('patientModal').classList.add('show');
    document.body.style.overflow = 'hidden';
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