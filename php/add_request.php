<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Unauthorized access.';
    header('Location: ../material_request.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Validate input
$project_id = (int)$_POST['project_id'];
$categories = $_POST['category'] ?? [];
$products   = $_POST['product'] ?? [];
$units      = $_POST['unit'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$dates      = $_POST['required_date'] ?? [];
$remarks    = $_POST['remarks'] ?? [];

if (!$project_id || empty($products)) {
    $_SESSION['error'] = 'Missing project or product data.';
    header('Location: ../material_request.php');
    exit();
}

// Check if user is assigned to this project
$check = $conn->query("SELECT 1 FROM project_users WHERE user_id = $user_id AND project_id = $project_id");
if ($check->num_rows === 0) {
    $_SESSION['error'] = 'You are not assigned to this project.';
    header('Location: ../material_request.php');
    exit();
}

// Step 1: Generate request number → BBI/PRJ/001
$res = $conn->query("SELECT project_name FROM projects WHERE id = $project_id");
if (!$res || $res->num_rows === 0) {
    $_SESSION['error'] = 'Invalid project selected.';
    header('Location: ../material_request.php');
    exit();
}
$pname = $res->fetch_assoc()['project_name'];
$short = strtoupper(substr($pname, 0, 1) . substr($pname, intval(strlen($pname)/2), 1) . substr($pname, -1));

// Find last request number for this project prefix
$prefix = 'BBI/' . $short . '/';
$res = $conn->query("SELECT request_number FROM material_requests WHERE request_number LIKE '$prefix%' ORDER BY id DESC LIMIT 1");
if ($res && $res->num_rows > 0) {
    $last = $res->fetch_assoc()['request_number'];
    $num = (int)substr($last, strrpos($last, '/') + 1) + 1;
} else {
    $num = 1;
}
$request_number = $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);

// Step 2: Insert products
$stmt = $conn->prepare("INSERT INTO material_requests
    (user_id, project_id, request_number, category, product, unit, quantity, required_date, remarks)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$success = true;
for ($i = 0; $i < count($products); $i++) {
    $category = trim($categories[$i] ?? '');
    $product  = trim($products[$i] ?? '');
    $unit     = trim($units[$i] ?? '');
    $qty      = floatval($quantities[$i] ?? 0);
    $date     = $dates[$i] ?? '';
    $remark   = trim($remarks[$i] ?? '');

    if (!$product || !$qty || !$date) continue;

    $stmt->bind_param("iisssssss", $user_id, $project_id, $request_number, $category, $product, $unit, $qty, $date, $remark);
    if (!$stmt->execute()) {
        $success = false;
        break;
    }
}

$_SESSION[$success ? 'success' : 'error'] = $success
    ? "Material Request <strong>$request_number</strong> submitted."
    : "Something went wrong. Please try again.";

header('Location: ../material_request.php');
exit();
?>
