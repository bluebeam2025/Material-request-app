<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: dashboard.php'); exit();
}
include 'php/db_connect.php';

$project_id = (int)($_GET['project_id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

// Fetch project name
$project = $conn->query("SELECT project_name FROM projects WHERE id = $project_id")->fetch_assoc();
if (!$project) {
    echo "<p>Invalid project ID.</p>";
    exit;
}

// Fetch existing expense entries
$expenses = $conn->query("SELECT * FROM expenses WHERE project_id = $project_id ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Expense Sheet</title>
<link rel="stylesheet" href="css/style.css">
<style>
table { width:100%; border-collapse:collapse; background:#fff; margin-top:10px }
th, td { padding:10px; border:1px solid #ccc; font-size:.92rem }
th { background:#0d47a1; color:#fff }
input[type=text], input[type=number] { width:100%; padding:6px; box-sizing:border-box }
textarea { width:100%; padding:6px; box-sizing:border-box }
button { padding:6px 12px; background:#0d47a1; color:#fff; border:none; border-radius:4px; cursor:pointer; margin-top:10px }
</style>
</head>
<body>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/header.php'; ?>
<div class="main-content">
  <h2>Expense Sheet: <?= htmlspecialchars($project['project_name']) ?></h2>
  <form method="POST" action="php/update_expenses.php">
    <input type="hidden" name="project_id" value="<?= $project_id ?>">
    <table>
      <thead>
        <tr>
          <th>SN</th><th>Description</th><th>Category</th><th>Cash In</th><th>Cash Out</th><th>Balance</th><th>Remarks</th>
        </tr>
      </thead>
      <tbody>
      <?php 
      $sn = 1; $running_balance = 0;
      foreach ($expenses as $e): 
          $running_balance += (float)$e['cash_in'] - (float)$e['cash_out'];
      ?>
        <tr>
          <td><?= $sn++ ?></td>
          <td><input type="text" name="desc[]" value="<?= htmlspecialchars($e['description']) ?>"></td>
          <td><input type="text" name="cat[]" value="<?= htmlspecialchars($e['category']) ?>"></td>
          <td><input type="number" name="in[]" step="0.01" value="<?= $e['cash_in'] ?>"></td>
          <td><input type="number" name="out[]" step="0.01" value="<?= $e['cash_out'] ?>"></td>
          <td><?= number_format($running_balance, 2) ?></td>
          <td><textarea name="remarks[]"><?= htmlspecialchars($e['remarks']) ?></textarea></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="3">Total</th>
          <th><?= number_format($totalIn, 2) ?></th>
          <th><?= number_format($totalOut, 2) ?></th>
          <th><?= number_format($balance, 2) ?></th>
          <th></th>
        </tr>
      </tfoot>
    </table>
    <button type="submit">Save Changes</button>
  </form>
</div>
</body>
</html>
