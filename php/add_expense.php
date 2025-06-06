<?php
// ✅ FILE: php/add_expense.php
session_start();
include 'db_connect.php';

$user_id = (int)$_SESSION['user_id'] ?? 0;
$project_id = (int)($_POST['project_id'] ?? 0);

if (!$user_id || !$project_id) {
  $_SESSION['error'] = "Invalid submission.";
  header("Location: ../expense_requests.php");
  exit;
}

// Insert new expense sheet
$stmt = $conn->prepare("INSERT INTO expense_requests (user_id, project_id, created_at) VALUES (?, ?, NOW())");
$stmt->bind_param("ii", $user_id, $project_id);

if ($stmt->execute()) {
  $_SESSION['success'] = "New expense sheet created.";
} else {
  $_SESSION['error'] = "Failed to create expense sheet.";
}

header("Location: ../expense_requests.php");
exit;
