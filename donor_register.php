<?php include 'config.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Registration</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50">
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-red-600 py-4 px-6">
                <h2 class="text-xl font-semibold text-white">Donor Registration</h2>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">
                <!-- Full Name -->
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="full_name" id="full_name" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500" placeholder="Enter your full name" required>
                </div>

                <!-- Gender and Age in one row -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Gender -->
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <select name="gender" id="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500" required>
                            <option value="Male">Male</option>
                            
                        </select>
                    </div>

                    <!-- Age -->
                    <div>
                        <label for="age" class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                        <input type="number" name="age" id="age" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500" min="16" max="65" placeholder="Age" required>
                    </div>
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="text" name="phone" id="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500" placeholder="Enter phone number" required>
                </div>

                <!-- Address -->
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea name="address" id="address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500" placeholder="Enter your address" required></textarea>
                </div>

                <!-- Blood Group -->
                <div>
                    <label for="blood_group_id" class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                    <select name="blood_group_id" id="blood_group_id" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500" required>
                        <?php
                        $result = $conn->query("SELECT * FROM blood_groups");
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['group_name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" name="register" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Register
                    </button>
                </div>
            </form>

            <?php
            if (isset($_POST['register'])) {
                $name = $_POST['full_name'];
                $gender = $_POST['gender'];
                $age = $_POST['age'];
                $phone = $_POST['phone'];
                $address = $_POST['address'];
                $blood_group_id = $_POST['blood_group_id'];

                if ($age < 16 || $age > 65) {
                    echo "<script>
                        Swal.fire({
                            icon: 'warning',
                            title: 'Da'da lama ogola',
                            text: 'Diiwaangelintu waxay u furan tahay dadka da'doodu u dhaxayso 16 ilaa 65 sano.'
                        });
                    </script>";
                } else {
                    $sql = "INSERT INTO donors (full_name, gender, age, phone, address, blood_group_id)
                            VALUES ('$name', '$gender', $age, '$phone', '$address', $blood_group_id)";
                    if ($conn->query($sql) === TRUE) {
                        echo "<script>
                            Swal.fire({
                                icon: 'success',
                                title: 'Regesterd Success Fully!',
                                text: 'Fadlan geli si aad u gasho!',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.href = 'donor_login.php';
                            });
                        </script>";
                    } else {
                        echo "<script>
                            Swal.fire({
                                icon: 'error',
                                title: 'Qalad dhacay!',
                                text: 'Fadlan isku day mar kale.'
                            });
                        </script>";
                    }
                }
            }
            ?>

            <div class="px-6 py-4 bg-gray-50 text-center">
                <p class="text-sm text-gray-600">Already registered? 
                    <a href="donor_login.php" class="font-medium text-red-600 hover:text-red-500">Login here</a>
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>