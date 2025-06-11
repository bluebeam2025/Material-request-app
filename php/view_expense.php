<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header("Location: ../dashboard.php");
    exit();
}

$request_id = (int)($_GET['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

// Fetch sheet metadata
$sheet = $conn->query("SELECT er.*, p.project_name FROM expense_requests er JOIN projects p ON p.id = er.project_id WHERE er.id = $request_id AND er.user_id = $user_id")->fetch_assoc();
if (!$sheet) {
    echo "<p>Sheet not found or access denied.</p>";
    exit();
}

// Fetch entries
$entries = $conn->query("SELECT * FROM expense_entries WHERE request_id = $request_id ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<title>Expense Sheet - <?= htmlspecialchars($sheet['project_name']) ?></title>
<link rel="stylesheet" href="../css/style.css">
<style>
table { width:100%; border-collapse:collapse; background:#fff; margin-top:10px; font-size:.95rem }
th, td { padding:20px 10px; border:1px solid #ccc; vertical-align:middle; text-align:center; }
th { background:#0d47a1; color:#fff; }
input[type="text"], input[type="number"], input[type="file"], textarea { width:100%; padding:8px; box-sizing:border-box; font-size:.9rem; }
textarea { resize:vertical; height: 60px; }
button { padding:8px 14px; background:#0d47a1; color:#fff; border:none; border-radius:6px; cursor:pointer; margin-top:10px; }
button:hover { opacity:0.9; }
.add-btn { background:#1565c0; margin-top:12px; }
.print-btn { background:#2e7d32; margin-left:10px; }
a.invoice-link { display:inline-block; margin-top:6px; font-size:0.85rem; color:#0d47a1; }
</style>
</head>
<body>
<?php include '../partials/sidebar.php'; ?>
<?php include '../partials/header.php'; ?>

<div class="main-content">
<h2>Expense Sheet – <?= htmlspecialchars($sheet['project_name']) ?></h2>

<form method="POST" action="update_expense.php" enctype="multipart/form-data">
<input type="hidden" name="request_id" value="<?= $sheet['id'] ?>">

<table id="expenseTable">
<thead>
    <tr>
        <th>SN</th>
        <th>Description</th>
        <th>Category</th>
        <th>Cash In</th>
        <th>Cash Out</th>
        <th>Remarks</th>
        <th>Invoice</th>
        <th>Delete</th>
    </tr>
</thead>
<tbody>
<?php 
$sn = 1;
foreach ($entries as $i => $e):
?>
<tr>
    <td><?= $sn++ ?></td>
    <td>
        <input type="hidden" name="entry_id[]" value="<?= $e['id'] ?>">
        <input type="text" name="description[]" value="<?= htmlspecialchars($e['description']) ?>">
    </td>
    <td><input type="text" name="category[]" value="<?= htmlspecialchars($e['category']) ?>"></td>
    <td><input type="number" step="0.01" name="cash_in[]" value="<?= $e['cash_in'] ?>"></td>
    <td><input type="number" step="0.01" name="cash_out[]" value="<?= $e['cash_out'] ?>"></td>
    <td><textarea name="remarks[]"><?= htmlspecialchars($e['remarks']) ?></textarea></td>
    <td>
        <input type="file" name="invoice_file_<?= $i ?>">
        <?php if (!empty($e['invoice_file'])): ?>
            <a class="invoice-link" href="../<?= htmlspecialchars($e['invoice_file']) ?>" target="_blank">View File</a>
        <?php endif; ?>
    </td>
    <td>
        <input type="checkbox" name="delete_entry[<?= $i ?>]" value="1">
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<button type="button" class="add-btn" onclick="addRow()">+ Add Row</button>
<button type="submit">Save Changes</button>
<button type="button" class="print-btn" onclick="window.print()">Print</button>

</form>
</div>

<script>
function addRow() {
    const table = document.getElementById('expenseTable').getElementsByTagName('tbody')[0];
    const rowCount = table.rows.length;
    const row = table.rows[0].cloneNode(true);

    // Clear inputs in cloned row
    row.querySelectorAll('input[type="text"], input[type="number"], textarea').forEach(el => el.value = '');
    row.querySelector('input[type="hidden"]').value = '0'; // new row (entry_id = 0)
    row.querySelector('input[type="file"]').setAttribute('name', 'invoice_file_' + rowCount);
    const link = row.querySelector('a.invoice-link');
    if (link) link.remove();

    table.appendChild(row);
}
</script>
</body>
</html>
