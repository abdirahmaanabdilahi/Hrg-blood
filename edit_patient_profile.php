<?php
session_start();
include 'config.php';

// Check if logged in
if (!isset($_SESSION['patient_id'])) {
    header("Location: patient_login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];

// Fetch patient data
$query = "SELECT * FROM patients WHERE id = $patient_id";
$result = $conn->query($query);
$patient = $result->fetch_assoc();

// Fetch blood groups (we'll fetch this again after update if needed)
$groups_result = $conn->query("SELECT * FROM blood_groups");
$blood_groups = [];
while ($group = $groups_result->fetch_assoc()) {
    $blood_groups[] = $group;
}

// Update logic
$show_success_alert = false;
$show_error_alert = false;

if (isset($_POST['update'])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $gender = $conn->real_escape_string($_POST['gender']);
    $age = intval($_POST['age']);
    $marriage_status = $conn->real_escape_string($_POST['marriage_status']);
    $diagnosis = $conn->real_escape_string($_POST['diagnosis']);
    $blood_group_id = intval($_POST['blood_group_id']);

    $update_query = "UPDATE patients 
                     SET full_name='$full_name', gender='$gender', age=$age, 
                         marriage_status='$marriage_status', diagnosis='$diagnosis', 
                         blood_group_id=$blood_group_id
                     WHERE id = $patient_id";

    if ($conn->query($update_query)) {
        $show_success_alert = true;
        // Refresh patient data after update
        $result = $conn->query("SELECT * FROM patients WHERE id = $patient_id");
        $patient = $result->fetch_assoc();
    } else {
        $show_error_alert = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient Profile</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50">
<?php if ($show_success_alert): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Updated Successfully!',
                text: 'Your profile has been updated.',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'patient_profile.php';
            });
        });
    </script>
<?php elseif ($show_error_alert): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Update Failed',
                text: 'There was an error updating your profile.',
                confirmButtonText: 'OK'
            });
        });
    </script>
<?php endif; ?>

<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-red-500 py-4 px-6">
                <h2 class="text-xl font-semibold text-white">Edit Patient Profile</h2>
            </div>

            <form method="POST" class="p-6 space-y-4">
                <!-- Full Name -->
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="full_name" id="full_name" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500" value="<?= htmlspecialchars($patient['full_name']); ?>" required>
                </div>

                <!-- Gender and Age in one row -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Gender -->
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <select name="gender" id="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500" required>
                            <option value="Male" <?= $patient['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?= $patient['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>

                    <!-- Age -->
                    <div>
                        <label for="age" class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                        <input type="number" name="age" id="age" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500" min="1" max="120" value="<?= $patient['age']; ?>" required>
                    </div>
                </div>

                <!-- Marriage Status -->
                <div>
                    <label for="marriage_status" class="block text-sm font-medium text-gray-700 mb-1">Marriage Status</label>
                    <input type="text" name="marriage_status" id="marriage_status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500" value="<?= htmlspecialchars($patient['marriage_status']); ?>" required>
                </div>

                <!-- Diagnosis -->
                <div>
                    <label for="diagnosis" class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
                    <textarea name="diagnosis" id="diagnosis" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500"><?= htmlspecialchars($patient['diagnosis']); ?></textarea>
                </div>

                <!-- Blood Group -->
                <div>
                    <label for="blood_group_id" class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                    <select name="blood_group_id" id="blood_group_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-yellow-500 focus:border-yellow-500" required>
                        <?php foreach ($blood_groups as $group): ?>
                            <option value="<?= $group['id']; ?>" <?= $group['id'] == $patient['blood_group_id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($group['group_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Update Button -->
                    <button type="submit" name="update" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-500 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                        Update Profile
                    </button>
                    
                    <!-- Cancel Button -->
                    <a href="patient_profile.php" class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>