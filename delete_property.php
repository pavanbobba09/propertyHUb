<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'seller') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $property_id = mysqli_real_escape_string($conn, $_GET['id']);
    $seller_id = $_SESSION['user_id'];
    
    // Make sure the property belongs to the logged-in seller
    $sql = "DELETE FROM properties WHERE id = ? AND seller_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $property_id, $seller_id);
    
    if ($stmt->execute()) {
        header("Location: seller_dashboard.php?msg=deleted");
    } else {
        header("Location: seller_dashboard.php?error=delete_failed");
    }
} else {
    header("Location: seller_dashboard.php");
}
exit();
?>