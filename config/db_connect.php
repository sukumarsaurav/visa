<?php
// Database connection
$servername = "193.203.184.121";
$username = "u911550082_visafy_v2";
$password = "Milk@sdk14";
$dbname = "u911550082_visafy_v2";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");
?> 