<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../dashboard.php'); exit();
}

$user_id = (int)$_SESSION['user_id'];
$entry_id = (int)($_GET['id'] ?? 0);
$request_id = (int)($_GET['req_id'] ?? 0);

if (!$entry_id || !$request_id) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../expense_request.php");
    exit();
}

// Verify that this sheet belongs to user
$sheet = $conn->query("SELECT id FROM expense_requests WHERE id = $request_id AND user_id = $user_id")->fetch_assoc();
if (!$sheet) {
    $_SESSION['error'] = "Unauthorized delete.";
    header("Location: ../expense_request.php");
    exit();
}

// Get invoice filename (if any)
$entry = $conn->query("SELECT invoice_file FROM expense_entries WHERE id = $entry_id AND request_id = $request_id")->fetch_assoc();

if ($entry) {
    // Delete row
    $conn->query("DELETE FROM expense_entries WHERE id = $entry_id AND request_id = $request_id");

    // Delete file if present
    if ($entry['invoice_file'] && file_exists('../uploads/invoices/' . $entry['invoice_file'])) {
        unlink('../uploads/invoices/' . $entry['invoice_file']);
    }

    $_SESSION['success'] = "Expense entry deleted.";
    header("Location: view_expense.php?id=$request_id");
    exit();
} else {
    $_SESSION['error'] = "Entry not found.";
    header("Location: view_expense.php?id=$request_id");
    exit();
}
