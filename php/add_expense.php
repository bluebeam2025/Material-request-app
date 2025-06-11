<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ../expense_request.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$project_id = (int)($_POST['project_id'] ?? 0);

// Expense entries arrays
$desc = $_POST['description'] ?? [];
$cat = $_POST['category'] ?? [];
$cash_in = $_POST['cash_in'] ?? [];
$cash_out = $_POST['cash_out'] ?? [];
$remarks = $_POST['remarks'] ?? [];

if (!$project_id || empty($desc)) {
    $_SESSION['error'] = 'Project and at least one entry is required.';
    header('Location: ../expense_request.php');
    exit();
}

// First insert a new "expense_request" record to generate a sheet_id
$stmt = $conn->prepare("
    INSERT INTO expense_requests (user_id, project_id, amount, request_date, required_date, sheet_id, approver1_id, approver2_id)
    VALUES (?, ?, 0, NULL, NULL, NULL, 0, 0)
");
$stmt->bind_param('ii', $user_id, $project_id);
$stmt->execute();
$sheet_id = $stmt->insert_id;

// Now insert expense_entries for this sheet_id
$insert = $conn->prepare("
    INSERT INTO expense_entries (request_id, description, category, cash_in, cash_out, remarks)
    VALUES (?, ?, ?, ?, ?, ?)
");

for ($i = 0; $i < count($desc); $i++) {
    $d = trim($desc[$i]);
    $c = trim($cat[$i]);
    $in = (float)$cash_in[$i];
    $out = (float)$cash_out[$i];
    $r = trim($remarks[$i]);

    // Skip empty rows
    if ($d || $c || $in > 0 || $out > 0 || $r) {
        $insert->bind_param('issdds', $sheet_id, $d, $c, $in, $out, $r);
        $insert->execute();
    }
}

$_SESSION['success'] = 'Expense sheet created successfully.';
header('Location: ../expense_request.php');
exit();
?>
