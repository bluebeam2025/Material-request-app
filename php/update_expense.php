<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../expense_request.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$sheet_id = (int)($_POST['sheet_id'] ?? 0);

if (!$sheet_id) {
    $_SESSION['error'] = 'Invalid sheet ID.';
    header('Location: ../expense_request.php');
    exit();
}

// Process rows
$ids = $_POST['entry_id'] ?? [];
$entry_dates = $_POST['entry_date'] ?? [];
$descs = $_POST['description'] ?? [];
$cats = $_POST['category'] ?? [];
$cash_in = $_POST['cash_in'] ?? [];
$cash_out = $_POST['cash_out'] ?? [];
$remarks = $_POST['remarks'] ?? [];

$update = $conn->prepare("
    UPDATE expense_entries 
    SET entry_date=?, description=?, category=?, cash_in=?, cash_out=?, remarks=?, invoice_file=? 
    WHERE id=? AND sheet_id=?
");

for ($i = 0; $i < count($ids); $i++) {
    $eid = (int)$ids[$i];
    $date = $entry_dates[$i] ?? null;
    $d = trim($descs[$i]);
    $c = trim($cats[$i]);
    $in = (float)$cash_in[$i];
    $out = (float)$cash_out[$i];
    $r = trim($remarks[$i]);

    // Upload new invoice if provided
    $invoice = $_POST['existing_invoice'][$i] ?? '';
    if (isset($_FILES['invoice_file']['name'][$i]) && $_FILES['invoice_file']['name'][$i] != '') {
        $target_dir = "../uploads/invoices/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $filename = time() . '_' . basename($_FILES['invoice_file']['name'][$i]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES['invoice_file']['tmp_name'][$i], $target_file)) {
            $invoice = $filename;
        }
    }

    $update->bind_param('sssddssi', $date, $d, $c, $in, $out, $r, $invoice, $eid, $sheet_id);
    $update->execute();
}

$_SESSION['success'] = 'Expense sheet updated successfully.';
header("Location: ../php/view_expense.php?id=$sheet_id");
exit();
