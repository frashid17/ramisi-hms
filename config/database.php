<?php

// Database configuration
$host = 'localhost'; // Your database host
$db = 'Ramisi_HMS'; // Your database name
$user = 'root'; // Your database username
$pass = ''; // Your database password (leave empty if using XAMPP or default MySQL setup)

// Create a PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Enable exception mode for errors
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
