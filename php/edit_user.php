<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $username = $_POST['username'];
    $designation = $_POST['designation'];
    $user_type = $_POST['user_type'];

    if (!empty($_POST['password'])) {
        // If password field is filled, update it
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, designation = ?, user_type = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $username, $designation, $user_type, $password, $id);
    } else {
        // If password is blank, don't change it
        $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, designation = ?, user_type = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $username, $designation, $user_type, $id);
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = "User updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update user.";
    }

    header("Location: ../users.php");
    exit();
} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../users.php");
    exit();
}
