<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
  header('Location: ../dashboard.php');
  exit();
}

$user_id     = (int)$_SESSION['user_id'];
$project_id  = (int)($_POST['project_id'] ?? 0);
$descArr     = $_POST['description'] ?? [];
$catArr      = $_POST['category'] ?? [];
$inArr       = $_POST['cash_in'] ?? [];
$outArr      = $_POST['cash_out'] ?? [];
$remarksArr  = $_POST['remarks'] ?? [];

// Basic validation
if (!$project_id || empty($descArr)) {
  $_SESSION['error'] = "Invalid submission.";
  header('Location: ../add_expense_sheet.php');
  exit();
}

// Insert main expense request
$stmt = $conn->prepare("INSERT INTO expense_requests (user_id, project_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $project_id);
$stmt->execute();
$request_id = $conn->insert_id;

// Insert each expense entry
$stmt2 = $conn->prepare("INSERT INTO expense_entries (request_id, description, category, cash_in, cash_out, remarks) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($descArr as $i => $desc) {
  $desc    = trim($desc);
  $cat     = trim($catArr[$i] ?? '');
  $cash_in = (float)($inArr[$i] ?? 0);
  $cash_out= (float)($outArr[$i] ?? 0);
  $remarks = trim($remarksArr[$i] ?? '');

  if ($desc === '') continue; // skip empty rows

  $stmt2->bind_param("issdds", $request_id, $desc, $cat, $cash_in, $cash_out, $remarks);
  $stmt2->execute();
}

$_SESSION['success'] = "Expense sheet created.";
header("Location: ../expense_request.php");
exit();
