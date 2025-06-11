<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: dashboard.php'); exit();
}
include 'db_connect.php';

$user_id = (int)$_SESSION['user_id'];
$project_id = (int)($_POST['project_id'] ?? 0);

if (!$project_id) {
    $_SESSION['error'] = "Invalid project selected.";
    header("Location: ../add_expense_sheet.php");
    exit();
}

// Insert main sheet entry first
$stmt = $conn->prepare("INSERT INTO expense_requests (project_id, user_id) VALUES (?, ?)");
$stmt->bind_param("ii", $project_id, $user_id);
$stmt->execute();
$request_id = $conn->insert_id;

// Prepare entries
$desc = $_POST['description'] ?? [];
$cat  = $_POST['category'] ?? [];
$cash_in = $_POST['cash_in'] ?? [];
$cash_out = $_POST['cash_out'] ?? [];
$remarks = $_POST['remarks'] ?? [];

// Prepare insert entry
$insert = $conn->prepare("INSERT INTO expense_entries 
    (request_id, description, category, cash_in, cash_out, remarks, invoice_file) 
    VALUES (?, ?, ?, ?, ?, ?, ?)");

// Upload dir
$upload_dir = '../uploads/expense_invoices/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

for ($i = 0; $i < count($desc); $i++) {

    $d = trim($desc[$i]);
    $c = trim($cat[$i]);
    $in = (float)$cash_in[$i];
    $out = (float)$cash_out[$i];
    $r = trim($remarks[$i]);

    // Upload file for this row
    $invoice_field = "invoice_file_" . $i;
    $invoice_path = '';

    if (isset($_FILES[$invoice_field]) && $_FILES[$invoice_field]['error'] == 0) {
        $tmp_name = $_FILES[$invoice_field]['tmp_name'];
        $orig_name = basename($_FILES[$invoice_field]['name']);
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        // Allow pdf,jpg,jpeg,png only
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
            $new_name = "invoice_" . time() . "_$i.$ext";
            $target_path = $upload_dir . $new_name;

            if (move_uploaded_file($tmp_name, $target_path)) {
                $invoice_path = 'uploads/expense_invoices/' . $new_name;
            }
        }
    }

    // Insert row if at least one value entered
    if ($d || $c || $in > 0 || $out > 0 || $r || $invoice_path) {
        $insert->bind_param("issddss", $request_id, $d, $c, $in, $out, $r, $invoice_path);
        $insert->execute();
    }
}

$_SESSION['success'] = "Expense sheet added successfully.";
header("Location: ../expense_request.php");
exit;
