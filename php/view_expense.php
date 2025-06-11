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
$request_id = (int)($_GET['id'] ?? 0);

// Fetch sheet metadata
$sheet = $conn->query("
    SELECT er.*, p.project_name 
    FROM expense_requests er
    JOIN projects p ON p.id = er.project_id
    WHERE er.id = $request_id AND er.user_id = $user_id
")->fetch_assoc();

if (!$sheet) {
    echo "<p>Sheet not found or access denied.</p>";
    exit();
}

// Fetch expense entries
$entries = $conn->query("
    SELECT * FROM expense_entries 
    WHERE request_id = $request_id 
    ORDER BY id ASC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Expense Sheet - <?= htmlspecialchars($sheet['project_name']) ?></title>
<link rel="stylesheet" href="../css/style.css">
<style>
table { width:100%; border-collapse:collapse; background:#fff; margin-top:10px }
th, td { padding:15px; border:1px solid #ccc; font-size:.92rem; vertical-align:top }
th { background:#0d47a1; color:#fff }
input[type=text], input[type=number], input[type=date] { width:100%; padding:8px; box-sizing:border-box }
textarea { width:100%; padding:8px; box-sizing:border-box; resize:vertical }
input[type=file] { width:auto; }
button { padding:6px 14px; background:#0d47a1; color:#fff; border:none; border-radius:4px; cursor:pointer; margin-top:8px }
button:hover { opacity:.9 }
.delete-btn { background:#c62828; }
.add-row-btn { background:#1565c0; margin-top:12px; }
.print-btn { background:#4e342e; float:right; margin-bottom:10px; }
</style>
</head>
<body>
<?php include '../partials/sidebar.php'; ?>
<?php include '../partials/header.php'; ?>

<div class="main-content">
<h2>Expense Sheet: <?= htmlspecialchars($sheet['project_name']) ?></h2>

<!-- Print Button -->
<button class="print-btn" onclick="window.print()">Print PDF</button>

<!-- Delete entire sheet button -->
<form method="POST" action="delete_expense_sheet.php" style="margin-bottom:20px">
    <input type="hidden" name="request_id" value="<?= $sheet['id'] ?>">
    <button type="submit" class="delete-btn" onclick="return confirm('Delete this entire expense sheet?')">Delete Expense Sheet</button>
</form>

<!-- Expense Sheet Form -->
<form method="POST" action="update_expense.php" enctype="multipart/form-data">
    <input type="hidden" name="request_id" value="<?= $sheet['id'] ?>">

    <table>
    <thead>
        <tr>
            <th>SN</th>
            <th>Date</th>
            <th>Description</th>
            <th>Category</th>
            <th>Cash In</th>
            <th>Cash Out</th>
            <th>Remarks</th>
            <th>Invoice (PDF/JPG/PNG)</th>
            <th>Delete Row</th>
        </tr>
    </thead>
    <tbody>
    <?php 
    $sn = 1;
    foreach ($entries as $e): ?>
        <tr>
            <input type="hidden" name="entry_id[]" value="<?= $e['id'] ?>">
            <td><?= $sn++ ?></td>
            <td><input type="date" name="date[]" value="<?= htmlspecialchars($e['date'] ?? '') ?>"></td>
            <td><input type="text" name="description[]" value="<?= htmlspecialchars($e['description']) ?>"></td>
            <td><input type="text" name="category[]" value="<?= htmlspecialchars($e['category']) ?>"></td>
            <td><input type="number" step="0.01" name="cash_in[]" value="<?= $e['cash_in'] ?>"></td>
            <td><input type="number" step="0.01" name="cash_out[]" value="<?= $e['cash_out'] ?>"></td>
            <td><textarea name="remarks[]"><?= htmlspecialchars($e['remarks']) ?></textarea></td>
            <td>
                <?php if (!empty($e['invoice_file'])): ?>
                    <a href="../uploads/<?= htmlspecialchars($e['invoice_file']) ?>" target="_blank">View File</a><br>
                <?php endif; ?>
                <input type="file" name="invoice_file[]">
            </td>
            <td>
                <input type="checkbox" name="delete_row[]" value="<?= $e['id'] ?>">
            </td>
        </tr>
    <?php endforeach; ?>
    <!-- Empty row for adding new -->
    <tr>
        <input type="hidden" name="entry_id[]" value="0">
        <td><?= $sn ?></td>
        <td><input type="date" name="date[]" ></td>
        <td><input type="text" name="description[]" ></td>
        <td><input type="text" name="category[]" ></td>
        <td><input type="number" step="0.01" name="cash_in[]" value="0"></td>
        <td><input type="number" step="0.01" name="cash_out[]" value="0"></td>
        <td><textarea name="remarks[]"></textarea></td>
        <td><input type="file" name="invoice_file[]"></td>
        <td>New Row</td>
    </tr>
    </tbody>
    </table>

    <button type="submit">Save Changes</button>
</form>

</div>
</body>
</html>
