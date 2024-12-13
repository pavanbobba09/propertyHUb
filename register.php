<?php
session_start();
require_once 'db_connection.php';

$errors = [];
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);

    // Validate inputs
    if (empty($first_name) || empty($last_name) || empty($email) || 
        empty($username) || empty($password) || empty($user_type)) {
        $errors[] = "All fields are required";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Check if email exists using prepared statement
    $check_email = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    if ($check_email->get_result()->num_rows > 0) {
        $errors[] = "Email already exists";
    }

    // Check if username exists using prepared statement
    $check_username = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $check_username->bind_param("s", $username);
    $check_username->execute();
    if ($check_username->get_result()->num_rows > 0) {
        $errors[] = "Username already exists";
    }

    // If no errors, insert user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (first_name, last_name, email, username, password, user_type) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $first_name, $last_name, $email, $username, $hashed_password, $user_type);
        
        if ($stmt->execute()) {
            $success = "Registration successful! Redirecting to login...";
            header("refresh:2;url=login.php");
        } else {
            $errors[] = "Registration failed: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PropertyHub</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="register-container">
        <h2>Create Your Account</h2>

        <?php if (!empty($errors)): ?>
            <div class="error-msg">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-msg">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" placeholder="Enter your first name">
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" placeholder="Enter your last name">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" placeholder="Choose a username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Create a password">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password">
            </div>

            <div class="form-group">
                <label for="user_type">I want to</label>
                <select id="user_type" name="user_type">
                    <option value="">Select user type</option>
                    <option value="buyer" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'buyer') ? 'selected' : ''; ?>>Buy Property</option>
                    <option value="seller" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'seller') ? 'selected' : ''; ?>>Sell Property</option>
                </select>
            </div>

            <button type="submit">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>

    <script>
        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters long!');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>