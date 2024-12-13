<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'seller') {
    header("Location: login.php");
    exit();
}

// Helper function for default images
function getDefaultPropertyImage() {
    $default_images = [
        'default1.jpg',
        'default2.jpg',
        'default3.jpg',
        'default4.jpg'
    ];
    return $default_images[array_rand($default_images)];
}

// Get seller's properties
$seller_id = $_SESSION['user_id'];
$sql = "SELECT p.*, 
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM properties p 
        WHERE seller_id = ? 
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - PropertyHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="seller.css">
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
        <div class="dashboard-header">
            <h1>My Properties</h1>
            <a href="seller_form.php" class="add-property-btn">+ Add New Property</a>
        </div>

        <div class="property-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while($property = $result->fetch_assoc()): ?>
                    <div class="property-card">
                        <div class="property-image">
                            <img src="<?php 
                                echo !empty($property['primary_image']) ? 
                                    htmlspecialchars($property['primary_image']) : 
                                    getDefaultPropertyImage(); 
                            ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                        </div>
                        <div class="property-details">
                            <h2 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h2>
                            <div class="property-price">$<?php echo number_format($property['price']); ?></div>
                            <div class="property-location"><?php echo htmlspecialchars($property['location']); ?></div>
                            
                            <div class="property-features">
                                <?php if ($property['bedrooms']): ?>
                                    <span><?php echo $property['bedrooms']; ?> Beds</span>
                                <?php endif; ?>
                                <?php if ($property['bathrooms']): ?>
                                    <span><?php echo $property['bathrooms']; ?> Baths</span>
                                <?php endif; ?>
                                <?php if ($property['square_footage']): ?>
                                    <span><?php echo number_format($property['square_footage']); ?> sqft</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="property-actions">
                                <a href="edit_property.php?id=<?php echo $property['id']; ?>" class="edit-btn">Edit</a>
                                <a href="delete_property.php?id=<?php echo $property['id']; ?>" 
                                   class="delete-btn" 
                                   onclick="return confirm('Are you sure you want to delete this property?')">Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-properties">
                    <h2>No properties listed yet</h2>
                    <p>Click the 'Add New Property' button to list your first property.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmDelete(id) {
            if(confirm('Are you sure you want to delete this property?')) {
                window.location.href = 'delete_property.php?id=' + id;
            }
        }
    </script>
</body>
</html>