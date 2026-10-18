<?php
session_start();
include 'config.php';

// Handle Add Donor
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"]);
    $gender = $_POST["gender"];
    $age = (int)$_POST["age"];
    $phone = trim($_POST["phone"]);
    $blood_group_id = $_POST["blood_group_id"];
    $last_donation_date = $_POST["last_donation_date"];
    $address = trim($_POST["address"]);

    if ($age < 15) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Donor must be at least 15 years old.'];
    } elseif (empty($full_name) || empty($gender) || empty($phone) || empty($blood_group_id)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Please fill all required fields.'];
    } else {
        $stmt = $conn->prepare("INSERT INTO donors (full_name, gender, age, phone, blood_group_id, last_donation_date, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissss", $full_name, $gender, $age, $phone, $blood_group_id, $last_donation_date, $address);

        if ($stmt->execute()) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Donor added successfully.'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Error adding donor.'];
        }
    }

    header("Location: donors.php");
    exit;
}

// Handle Delete Donor
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM donors WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Donor deleted successfully.'];
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to delete donor.'];
    }
    header("Location: donors.php");
    exit;
}

// Pagination
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page > 1) ? ($page - 1) * $per_page : 0;

// Search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_condition = $search ? " AND (d.full_name LIKE '%$search%' OR d.phone LIKE '%$search%' OR bg.group_name LIKE '%$search%')" : '';

// Get total records for pagination
$total_query = $conn->query("SELECT COUNT(*) as total FROM donors d JOIN blood_groups bg ON d.blood_group_id = bg.id WHERE 1 $search_condition");
$total = $total_query->fetch_assoc()['total'];
$pages = ceil($total / $per_page);

// Get donors with pagination and search
$donors = $conn->query("SELECT d.*, bg.group_name FROM donors d JOIN blood_groups bg ON d.blood_group_id = bg.id WHERE 1 $search_condition ORDER BY d.full_name ASC LIMIT $start, $per_page");
$blood_groups = $conn->query("SELECT * FROM blood_groups");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Management | BloodBank</title>
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
            
            <a href="donors.php" class="nav-item active flex items-center py-3 px-4 rounded-lg bg-white bg-opacity-10">
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
                        <h1 class="text-2xl font-bold text-gray-800">Donor Management</h1>
                        <p class="text-gray-600">Manage all blood donors in the system</p>
                    </div>
                    
                    <button id="openAddDonorModal" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center mt-4 md:mt-0">
                        <i class="fas fa-plus mr-2"></i> Add Donor
                    </button>
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

                <!-- Donors Table -->
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Blood Group</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Donation</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $donors->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row['gender']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= (int)$row['age'] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row['phone']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <?= htmlspecialchars($row['group_name']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $row['last_donation_date'] ? htmlspecialchars($row['last_donation_date']) : 'Never' ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="?delete=<?= $row['id'] ?>" onclick="return confirmDelete(event)" class="text-red-600 hover:text-red-900 mr-3">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        &nbsp;&nbsp;
                                        <a href="blood_tests.php" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-flask"></i>
                                        </a>

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

<!-- Add Donor Modal -->
<div id="addDonorModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center px-4 z-50 opacity-0 invisible">
    <div class="modal-content bg-white rounded-xl shadow-xl w-full max-w-md">
        <form method="POST">
            <div class="border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Add New Donor</h3>
                <button type="button" id="closeAddDonorModal" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="full_name" id="full_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <select name="gender" id="gender" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label for="age" class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                        <input type="number" name="age" id="age" min="15" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                    </div>
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" id="phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                </div>
                
                <div>
                    <label for="blood_group_id" class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                    <select name="blood_group_id" id="blood_group_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" required>
                        <option value="">Select Blood Group</option>
                        <?php 
                        $blood_groups->data_seek(0); // Reset pointer
                        while ($bg = $blood_groups->fetch_assoc()): ?>
                            <option value="<?= $bg['id'] ?>"><?= $bg['group_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div>
                    <label for="last_donation_date" class="block text-sm font-medium text-gray-700 mb-1">Last Donation Date</label>
                    <input type="date" name="last_donation_date" id="last_donation_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500">
                </div>
                
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea name="address" id="address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500"></textarea>
                </div>
            </div>
            <div class="border-t border-gray-200 px-6 py-4 flex justify-end space-x-3">
                <button type="button" id="cancelAddDonor" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Add Donor
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

// Modal functionality
const modal = document.getElementById('addDonorModal');
const openModalBtn = document.getElementById('openAddDonorModal');
const closeModalBtn = document.getElementById('closeAddDonorModal');
const cancelModalBtn = document.getElementById('cancelAddDonor');

function openModal() {
    modal.classList.add('show');
    modal.classList.remove('invisible', 'opacity-0');
}

function closeModal() {
    modal.classList.remove('show');
    modal.classList.add('invisible', 'opacity-0');
}

openModalBtn.addEventListener('click', openModal);
closeModalBtn.addEventListener('click', closeModal);
cancelModalBtn.addEventListener('click', closeModal);

// Close modal when clicking outside
modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        closeModal();
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
    }, 500); // 500ms delay
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
        text: "You are about to delete this donor record.",
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