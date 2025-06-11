<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: dashboard.php'); exit();
}
include 'php/db_connect.php';

$user_id = (int)$_SESSION['user_id'];

// Assigned projects
$projects = $conn->query("
    SELECT p.id, p.project_name
    FROM projects p
    JOIN project_users pu ON pu.project_id = p.id
    WHERE pu.user_id = $user_id
    ORDER BY p.project_name
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Add Expense Sheet – Bluebeam Infra</title>
<link rel="stylesheet" href="../css/style.css" />
<style>
table { width:100%; border-collapse:collapse; background:#fff; margin-top:10px; font-size:.95rem }
th, td { padding:20px 10px; border:1px solid #ccc; vertical-align:middle; text-align:center; }
th { background:#0d47a1; color:#fff; }
input[type="text"], input[type="number"], input[type="file"], textarea, select { width:100%; padding:8px; box-sizing:border-box; font-size:.9rem; }
textarea { resize:vertical; height: 60px; }
button { padding:8px 14px; background:#0d47a1; color:#fff; border:none; border-radius:6px; cursor:pointer; margin-top:10px; }
button:hover { opacity:0.9; }
.add-btn { background:#1565c0; margin-top:12px; }
</style>
</head>
<body>
<?php include '../partials/sidebar.php'; ?>
<?php include '../partials/header.php'; ?>

<div class="main-content">
<h2>Add Expense Sheet</h2>

<form method="POST" action="add_expense.php" enctype="multipart/form-data">
<label>Project</label>
<select name="project_id" required>
    <option value="">Select project</option>
    <?php foreach ($projects as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['project_name']) ?></option>
    <?php endforeach; ?>
</select>

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
    <tr>
        <td>1</td>
        <td><input type="text" name="description[]" required></td>
        <td><input type="text" name="category[]" required></td>
        <td><input type="number" step="0.01" name="cash_in[]" value="0"></td>
        <td><input type="number" step="0.01" name="cash_out[]" value="0"></td>
        <td><textarea name="remarks[]"></textarea></td>
        <td><input type="file" name="invoice_file_0" accept=".pdf,.png,.jpg,.jpeg"></td>
        <td><input type="checkbox" name="delete_entry[0]" value="1" disabled></td>
    </tr>
</tbody>
</table>

<button type="button" class="add-btn" onclick="addRow()">+ Add Row</button>
<button type="submit">Save Sheet</button>
</form>
</div>

<script>
function addRow() {
    const table = document.getElementById('expenseTable').getElementsByTagName('tbody')[0];
    const rowCount = table.rows.length;
    const row = table.rows[0].cloneNode(true);

    row.querySelectorAll('input[type="text"], input[type="number"], textarea').forEach(el => el.value = '');
    row.querySelector('input[type="file"]').setAttribute('name', 'invoice_file_' + rowCount);
    row.querySelector('input[type="checkbox"]').disabled = true;

    row.cells[0].innerText = rowCount + 1;
    table.appendChild(row);
}
</script>
</body>
</html>
