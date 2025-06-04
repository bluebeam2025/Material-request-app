<?php
session_start();
include 'db_connect.php';

if (!isset($_POST['leave_id'], $_POST['action'])) {
  $_SESSION['error'] = 'Missing form data.';
  header('Location: ../leave_request.php');
  exit;
}

$leave_id = (int)$_POST['leave_id'];
$action   = $_POST['action'];
$comment  = trim($_POST['comment'] ?? '');
$user_id  = (int)$_SESSION['user_id'];

// Fetch leave row
$stmt = $conn->prepare("SELECT * FROM leave_requests WHERE id = ?");
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
  $_SESSION['error'] = 'Leave request not found.';
  header('Location: ../leave_request.php');
  exit;
}
$leave = $result->fetch_assoc();

// Decide which approver is acting
if ($user_id === (int)$leave['approver1_id'] && $leave['status'] === 'Pending-L1') {
  $new_status = $action === 'Approved' ? 'Pending-L2' : 'Rejected';
  $query = "UPDATE leave_requests SET status = ?, l1_comment = ? WHERE id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("ssi", $new_status, $comment, $leave_id);

} elseif ($user_id === (int)$leave['approver2_id'] && $leave['status'] === 'Pending-L2') {
  $new_status = $action;
  $query = "UPDATE leave_requests SET status = ?, l2_comment = ? WHERE id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("ssi", $new_status, $comment, $leave_id);

} else {
  $_SESSION['error'] = 'Unauthorized or invalid action.';
  header('Location: ../leave_request.php');
  exit;
}

// Execute update
if ($stmt->execute()) {
  $_SESSION['success'] = "Leave request $action successfully.";
} else {
  $_SESSION['error'] = 'Failed to update request.';
}

header('Location: ../leave_request.php');
exit;
