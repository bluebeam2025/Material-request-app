<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: dashboard.php'); exit();
}
include 'php/db_connect.php';

$sheet_id = (int)($_GET['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

// Fetch sheet info
$sheet = $conn->query("SELECT er.*, p.project_name 
    FROM expense_requests er 
    JOIN projects p ON p.id = er.project_id 
    WHERE er.id = $sheet_id AND er.user_id = $user_id")->fetch_assoc();

if (!$sheet) {
    echo "<p>Invalid sheet ID or access denied.</p>";
    exit;
}

// Fetch expense entries
$expenses = $conn->query("SELECT * FROM expense_entries WHERE request_id = $sheet_id ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$totalIn = 0;
$totalOut = 0;
foreach ($expenses as $e) {
    $totalIn  += (float)$e['cash_in'];
    $totalOut += (float)$e['cash_out'];
}
$balance = $totalIn - $totalOut;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Expense Sheet</title>
<link rel="stylesheet" href="../css/style.css">
<style>
table { width:100%; border-collapse:collapse; background:#fff; margin-top:10px }
th, td { padding:20px 10px; border:1px solid #ccc; font-size:.92rem }
th { background:#0d47a1; color:#fff }
input[type=text], input[type=number], input[type=date], input[type=file] { width:100%; padding:8px; box-sizing:border-box }
textarea { width:100%; padding:8px; box-sizing:border-box; resize:vertical }
button { padding:8px 16px; background:#0d47a1; color:#fff; border:none; border-radius:4px; cursor:pointer; margin:10px 4px 0 0 }
.btn-delete { background:#c62828; }
.btn-print  { background:#2e7d32; }
.btn-edit   { background:#1565c0; }
.editable-row input, .editable-row textarea { background:#f9f9f9; border:1px solid #999; }
</style>
</head>
<body>
<?php include '../partials/sidebar.php'; ?>
<?php include '../partials/header.php'; ?>
<div class="main-content">
  <h2>Expense Sheet: <?= htmlspecialchars($sheet['project_name']) ?></h2>

  <!-- Action buttons -->
  <div style="margin-bottom:12px;">
    <button class="btn-print" onclick="window.print()">Print PDF</button>
    <a href="delete_expense_sheet.php?id=<?= $sheet_id ?>" onclick="return confirm('Delete this entire expense sheet?')" class="btn-delete">Delete Sheet</a>
  </div>

  <form method="POST" action="update_expenses.php" enctype="multipart/form-data">
    <input type="hidden" name="request_id" value="<?= $sheet_id ?>">

    <table>
      <thead>
        <tr>
          <th>SN</th>
          <th>Description</th>
          <th>Category</th>
          <th>Cash In</th>
          <th>Cash Out</th>
          <th>Date</th>
          <th>Remarks</th>
          <th>Invoice</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php 
      $sn = 1; $running_balance = 0;
      foreach ($expenses as $e): 
          $running_balance += (float)$e['cash_in'] - (float)$e['cash_out'];
      ?>
        <tr>
          <td><?= $sn++ ?><input type="hidden" name="entry_id[]" value="<?= $e['id'] ?>"></td>
          <td><input type="text" name="desc[]" value="<?= htmlspecialchars($e['description']) ?>" readonly></td>
          <td><input type="text" name="cat[]" value="<?= htmlspecialchars($e['category']) ?>" readonly></td>
          <td><input type="number" step="0.01" name="in[]" value="<?= $e['cash_in'] ?>" readonly></td>
          <td><input type="number" step="0.01" name="out[]" value="<?= $e['cash_out'] ?>" readonly></td>
          <td><input type="date" name="entry_date[]" value="<?= $e['entry_date'] ?>" readonly></td>
          <td><textarea name="remarks[]" readonly><?= htmlspecialchars($e['remarks']) ?></textarea></td>
          <td>
            <?php if (!empty($e['invoice_file'])): ?>
              <a href="../uploads/<?= htmlspecialchars($e['invoice_file']) ?>" target="_blank">View</a><br>
            <?php endif; ?>
            <input type="file" name="invoice_file[]">
          </td>
          <td>
            <button type="button" class="btn-edit" onclick="makeEditable(this)">Edit</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="3">Total</th>
          <th><?= number_format($totalIn, 2) ?></th>
          <th><?= number_format($totalOut, 2) ?></th>
          <th><?= number_format($balance, 2) ?></th>
          <th colspan="3"></th>
        </tr>
      </tfoot>
    </table>
    <button type="submit">Save Changes</button>
  </form>
</div>

<script>
function makeEditable(btn) {
  const row = btn.closest('tr');
  row.classList.add('editable-row');
  row.querySelectorAll('input, textarea').forEach(input => input.removeAttribute('readonly'));
}
</script>
</body>
</html>
