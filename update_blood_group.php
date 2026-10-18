<?php
include 'config.php';

if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $group_name = $_POST['group_name'];

    $stmt = $conn->prepare("UPDATE blood_groups SET group_name = ? WHERE id = ?");
    $stmt->bind_param("si", $group_name, $id);
    $stmt->execute();
    header("Location: admin_blood_groups.php");
}
?>
