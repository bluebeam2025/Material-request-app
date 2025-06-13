<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../dashboard.php');
    exit();
}

$entry_id = (int)($_GET['id'] ?? 0);
$sheet_id = (int)($_GET['sheet_id'] ?? 0);

if (!$entry_id || !$sheet_id) {
    header("Location: view_expense.php?id=$sheet_id");
    exit();
}

$entry = $conn->query("SELECT invoice_file FROM expense_entries WHERE id = $entry_id")->fetch_assoc();

if ($entry && $entry['invoice_file']) {
    $file_path = "../uploads/invoices/" . $entry['invoice_file'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    $conn->query("UPDATE expense_entries SET invoice_file = NULL WHERE id = $entry_id");
}

$_SESSION['success'] = "Invoice deleted.";
header("Location: view_expense.php?id=$sheet_id");
exit();
