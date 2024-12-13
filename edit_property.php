<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'seller') {
    header("Location: login.php");
    exit();
}

$errors = [];
$success = '';

// Get property data if it exists
if (isset($_GET['id'])) {
    $property_id = mysqli_real_escape_string($conn, $_GET['id']);
    $seller_id = $_SESSION['user_id'];
    
    // Get property details
    $sql = "SELECT * FROM properties WHERE id = ? AND seller_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $property_id, $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: seller_dashboard.php");
        exit();
    }
    
    $property = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $bedrooms = mysqli_real_escape_string($conn, $_POST['bedrooms']);
    $bathrooms = mysqli_real_escape_string($conn, $_POST['bathrooms']);
    $square_footage = mysqli_real_escape_string($conn, $_POST['square_footage']);
    $has_garden = isset($_POST['has_garden']) ? 1 : 0;
    $parking_available = isset($_POST['parking_available']) ? 1 : 0;
    $property_id = mysqli_real_escape_string($conn, $_POST['property_id']);

    // Validate inputs
    if (empty($title) || empty($description) || empty($price) || empty($location)) {
        $errors[] = "Please fill all required fields";
    }

    if (!is_numeric($price) || $price <= 0) {
        $errors[] = "Please enter a valid price";
    }

    // If no errors, update property
    if (empty($errors)) {
        $sql = "UPDATE properties SET 
                title = ?, 
                description = ?, 
                price = ?, 
                location = ?, 
                bedrooms = ?, 
                bathrooms = ?, 
                square_footage = ?, 
                has_garden = ?, 
                parking_available = ? 
                WHERE id = ? AND seller_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdsiiiiiii", 
            $title, 
            $description, 
            $price, 
            $location, 
            $bedrooms, 
            $bathrooms, 
            $square_footage, 
            $has_garden, 
            $parking_available, 
            $property_id, 
            $_SESSION['user_id']
        );

        if ($stmt->execute()) {
            $success = "Property updated successfully!";
            header("refresh:2;url=seller_dashboard.php");
        } else {
            $errors[] = "Error updating property: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property - PropertyHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="edit.css">
    
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <a href="index.html" class="logo">PropertyHub</a>
            <div class="nav-links">
                <a href="seller_dashboard.php">My Properties</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 style="margin-bottom: 1.5rem;">Edit Property</h1>

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

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
            
            <div class="form-group">
                <label for="title">Property Title *</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($property['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($property['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="price">Price (USD) *</label>
                <input type="number" id="price" name="price" min="0" step="0.01" value="<?php echo $property['price']; ?>" required>
            </div>

            <div class="form-group">
                <label for="location">Location *</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($property['location']); ?>" required>
            </div>

            <div class="form-group">
                <label for="bedrooms">Number of Bedrooms</label>
                <input type="number" id="bedrooms" name="bedrooms" min="0" value="<?php echo $property['bedrooms']; ?>">
            </div>

            <div class="form-group">
                <label for="bathrooms">Number of Bathrooms</label>
                <input type="number" id="bathrooms" name="bathrooms" min="0" step="0.5" value="<?php echo $property['bathrooms']; ?>">
            </div>

            <div class="form-group">
                <label for="square_footage">Square Footage</label>
                <input type="number" id="square_footage" name="square_footage" min="0" value="<?php echo $property['square_footage']; ?>">
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox" id="has_garden" name="has_garden" <?php echo $property['has_garden'] ? 'checked' : ''; ?>>
                <label for="has_garden">Has Garden</label>
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox" id="parking_available" name="parking_available" <?php echo $property['parking_available'] ? 'checked' : ''; ?>>
                <label for="parking_available">Parking Available</label>
            </div>

            <button type="submit">Update Property</button>
        </form>
    </div>
</body>
</html>