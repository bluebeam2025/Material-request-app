<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../dashboard.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$request_id = (int)($_POST['request_id'] ?? 0);

if (!$request_id) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../expense_request.php");
    exit();
}

// Upload config
$upload_dir = "../uploads/invoices/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0775, true);
}

// Process entries
$entry_ids = $_POST['entry_id'] ?? [];
$entry_dates = $_POST['entry_date'] ?? [];
$descriptions = $_POST['description'] ?? [];
$categories = $_POST['category'] ?? [];
$cash_in = $_POST['cash_in'] ?? [];
$cash_out = $_POST['cash_out'] ?? [];
$remarks = $_POST['remarks'] ?? [];
$invoice_files = $_FILES['invoice_file'] ?? [];

for ($i = 0; $i < count($entry_ids); $i++) {
    $eid = (int)$entry_ids[$i];
    $date = $entry_dates[$i] ?: NULL;
    $desc = trim($descriptions[$i]);
    $cat = trim($categories[$i]);
    $in = (float)$cash_in[$i];
    $out = (float)$cash_out[$i];
    $remark = trim($remarks[$i]);

    // Handle file upload
    $invoice_name = '';
    if (isset($invoice_files['name'][$i]) && $invoice_files['name'][$i]) {
        $tmp_name = $invoice_files['tmp_name'][$i];
        $orig_name = basename($invoice_files['name'][$i]);
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
            $invoice_name = time() . "_" . $orig_name;
            move_uploaded_file($tmp_name, $upload_dir . $invoice_name);
        }
    }

    // UPDATE existing
    if ($eid > 0) {
        $sql = "UPDATE expense_entries 
                SET entry_date=?, description=?, category=?, cash_in=?, cash_out=?, remarks=?";
        $params = [$date, $desc, $cat, $in, $out, $remark];

        if ($invoice_name) {
            $sql .= ", invoice_file=?";
            $params[] = $invoice_name;
        }

        $sql .= " WHERE id=? AND request_id=?";
        $params[] = $eid;
        $params[] = $request_id;

        $stmt = $conn->prepare($sql);
        $types = str_repeat("s", 3) . "ddssii";
        if ($invoice_name) {
            $types = str_repeat("s", 4) . "ddssii";
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();

    // INSERT new
    } elseif ($desc || $cat || $in > 0 || $out > 0 || $remark || $invoice_name) {
        $stmt = $conn->prepare("INSERT INTO expense_entries 
            (request_id, entry_date, description, category, cash_in, cash_out, remarks, invoice_file)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssddss", $request_id, $date, $desc, $cat, $in, $out, $remark, $invoice_name);
        $stmt->execute();
    }
}

$_SESSION['success'] = "Expense sheet updated successfully.";
header("Location: view_expense.php?id=$request_id");
exit();
