<?php
session_start();
include 'config.php';

$id = $_POST['user_id'];
$username = $_POST['username'];
$name = $_POST['name'];
$designation = $_POST['designation'];
$user_type = $_POST['user_type'];

$photo_path = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $photo_name = time() . '_' . basename($_FILES['photo']['name']);
    $target_path = 'uploads/' . $photo_name;
    move_uploaded_file($_FILES['photo']['tmp_name'], $target_path);
    $photo_path = $target_path;
}

if ($id) {
    // UPDATE user
    $sql = "UPDATE users SET username=?, name=?, designation=?, user_type=?";
    $params = [$username, $name, $designation, $user_type];

    if ($photo_path) {
        $sql .= ", photo=?";
        $params[] = $photo_path;
    }

    $sql .= " WHERE id=?";
    $params[] = $id;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat("s", count($params) - 1) . "i", ...$params);

} else {
    // INSERT new user
    $default_password = password_hash("123456", PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, name, designation, user_type, photo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $username, $default_password, $name, $designation, $user_type, $photo_path);
}

$stmt->execute();
header("Location: users.php");
exit();
?>
