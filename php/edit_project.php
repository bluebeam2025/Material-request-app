<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}

include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_id'])) {
    $pid     = (int)$_POST['project_id'];
    $name    = $conn->real_escape_string($_POST['project_name']);
    $address = $conn->real_escape_string($_POST['project_address']);
    $users   = $_POST['assigned_users'] ?? [];

    /* update basic info */
    $conn->query("UPDATE projects
                  SET project_name = '$name', project_address = '$address'
                  WHERE id = $pid");

    /* replace assignments */
    $conn->query("DELETE FROM project_users WHERE project_id = $pid");
    foreach ($users as $uid) {
        $uid = (int)$uid;
        $conn->query("INSERT INTO project_users (project_id,user_id) VALUES ($pid,$uid)");
    }

    $_SESSION['success'] = 'Project updated successfully.';
} else {
    $_SESSION['error'] = 'Invalid request.';
}

header('Location: ../projects.php');
exit();
?>
