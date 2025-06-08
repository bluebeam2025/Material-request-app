// ✅ FILE: php/view_expense.php
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('db_connect.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
  header("Location: dashboard.php");
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
    table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; }
    th, td { padding: 10px 12px; border: 1px solid #ccc; font-size: 0.92rem; color: #000; text-align: center; }
    th { background: #0d47a1; color: #fff; }
    input, textarea { width: 100%; border: none; padding: 6px; }
    .save-btn { background: #2e7d32; color: #fff; padding: 8px 16px; border: none; border-radius: 5px; margin-top: 10px; cursor: pointer; }
    .add-btn {
      background: #1565c0;
      color: #fff;
      padding: 7px 14px;
      border: none;
      border-radius: 4px;
      margin-top: 12px;
      cursor: pointer;
    }
    .add-btn:hover { opacity: 0.9; }
    .del-btn {
      background: #c62828;
      color: #fff;
      border: none;
      padding: 4px 10px;
      border-radius: 4px;
      margin-top: 6px;
      font-size: 0.85rem;
      cursor: pointer;
    }
    .del-btn:hover { opacity: 0.85; }
  </style>
</head><body>
<?php include '../partials/sidebar.php'; ?>
<?php include '../partials/header.php'; ?>

<div class="main-content">
  <h2>Expense Sheet – <?= htmlspecialchars($sheet['project_name']) ?></h2>

  <form method="POST" action="update_expense.php">
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
          <th>Delete</th>
        </tr>
      </thead>
      <tbody>
        <?php $total_in = 0; $total_out = 0; $sn = 1; foreach ($entries as $e): 
          $total_in += $e['cash_in'];
          $total_out += $e['cash_out'];
        ?>
          <tr>
            <td><?= $sn++ ?></td>
            <td><input name="description[]" value="<?= htmlspecialchars($e['description']) ?>"></td>
            <td><input name="category[]" value="<?= htmlspecialchars($e['category']) ?>"></td>
            <td><input type="number" step="0.01" name="cash_in[]" value="<?= $e['cash_in'] ?>"></td>
            <td><input type="number" step="0.01" name="cash_out[]" value="<?= $e['cash_out'] ?>"></td>
            <td><textarea name="remarks[]"><?= htmlspecialchars($e['remarks']) ?></textarea></td>
            <td><button type="button" class="del-btn" onclick="this.closest('tr').remove();">×</button></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="3">Total</th>
          <th><?= number_format($total_in, 2) ?></th>
          <th><?= number_format($total_out, 2) ?></th>
          <th colspan="2"><?= number_format($total_in - $total_out, 2) ?></th>
        </tr>
      </tfoot>
    </table>
    <button type="button" class="add-btn" onclick="addNewRow()">+ Add Row</button>
    <button class="save-btn">Save Changes</button>
  </form>
</div>
<script>
function addNewRow() {
  const table = document.getElementById("expenseTable").getElementsByTagName('tbody')[0];
  const row = table.insertRow();
  row.innerHTML = `
    <td>New</td>
    <td><input name="description[]"></td>
    <td><input name="category[]"></td>
    <td><input type="number" step="0.01" name="cash_in[]"></td>
    <td><input type="number" step="0.01" name="cash_out[]"></td>
    <td><textarea name="remarks[]"></textarea></td>
    <td><button type="button" class="del-btn" onclick="this.closest('tr').remove();">×</button></td>
  `;
}
</script>
</body></html>
