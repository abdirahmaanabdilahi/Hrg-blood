<?php
session_start();
include 'config.php';

if (!isset($_SESSION['patient_id'])) {
    header("Location: patient_login.php");
    exit();
}

if (isset($_GET['id'])) {
    $request_id = intval($_GET['id']);
    $patient_id = $_SESSION['patient_id'];

    // Make sure the request belongs to this patient
    $check = $conn->prepare("SELECT id FROM requests WHERE id = ? AND patient_id = ?");
    $check->bind_param("ii", $request_id, $patient_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $delete = $conn->prepare("DELETE FROM requests WHERE id = ?");
        $delete->bind_param("i", $request_id);
        $delete->execute();
    }
}

header("Location: request_history.php");
exit();
