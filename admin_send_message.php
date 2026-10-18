<?php
include 'config.php';
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}


$donor_id = isset($_GET['donor_id']) ? intval($_GET['donor_id']) : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = trim($_POST['message']);
    if ($message && $donor_id) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, sender_type) VALUES (?, ?, ?, 'admin')");
        $stmt->bind_param("iis", $_SESSION['admin_id'], $donor_id, $message);
        if ($stmt->execute()) {
            // Store in session that we need to show the alert
            $_SESSION['show_message_alert'] = true;
        }
        $stmt->close();
        exit; // Important to prevent form submission from reloading the page
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Message to Donor</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-2xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg p-6">
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-gray-800">Send Message to Donor #<?= $donor_id ?></h3>
                <p class="text-gray-600 mt-1">Send a direct message to this donor</p>
            </div>

            <form method="POST" class="space-y-6" id="messageForm">
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <textarea 
                        id="message" 
                        name="message" 
                        rows="4" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                        required
                        placeholder="Write your message here..."></textarea>
                </div>

                <div class="flex items-center justify-end space-x-3">
                    <a href="admin_manage_requests.php" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Back
                    </a>
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('messageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Create FormData object
            const formData = new FormData(this);
            
            // Add donor_id to the form data
            formData.append('donor_id', <?= $donor_id ?>);
            
            // Send form data via AJAX
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    // Show SweetAlert on success
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Message sent successfully!',
                        confirmButtonColor: '#3b82f6',
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    }).then(() => {
                        // Redirect after alert is closed
                        window.location.href = 'admin_manage_requests.php';
                    });
                } else {
                    throw new Error('Network response was not ok');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'There was a problem sending your message.',
                    confirmButtonColor: '#3b82f6'
                });
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>