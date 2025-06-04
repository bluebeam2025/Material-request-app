<?php
// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'php/db_connect.php'; // Adjust this if your login.php is in /php/

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validate inputs
if (!$username || !$password) {
    header("Location: index.php?error=" . urlencode("Missing credentials"));
    exit();
}

// Prepare and execute
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Verify user
if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['name'] = $user['name'];

    header("Location: dashboard.php");
    exit();
} else {
    header("Location: index.php?error=" . urlencode("Invalid username or password"));
    exit();
}
?>
