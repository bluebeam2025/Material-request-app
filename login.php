<?php
session_start();
require 'php/db_connect.php'; // adjust if in a subfolder

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Return if empty
if (!$username || !$password) {
    header("Location: index.php?error=" . urlencode("Missing credentials"));
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_type'] = $user['user_type'];
    header("Location: dashboard.php");
    exit();
} else {
    $error = "Invalid username or password";
    header("Location: index.php?error=" . urlencode($error));
    exit();
}
?>
