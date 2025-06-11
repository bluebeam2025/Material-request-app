<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ../expense_request.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$request_id = (int)($_POST['request_id'] ?? 0);

if (!$request_id) {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: ../expense_request.php');
    exit();
}

// Fetch request owner
$check = $conn->query("SELECT user_id FROM expense_requests WHERE id = $request_id")->fetch_assoc();
if (!$check || $check['user_id'] != $user_id) {
    $_SESSION['error'] = 'Unauthorized.';
    header('Location: ../expense_request.php');
    exit();
}

// Process rows
$ids       = $_POST['entry_id'] ?? [];
$desc      = $_POST['description'] ?? [];
$cat       = $_POST['category'] ?? [];
$cash_in   = $_POST['cash_in'] ?? [];
$cash_out  = $_POST['cash_out'] ?? [];
$remarks   = $_POST['remarks'] ?? [];
$del_flags = $_POST['delete_entry'] ?? [];

// Prepare UPDATE
$update = $conn->prepare("UPDATE expense_entries SET description=?, category=?, cash_in=?, cash_out=?, remarks=?, invoice_file=? WHERE id=? AND request_id=?");

// Prepare INSERT
$insert = $conn->prepare("INSERT INTO expense_entries (request_id, description, category, cash_in, cash_out, remarks, invoice_file) VALUES (?, ?, ?, ?, ?, ?, ?)");

// Process each row
for ($i = 0; $i < count($desc); $i++) {
    $eid = (int)($ids[$i] ?? 0);
    $d   = trim($desc[$i]);
    $c   = trim($cat[$i]);
    $in  = (float)($cash_in[$i]);
    $out = (float)($cash_out[$i]);
    $r   = trim($remarks[$i]);

    // Handle file upload
    $inv_file = '';
    $fieldname = "invoice_file_$i";

    if (isset($_FILES[$fieldname]) && $_FILES[$fieldname]['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES[$fieldname]['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'jpeg', 'jpg', 'png'])) {
            $newname = 'uploads/invoice_' . time() . '_' . $i . '.' . $ext;
            move_uploaded_file($_FILES[$fieldname]['tmp_name'], '../' . $newname);
            $inv_file = $newname;
        }
    }

    // DELETE row if requested
    if (isset($del_flags[$i]) && $del_flags[$i] == '1' && $eid > 0) {
        $conn->query("DELETE FROM expense_entries WHERE id=$eid AND request_id=$request_id");
        continue;
    }

    // UPDATE existing row
    if ($eid > 0) {
        // Fetch existing file if not new upload
        if (!$inv_file) {
            $row = $conn->query("SELECT invoice_file FROM expense_entries WHERE id=$eid")->fetch_assoc();
            $inv_file = $row['invoice_file'] ?? '';
        }

        $update->bind_param("ssddssii", $d, $c, $in, $out, $r, $inv_file, $eid, $request_id);
        $update->execute();
    }
    // INSERT new row if at least one field filled
    elseif ($d || $c || $in > 0 || $out > 0 || $r || $inv_file) {
        $insert->bind_param("issddss", $request_id, $d, $c, $in, $out, $r, $inv_file);
        $insert->execute();
    }
}

$_SESSION['success'] = 'Expense sheet updated.';
header('Location: ../php/view_expense.php?id=' . $request_id);
exit();
