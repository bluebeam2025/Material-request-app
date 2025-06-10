<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../dashboard.php'); exit();
}

$request_id = (int)($_POST['request_id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

if (!$request_id) {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: ../expense_request.php'); exit();
}

// Validate sheet belongs to user
$check = $conn->prepare("SELECT id FROM expense_requests WHERE id=? AND user_id=?");
$check->bind_param("ii", $request_id, $user_id);
$check->execute();
$res = $check->get_result();
if ($res->num_rows === 0) {
    $_SESSION['error'] = 'Unauthorized.';
    header('Location: ../expense_request.php'); exit();
}

// Process entries
$ids = $_POST['entry_id'] ?? [];
$desc = $_POST['description'] ?? [];
$cat = $_POST['category'] ?? [];
$cash_in = $_POST['cash_in'] ?? [];
$cash_out = $_POST['cash_out'] ?? [];
$date = $_POST['entry_date'] ?? [];
$remarks = $_POST['remarks'] ?? [];

$total_rows = count($desc);

for ($i = 0; $i < $total_rows; $i++) {
    $eid = (int)($ids[$i] ?? 0);
    $d = trim($desc[$i]);
    $c = trim($cat[$i]);
    $in = (float)$cash_in[$i];
    $out = (float)$cash_out[$i];
    $edate = $date[$i] ?? null;
    $r = trim($remarks[$i]);

    // Handle file upload if any
    $invoice_file = '';
    if (isset($_FILES['invoice']['name'][$i]) && $_FILES['invoice']['name'][$i]) {
        $allowed = ['pdf','jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES['invoice']['name'][$i], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $dir = '../uploads/invoices/';
            if (!is_dir($dir)) mkdir($dir,0777,true);
            $filename = 'invoice_' . $request_id . '_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            move_uploaded_file($_FILES['invoice']['tmp_name'][$i], $dir.$filename);
            $invoice_file = $filename;
        }
    }

    if ($eid > 0) {
        // Update existing row
        $sql = "UPDATE expense_entries SET 
                    description=?, category=?, cash_in=?, cash_out=?, entry_date=?, remarks=?";
        if ($invoice_file) $sql .= ", invoice_file=?";
        $sql .= " WHERE id=? AND request_id=?";
        
        if ($invoice_file) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdsssii", $d, $c, $in, $out, $edate, $r, $invoice_file, $eid, $request_id);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdssii", $d, $c, $in, $out, $edate, $r, $eid, $request_id);
        }
        $stmt->execute();
    } else if ($d || $c || $in > 0 || $out > 0 || $r || $invoice_file) {
        // Insert new row
        $stmt = $conn->prepare("
            INSERT INTO expense_entries 
            (request_id, description, category, cash_in, cash_out, entry_date, remarks, invoice_file)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issddsss", $request_id, $d, $c, $in, $out, $edate, $r, $invoice_file);
        $stmt->execute();
    }
}

$_SESSION['success'] = 'Expense sheet updated.';
header("Location: ../php/view_expense.php?id=$request_id");
exit;
?>
