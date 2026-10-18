<?php
session_start();
include 'config.php';

$login_error = "";

if (isset($_POST['login'])) {
    $phone = $_POST['phone'];
    $query = "SELECT * FROM patients WHERE phone = '$phone'";
    $result = $conn->query($query);

    if ($result->num_rows === 1) {
        $patient = $result->fetch_assoc();
        $_SESSION['patient_id'] = $patient['id'];
        header("Location: patient_dashboard.php");
        exit();
    } else {
        $login_error = "❌ Phone number not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Login</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">

    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md p-6 bg-white shadow-lg rounded-xl">
        <h2 class="text-2xl font-semibold text-center text-red-600 mb-4">Patient Login</h2>

        <?php if ($login_error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded text-center mb-4">
                <?= $login_error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label for="phone" class="block text-gray-700 font-medium mb-1">Phone Number</label>
                <input type="text" name="phone" id="phone" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="Enter your phone number" required>
            </div>

            <button type="submit" name="login" class="w-full bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-semibold transition duration-200">
                Login
            </button>
        </form>

        <div class="text-center mt-4 text-sm text-gray-600">
            Not registered? <a href="regester_patients.php" class="text-red-600 hover:underline">Register here</a>
        </div>
    </div>
</body>
</html>
