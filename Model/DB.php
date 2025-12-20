<?php
// Database configuration (Please adjust these settings as needed)
$HOST = "127.0.0.1";
$USER = "root";
$PASSWORD = "11908721";
$DBNAME = "2entral";

// Create connection
$conn = new mysqli($HOST, $USER, $PASSWORD, $DBNAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>