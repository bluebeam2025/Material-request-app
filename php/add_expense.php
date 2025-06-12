<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../dashboard.php'); exit();
}

$user_id = (int)$_SESSION['user_id'];
$project_id = (int)($_POST['project_id'] ?? 0);

if (!$project_id) {
    $_SESSION['error'] = 'Please select a project.';
    header('Location: ../add_expense_sheet.php'); exit();
}

// Create parent expense_request row
$stmt = $conn->prepare("INSERT INTO expense_requests (user_id, project_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $project_id);
$stmt->execute();

$request_id = $conn->insert_id; // Get new expense_request ID

// Prepare child inserts
$desc = $_POST['description'] ?? [];
$cat = $_POST['category'] ?? [];
$cash_in = $_POST['cash_in'] ?? [];
$cash_out = $_POST['cash_out'] ?? [];
$remarks = $_POST['remarks'] ?? [];

$totalRows = count($desc);

$target_dir = "../uploads/invoices/";

// Ensure uploads folder exists
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0775, true);
}

for ($i = 0; $i < $totalRows; $i++) {
    $d = trim($desc[$i]);
    $c = trim($cat[$i]);
    $in = (float)$cash_in[$i];
    $out = (float)$cash_out[$i];
    $r = trim($remarks[$i]);

    // Handle file upload
    $invoiceFile = null;
    $fileField = 'invoice_file_' . $i;

    if (isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES[$fileField]['tmp_name'];
        $originalName = basename($_FILES[$fileField]['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Safe filename
        $newName = 'inv_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        $targetFile = $target_dir . $newName;

        // Allowed extensions
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
            if (move_uploaded_file($tmpName, $targetFile)) {
                $invoiceFile = $newName;
            }
        }
    }

    // Insert row if any value entered
    if ($d || $c || $in > 0 || $out > 0 || $r || $invoiceFile) {
        $stmt2 = $conn->prepare("
            INSERT INTO expense_entries (request_id, description, category, cash_in, cash_out, remarks, invoice_file)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt2->bind_param("issddss", $request_id, $d, $c, $in, $out, $r, $invoiceFile);
        $stmt2->execute();
    }
}

$_SESSION['success'] = "Expense Sheet saved successfully.";
header("Location: ../expense_request.php");
exit();
