<?php
$isLocal = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);

if ($isLocal) {
    // XAMPP (local)
    $host   = 'localhost';
    $user   = 'root';
    $pass   = '';
    $dbname = 'material_db';
} else {
    // Hostinger credentials
    $host   = 'localhost'; // YES, Hostinger uses localhost here
    $user   = 'u473799260_material_reque';   // Check carefully
    $pass   = 'Sanjay@2801';       // Enter exact password from Hostinger
    $dbname = 'u473799260_material_db';      // Confirm this in hPanel
}

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
