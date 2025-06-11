<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../dashboard.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$request_id = (int)($_POST['request_id'] ?? 0);

// Security check: is this request_id owned by user?
$check = $conn->query("SELECT id FROM expense_requests WHERE id = $request_id AND user_id = $user_id")->fetch_assoc();
if (!$check) {
    $_SESSION['error'] = 'Access denied.';
    header('Location: ../expense_request.php');
    exit();
}

// Read form data
$entry_ids = $_POST['entry_id'] ?? [];
$descriptions = $_POST['description'] ?? [];
$categories = $_POST['category'] ?? [];
$cash_in = $_POST['cash_in'] ?? [];
$cash_out = $_POST['cash_out'] ?? [];
$remarks = $_POST['remarks'] ?? [];
$dates = $_POST['date'] ?? [];
$delete_rows = $_POST['delete_row'] ?? [];

$total = count($entry_ids);

// Handle deletions first
if (!empty($delete_rows)) {
    $idsToDelete = array_map('intval', $delete_rows);
    $idsList = implode(',', $idsToDelete);

    // Optionally delete invoice files here too if needed!
    $files = $conn->query("SELECT invoice_file FROM expense_entries WHERE id IN ($idsList)");
    while ($f = $files->fetch_assoc()) {
        if (!empty($f['invoice_file']) && file_exists("../uploads/".$f['invoice_file'])) {
            unlink("../uploads/".$f['invoice_file']);
        }
    }

    // Now delete rows
    $conn->query("DELETE FROM expense_entries WHERE id IN ($idsList) AND request_id = $request_id");
}

// Prepare statements
$update = $conn->prepare("
    UPDATE expense_entries 
    SET date=?, description=?, category=?, cash_in=?, cash_out=?, remarks=?, invoice_file=? 
    WHERE id=? AND request_id=?");

$insert = $conn->prepare("
    INSERT INTO expense_entries (request_id, date, description, category, cash_in, cash_out, remarks, invoice_file)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

// Handle each row (update or insert)
for ($i = 0; $i < $total; $i++) {
    $eid = (int)$entry_ids[$i];
    $desc = trim($descriptions[$i]);
    $cat = trim($categories[$i]);
    $in = (float)$cash_in[$i];
    $out = (float)$cash_out[$i];
    $remark = trim($remarks[$i]);
    $date = trim($dates[$i]);

    // Handle file upload (optional)
    $invoice_filename = '';

    if (isset($_FILES['invoice_file']['name'][$i]) && $_FILES['invoice_file']['error'][$i] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['invoice_file']['tmp_name'][$i];
        $orig_name = basename($_FILES['invoice_file']['name'][$i]);
        $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
            $new_name = "inv_".$request_id."_".time()."_".$i.".".$ext;
            move_uploaded_file($tmp_name, "../uploads/".$new_name);
            $invoice_filename = $new_name;
        }
    }

    // UPDATE existing row
    if ($eid > 0) {
        // If new file uploaded, use new file. Otherwise fetch old file
        if (empty($invoice_filename)) {
            $old = $conn->query("SELECT invoice_file FROM expense_entries WHERE id = $eid")->fetch_assoc();
            $invoice_filename = $old['invoice_file'] ?? '';
        }

        $update->bind_param("sssddssii", $date, $desc, $cat, $in, $out, $remark, $invoice_filename, $eid, $request_id);
        $update->execute();
    }
    // INSERT new row if any field is provided
    else if ($desc || $cat || $in > 0 || $out > 0 || $remark || $date) {
        $insert->bind_param("issddsss", $request_id, $date, $desc, $cat, $in, $out, $remark, $invoice_filename);
        $insert->execute();
    }
}

$_SESSION['success'] = "Expense sheet updated.";
header("Location: view_expense.php?id=$request_id");
exit();
