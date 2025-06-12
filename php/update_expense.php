<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: dashboard.php');
    exit();
}

$request_id = (int)($_POST['request_id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

// Verify user owns this request
$sheet = $conn->query("SELECT * FROM expense_requests WHERE id = $request_id AND user_id = $user_id")->fetch_assoc();
if (!$sheet) {
    $_SESSION['error'] = 'Invalid expense sheet.';
    header('Location: ../expense_request.php');
    exit();
}

// Prepare posted data
$entry_ids   = $_POST['entry_id'] ?? [];
$desc        = $_POST['description'] ?? [];
$cat         = $_POST['category'] ?? [];
$cash_in     = $_POST['cash_in'] ?? [];
$cash_out    = $_POST['cash_out'] ?? [];
$remarks     = $_POST['remarks'] ?? [];
$delete_ids  = $_POST['delete_entry'] ?? [];

// Loop through all rows
for ($i = 0; $i < count($entry_ids); $i++) {
    $eid = (int)$entry_ids[$i];
    $d   = trim($desc[$i]);
    $c   = trim($cat[$i]);
    $in  = (float)$cash_in[$i];
    $out = (float)$cash_out[$i];
    $r   = trim($remarks[$i]);

    // Handle uploaded file for this row
    $invoiceFileName = '';
    if (isset($_FILES["invoice_file_$i"]) && $_FILES["invoice_file_$i"]['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES["invoice_file_$i"]['tmp_name'];
        $orig_name = basename($_FILES["invoice_file_$i"]['name']);
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
            $invoiceFileName = "uploads/invoices/" . uniqid() . "." . $ext;
            move_uploaded_file($tmp_name, "../" . $invoiceFileName);
        }
    }

    // Delete row if requested
    if (in_array($eid, $delete_ids) && $eid > 0) {
        $conn->query("DELETE FROM expense_entries WHERE id = $eid AND request_id = $request_id");
        continue;
    }

    // Update existing row
    if ($eid > 0) {
        $sql = "UPDATE expense_entries 
                SET description=?, category=?, cash_in=?, cash_out=?, remarks=?";
        if ($invoiceFileName) {
            $sql .= ", invoice_file=?";
        }
        $sql .= " WHERE id=? AND request_id=?";

        if ($invoiceFileName) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssddssii", $d, $c, $in, $out, $r, $invoiceFileName, $eid, $request_id);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssddsii", $d, $c, $in, $out, $r, $eid, $request_id);
        }

        $stmt->execute();
    }
    // Insert new row if any field filled
    elseif ($d || $c || $in > 0 || $out > 0 || $r || $invoiceFileName) {
        $stmt = $conn->prepare("
            INSERT INTO expense_entries
            (request_id, description, category, cash_in, cash_out, remarks, invoice_file)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issddss", $request_id, $d, $c, $in, $out, $r, $invoiceFileName);
        $stmt->execute();
    }
}

$_SESSION['success'] = 'Expense sheet updated successfully.';
header('Location: ../php/view_expense.php?id=' . $request_id);
exit();
