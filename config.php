<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "veggies";

// Create connection without selecting a database
$conn = mysqli_connect($servername, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it does not exist
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if (mysqli_query($conn, $sql)) {
    // echo "Database checked/created successfully.<br>";
} else {
    die("Error creating database: " . mysqli_error($conn));
}

// Now connect to the database
mysqli_close($conn);
$conn = mysqli_connect($servername, $username, $password, $database);

// Check new connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4
mysqli_set_charset($conn, 'utf8mb4');

// echo "Connected successfully!";
?>