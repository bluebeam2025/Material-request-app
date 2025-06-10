<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: dashboard.php'); exit();
}
include 'php/db_connect.php';

$request_id = (int)($_GET['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

// Validate sheet
$sheet = $conn->query("SELECT er.*, p.project_name 
    FROM expense_requests er 
    JOIN projects p ON p.id = er.project_id 
    WHERE er.id = $request_id AND er.user_id = $user_id")->fetch_assoc();

if (!$sheet) {
    echo "<p>Invalid expense sheet or access denied.</p>"; exit;
}

$entries = $conn->query("SELECT * FROM expense_entries WHERE request_id = $request_id ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

$totalIn = 0; $totalOut = 0;
foreach ($entries as $e) {
    $totalIn  += (float)$e['cash_in'];
    $totalOut += (float)$e['cash_out'];
}
$balance = $totalIn - $totalOut;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Expense Sheet – <?= htmlspecialchars($sheet['project_name']) ?></title>
<link rel="stylesheet" href="../css/style.css">
<style>
table { width:100%; border-collapse:collapse; background:#fff; margin-top:10px }
th, td { padding:20px 10px; border:1px solid #ccc; font-size:.92rem; vertical-align:middle }
th { background:#0d47a1; color:#fff }
input[type=text], input[type=number], input[type=date], input[type=file] { width:100%; padding:6px; box-sizing:border-box }
textarea { width:100%; padding:6px; box-sizing:border-box }
button { padding:8px 14px; background:#0d47a1; color:#fff; border:none; border-radius:4px; cursor:pointer; margin-top:10px }
.add-row-btn { background:#2e7d32; }
.delete-sheet-btn { background:#c62828; float:right; margin-left:10px }
.print-btn { background:#4fc3f7; float:right; margin-left:10px }
</style>
</head>
<body>
<?php include '../partials/sidebar.php'; ?>
<?php include '../partials/header.php'; ?>

<div class="main-content">
  <h2>Expense Sheet – <?= htmlspecialchars($sheet['project_name']) ?></h2>

  <!-- Delete and Print buttons -->
  <a href="delete_expense_sheet.php?id=<?= $request_id ?>" class="delete-sheet-btn" onclick="return confirm('Delete this entire expense sheet?')">Delete Sheet</a>
  <button class="print-btn" onclick="window.print()">Print PDF</button>

  <form method="POST" action="update_expenses.php" enctype="multipart/form-data">
    <input type="hidden" name="request_id" value="<?= $request_id ?>">

    <table>
      <thead>
        <tr>
          <th>SN</th>
          <th>Description</th>
          <th>Category</th>
          <th>Cash In</th>
          <th>Cash Out</th>
          <th>Entry Date</th>
          <th>Invoice</th>
          <th>Remarks</th>
        </tr>
      </thead>
      <tbody id="entryBody">
        <?php $sn=1; foreach ($entries as $e): ?>
        <tr>
          <td><?= $sn++ ?></td>
          <td><input type="text" name="description[]" value="<?= htmlspecialchars($e['description']) ?>"></td>
          <td><input type="text" name="category[]" value="<?= htmlspecialchars($e['category']) ?>"></td>
          <td><input type="number" name="cash_in[]" step="0.01" value="<?= $e['cash_in'] ?>"></td>
          <td><input type="number" name="cash_out[]" step="0.01" value="<?= $e['cash_out'] ?>"></td>
          <td><input type="date" name="entry_date[]" value="<?= htmlspecialchars($e['entry_date']) ?>"></td>
          <td>
            <?php if (!empty($e['invoice_file'])): ?>
              <a href="../uploads/invoices/<?= $e['invoice_file'] ?>" target="_blank">View</a><br>
            <?php endif; ?>
            <input type="file" name="invoice[]">
          </td>
          <td><textarea name="remarks[]"><?= htmlspecialchars($e['remarks']) ?></textarea></td>
          <input type="hidden" name="entry_id[]" value="<?= $e['id'] ?>">
        </tr>
        <?php endforeach; ?>

        <!-- One blank row for adding new -->
        <tr>
          <td><?= $sn++ ?></td>
          <td><input type="text" name="description[]" value=""></td>
          <td><input type="text" name="category[]" value=""></td>
          <td><input type="number" name="cash_in[]" step="0.01" value=""></td>
          <td><input type="number" name="cash_out[]" step="0.01" value=""></td>
          <td><input type="date" name="entry_date[]" value=""></td>
          <td><input type="file" name="invoice[]"></td>
          <td><textarea name="remarks[]"></textarea></td>
          <input type="hidden" name="entry_id[]" value="0">
        </tr>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="3">Total</th>
          <th><?= number_format($totalIn,2) ?></th>
          <th><?= number_format($totalOut,2) ?></th>
          <th colspan="3"><?= number_format($balance,2) ?></th>
        </tr>
      </tfoot>
    </table>

    <button type="button" class="add-row-btn" onclick="addRow()">+ Add Row</button>
    <button type="submit">Save Changes</button>
  </form>
</div>

<script>
function addRow() {
  const tbody = document.getElementById('entryBody');
  const tr = tbody.rows[tbody.rows.length-1].cloneNode(true);
  tr.querySelectorAll('input,textarea').forEach(e => e.value = '');
  tr.querySelector('input[type=hidden]').value = '0';
  tbody.appendChild(tr);
}
</script>

</body>
</html>
