<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "blood_donation_system";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure admin is logged in


// Fetch all data needed for the report
$bloodStock = $conn->query("
    SELECT bg.group_name, SUM(bs.total_quantity) as total, 
           MAX(bs.expire_date) as nearest_expiry
    FROM blood_stock bs 
    JOIN blood_groups bg ON bs.blood_group_id = bg.id 
    GROUP BY bg.group_name
")->fetch_all(MYSQLI_ASSOC);

$donations = $conn->query("
    SELECT status, COUNT(*) as count, AVG(quantity) as avg, 
           SUM(quantity) as total, MAX(requested_date) as last_date
    FROM blood_donation_requests 
    GROUP BY status
")->fetch_all(MYSQLI_ASSOC);

$donors = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN gender = 'Male' THEN 1 ELSE 0 END) as male,
        SUM(CASE WHEN gender = 'Female' THEN 1 ELSE 0 END) as female,
        AVG(age) as avg_age,
        MIN(age) as min_age,
        MAX(age) as max_age
    FROM donors
")->fetch_assoc();

$ageGroups = $conn->query("
    SELECT 
        CASE 
            WHEN age < 20 THEN 'Under 20'
            WHEN age BETWEEN 20 AND 29 THEN '20-29'
            WHEN age BETWEEN 30 AND 39 THEN '30-39'
            WHEN age BETWEEN 40 AND 49 THEN '40-49'
            WHEN age >= 50 THEN '50+'
            ELSE 'Unknown'
        END as age_group,
        COUNT(*) as count
    FROM donors
    GROUP BY age_group
    ORDER BY 
        CASE age_group
            WHEN 'Under 20' THEN 1
            WHEN '20-29' THEN 2
            WHEN '30-39' THEN 3
            WHEN '40-49' THEN 4
            WHEN '50+' THEN 5
            ELSE 6
        END
")->fetch_all(MYSQLI_ASSOC);

$bloodTypeDistribution = $conn->query("
    SELECT bg.group_name, COUNT(d.id) as donor_count
    FROM blood_groups bg
    LEFT JOIN donors d ON bg.id = d.blood_group_id
    GROUP BY bg.group_name
    ORDER BY donor_count DESC
")->fetch_all(MYSQLI_ASSOC);

$recentRequests = $conn->query("
    SELECT r.id, p.full_name, bg.group_name, r.quantity_units, 
           r.request_date, r.status, r.reason
    FROM requests r
    JOIN patients p ON r.patient_id = p.id
    JOIN blood_groups bg ON r.blood_group_id = bg.id
    ORDER BY r.request_date DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$donationTrends = $conn->query("
    SELECT 
        DATE_FORMAT(requested_date, '%Y-%m') as month,
        COUNT(*) as donation_count,
        SUM(quantity) as total_quantity
    FROM blood_donation_requests
    WHERE requested_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month
")->fetch_all(MYSQLI_ASSOC);

$expiringSoon = $conn->query("
    SELECT bg.group_name, bs.total_quantity, bs.expire_date
    FROM blood_stock bs
    JOIN blood_groups bg ON bs.blood_group_id = bg.id
    WHERE bs.expire_date BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 15 DAY)
    ORDER BY bs.expire_date ASC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// New queries for additional sections
$topDonors = $conn->query("
    SELECT d.id, d.full_name, d.blood_group_id, bg.group_name, 
           COUNT(bdr.id) as donation_count, SUM(bdr.quantity) as total_donated
    FROM donors d
    LEFT JOIN blood_donation_requests bdr ON d.id = bdr.donor_id AND bdr.status = 'Approved'
    LEFT JOIN blood_groups bg ON d.blood_group_id = bg.id
    GROUP BY d.id
    ORDER BY total_donated DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$topPatients = $conn->query("
    SELECT p.id, p.full_name, p.blood_group_id, bg.group_name, 
           COUNT(r.id) as request_count, SUM(r.quantity_units) as total_requested
    FROM patients p
    LEFT JOIN requests r ON p.id = r.patient_id AND r.status = 'Approved'
    LEFT JOIN blood_groups bg ON p.blood_group_id = bg.id
    GROUP BY p.id
    ORDER BY total_requested DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$donorLocations = $conn->query("
    SELECT address, COUNT(*) as donor_count
    FROM donors
    WHERE address IS NOT NULL AND address != ''
    GROUP BY address
    ORDER BY donor_count DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$patientBloodGroups = $conn->query("
    SELECT bg.group_name, COUNT(p.id) as patient_count
    FROM blood_groups bg
    LEFT JOIN patients p ON bg.id = p.blood_group_id
    GROUP BY bg.group_name
    ORDER BY patient_count DESC
")->fetch_all(MYSQLI_ASSOC);

$donorBloodGroups = $conn->query("
    SELECT bg.group_name, COUNT(d.id) as donor_count, 
           SUM(CASE WHEN bdr.status = 'Approved' THEN bdr.quantity ELSE 0 END) as total_donated
    FROM blood_groups bg
    LEFT JOIN donors d ON bg.id = d.blood_group_id
    LEFT JOIN blood_donation_requests bdr ON d.id = bdr.donor_id
    GROUP BY bg.group_name
    ORDER BY total_donated DESC
")->fetch_all(MYSQLI_ASSOC);

$requestStats = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM requests) as total_requests,
        (SELECT COUNT(*) FROM requests WHERE status = 'Approved') as approved_requests,
        (SELECT COUNT(*) FROM requests WHERE status = 'Pending') as pending_requests,
        (SELECT COUNT(*) FROM blood_donation_requests) as total_donor_requests,
        (SELECT COUNT(*) FROM blood_donation_requests WHERE status = 'Approved') as approved_donor_requests,
        (SELECT COUNT(*) FROM blood_donation_requests WHERE status = 'Pending') as pending_donor_requests
")->fetch_assoc();

$patientReasons = $conn->query("
    SELECT reason, COUNT(*) as request_count
    FROM requests
    GROUP BY reason
    ORDER BY request_count DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$conn->close();

$show_export_buttons = true;
if (isset($_GET['noexport'])) {
    $show_export_buttons = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Donation System Analytics</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Export Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/docx@7.8.2/build/index.min.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
    <style>
        @media print {
            .no-print, .no-print * {
                display: none !important;
            }
        }

        /* Professional Button Styling */
.dashboard-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    border-radius: 0.375rem;
    font-weight: 500;
    font-size: 0.875rem;
    line-height: 1.25rem;
    text-align: center;
    text-decoration: none;
    transition: all 0.2s ease-in-out;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    background-color: #4f46e5; /* Professional indigo */
    color: white;
    border: 1px solid #4f46e5;
}

.dashboard-button:hover {
    background-color: #4338ca;
    border-color: #4338ca;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.dashboard-button:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.dashboard-button .button-content {
    display: flex;
    align-items: center;
}

.dashboard-button i {
    transition: transform 0.2s ease-in-out;
}

.dashboard-button:hover i {
    transform: translateX(-3px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-button {
        padding: 0.5rem 1rem;
        font-size: 0.8125rem;
    }
    
    .dashboard-button i {
        margin-right: 0.25rem;
    }
}
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <div class="container mx-auto px-4 py-8">
        
        <!-- Export Buttons -->
        <?php if ($show_export_buttons): ?>
        <div class="flex justify-end mb-6 space-x-3 no-print">
        <a href="admin_dashboard.php" class="dashboard-button">
        <span class="button-content">
            <i class="fas fa-chevron-left mr-2 transition-transform"></i>
            <span>Return to Dashboard</span>
        </span>
    </a>
            <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-print mr-2"></i> Print
            </button>
        </div>
        <?php endif; ?>

        <!-- Header -->
        <header class="mb-10">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-red-700 mb-1">Blood Donation System Analytics</h1>
                    <p class="text-gray-600">Comprehensive report generated on <?= date('F j, Y') ?></p>
                </div>
                <div class="bg-red-100 p-3 rounded-full">
                    <i class="fas fa-tint text-red-600 text-2xl"></i>
                </div>
            </div>
            <div class="border-b border-gray-200 mt-4"></div>
        </header>

        <!-- Executive Summary -->
        <section class="mb-12">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-chart-line text-red-600 mr-2"></i> Executive Summary
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
                    <div class="flex items-center">
                        <div class="bg-red-100 p-3 rounded-full mr-4">
                            <i class="fas fa-tint text-red-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-700">Total Blood Stock</h3>
                            <p class="text-2xl font-bold text-gray-800 mt-1">
                                <?= number_format(array_sum(array_column($bloodStock, 'total'))) ?> ml
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-full mr-4">
                            <i class="fas fa-users text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-700">Registered Donors</h3>
                            <p class="text-2xl font-bold text-gray-800 mt-1"><?= $donors['total'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-full mr-4">
                            <i class="fas fa-hand-holding-medical text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-700">Approved Donations</h3>
                            <p class="text-2xl font-bold text-gray-800 mt-1">
                                <?= array_column($donations, 'count', 'status')['Approved'] ?? 0 ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500">
                    <div class="flex items-center">
                        <div class="bg-purple-100 p-3 rounded-full mr-4">
                            <i class="fas fa-procedures text-purple-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-700">Pending Requests</h3>
                            <p class="text-2xl font-bold text-gray-800 mt-1">
                                <?= array_column($donations, 'count', 'status')['Pending'] ?? 0 ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Top Donors and Patients -->
        <section class="mb-12">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-star text-yellow-500 mr-2"></i> Top Contributors
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-700 mb-4 flex items-center">
                        <i class="fas fa-hand-holding-water text-red-500 mr-2"></i> Top Donors
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Donor</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Blood Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Donations</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total (ml)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($topDonors as $donor): ?>
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap font-medium"><?= $donor['full_name'] ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap"><?= $donor['group_name'] ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap"><?= $donor['donation_count'] ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-red-600 font-medium"><?= $donor['total_donated'] ?? 0 ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-700 mb-4 flex items-center">
                        <i class="fas fa-user-injured text-blue-500 mr-2"></i> Top Patients
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Blood Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requests</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total (units)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($topPatients as $patient): ?>
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap font-medium"><?= $patient['full_name'] ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap"><?= $patient['group_name'] ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap"><?= $patient['request_count'] ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-blue-600 font-medium"><?= $patient['total_requested'] ?? 0 ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Donor Locations -->
        <section class="mb-12">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-map-marker-alt text-green-600 mr-2"></i> Donor Locations
            </h2>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-700 mb-4">Most Common Donor Locations</h3>
                        <div class="space-y-4">
                            <?php foreach ($donorLocations as $location): ?>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="font-medium"><?= $location['address'] ?></span>
                                    <span class="text-gray-600"><?= $location['donor_count'] ?> donors</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-green-600 h-2.5 rounded-full" 
                                         style="width: <?= min(100, ($location['donor_count'] / max(array_column($donorLocations, 'donor_count')) * 100)) ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-700 mb-4">Request Statistics</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="text-blue-800 font-medium mb-1">Patient Requests</div>
                                <div class="text-2xl font-bold"><?= $requestStats['total_requests'] ?></div>
                                <div class="text-sm text-gray-600 mt-1">
                                    <span class="text-green-600"><?= $requestStats['approved_requests'] ?> approved</span> • 
                                    <span class="text-yellow-600"><?= $requestStats['pending_requests'] ?> pending</span>
                                </div>
                            </div>
                            
                            <div class="bg-red-50 p-4 rounded-lg">
                                <div class="text-red-800 font-medium mb-1">Donor Requests</div>
                                <div class="text-2xl font-bold"><?= $requestStats['total_donor_requests'] ?></div>
                                <div class="text-sm text-gray-600 mt-1">
                                    <span class="text-green-600"><?= $requestStats['approved_donor_requests'] ?> approved</span> • 
                                    <span class="text-yellow-600"><?= $requestStats['pending_donor_requests'] ?> pending</span>
                                </div>
                            </div>
                            
                            <div class="bg-purple-50 p-4 rounded-lg col-span-2">
                                <div class="text-purple-800 font-medium mb-2">Most Common Patient Reasons</div>
                                <div class="space-y-2">
                                    <?php foreach ($patientReasons as $reason): ?>
                                    <div class="flex items-center">
                                        <div class="w-2 h-2 bg-purple-600 rounded-full mr-2"></div>
                                        <span class="text-sm"><?= $reason['reason'] ?></span>
                                        <span class="text-sm font-medium ml-auto"><?= $reason['request_count'] ?> requests</span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

       <!-- Blood Group Analysis -->
<section class="mb-12">
    <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
        <i class="fas fa-dna text-purple-600 mr-2"></i> Blood Group Analysis
    </h2>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Patient Blood Groups Chart -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-700 mb-4">Patient Blood Groups</h3>
            <canvas id="patientBloodGroupsChart" class="w-full h-64"></canvas>
            <div class="mt-4 text-sm text-gray-600">
                <p>Total Patients: <?= array_sum(array_column($patientBloodGroups, 'patient_count')) ?></p>
                <p>Most Common: <?= $patientBloodGroups[0]['group_name'] ?? 'N/A' ?> (<?= $patientBloodGroups[0]['patient_count'] ?? 0 ?>)</p>
            </div>
        </div>
        
        <!-- Donor Blood Groups Chart -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-700 mb-4">Donor Blood Groups</h3>
            <canvas id="donorBloodGroupsChart" class="w-full h-64"></canvas>
            <div class="mt-4 text-sm text-gray-600">
                <p>Total Donors: <?= array_sum(array_column($donorBloodGroups, 'donor_count')) ?></p>
                <p>Most Common: <?= $donorBloodGroups[0]['group_name'] ?? 'N/A' ?> (<?= $donorBloodGroups[0]['donor_count'] ?? 0 ?>)</p>
            </div>
        </div>
        
        <!-- Blood Group Comparison Table -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-700 mb-4">Blood Group Comparison</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Blood Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patients</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Donors</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        // Create combined list of all blood groups
                        $allGroups = [];
                        foreach ($patientBloodGroups as $group) $allGroups[$group['group_name']] = true;
                        foreach ($donorBloodGroups as $group) $allGroups[$group['group_name']] = true;
                        ksort($allGroups);
                        
                        foreach ($allGroups as $groupName => $val): 
                            $patientCount = 0;
                            $donorCount = 0;
                            
                            foreach ($patientBloodGroups as $group) {
                                if ($group['group_name'] == $groupName) {
                                    $patientCount = $group['patient_count'];
                                    break;
                                }
                            }
                            
                            foreach ($donorBloodGroups as $group) {
                                if ($group['group_name'] == $groupName) {
                                    $donorCount = $group['donor_count'];
                                    break;
                                }
                            }
                        ?>
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap font-medium"><?= $groupName ?></td>
                            <td class="px-4 py-3 whitespace-nowrap"><?= $patientCount ?></td>
                            <td class="px-4 py-3 whitespace-nowrap"><?= $donorCount ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

        <!-- Blood Stock Analysis -->
        <section class="mb-12">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-warehouse text-red-600 mr-2"></i> Blood Inventory
            </h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow lg:col-span-2">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-700">Blood Stock Levels</h3>
                        <span class="text-sm text-gray-500">Updated: <?= date('M j, Y') ?></span>
                    </div>
                    <canvas id="bloodStockChart" class="w-full h-72"></canvas>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Inventory Status</h3>
                    <div class="space-y-4">
                        <?php foreach ($bloodStock as $stock): ?>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="font-medium"><?= $stock['group_name'] ?></span>
                                <span class="text-gray-600"><?= $stock['total'] ?> ml</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-red-600 h-2.5 rounded-full" 
                                     style="width: <?= min(100, ($stock['total'] / 1000) * 100) ?>%"></div>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                <?php if ($stock['nearest_expiry']): ?>
                                Expires: <?= date('M j, Y', strtotime($stock['nearest_expiry'])) ?>
                                <?php else: ?>
                                No expiry data
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Blood Expiring Soon (Next 15 Days)</h3>
                    <?php if (!empty($expiringSoon)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Blood Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($expiringSoon as $item): ?>
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap font-medium"><?= $item['group_name'] ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap"><?= $item['total_quantity'] ?> ml</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-red-600 font-medium">
                                        <?= date('M j, Y', strtotime($item['expire_date'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-6 text-gray-500">
                        <i class="fas fa-check-circle text-green-500 text-3xl mb-2"></i>
                        <p>No blood units expiring in the next 15 days</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Recent Blood Requests</h3>
                    <div class="space-y-4">
                        <?php foreach ($recentRequests as $request): ?>
                        <div class="border-l-4 border-blue-500 pl-4 py-2">
                            <div class="flex justify-between">
                                <span class="font-medium"><?= $request['full_name'] ?></span>
                                <span class="text-sm <?= $request['status'] === 'Approved' ? 'text-green-600' : 'text-yellow-600' ?>">
                                    <?= $request['status'] ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-600">
                                <?= $request['group_name'] ?> • <?= $request['quantity_units'] ?> units
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                <?= date('M j, Y', strtotime($request['request_date'])) ?> • <?= $request['reason'] ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Donor Analytics -->
        <section class="mb-12">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-user-friends text-blue-600 mr-2"></i> Donor Analytics
            </h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Gender Distribution</h3>
                    <canvas id="genderChart" class="w-full h-48"></canvas>
                    <div class="mt-4 text-center text-sm text-gray-600">
                        <span class="inline-block w-3 h-3 bg-blue-500 rounded-full mr-1"></span>
                        Male: <?= $donors['male'] ?> (<?= round(($donors['male']/$donors['total'])*100) ?>%)
                        <span class="inline-block w-3 h-3 bg-pink-500 rounded-full ml-4 mr-1"></span>
                        Female: <?= $donors['female'] ?> (<?= round(($donors['female']/$donors['total'])*100) ?>%)
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Age Distribution</h3>
                    <canvas id="ageChart" class="w-full h-48"></canvas>
                    <div class="mt-2 text-center text-sm text-gray-600">
                        Range: <?= $donors['min_age'] ?>-<?= $donors['max_age'] ?> years • Avg: <?= round($donors['avg_age']) ?> years
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Blood Type Distribution</h3>
                    <canvas id="bloodTypeChart" class="w-full h-48"></canvas>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-medium text-gray-700 mb-4">Donation Trends (Last 6 Months)</h3>
                <canvas id="donationTrendsChart" class="w-full h-64"></canvas>
            </div>
        </section>

        <!-- System Insights -->
        <section class="mb-12">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-lightbulb text-yellow-500 mr-2"></i> System Insights
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Strengths</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>High O+ blood stock (8,500ml) - the most common blood type</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Active donor base with recent donations</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>No blood expiring in the immediate future</span>
                        </li>
                    </ul>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Opportunities</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mt-1 mr-2"></i>
                            <span>Several blood types have no registered donors (AB-, B+, B-, O-)</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mt-1 mr-2"></i>
                            <span>No female donors in the system - consider targeted campaigns</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mt-1 mr-2"></i>
                            <span>Limited donors in 20-29 age range - important for long-term donor base</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Actionable Recommendations -->
        <section class="mb-12">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-clipboard-list text-purple-600 mr-2"></i> Recommendations
            </h2>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="border-l-4 border-red-500 pl-4">
                        <h3 class="font-medium text-lg text-gray-800 mb-3">Donor Recruitment</h3>
                        <ul class="space-y-2 text-gray-700">
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-red-500 mt-1 mr-2 text-xs"></i>
                                <span>Target campaigns for missing blood types</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-red-500 mt-1 mr-2 text-xs"></i>
                                <span>Develop university partnerships for younger donors</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-red-500 mt-1 mr-2 text-xs"></i>
                                <span>Create women-focused donation initiatives</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="border-l-4 border-blue-500 pl-4">
                        <h3 class="font-medium text-lg text-gray-800 mb-3">Inventory Management</h3>
                        <ul class="space-y-2 text-gray-700">
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-blue-500 mt-1 mr-2 text-xs"></i>
                                <span>Monitor O+ levels to prevent overstocking</span>
                            </li>
                            <li class="fas fa-arrow-right text-blue-500 mt-1 mr-2 text-xs"></i>
                                <span>Implement FIFO system for blood usage</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-blue-500 mt-1 mr-2 text-xs"></i>
                                <span>Regular stock audits and reporting</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="border-l-4 border-green-500 pl-4">
                        <h3 class="font-medium text-lg text-gray-800 mb-3">Donor Engagement</h3>
                        <ul class="space-y-2 text-gray-700">
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-green-500 mt-1 mr-2 text-xs"></i>
                                <span>Create donor loyalty program</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-green-500 mt-1 mr-2 text-xs"></i>
                                <span>Implement regular communication (SMS/email)</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-green-500 mt-1 mr-2 text-xs"></i>
                                <span>Recognize frequent donors publicly</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-green-500 mt-1 mr-2 text-xs"></i>
                                <span>Provide health checkups for regular donors</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Patient Blood Groups Chart
    const patientBloodGroupsCtx = document.getElementById('patientBloodGroupsChart').getContext('2d');
    const patientBloodGroupsChart = new Chart(patientBloodGroupsCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($patientBloodGroups, 'group_name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($patientBloodGroups, 'patient_count')) ?>,
                backgroundColor: [
                    '#ef4444', '#f97316', '#f59e0b', '#10b981',
                    '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Donor Blood Groups Chart
    const donorBloodGroupsCtx = document.getElementById('donorBloodGroupsChart').getContext('2d');
    const donorBloodGroupsChart = new Chart(donorBloodGroupsCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($donorBloodGroups, 'group_name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($donorBloodGroups, 'donor_count')) ?>,
                backgroundColor: [
                    '#ef4444', '#f97316', '#f59e0b', '#10b981',
                    '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Blood Stock Chart
    const bloodStockCtx = document.getElementById('bloodStockChart').getContext('2d');
    const bloodStockChart = new Chart(bloodStockCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($bloodStock, 'group_name')) ?>,
            datasets: [{
                label: 'Blood Stock (ml)',
                data: <?= json_encode(array_column($bloodStock, 'total')) ?>,
                backgroundColor: '#ef4444',
                borderColor: '#dc2626',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            const data = <?= json_encode($bloodStock) ?>;
                            const index = context.dataIndex;
                            if (data[index].nearest_expiry) {
                                return `Next expiry: ${new Date(data[index].nearest_expiry).toLocaleDateString()}`;
                            }
                            return '';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Quantity (ml)'
                    }
                }
            }
        }
    });

    // Gender Distribution Chart
    const genderCtx = document.getElementById('genderChart').getContext('2d');
    const genderChart = new Chart(genderCtx, {
        type: 'doughnut',
        data: {
            labels: ['Male', 'Female'],
            datasets: [{
                data: [<?= $donors['male'] ?>, <?= $donors['female'] ?>],
                backgroundColor: ['#3b82f6', '#ec4899'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '70%'
        }
    });

    // Age Distribution Chart
    const ageCtx = document.getElementById('ageChart').getContext('2d');
    const ageChart = new Chart(ageCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($ageGroups, 'age_group')) ?>,
            datasets: [{
                label: 'Donors',
                data: <?= json_encode(array_column($ageGroups, 'count')) ?>,
                backgroundColor: '#10b981',
                borderColor: '#059669',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Donors'
                    }
                }
            }
        }
    });

    // Blood Type Distribution Chart
    const bloodTypeCtx = document.getElementById('bloodTypeChart').getContext('2d');
    const bloodTypeChart = new Chart(bloodTypeCtx, {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_column($bloodTypeDistribution, 'group_name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($bloodTypeDistribution, 'donor_count')) ?>,
                backgroundColor: [
                    '#ef4444', '#f97316', '#f59e0b', '#10b981', 
                    '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });

    // Donation Trends Chart
    const donationTrendsCtx = document.getElementById('donationTrendsChart').getContext('2d');
    const donationTrendsChart = new Chart(donationTrendsCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($donationTrends, 'month')) ?>,
            datasets: [
                {
                    label: 'Number of Donations',
                    data: <?= json_encode(array_column($donationTrends, 'donation_count')) ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    yAxisID: 'y'
                },
                {
                    label: 'Total Quantity (ml)',
                    data: <?= json_encode(array_column($donationTrends, 'total_quantity')) ?>,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.datasetIndex === 0) {
                                label += context.raw + ' donations';
                            } else {
                                label += context.raw + ' ml';
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Number of Donations'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Total Quantity (ml)'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
});

// Add this at the end of your existing script section, before the closing </script> tag
<script>
// Export to PDF
document.getElementById('exportPdf').addEventListener('click', function() {
    // Initialize jsPDF
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'pt', 'a4');
    
    // Add title
    doc.setFontSize(18);
    doc.setTextColor(40);
    doc.text('Blood Donation System Analytics Report', 40, 40);
    
    doc.setFontSize(12);
    doc.text(`Generated on ${new Date().toLocaleDateString()}`, 40, 60);
    
    // Add a line
    doc.setDrawColor(200);
    doc.setLineWidth(0.5);
    doc.line(40, 70, 555, 70);
    
    let yPosition = 90;
    
    // Add executive summary
    doc.setFontSize(14);
    doc.setTextColor(0, 0, 0);
    doc.text('Executive Summary', 40, yPosition);
    yPosition += 20;
    
    // Add summary cards
    const cards = [
        { title: 'Total Blood Stock', value: '<?= number_format(array_sum(array_column($bloodStock, "total"))) ?> ml', color: [239, 68, 68] },
        { title: 'Registered Donors', value: '<?= $donors["total"] ?>', color: [59, 130, 246] },
        { title: 'Approved Donations', value: '<?= array_column($donations, "count", "status")["Approved"] ?? 0 ?>', color: [16, 185, 129] },
        { title: 'Pending Requests', value: '<?= array_column($donations, "count", "status")["Pending"] ?? 0 ?>', color: [139, 92, 246] }
    ];
    
    cards.forEach((card, i) => {
        const x = 40 + (i % 2) * 260;
        const y = yPosition + Math.floor(i / 2) * 60;
        
        doc.setFillColor(card.color[0], card.color[1], card.color[2], 10);
        doc.roundedRect(x, y, 240, 50, 3, 3, 'F');
        doc.setDrawColor(card.color[0], card.color[1], card.color[2]);
        doc.roundedRect(x, y, 240, 50, 3, 3, 'S');
        
        doc.setFontSize(12);
        doc.setTextColor(100);
        doc.text(card.title, x + 15, y + 20);
        
        doc.setFontSize(16);
        doc.setTextColor(0);
        doc.text(card.value, x + 15, y + 35);
    });
    
    yPosition += 120;
    
    // Add blood stock table
    doc.setFontSize(14);
    doc.text('Blood Stock Levels', 40, yPosition);
    yPosition += 20;
    
    const bloodStockData = [
        ['Blood Type', 'Quantity (ml)', 'Nearest Expiry']
    ];
    
    <?php foreach ($bloodStock as $stock): ?>
    bloodStockData.push([
        '<?= $stock["group_name"] ?>',
        '<?= $stock["total"] ?>',
        '<?= $stock["nearest_expiry"] ? date("M j, Y", strtotime($stock["nearest_expiry"])) : "N/A" ?>'
    ]);
    <?php endforeach; ?>
    
    doc.autoTable({
        startY: yPosition,
        head: [bloodStockData[0]],
        body: bloodStockData.slice(1),
        margin: { left: 40 },
        styles: { fontSize: 10 },
        headStyles: { fillColor: [239, 68, 68], textColor: 255 }
    });
    
    yPosition = doc.lastAutoTable.finalY + 20;
    
    // Add donor statistics
    doc.setFontSize(14);
    doc.text('Donor Statistics', 40, yPosition);
    yPosition += 20;
    
    const donorData = [
        ['Metric', 'Value'],
        ['Total Donors', '<?= $donors["total"] ?>'],
        ['Male Donors', '<?= $donors["male"] ?> (<?= round(($donors["male"]/$donors["total"])*100) ?>%)'],
        ['Female Donors', '<?= $donors["female"] ?> (<?= round(($donors["female"]/$donors["total"])*100) ?>%)'],
        ['Average Age', '<?= round($donors["avg_age"]) ?> years'],
        ['Age Range', '<?= $donors["min_age"] ?>-<?= $donors["max_age"] ?> years']
    ];
    
    doc.autoTable({
        startY: yPosition,
        body: donorData,
        margin: { left: 40 },
        styles: { fontSize: 10 },
        columnStyles: { 0: { fontStyle: 'bold' } },
        theme: 'grid',
        headStyles: { fillColor: [59, 130, 246], textColor: 255 }
    });
    
    yPosition = doc.lastAutoTable.finalY + 20;
    
    // Add recent requests
    doc.setFontSize(14);
    doc.text('Recent Blood Requests', 40, yPosition);
    yPosition += 20;
    
    const recentRequestsData = [
        ['Patient', 'Blood Type', 'Quantity', 'Status', 'Date']
    ];
    
    <?php foreach ($recentRequests as $request): ?>
    recentRequestsData.push([
        '<?= $request["full_name"] ?>',
        '<?= $request["group_name"] ?>',
        '<?= $request["quantity_units"] ?> units',
        '<?= $request["status"] ?>',
        '<?= date("M j, Y", strtotime($request["request_date"])) ?>'
    ]);
    <?php endforeach; ?>
    
    doc.autoTable({
        startY: yPosition,
        head: [recentRequestsData[0]],
        body: recentRequestsData.slice(1),
        margin: { left: 40 },
        styles: { fontSize: 9 },
        headStyles: { fillColor: [16, 185, 129], textColor: 255 }
    });
    
    // Add page number
    const pageCount = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(10);
        doc.setTextColor(150);
        doc.text(`Page ${i} of ${pageCount}`, 520, doc.internal.pageSize.height - 20);
    }
    
    // Save the PDF
    doc.save('Blood_Donation_Analytics_Report.pdf');
});

// Export to Excel
document.getElementById('exportExcel').addEventListener('click', function() {
    // Create a new workbook
    const wb = XLSX.utils.book_new();
    wb.Props = {
        Title: "Blood Donation System Analytics",
        Subject: "Analytics Report",
        Author: "Blood Donation System",
        CreatedDate: new Date()
    };
    
    // Add Blood Stock sheet
    const bloodStockData = [
        ['Blood Type', 'Quantity (ml)', 'Nearest Expiry Date']
    ];
    
    <?php foreach ($bloodStock as $stock): ?>
    bloodStockData.push([
        '<?= $stock["group_name"] ?>',
        <?= $stock["total"] ?>,
        '<?= $stock["nearest_expiry"] ?>'
    ]);
    <?php endforeach; ?>
    
    const bloodStockWS = XLSX.utils.aoa_to_sheet(bloodStockData);
    XLSX.utils.book_append_sheet(wb, bloodStockWS, "Blood Stock");
    
    // Add Donor Statistics sheet
    const donorData = [
        ['Metric', 'Value'],
        ['Total Donors', <?= $donors["total"] ?>],
        ['Male Donors', <?= $donors["male"] ?>],
        ['Female Donors', <?= $donors["female"] ?>],
        ['Average Age', <?= round($donors["avg_age"]) ?>],
        ['Minimum Age', <?= $donors["min_age"] ?>],
        ['Maximum Age', <?= $donors["max_age"] ?>]
    ];
    
    const donorWS = XLSX.utils.aoa_to_sheet(donorData);
    XLSX.utils.book_append_sheet(wb, donorWS, "Donor Stats");
    
    // Add Age Distribution sheet
    const ageData = [
        ['Age Group', 'Number of Donors']
    ];
    
    <?php foreach ($ageGroups as $group): ?>
    ageData.push([
        '<?= $group["age_group"] ?>',
        <?= $group["count"] ?>
    ]);
    <?php endforeach; ?>
    
    const ageWS = XLSX.utils.aoa_to_sheet(ageData);
    XLSX.utils.book_append_sheet(wb, ageWS, "Age Distribution");
    
    // Add Recent Requests sheet
    const requestsData = [
        ['Patient Name', 'Blood Type', 'Quantity (units)', 'Status', 'Request Date', 'Reason']
    ];
    
    <?php foreach ($recentRequests as $request): ?>
    requestsData.push([
        '<?= $request["full_name"] ?>',
        '<?= $request["group_name"] ?>',
        <?= $request["quantity_units"] ?>,
        '<?= $request["status"] ?>',
        '<?= $request["request_date"] ?>',
        '<?= $request["reason"] ?>'
    ]);
    <?php endforeach; ?>
    
    const requestsWS = XLSX.utils.aoa_to_sheet(requestsData);
    XLSX.utils.book_append_sheet(wb, requestsWS, "Recent Requests");
    
    // Add Top Donors sheet
    const topDonorsData = [
        ['Donor Name', 'Blood Type', 'Donation Count', 'Total Donated (ml)']
    ];
    
    <?php foreach ($topDonors as $donor): ?>
    topDonorsData.push([
        '<?= $donor["full_name"] ?>',
        '<?= $donor["group_name"] ?>',
        <?= $donor["donation_count"] ?>,
        <?= $donor["total_donated"] ?? 0 ?>
    ]);
    <?php endforeach; ?>
    
    const topDonorsWS = XLSX.utils.aoa_to_sheet(topDonorsData);
    XLSX.utils.book_append_sheet(wb, topDonorsWS, "Top Donors");
    
    // Save the Excel file
    XLSX.writeFile(wb, 'Blood_Donation_Analytics.xlsx');
});
</script>



