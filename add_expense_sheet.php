<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: dashboard.php'); exit();
}
include 'php/db_connect.php';

$user_id = (int)$_SESSION['user_id'];

// Get assigned projects
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
<meta charset="UTF-8">
<title>Add Expense Sheet – Bluebeam Infra</title>
<link rel="stylesheet" href="css/style.css">
<style>
table { width: 100%; border-collapse: collapse; margin-top: 12px; background: #fff; }
th, td { border: 1px solid #ccc; padding: 14px; font-size: 0.92rem; color: #000 }
th { background: #0d47a1; color: #fff }
textarea { resize: vertical; }
input[type=text], input[type=number], textarea, input[type=file] {
  width: 100%; padding: 6px; box-sizing: border-box; margin-bottom: 4px;
}
.submit-btn { background: #0d47a1; color: #fff; padding: 8px 14px; border: none; border-radius: 6px; margin-top: 12px; cursor: pointer }
.submit-btn:hover { opacity: 0.9 }
.add-btn { background: #1565c0; color: #fff; padding: 6px 10px; border-radius: 4px; font-size: 0.85rem; margin-top: 8px; cursor: pointer; border: none; }
</style>
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/header.php'; ?>

<div class="main-content">
  <h2>Add Expense Sheet</h2>
  <form method="POST" action="php/add_expense.php" enctype="multipart/form-data">
    <label>Select Project</label>
    <select name="project_id" required>
      <option value="">Select project</option>
      <?php foreach($projects as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['project_name']) ?></option>
      <?php endforeach; ?>
    </select>

    <table id="expenseTable">
      <thead>
        <tr>
          <th>SN</th>
          <th>Description / Category</th>
          <th>Cash In / Cash Out</th>
          <th>Remarks</th>
          <th>Invoice File</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>1</td>
          <td>
            <input type="text" name="description[]" placeholder="Description" required><br>
            <input type="text" name="category[]" placeholder="Category" required>
          </td>
          <td>
            <input type="number" step="0.01" name="cash_in[]" placeholder="Cash In" value="0"><br>
            <input type="number" step="0.01" name="cash_out[]" placeholder="Cash Out" value="0">
          </td>
          <td><textarea name="remarks[]" placeholder="Remarks"></textarea></td>
          <td>
            <input type="file" name="invoice_file_0" accept=".pdf,.jpg,.jpeg,.png">
          </td>
          <td><button type="button" onclick="removeRow(this)">✖</button></td>
        </tr>
      </tbody>
    </table>

    <button type="button" class="add-btn" onclick="addRow()">+ Add Row</button><br>
    <button type="submit" class="submit-btn">Save Sheet</button>
  </form>
</div>

<script>
function addRow() {
    const table = document.getElementById('expenseTable').getElementsByTagName('tbody')[0];
    const rowCount = table.rows.length;
    const row = table.rows[0].cloneNode(true);
    row.querySelectorAll('input[type=text], input[type=number], textarea').forEach(el => el.value = '');
    row.querySelector('input[type=file]').name = 'invoice_file_' + rowCount;
    row.cells[0].innerText = rowCount + 1;
    table.appendChild(row);
}

function removeRow(btn) {
    const row = btn.closest('tr');
    const table = row.parentNode;
    if (table.rows.length > 1) {
        row.remove();
    }
}
</script>
</body>
</html>
