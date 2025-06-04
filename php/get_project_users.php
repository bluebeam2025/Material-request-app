<?php
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode([]);
    exit();
}

include 'db_connect.php';

$pid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$result = $conn->query("SELECT user_id FROM project_users WHERE project_id = $pid");

$ids = [];
while ($row = $result->fetch_assoc()) {
    $ids[] = (int)$row['user_id'];
}

echo json_encode($ids);
?>
