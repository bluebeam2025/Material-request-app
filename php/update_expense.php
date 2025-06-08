<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$request_id = (int)($_POST['request_id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

// Sanitize all inputs
$ids       = $_POST['entry_id']     ?? [];
$desc      = $_POST['description']  ?? [];
$cat       = $_POST['category']     ?? [];
$cash_in   = $_POST['cash_in']      ?? [];
$cash_out  = $_POST['cash_out']     ?? [];
$remarks   = $_POST['remarks']      ?? [];

if (count($ids) !== count($desc)) {
    $_SESSION['error'] = "Mismatch in entry data.";
    header("Location: ../view_expense.php?id=$request_id");
    exit();
}

// Prepare update and insert statements
$update = $conn->prepare("UPDATE expense_entries SET description=?, category=?, cash_in=?, cash_out=?, remarks=? WHERE id=? AND request_id=?");
$insert = $conn->prepare("INSERT INTO expense_entries (request_id, description, category, cash_in, cash_out, remarks) VALUES (?, ?, ?, ?, ?, ?)");

// Loop through submitted rows
for ($i = 0; $i < count($ids); $i++) {
    $eid  = (int)$ids[$i];
    $d    = trim($desc[$i]);
    $c    = trim($cat[$i]);
    $in   = floatval($cash_in[$i]);
    $out  = floatval($cash_out[$i]);
    $r    = trim($remarks[$i]);

    if ($eid > 0) {
        // Existing entry – update
        $update->bind_param("ssddssi", $d, $c, $in, $out, $r, $eid, $request_id);
        $update->execute();
    } else if ($d || $c || $in > 0 || $out > 0 || $r) {
        // New row – insert if any value is filled
        $insert->bind_param("issdds", $request_id, $d, $c, $in, $out, $r);
        $insert->execute();
    }
}

$_SESSION['success'] = "Expense sheet updated.";
header("Location: ../view_expense.php?id=$request_id");
exit();
