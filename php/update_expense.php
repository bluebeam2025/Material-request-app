<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../dashboard.php'); exit();
}

$request_id = (int)($_POST['request_id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

// Confirm user owns this sheet
$sheet = $conn->query("
    SELECT id FROM expense_requests 
    WHERE id = $request_id AND user_id = $user_id
")->fetch_assoc();

if (!$sheet) {
    $_SESSION['error'] = "Unauthorized update.";
    header("Location: ../expense_request.php");
    exit();
}

// Loop over entries
$entry_ids = $_POST['entry_id'] ?? [];
$descs = $_POST['description'] ?? [];
$cats = $_POST['category'] ?? [];
$cash_ins = $_POST['cash_in'] ?? [];
$cash_outs = $_POST['cash_out'] ?? [];
$remarks = $_POST['remarks'] ?? [];

for ($i = 0; $i < count($entry_ids); $i++) {
    $eid = (int)$entry_ids[$i];
    $desc = trim($descs[$i]);
    $cat = trim($cats[$i]);
    $in = (float)$cash_ins[$i];
    $out = (float)$cash_outs[$i];
    $remark = trim($remarks[$i]);

    // Handle invoice upload (if any)
    $invoice_file_field = 'invoice_file_' . $eid;
    $invoice_filename = null;

    if (isset($_FILES[$invoice_file_field]) && $_FILES[$invoice_file_field]['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
        $filetype = mime_content_type($_FILES[$invoice_file_field]['tmp_name']);
        
        if (in_array($filetype, $allowed_types)) {
            // Generate safe filename
            $ext = pathinfo($_FILES[$invoice_file_field]['name'], PATHINFO_EXTENSION);
            $safe_name = 'inv_' . $request_id . '_' . $eid . '_' . time() . '.' . $ext;
            $upload_path = '../uploads/invoices/' . $safe_name;

            // Move file
            if (move_uploaded_file($_FILES[$invoice_file_field]['tmp_name'], $upload_path)) {
                $invoice_filename = $safe_name;

                // Optionally → delete old file if needed
                $old = $conn->query("SELECT invoice_file FROM expense_entries WHERE id = $eid")->fetch_assoc();
                if ($old && $old['invoice_file'] && file_exists('../uploads/invoices/' . $old['invoice_file'])) {
                    unlink('../uploads/invoices/' . $old['invoice_file']);
                }
            }
        }
    }

    // Build update query
    if ($invoice_filename) {
        $stmt = $conn->prepare("
            UPDATE expense_entries 
            SET description=?, category=?, cash_in=?, cash_out=?, remarks=?, invoice_file=? 
            WHERE id=? AND request_id=?");
        $stmt->bind_param("ssddssii", $desc, $cat, $in, $out, $remark, $invoice_filename, $eid, $request_id);
    } else {
        $stmt = $conn->prepare("
            UPDATE expense_entries 
            SET description=?, category=?, cash_in=?, cash_out=?, remarks=? 
            WHERE id=? AND request_id=?");
        $stmt->bind_param("ssddsii", $desc, $cat, $in, $out, $remark, $eid, $request_id);
    }

    $stmt->execute();
}

$_SESSION['success'] = "Expense sheet updated successfully.";
header("Location: view_expense.php?id=$request_id");
exit();
