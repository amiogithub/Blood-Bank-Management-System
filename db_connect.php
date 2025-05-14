<?php
// Database configuration
$host = "localhost";
$username = "root";
$password = ""; // Default XAMPP password is empty

// Create connection without selecting a database
$conn = mysqli_connect($host, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS blood_bank_db";
if (mysqli_query($conn, $sql)) {
    // Select the database
    mysqli_select_db($conn, "blood_bank_db");
} else {
    die("Error creating database: " . mysqli_error($conn));
}
?>