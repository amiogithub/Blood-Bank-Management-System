<?php
require_once 'db_connect.php';

// Alter users table to update user_type ENUM values
$update_users_table = "ALTER TABLE users MODIFY COLUMN user_type ENUM('user', 'admin') NOT NULL";

if (mysqli_query($conn, $update_users_table)) {
    echo "Users table updated successfully!<br>";
} else {
    echo "Error updating users table: " . mysqli_error($conn) . "<br>";
}

// Update existing donor/recipient users to the new 'user' type
$update_user_types = "UPDATE users SET user_type = 'user' WHERE user_type IN ('donor', 'recipient')";

if (mysqli_query($conn, $update_user_types)) {
    echo "User types updated successfully!<br>";
} else {
    echo "Error updating user types: " . mysqli_error($conn) . "<br>";
}






// Create a new table to track user roles
$create_user_roles_table = "CREATE TABLE IF NOT EXISTS user_roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    is_donor BOOLEAN DEFAULT FALSE,
    is_recipient BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $create_user_roles_table)) {
    echo "User roles table created successfully!<br>";
} else {
    echo "Error creating user roles table: " . mysqli_error($conn) . "<br>";
}

// Insert existing users into user_roles table
$insert_existing_donors = "INSERT INTO user_roles (user_id, is_donor, is_recipient)
                         SELECT user_id, TRUE, FALSE FROM users 
                         WHERE user_id IN (SELECT user_id FROM donor_profiles)";

if (mysqli_query($conn, $insert_existing_donors)) {
    echo "Existing donors added to user roles table!<br>";
} else {
    echo "Error adding existing donors: " . mysqli_error($conn) . "<br>";
}

// Update remaining users as recipients (those without donor profiles)
$insert_remaining_users = "INSERT INTO user_roles (user_id, is_donor, is_recipient)
                         SELECT user_id, FALSE, TRUE FROM users 
                         WHERE user_type = 'user' 
                         AND user_id NOT IN (SELECT user_id FROM user_roles)";

if (mysqli_query($conn, $insert_remaining_users)) {
    echo "Remaining users added to user roles table!<br>";
} else {
    echo "Error adding remaining users: " . mysqli_error($conn) . "<br>";
}

echo "Database update completed!";
mysqli_close($conn);
?>