<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: ../material_request.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$request_number = $_POST['request_number'] ?? '';

if (!$request_number) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../material_request.php");
    exit();
}

// Verify user owns the request and it’s still pending
$check = $conn->prepare("SELECT * FROM material_requests WHERE request_number = ? AND user_id = ?");
$check->bind_param("si", $request_number, $user_id);
$check->execute();
$rows = $check->get_result()->fetch_all(MYSQLI_ASSOC);

if (!$rows || stripos($rows[0]['status'], 'pending') !== 0) {
    $_SESSION['error'] = "Only pending requests can be edited.";
    header("Location: ../material_request.php");
    exit();
}


// Collect updated fields
$project_id = (int)$_POST['project_id'];
$categories = $_POST['category'] ?? [];
$products = $_POST['product'] ?? [];
$units = $_POST['unit'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$dates = $_POST['required_date'] ?? [];
$remarks = $_POST['remarks'] ?? [];

if (!$project_id || empty($products)) {
    $_SESSION['error'] = "Missing project or product data.";
    header("Location: ../material_request.php");
    exit();
}

// Step 1: Delete old request lines (safe as we're replacing)
$conn->query("DELETE FROM material_requests WHERE request_number = '$request_number' AND user_id = $user_id");

// Step 2: Insert updated request lines
$stmt = $conn->prepare("INSERT INTO material_requests 
    (user_id, project_id, request_number, category, product, unit, quantity, required_date, remarks) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$success = true;
for ($i = 0; $i < count($products); $i++) {
    $cat = $categories[$i] ?? '';
    $prod = $products[$i] ?? '';
    $unit = $units[$i] ?? '';
    $qty = floatval($quantities[$i] ?? 0);
    $req_date = $dates[$i] ?? '';
    $remark = $remarks[$i] ?? '';

    if (!$prod || !$qty || !$req_date) continue;

    $stmt->bind_param("iisssssss", $user_id, $project_id, $request_number, $cat, $prod, $unit, $qty, $req_date, $remark);
    if (!$stmt->execute()) {
        $success = false;
        break;
    }
}

if ($success) {
    $_SESSION['success'] = "Request updated successfully.";
} else {
    $_SESSION['error'] = "Failed to update request.";
}

header("Location: ../material_request.php");
exit();
?>
