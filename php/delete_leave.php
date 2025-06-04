<?php
session_start();
include 'db_connect.php';

if (!isset($_GET['id']) || !isset($_SESSION['user_id'])) {
  $_SESSION['error'] = "Unauthorized request.";
  header("Location: ../leave_request.php");
  exit;
}

$leave_id = (int)$_GET['id'];
$user_id = (int)$_SESSION['user_id'];

// Ensure the user owns the request and it's in Pending-L1
$stmt = $conn->prepare("SELECT * FROM leave_requests WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $leave_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  $_SESSION['error'] = "You are not allowed to delete this request.";
  header("Location: ../leave_request.php");
  exit;
}

$leave = $result->fetch_assoc();
if ($leave['status'] !== 'Pending-L1') {
  $_SESSION['error'] = "Only Pending-L1 requests can be deleted.";
  header("Location: ../leave_request.php");
  exit;
}

// Delete the request
$stmt = $conn->prepare("DELETE FROM leave_requests WHERE id = ?");
$stmt->bind_param("i", $leave_id);
if ($stmt->execute()) {
  $_SESSION['success'] = "Leave request deleted.";
} else {
  $_SESSION['error'] = "Failed to delete request.";
}

header("Location: ../leave_request.php");
exit;
