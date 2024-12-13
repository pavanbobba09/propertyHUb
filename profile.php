<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check user type and redirect if invalid
if (!in_array($_SESSION['user_type'], ['buyer', 'seller'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$errors = [];
$success = '';

// Fetch current user data
$stmt = $conn->prepare("SELECT first_name, last_name, email, username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        // Get form data
        $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
        $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);

        // Validate inputs
        if (empty($first_name) || empty($last_name) || empty($email)) {
            $errors[] = "All fields are required";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        // Check if email already exists for another user
        $email_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $email_check->bind_param("si", $email, $user_id);
        $email_check->execute();
        if ($email_check->get_result()->num_rows > 0) {
            $errors[] = "Email already in use";
        }

        // Update profile if no errors
        if (empty($errors)) {
            $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
            $update_stmt->bind_param("sssi", $first_name, $last_name, $email, $user_id);
            
            if ($update_stmt->execute()) {
                $success = "Profile updated successfully!";
                // Update the displayed user data
                $user['first_name'] = $first_name;
                $user['last_name'] = $last_name;
                $user['email'] = $email;
            } else {
                $errors[] = "Error updating profile";
            }
        }
    }

    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verify current password
        $password_check = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $password_check->bind_param("i", $user_id);
        $password_check->execute();
        $pwd_result = $password_check->get_result()->fetch_assoc();

        if (!password_verify($current_password, $pwd_result['password'])) {
            $errors[] = "Current password is incorrect";
        }

        // Validate new password
        if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long";
        }

        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }

        // Update password if no errors
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $pwd_update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $pwd_update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($pwd_update_stmt->execute()) {
                $success = "Password updated successfully!";
            } else {
                $errors[] = "Error updating password";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - PropertyHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="profile.css">
    
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <a href="index.html" class="logo">PropertyHub</a>
            <div class="nav-links">
                <?php if ($user_type === 'seller'): ?>
                    <a href="seller_dashboard.php">My Properties</a>
                <?php else: ?>
                    <a href="buyer_dashboard.php">Browse Properties</a>
                    <a href="wishlist.php">My Wishlist</a>
                <?php endif; ?>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="profile-container">
        <h1>Profile Settings</h1>

        <?php if (!empty($errors)): ?>
            <div class="error-msg">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-msg"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="section">
            <h2>Personal Information</h2>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    <small style="color: #6b7280;">Username cannot be changed</small>
                </div>

                <button type="submit" name="update_profile" class="btn">Update Profile</button>
            </form>
        </div>

        <div class="section">
            <h2>Change Password</h2>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" name="change_password" class="btn">Change Password</button>
            </form>
        </div>
    </div>
</body>
</html>