<?php
// db.php - inside your project folder, e.g., Functionality/
$servername = "localhost";
$username = "root";  // default XAMPP MySQL username
$password = "";      // default XAMPP MySQL password
$dbname = "onlinelearninghub_new"; // your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
