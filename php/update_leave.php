<?php
session_start();
include 'db_connect.php';

if (!isset($_POST['leave_id'], $_POST['start_date'], $_POST['end_date'], $_POST['reason'], $_POST['approver1_id'], $_POST['approver2_id'])) {
  $_SESSION['error'] = "Missing form data.";
  header("Location: ../leave_request.php"); exit;
}

$leave_id      = (int)$_POST['leave_id'];
$user_id       = (int)$_SESSION['user_id'];
$start_date    = $_POST['start_date'];
$end_date      = $_POST['end_date'];
$reason        = trim($_POST['reason']);
$approver1_id  = (int)$_POST['approver1_id'];
$approver2_id  = (int)$_POST['approver2_id'];

// Confirm ownership and status
$stmt = $conn->prepare("SELECT * FROM leave_requests WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $leave_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
  $_SESSION['error'] = "Unauthorized request.";
  header("Location: ../leave_request.php");
  exit;
}
$leave = $result->fetch_assoc();
if ($leave['status'] !== 'Pending-L1') {
  $_SESSION['error'] = "Only Pending-L1 requests can be edited.";
  header("Location: ../leave_request.php");
  exit;
}

// Update request
$stmt = $conn->prepare("UPDATE leave_requests
    SET start_date = ?, end_date = ?, reason = ?, approver1_id = ?, approver2_id = ?
    WHERE id = ?");
$stmt->bind_param("sssiii", $start_date, $end_date, $reason, $approver1_id, $approver2_id, $leave_id);

if ($stmt->execute()) {
  $_SESSION['success'] = "Leave request updated.";
} else {
  $_SESSION['error'] = "Failed to update request.";
}
header("Location: ../leave_request.php");
exit;
