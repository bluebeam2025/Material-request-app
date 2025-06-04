<?php
session_start();

// Only admins can access this file
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

require_once 'db_connect.php';

// Sanitize & validate inputs
$name = trim($_POST['name']);
$username = trim($_POST['username']);
$password = trim($_POST['password']);
$designation = trim($_POST['designation']);
$user_type = $_POST['user_type'];

if (empty($name) || empty($username) || empty($password) || empty($user_type)) {
    echo "All required fields must be filled.";
    exit();
}

// Check for duplicate username
$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo "Username already exists.";
    exit();
}
$check->close();

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $conn->prepare("INSERT INTO users (name, username, password, designation, user_type) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $name, $username, $hashed_password, $designation, $user_type);

if ($stmt->execute()) {
    header("Location: ../users.php");
    exit();
} else {
    echo "Failed to add user.";
}

$stmt->close();
$conn->close();
?>
