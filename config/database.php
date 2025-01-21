<?php

// Database configuration
$host = 'localhost'; 
$db = 'Ramisi_HMS'; 
$user = 'root'; 
$pass = ''; // 

// Create a PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
