<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $_SESSION['error'] = 'Invalid request method.';
  header('Location: ../expense_request.php');
  exit();
}

$user_id = (int)$_SESSION['user_id'];
$project_id = (int)($_POST['project_id'] ?? 0);
$amount = (float)($_POST['amount'] ?? 0);
$request_date = $_POST['request_date'] ?? '';
$required_date = $_POST['required_date'] ?? '';
$sheet_id = (int)($_POST['sheet_id'] ?? 0);
$approver1_id = (int)($_POST['approver1_id'] ?? 0);
$approver2_id = (int)($_POST['approver2_id'] ?? 0);

if (!$project_id || !$amount || !$request_date || !$required_date || !$approver1_id || !$approver2_id) {
  $_SESSION['error'] = 'All fields are required.';
  header('Location: ../expense_request.php');
  exit();
}

$stmt = $conn->prepare("
  INSERT INTO expense_request 
  (user_id, project_id, amount, request_date, required_date, sheet_id, approver1_id, approver2_id)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('iidssiii', $user_id, $project_id, $amount, $request_date, $required_date, $sheet_id, $approver1_id, $approver2_id);

if ($stmt->execute()) {
  $_SESSION['success'] = 'Expense request submitted successfully.';
} else {
  $_SESSION['error'] = 'Failed to submit expense request.';
}

header('Location: ../expense_request.php');
exit();
