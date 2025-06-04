
<?php
// Detect environment
$isLocal = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);

// Local credentials (XAMPP)
if ($isLocal) {
    $host     = 'localhost';
    $user     = 'root';
    $pass     = '';
    $dbname   = 'material_db'; // make sure this matches your local DB name
} else {
    // Live credentials (Hostinger)
    $host     = 'srv684.hstgr.io';
    $user     = 'u473799260_material_reque';
    $pass     = "Sanjay@2801";
    $dbname   = 'u473799260_material_db';
}

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
