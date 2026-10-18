<?php include 'config.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration</title>
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
                <h2 class="text-xl font-semibold text-white">Patient Registration</h2>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">
                <!-- Full Name -->
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="full_name" id="full_name" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500" placeholder="Enter patient's full name" required>
                </div>

                <!-- Phone Number -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="text" name="phone" id="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500" placeholder="+1234567890" required>
                </div>

                <!-- Gender, Age, and Marital Status in one row -->
                <div class="grid grid-cols-3 gap-4">
                    <!-- Gender -->
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <select name="gender" id="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <!-- Age -->
                    <div>
                        <label for="age" class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                        <input type="number" name="age" id="age" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500" min="0" placeholder="Age" required>
                    </div>

                    <!-- Marital Status -->
                    <div>
                        <label for="marriage_status" class="block text-sm font-medium text-gray-700 mb-1">Marital Status</label>
                        <select name="marriage_status" id="marriage_status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500" required>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Divorced">Divorced</option>
                        </select>
                    </div>
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

                <!-- Diagnosis -->
                <div>
                    <label for="diagnosis" class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
                    <textarea name="diagnosis" id="diagnosis" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500" placeholder="Enter patient's diagnosis" required></textarea>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" name="register" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Register Patient
                    </button>
                </div>
            </form>

            <?php
            if (isset($_POST['register'])) {
                $full_name = $_POST['full_name'];
                $gender = $_POST['gender'];
                $age = $_POST['age'];
                $marriage_status = $_POST['marriage_status'];
                $blood_group_id = $_POST['blood_group_id'];
                $diagnosis = $_POST['diagnosis'];
                $phone = $_POST['phone'];

                if ($age < 0) {
                    echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Age',
                            text: 'Age cannot be negative!'
                        });
                    </script>";
                } elseif (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
                    echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Phone',
                            text: 'Please enter a valid phone number!'
                        });
                    </script>";
                } else {
                    $sql = "INSERT INTO patients (full_name, gender, age, marriage_status, blood_group_id, diagnosis, phone)
                            VALUES ('$full_name', '$gender', $age, '$marriage_status', $blood_group_id, '$diagnosis', '$phone')";

                    if ($conn->query($sql) === TRUE) {
                        echo "<script>
                            Swal.fire({
                                icon: 'success',
                                title: 'Registration Successful!',
                                text: 'Patient has been registered successfully.',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.href = 'patient_login.php';
                            });
                        </script>";
                    } else {
                        echo "<script>
                            Swal.fire({
                                icon: 'error',
                                title: 'Registration Failed',
                                text: 'Error: " . $conn->error . "'
                            });
                        </script>";
                    }
                }
            }
            ?>

            <div class="px-6 py-4 bg-gray-50 text-center">
                <p class="text-sm text-gray-600">Already registered? 
                    <a href="patient_login.php" class="font-medium text-red-600 hover:text-red-500">Login here</a>
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>