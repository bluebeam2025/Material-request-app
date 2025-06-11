<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: ../expense_request.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$project_id = (int)($_POST['project_id'] ?? 0);
$desc = $_POST['description'] ?? [];
$cat  = $_POST['category'] ?? [];
$cash_in  = $_POST['cash_in'] ?? [];
$cash_out = $_POST['cash_out'] ?? [];
$remarks  = $_POST['remarks'] ?? [];

if (!$project_id || count($desc) === 0) {
    $_SESSION['error'] = 'Project and at least one expense entry required.';
    header('Location: ../expense_request.php');
    exit();
}

// Insert new expense sheet record (for linking, optional)
$conn->query("INSERT INTO expense_requests (user_id, project_id, amount, request_date, required_date, approver1_id, approver2_id) 
VALUES ($user_id, $project_id, NULL, NULL, NULL, 0, 0)");
$sheet_id = $conn->insert_id;

// Insert expense entries
$stmt = $conn->prepare("
    INSERT INTO expense_entries (request_id, description, category, cash_in, cash_out, remarks)
    VALUES (?, ?, ?, ?, ?, ?)
");

for ($i = 0; $i < count($desc); $i++) {
    $d = trim($desc[$i]);
    $c = trim($cat[$i]);
    $in = (float)$cash_in[$i];
    $out = (float)$cash_out[$i];
    $r = trim($remarks[$i]);

    // Skip empty row
    if (!$d && !$c && $in == 0 && $out == 0 && !$r) continue;

    $stmt->bind_param("issdds", $sheet_id, $d, $c, $in, $out, $r);
    $stmt->execute();
}

$_SESSION['success'] = 'Expense sheet added successfully.';
header('Location: ../expense_request.php');
exit();
