<?php
include 'config.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['donor_id'])) {
    header("Location: donor_dashboard.php");
    exit();
}

$error = "";

if (isset($_POST['login'])) {
    $phone = trim($_POST['phone']);

    if (!empty($phone)) {
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, full_name FROM donors WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $donor = $result->fetch_assoc();
            $_SESSION['donor_id'] = $donor['id'];
            $_SESSION['donor_name'] = $donor['full_name'];
            header("Location: donor_dashboard.php");
            exit();
        } else {
            $error = "Invalid phone number.";
        }
        $stmt->close();
    } else {
        $error = "Phone number is required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Login</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-md p-8">
            <div class="flex justify-center mb-8">
                <h2 class="text-2xl font-bold text-gray-800">Donor Login</h2>
            </div>
            
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input 
                        type="text" 
                        name="phone" 
                        id="phone" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition"
                        placeholder="Enter your phone number" 
                        required
                    >
                </div>
                
                <button 
                    type="submit" 
                    name="login" 
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out transform hover:scale-[1.01] focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                >
                    Login
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">Don't have an account? 
                    <a href="donor_register.php" class="font-medium text-red-600 hover:text-red-700 transition">Sign Up</a>
                </p>
            </div>
        </div>
    </div>

    <?php if (!empty($error)): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: "<?= addslashes($error) ?>",
            confirmButtonColor: '#d33',
            backdrop: 'rgba(0,0,0,0.4)'
        });
    </script>
    <?php endif; ?>
</body>
</html>