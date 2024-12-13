<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        header("Location: login.php?error=required");
        exit();
    }

    // Check user credentials
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];

            // Redirect based on user type
            switch($user['user_type']) {
                case 'seller':
                    header("Location: seller_dashboard.php");
                    break;
                case 'buyer':
                    header("Location: buyer_dashboard.php");
                    break;
                default:
                    header("Location: index.html");
            }
            exit();
        } else {
            header("Location: login.php?error=invalid");
            exit();
        }
    } else {
        header("Location: login.php?error=invalid");
        exit();
    }
}
?>