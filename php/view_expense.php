<?php
session_start();
include 'db_connect.php';

$sheet_id = (int)($_GET['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

$sheet = $conn->query("
    SELECT es.*, p.project_name 
    FROM expense_sheets es 
    JOIN projects p ON p.id = es.project_id
    WHERE es.id = $sheet_id AND es.user_id = $user_id
")->fetch_assoc();

if (!$sheet) {
    echo "<p>Sheet not found or access denied.</p>";
    exit();
}

$entries = $conn->query("
    SELECT * FROM expense_entries 
    WHERE sheet_id = $sheet_id 
    ORDER BY id ASC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Expense Sheet - <?= htmlspecialchars($sheet['project_name']) ?></title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; }
    th, td { padding: 14px; border: 1px solid #ccc; font-size: 0.95rem; color: #000; }
    th { background: #0d47a1; color: #fff; }
    .save-btn { background: #2e7d32; color: #fff; padding: 8px 16px; border: none; border-radius: 5px; margin-top: 10px; }
    input, textarea { width: 100%; box-sizing: border-box; padding: 6px; }
    input[type="date"] { padding: 6px; }
  </style>
</head>
<body>
<?php include '../partials/sidebar.php'; ?>
<?php include '../partials/header.php'; ?>

<div class="main-content">
  <h2>Expense Sheet – <?= htmlspecialchars($sheet['project_name']) ?></h2>

  <form method="POST" action="update_expense.php" enctype="multipart/form-data">
    <input type="hidden" name="sheet_id" value="<?= $sheet_id ?>">
    <table>
      <thead>
        <tr>
          <th>SN</th><th>Date</th><th>Description</th><th>Category</th>
          <th>Cash In</th><th>Cash Out</th><th>Remarks</th><th>Invoice</th>
        </tr>
      </thead>
      <tbody>
        <?php $sn = 1; foreach ($entries as $e): ?>
          <tr>
            <td><?= $sn++ ?></td>
            <input type="hidden" name="entry_id[]" value="<?= $e['id'] ?>">
            <td><input type="date" name="entry_date[]" value="<?= $e['entry_date'] ?>"></td>
            <td><input type="text" name="description[]" value="<?= htmlspecialchars($e['description']) ?>"></td>
            <td><input type="text" name="category[]" value="<?= htmlspecialchars($e['category']) ?>"></td>
            <td><input type="number" step="0.01" name="cash_in[]" value="<?= $e['cash_in'] ?>"></td>
            <td><input type="number" step="0.01" name="cash_out[]" value="<?= $e['cash_out'] ?>"></td>
            <td><textarea name="remarks[]"><?= htmlspecialchars($e['remarks']) ?></textarea></td>
            <td>
              <?php if ($e['invoice_file']): ?>
                <a href="../uploads/invoices/<?= htmlspecialchars($e['invoice_file']) ?>" target="_blank">View</a><br>
              <?php endif; ?>
              <input type="hidden" name="existing_invoice[]" value="<?= htmlspecialchars($e['invoice_file']) ?>">
              <input type="file" name="invoice_file[]" accept=".pdf,.jpg,.jpeg,.png">
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <button class="save-btn">Save Changes</button>
  </form>
</div>
</body>
</html>
