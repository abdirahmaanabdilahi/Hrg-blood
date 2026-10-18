<?php
include 'config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM donors WHERE id = $id"; // beddel table_name haddii ay kala duwan tahay
    if (mysqli_query($conn, $sql)) {
        echo "Deleted";
    } else {
        echo "Error";
    }
}
?>
