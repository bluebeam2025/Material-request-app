<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $_SESSION['error'] = 'Invalid request.';
  header('Location: ../expense_request.php');
  exit();
}

$user_id = (int)$_SESSION['user_id'];
$project_id = (int)$_POST['project_id'];
$amount = (float)$_POST['amount'];
$request_date = $_POST['request_date'];
$required_date = $_POST['required_date'];
$related_request_id = $_POST['related_request_id'] ? (int)$_POST['related_request_id'] : null;
$approver1_id = (int)$_POST['approver1_id'];
$approver2_id = (int)$_POST['approver2_id'];

$stmt = $conn->prepare("
  INSERT INTO expense_approvals
  (user_id, project_id, amount, request_date, required_date, related_request_id, approver1_id, approver2_id)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param("iisssiii", $user_id, $project_id, $amount, $request_date, $required_date, $related_request_id, $approver1_id, $approver2_id);

if ($stmt->execute()) {
  $_SESSION['success'] = "Expense request submitted successfully.";
} else {
  $_SESSION['error'] = "Failed to submit request.";
}

header("Location: ../expense_request.php");
exit();
