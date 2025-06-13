<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../expense_request.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$project_id = (int)($_POST['project_id'] ?? 0);

if (!$project_id) {
    $_SESSION['error'] = 'Project not selected.';
    header('Location: ../expense_request.php');
    exit();
}

// Insert into expense_sheets table
$stmt = $conn->prepare("INSERT INTO expense_sheets (project_id, user_id) VALUES (?, ?)");
$stmt->bind_param('ii', $project_id, $user_id);
$stmt->execute();
$sheet_id = $stmt->insert_id;

// Process rows
$entry_dates = $_POST['entry_date'] ?? [];
$descs = $_POST['description'] ?? [];
$cats = $_POST['category'] ?? [];
$cash_in = $_POST['cash_in'] ?? [];
$cash_out = $_POST['cash_out'] ?? [];
$remarks = $_POST['remarks'] ?? [];

for ($i = 0; $i < count($descs); $i++) {
    $date = $entry_dates[$i] ?? null;
    $d = trim($descs[$i]);
    $c = trim($cats[$i]);
    $in = (float)$cash_in[$i];
    $out = (float)$cash_out[$i];
    $r = trim($remarks[$i]);

    // Upload invoice if present
    $invoice = '';
    if (isset($_FILES['invoice_file']['name'][$i]) && $_FILES['invoice_file']['name'][$i] != '') {
        $target_dir = "../uploads/invoices/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $filename = time() . '_' . basename($_FILES['invoice_file']['name'][$i]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES['invoice_file']['tmp_name'][$i], $target_file)) {
            $invoice = $filename;
        }
    }

    // Insert entry
    $insert = $conn->prepare("
        INSERT INTO expense_entries 
        (sheet_id, entry_date, description, category, cash_in, cash_out, remarks, invoice_file)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insert->bind_param('isssddss', $sheet_id, $date, $d, $c, $in, $out, $r, $invoice);
    $insert->execute();
}

$_SESSION['success'] = 'Expense sheet added successfully.';
header('Location: ../expense_request.php');
exit();
