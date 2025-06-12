<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: dashboard.php'); exit();
}
include 'db_connect.php';

$user_id = (int)$_SESSION['user_id'];
$request_id = (int)($_POST['request_id'] ?? 0);

if (!$request_id) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../expense_request.php");
    exit();
}

// Existing entry IDs
$ids = $_POST['entry_id'] ?? [];
$desc = $_POST['description'] ?? [];
$cat  = $_POST['category'] ?? [];
$cash_in = $_POST['cash_in'] ?? [];
$cash_out = $_POST['cash_out'] ?? [];
$remarks = $_POST['remarks'] ?? [];

// Upload dir
$upload_dir = '../uploads/expense_invoices/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

// Prepare update statement
$update = $conn->prepare("UPDATE expense_entries SET 
    description = ?, category = ?, cash_in = ?, cash_out = ?, remarks = ?, invoice_file = ? 
    WHERE id = ? AND request_id = ?");

// Prepare insert statement
$insert = $conn->prepare("INSERT INTO expense_entries 
    (request_id, description, category, cash_in, cash_out, remarks, invoice_file) 
    VALUES (?, ?, ?, ?, ?, ?, ?)");

// Process all rows
for ($i = 0; $i < count($desc); $i++) {

    $eid = (int)$ids[$i];
    $d = trim($desc[$i]);
    $c = trim($cat[$i]);
    $in = (float)$cash_in[$i];
    $out = (float)$cash_out[$i];
    $r = trim($remarks[$i]);

    // Handle invoice upload (optional)
    $invoice_field = "invoice_file_" . $i;
    $invoice_path = '';

    if (isset($_FILES[$invoice_field]) && $_FILES[$invoice_field]['error'] == 0) {
        $tmp_name = $_FILES[$invoice_field]['tmp_name'];
        $orig_name = basename($_FILES[$invoice_field]['name']);
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
            $new_name = "invoice_" . time() . "_$i.$ext";
            $target_path = $upload_dir . $new_name;

            if (move_uploaded_file($tmp_name, $target_path)) {
                $invoice_path = 'uploads/expense_invoices/' . $new_name;
            }
        }
    }

    // Existing entry → update
    if ($eid > 0) {

        // If no new file, get old path
        if (!$invoice_path) {
            $res = $conn->query("SELECT invoice_file FROM expense_entries WHERE id = $eid AND request_id = $request_id");
            $row = $res ? $res->fetch_assoc() : null;
            $invoice_path = $row ? $row['invoice_file'] : '';
        }

        $update->bind_param("ssddssii", $d, $c, $in, $out, $r, $invoice_path, $eid, $request_id);
        $update->execute();

    } else if ($d || $c || $in > 0 || $out > 0 || $r || $invoice_path) {
        // New row → insert
        $insert->bind_param("issddss", $request_id, $d, $c, $in, $out, $r, $invoice_path);
        $insert->execute();
    }
}

$_SESSION['success'] = "Expense sheet updated successfully.";
header("Location: ../expense_request.php");
exit();
