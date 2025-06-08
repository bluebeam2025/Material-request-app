<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: ../expense_requests.php");
    exit();
}

$request_id = (int)($_POST['request_id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

// Validate sheet ownership
$check = $conn->prepare("SELECT id FROM expense_requests WHERE id = ? AND user_id = ?");
$check->bind_param("ii", $request_id, $user_id);
$check->execute();
$result = $check->get_result();
if ($result->num_rows === 0) {
    $_SESSION['error'] = "Sheet not found or access denied.";
    header("Location: ../expense_requests.php");
    exit();
}

// Capture posted data
$ids         = $_POST['entry_ids'] ?? [];
$desc        = $_POST['description'] ?? [];
$cat         = $_POST['category'] ?? [];
$cash_in     = $_POST['cash_in'] ?? [];
$cash_out    = $_POST['cash_out'] ?? [];
$remarks     = $_POST['remarks'] ?? [];

// Prepare update statement
$update = $conn->prepare("
    UPDATE expense_entries 
       SET description = ?, category = ?, cash_in = ?, cash_out = ?, remarks = ?
     WHERE id = ? AND request_id = ?
");

// Update each entry
for ($i = 0; $i < count($ids); $i++) {
    $d = trim($desc[$i]);
    $c = trim($cat[$i]);
    $in = (float)$cash_in[$i];
    $out = (float)$cash_out[$i];
    $r = trim($remarks[$i]);
    $eid = (int)$ids[$i];

    $update->bind_param("ssddssi", $d, $c, $in, $out, $r, $eid, $request_id);
    $update->execute();
}

$_SESSION['success'] = "Expense sheet updated successfully.";
header("Location: ../expense_requests.php");
exit();
