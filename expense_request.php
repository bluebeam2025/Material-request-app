<?php
session_start();
include 'php/db_connect.php';

$request_id = (int)($_GET['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

// Get expense request info
$request = $conn->query("SELECT er.*, p.project_name
                          FROM expense_requests er
                          JOIN projects p ON p.id = er.project_id
                          WHERE er.id = $request_id AND er.user_id = $user_id")
                ->fetch_assoc();

if (!$request) {
    $_SESSION['error'] = "Invalid or unauthorized expense sheet.";
    header("Location: expense_requests.php");
    exit();
}

// Fetch entries
$entries = $conn->query("SELECT * FROM expense_entries WHERE request_id = $request_id ORDER BY id")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Expense Sheet – <?= htmlspecialchars($request['project_name']) ?></title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; }
    th, td { padding: 10px; border: 1px solid #ccc; font-size: .9rem; text-align: center; }
    th { background: #0d47a1; color: #fff; }
    .submit-btn { background: #0d47a1; color: #fff; padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; margin-top: 10px; }
    .add-user-btn { background: #2e7d32; color: #fff; padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; }
    label { display: block; margin-top: 10px; font-weight: 600; }
    input, textarea { width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #ccc; border-radius: 4px; }
    .form-box { background: #e3f2fd; padding: 15px; border-radius: 6px; margin-top: 15px; display: none; }
    tfoot td { font-weight: bold; background: #f0f0f0; }
  </style>
</head>
<body>
<?php include 'partials/sidebar.php'; include 'partials/header.php'; ?>

<div class="main-content">
  <h2>Expense Sheet: <?= htmlspecialchars($request['project_name']) ?></h2>

  <button class="add-user-btn" onclick="toggleForm()">+ Add Entry</button>

  <div id="entryForm" class="form-box">
    <form method="POST" action="php/add_expense_entry.php">
      <input type="hidden" name="request_id" value="<?= $request_id ?>">

      <label>Description</label>
      <input type="text" name="description" required>

      <label>Category</label>
      <input type="text" name="category" required>

      <label>Cash In</label>
      <input type="number" step="0.01" name="cash_in" value="0">

      <label>Cash Out</label>
      <input type="number" step="0.01" name="cash_out" value="0">

      <label>Remarks</label>
      <textarea name="remarks"></textarea>

      <button type="submit" class="submit-btn">Add Entry</button>
    </form>
  </div>

  <table>
    <thead>
      <tr>
        <th>SN</th>
        <th>Description</th>
        <th>Category</th>
        <th>Cash In</th>
        <th>Cash Out</th>
        <th>Balance</th>
        <th>Remarks</th>
      </tr>
    </thead>
    <tbody>
      <?php
        $sn = 1;
        $balance = 0;
        $total_in = 0;
        $total_out = 0;
        foreach ($entries as $e):
          $balance += ($e['cash_in'] - $e['cash_out']);
          $total_in += $e['cash_in'];
          $total_out += $e['cash_out'];
      ?>
        <tr>
          <td><?= $sn++ ?></td>
          <td><?= htmlspecialchars($e['description']) ?></td>
          <td><?= htmlspecialchars($e['category']) ?></td>
          <td><?= number_format($e['cash_in'], 2) ?></td>
          <td><?= number_format($e['cash_out'], 2) ?></td>
          <td><?= number_format($balance, 2) ?></td>
          <td><?= htmlspecialchars($e['remarks']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3">TOTAL</td>
        <td><?= number_format($total_in, 2) ?></td>
        <td><?= number_format($total_out, 2) ?></td>
        <td><?= number_format($total_in - $total_out, 2) ?></td>
        <td></td>
      </tr>
    </tfoot>
  </table>
</div>

<script>
function toggleForm() {
  const box = document.getElementById("entryForm");
  box.style.display = (box.style.display === "none" || box.style.display === "") ? "block" : "none";
}
</script>
</body>
</html>
