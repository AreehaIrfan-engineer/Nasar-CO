<?php
$host = "localhost";
$username = "u617641804_tjtechsoftware";
$password = "@Tjtech2025";
$database = "u617641804_software";  // Change this

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}  ?>