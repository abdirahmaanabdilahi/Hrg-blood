<?php
session_start();
include 'config.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = $_SESSION['patient_id'];

// Handle delete
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // Secure delete: Only delete if message belongs to the logged-in patient
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND receiver_patient_id = ?");
    $stmt->bind_param("ii", $delete_id, $patient_id);
    $stmt->execute();

    header("Location: patient-messages.php");
    exit();
}

// Fetch messages
$query = "SELECT id, message, sent_at FROM messages WHERE receiver_patient_id = ? ORDER BY sent_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Messages</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='red'><path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/><path d='M21 10h-2V8h-2v2h-2v2h2v2h2v-2h2z' fill='white'/></svg>">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-semibold mb-4">Messages from Admin</h2>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="border-b py-4 flex justify-between items-start">
                    <div>
                        <p><?= htmlspecialchars($row['message']) ?></p>
                        <small class="text-gray-500">Sent at: <?= $row['sent_at'] ?></small>
                    </div>
                    <button onclick="confirmDelete(<?= $row['id'] ?>)" class="text-red-600 hover:text-red-800">
                        🗑️
                    </button>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-gray-600">You have no messages.</p>
        <?php endif; ?>
    </div>

    <script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to delete this message?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e3342f',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'patient-messages.php?delete_id=' + id;
            }
        });
    }
    </script>
</body>
</html>
