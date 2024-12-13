<?php
require_once 'db_connection.php';

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

// SQL to create users table
$users_table = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('buyer', 'seller', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// SQL to create properties table
$properties_table = "CREATE TABLE IF NOT EXISTS properties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    seller_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL,
    location VARCHAR(255) NOT NULL,
    property_age INT,
    square_footage DECIMAL(10,2),
    bedrooms INT,
    bathrooms INT,
    has_garden BOOLEAN DEFAULT FALSE,
    parking_available BOOLEAN DEFAULT FALSE,
    nearby_facilities TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
)";

// SQL to create property_images table
$images_table = "CREATE TABLE IF NOT EXISTS property_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
)";



// SQL to create wishlist table
$wishlist_table = "CREATE TABLE IF NOT EXISTS wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, property_id)
)";

// Array of tables to create
$tables = [
    'users' => $users_table,
    'properties' => $properties_table,
    'property_images' => $images_table,
    'wishlist' => $wishlist_table
];

// Create tables
$success = true;
foreach ($tables as $tableName => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table '$tableName' created successfully or already exists<br>";
    } else {
        echo "Error creating table '$tableName': " . $conn->error . "<br>";
        $success = false;
    }
}

// Create admin user if users table is empty
if ($success) {
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Create default admin user
        $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
        $admin_sql = "INSERT INTO users (first_name, last_name, email, username, password, user_type) 
                     VALUES ('Admin', 'User', 'admin@propertyhub.com', 'admin', '$admin_password', 'admin')";
        
        if ($conn->query($admin_sql) === TRUE) {
            echo "Default admin user created successfully<br>";
            echo "Username: admin<br>";
            echo "Password: admin123<br>";
        } else {
            echo "Error creating admin user: " . $conn->error . "<br>";
        }
    }
}

$conn->close();
?>