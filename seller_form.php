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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $bedrooms = mysqli_real_escape_string($conn, $_POST['bedrooms']);
    $bathrooms = mysqli_real_escape_string($conn, $_POST['bathrooms']);
    $square_footage = mysqli_real_escape_string($conn, $_POST['square_footage']);
    $has_garden = isset($_POST['has_garden']) ? 1 : 0;
    $parking_available = isset($_POST['parking_available']) ? 1 : 0;

    // Validate inputs
    if (empty($title) || empty($description) || empty($price) || empty($location)) {
        $errors[] = "Please fill all required fields";
    }

    if (!is_numeric($price) || $price <= 0) {
        $errors[] = "Please enter a valid price";
    }

    // Handle image uploads
    $image_paths = [];
    $upload_dir = 'uploads/properties/';
    
    // Create upload directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Handle multiple image uploads
    if (isset($_FILES['property_images'])) {
        $files = $_FILES['property_images'];
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $files['tmp_name'][$i];
                $name = basename($files['name'][$i]);
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                
                // Validate file type
                $allowed_types = ['jpg', 'jpeg', 'png'];
                if (!in_array($extension, $allowed_types)) {
                    $errors[] = "Invalid file type for {$name}. Only JPG, JPEG, and PNG are allowed.";
                    continue;
                }

                // Generate unique filename
                $unique_name = uniqid() . '.' . $extension;
                $upload_path = $upload_dir . $unique_name;

                if (move_uploaded_file($tmp_name, $upload_path)) {
                    $image_paths[] = $upload_path;
                } else {
                    $errors[] = "Failed to upload {$name}";
                }
            }
        }
    }

    // If no errors, insert property and images
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $seller_id = $_SESSION['user_id'];
            $sql = "INSERT INTO properties (seller_id, title, description, price, location, bedrooms, bathrooms, 
                    square_footage, has_garden, parking_available) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issdsiidii", $seller_id, $title, $description, $price, $location, $bedrooms, 
                             $bathrooms, $square_footage, $has_garden, $parking_available);
            
            if ($stmt->execute()) {
                $property_id = $conn->insert_id;
                
                // Insert image paths into property_images table
                if (!empty($image_paths)) {
                    $image_sql = "INSERT INTO property_images (property_id, image_path) VALUES (?, ?)";
                    $image_stmt = $conn->prepare($image_sql);
                    
                    foreach ($image_paths as $path) {
                        $image_stmt->bind_param("is", $property_id, $path);
                        $image_stmt->execute();
                    }
                }
                
                $conn->commit();
                $success = "Property listed successfully!";
                header("refresh:2;url=seller_dashboard.php");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error listing property: " . $e->getMessage();
            
            // Clean up uploaded files if database insertion failed
            foreach ($image_paths as $path) {
                if (file_exists($path)) {
                    unlink($path);
                }
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
    <title>Add New Property - PropertyHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sellerform.css">
    
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

    <div class="form-container">
        <h1 style="margin-bottom: 1.5rem; color: #2c3e50;">Add New Property</h1>

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

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Property Title *</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" required></textarea>
            </div>

            <div class="form-group">
                <label for="price">Price (USD) *</label>
                <input type="number" id="price" name="price" min="0" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="location">Location *</label>
                <input type="text" id="location" name="location" required>
            </div>

            <div class="form-group">
                <label for="bedrooms">Number of Bedrooms</label>
                <input type="number" id="bedrooms" name="bedrooms" min="0">
            </div>

            <div class="form-group">
                <label for="bathrooms">Number of Bathrooms</label>
                <input type="number" id="bathrooms" name="bathrooms" min="0" step="0.5">
            </div>

            <div class="form-group">
                <label for="square_footage">Square Footage</label>
                <input type="number" id="square_footage" name="square_footage" min="0">
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox" id="has_garden" name="has_garden">
                <label for="has_garden">Has Garden</label>
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox" id="parking_available" name="parking_available">
                <label for="parking_available">Parking Available</label>
            </div>

            <div class="form-group">
                <label for="property_images">Property Images</label>
                <input type="file" id="property_images" name="property_images[]" multiple accept="image/*" onchange="previewImages(event)">
                <div id="image-preview-container" class="image-preview"></div>
            </div>

            <button type="submit" class="submit-btn">List Property</button>
        </form>
    </div>

    <script>
        function previewImages(event) {
            const container = document.getElementById('image-preview-container');
            container.innerHTML = '';
            
            const files = event.target.files;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.classList.add('preview-image');
                        container.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                }
            }
        }
    </script>
</body>
</html>