<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../dashboard.php'); exit();
}

$request_id = (int)($_GET['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

if (!$request_id) {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: ../expense_request.php'); exit();
}

// Validate sheet belongs to user
$check = $conn->prepare("SELECT id FROM expense_requests WHERE id=? AND user_id=?");
$check->bind_param("ii", $request_id, $user_id);
$check->execute();
$res = $check->get_result();
if ($res->num_rows === 0) {
    $_SESSION['error'] = 'Unauthorized.';
    header('Location: ../expense_request.php'); exit();
}

// Delete invoices
$rows = $conn->query("SELECT invoice_file FROM expense_entries WHERE request_id = $request_id");
while ($row = $rows->fetch_assoc()) {
    if (!empty($row['invoice_file'])) {
        $file = '../uploads/invoices/' . $row['invoice_file'];
        if (file_exists($file)) unlink($file);
    }
}

// Delete entries
$conn->query("DELETE FROM expense_entries WHERE request_id = $request_id");

// Delete sheet
$conn->query("DELETE FROM expense_requests WHERE id = $request_id");

$_SESSION['success'] = 'Expense sheet deleted.';
header('Location: ../expense_request.php');
exit;
?>
